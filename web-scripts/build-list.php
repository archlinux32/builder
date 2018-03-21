<html>
<head>
<?php

if (isset($_GET["show"]))
  $to_show=$_GET["show"];
else
  $to_show="all";

if ($to_show == "all")
  $match = "";
elseif ($to_show == "broken")
  $match = " AND (`build_assignments`.`is_broken` OR `build_assignments`.`is_blocked` IS NOT NULL)";
elseif ($to_show == "next")
  $match = "";
else
  die();

print "<title>List of " . $to_show . " package builds</title>\n";
print "<link rel=\"stylesheet\" type=\"text/css\" href=\"/static/style.css\">\n";
print "</head>\n";
print "<body>\n";
print "<a href=\"/\">Start page</a>\n";
print "<a href=\"/build-logs/\">build logs</a><br>\n";

$mysql = new mysqli("localhost", "webserver", "empty", "buildmaster");
if ($mysql->connect_error) {
  die("Connection failed: " . $mysql->connect_error);
}

$result = $mysql -> query(
  "SELECT DISTINCT " .
  "`build_assignments`.`id`," .
  "`build_assignments`.`is_blocked`," .
  "`package_sources`.`pkgbase`," .
  "`package_sources`.`git_revision`," .
  "`package_sources`.`mod_git_revision`," .
  "`package_sources`.`uses_upstream`," .
  "`package_sources`.`uses_modification`," .
  "`upstream_repositories`.`name` AS `package_repository`," .
  "`git_repositories`.`name` AS `git_repository`," .
  "`architectures`.`name` AS `arch`," .
  "EXISTS (SELECT * " .
    "FROM `binary_packages` `broken_bin` " .
    "JOIN `dependencies` ON `dependencies`.`dependent` = `broken_bin`.`id` " .
    "JOIN `install_target_providers` ON `install_target_providers`.`install_target` = `dependencies`.`depending_on` " .
    "JOIN `binary_packages` `to_be_built` ON `to_be_built`.`id` = `install_target_providers`.`package` " .
    "JOIN `repositories` ON `to_be_built`.`repository` = `repositories`.`id` " .
    "WHERE `broken_bin`.`build_assignment`=`build_assignments`.`id` ".
    "AND `repositories`.`name`=\"build-list\" " .
    "AND `to_be_built`.`build_assignment`!=`build_assignments`.`id`" .
  ") AS `dependencies_pending`," .
  "(SELECT count(*) " .
    "FROM `build_dependency_loops` " .
    "WHERE `build_dependency_loops`.`build_assignment`=`build_assignments`.`id`" .
  ") AS `loops`, " .
  "`build_slaves`.`name` AS `build_slave` " .
  "FROM `build_assignments` " .
  "JOIN `architectures` ON `build_assignments`.`architecture` = `architectures`.`id` " .
  "JOIN `package_sources` ON `build_assignments`.`package_source` = `package_sources`.`id` " .
  "JOIN `upstream_repositories` ON `package_sources`.`upstream_package_repository` = `upstream_repositories`.`id` " .
  "JOIN `git_repositories` ON `upstream_repositories`.`git_repository`=`git_repositories`.`id` " .
  "JOIN `binary_packages` ON `binary_packages`.`build_assignment` = `build_assignments`.`id` " .
  "JOIN `repositories` ON `binary_packages`.`repository` = `repositories`.`id` " .
  "LEFT JOIN `build_slaves` ON `build_slaves`.`currently_building`=`build_assignments`.`id` " .
  "WHERE `repositories`.`name`=\"build-list\"" . $match
);
if ($result -> num_rows > 0) {

  $count = 0;

  while($row = $result->fetch_assoc()) {

    if (($to_show == "next") &&
      ($row["loops"]==0) &&
      ($row["dependencies_pending"]==1))
      continue;

    $fail_result = $mysql -> query(
      "SELECT " .
      "`fail_reasons`.`name`, " .
      "`failed_builds`.`log_file` " .
      "FROM `failed_builds` " .
      "JOIN `fail_reasons` ON `failed_builds`.`reason`=`fail_reasons`.`id` " .
      "WHERE `failed_builds`.`build_assignment`=".$row["id"]." " .
      "ORDER BY `failed_builds`.`date`"
    );

    unset($reasons);
    $rows[$count]["trials"] = $fail_result -> num_rows;
    if ($rows[$count]["trials"] > 0) {
      while($fail_row = $fail_result->fetch_assoc()) {
        $reasons[$fail_row["name"]] = $fail_row["log_file"];
      }
    }
    if (isset($reasons)) {
      $to_print="";
      foreach ($reasons as $reason => $last_log) {
        if (file_exists("/srv/http/build-logs/error/".$last_log)) {
          $to_print= $to_print .
            ", <a href=\"/build-logs/error/" .
            $last_log .
            "\">" .
            $reason .
            "</a>";
        } else {
          $to_print= $to_print . ", " . $reason;
        }
      }
      $rows[$count]["fail_reasons"]=substr($to_print,2);
    } else {
      $rows[$count]["fail_reasons"]="&nbsp;";
    }

    $rows[$count]["loops"] = $row["loops"];
    $rows[$count]["pkgbase"] = $row["pkgbase"];
    if ($row["dependencies_pending"]=="0")
      $rows[$count]["pkgbase_print"] = $rows[$count]["pkgbase"];
    else
      $rows[$count]["pkgbase_print"] = "(" . $rows[$count]["pkgbase"] . ")";
    if ($row["uses_upstream"]) {
      $rows[$count]["git_revision"] =
        "<a href=\"https://git.archlinux.org/svntogit/" .
        $row["git_repository"] . ".git/tree/" .
        $row["pkgbase"] . "/repos/" .
        $row["package_repository"] . "-";
      if ($row["arch"]=="any")
        $rows[$count]["git_revision"] =
          $rows[$count]["git_revision"] . "any";
      else
        $rows[$count]["git_revision"] =
          $rows[$count]["git_revision"] . "x86_64";
      $rows[$count]["git_revision"] =
        $rows[$count]["git_revision"] . "?id=" .
        $row["git_revision"];
      $rows[$count]["git_revision"] =
        $rows[$count]["git_revision"] . "\">" .
        $row["git_revision"] . "</a>";
    } else
      $rows[$count]["git_revision"] = $row["git_revision"];
    if ($row["uses_modification"])
      $rows[$count]["mod_git_revision"] =
        "<a href=\"https://github.com/archlinux32/packages/tree/" .
        $row["mod_git_revision"] . "/" .
        $row["package_repository"] . "/" .
        $row["pkgbase"] . "\">" .
        $row["mod_git_revision"] . "</a>";
    else
      $rows[$count]["mod_git_revision"] = $row["mod_git_revision"];
    $rows[$count]["package_repository"] = $row["package_repository"];
    if ($row["is_blocked"]=="") {
      $rows[$count]["is_blocked"]="&nbsp;";
    }
    else {
      $rows[$count]["is_blocked"] = preg_replace(
        array (
          "/FS32#(\\d+)/",
          "/FS#(\\d+)/"
        ),
        array (
          "<a href=\"https://bugs.archlinux32.org/index.php?do=details&task_id=$1\">$0</a>",
          "<a href=\"https://bugs.archlinux.org/task/$1\">$0</a>"
        ),
        $row["is_blocked"]
      );
    }
    if (isset($row["build_slave"]))
      $rows[$count]["build_slave"] = $row["build_slave"];
    else
      $rows[$count]["build_slave"] = "&nbsp;";
    $count++;
  }

  usort(
    $rows,
    function (array $a, array $b) {
      if ($a["trials"] < $b["trials"])
        return -1;
      if ($a["trials"] > $b["trials"])
        return 1;
      return strcmp($a["pkgbase"],$b["pkgbase"]);
    }
  );

  print "<table>\n";
  print "<tr>";
  print "<th>package</th>";
  print "<th>git revision</th>";
  print "<th>modification git revision</th>";
  print "<th>package repository</th>";
  print "<th>compilations</th>";
  print "<th>loops</th>";
  print "<th>build error</th>";
  print "<th>blocked</th>";
  print "<th>handed out to</th>";
  print "</tr>\n";

  foreach($rows as $row) {

    print "<tr>";

    print "<td><a href=\"/scripts/dependencies.php?b=".$row["pkgbase"]."&r=build-list\">".$row["pkgbase_print"]."</a></td>";
    print "<td><p style=\"font-size:8px\">".$row["git_revision"]."</p></td>";
    print "<td><p style=\"font-size:8px\">".$row["mod_git_revision"]."</p></td>";
    print "<td>".$row["package_repository"]."</td>";
    print "<td>".$row["trials"]."</td>";
    print "<td>".$row["loops"]."</td>";
    print "<td>".$row["fail_reasons"]."</td>";
    print "<td>".$row["is_blocked"]."</td>";
    print "<td>".$row["build_slave"]."</td>";

    print "</tr>\n";
  }

  print "</table>\n";
}

?>
</body>
</html>
