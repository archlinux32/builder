<html>
<head>
<title>List of broken package builds</title>
<link rel="stylesheet" type="text/css" href="/static/style.css">
</head>
<body>
<a href="build-logs/">build logs</a><br>
<?php

$mysql = new mysqli("localhost", "webserver", "empty", "buildmaster");
if ($mysql->connect_error) {
  die("Connection failed: " . $mysql->connect_error);
}

$result = $mysql -> query(
  "SELECT " .
  "`build_assignments`.`id`," .
  "`build_assignments`.`is_blocked`," .
  "`package_sources`.`pkgbase`," .
  "`package_sources`.`git_revision`," .
  "`package_sources`.`mod_git_revision`," .
  "`upstream_repositories`.`name`," .
  "EXISTS (SELECT * " .
    "FROM `binary_packages` `broken_bin` " .
    "JOIN `dependencies` ON `dependencies`.`dependent` = `broken_bin`.`id` " .
    "JOIN `install_target_providers` ON `install_target_providers`.`install_target` = `dependencies`.`depending_on` " .
    "JOIN `binary_packages` `to_be_built` ON `to_be_built`.`id` = `install_target_providers`.`package` " .
    "JOIN `repositories` ON `to_be_built`.`repository` = `repositories`.`id` " .
    "WHERE `broken_bin`.`build_assignment`=`build_assignments`.`id` ".
    "AND `repositories`.`name`=\"community-testing\"" .
  ") AS `dependencies_pending`," .
  "(SELECT count(*) " .
    "FROM `build_dependency_loops` " .
    "WHERE `build_dependency_loops`.`build_assignment`=`build_assignments`.`id`" .
  ") AS `loops` " .
  "FROM `build_assignments` " .
  "JOIN `package_sources` ON `build_assignments`.`package_source` = `package_sources`.`id` " .
  "JOIN `upstream_repositories` ON `package_sources`.`upstream_package_repository` = `upstream_repositories`.`id` " .
  "WHERE `build_assignments`.`is_broken` OR `build_assignments`.`is_blocked` IS NOT NULL"
);
if ($result -> num_rows > 0) {

  $count = 0;

  while($row = $result->fetch_assoc()) {

foreach ($row as $key => $val)

    $fail_result = $mysql -> query(
      "SELECT " .
      "`fail_reasons`.`name`, " .
      "`failed_builds`.`log_file` " .
      "FROM `failed_builds` " .
      "JOIN `build_assignments` ON `failed_builds`.`build_assignment`=`build_assignments`.`id` ".
      "JOIN `fail_reasons` ON `failed_builds`.`reason`=`fail_reasons`.`id` ".
      "WHERE `build_assignments`.`id`=".$row["id"]." " .
      "ORDER BY `failed_builds`.`date`"
    );

    unset($reasons);
    unset($last_log);
    $rows[$count]["trials"] = $fail_result -> num_rows;
    if ($rows[$count]["trials"] > 0) {
      while($fail_row = $fail_result->fetch_assoc()) {
        $reasons[$fail_row["name"]] = $fail_row["name"];
        $last_log = $fail_row["log_file"];
      }
    }
    if (isset($reasons)) {
      $to_print="";
      foreach ($reasons as $reason) {
        $to_print=$to_print.", ".$reason;
      }
      $rows[$count]["fail_reasons"]=substr($to_print,2);
    } else {
      $rows[$count]["fail_reasons"]="&nbsp;";
    }

    $rows[$count]["loops"] = $row["loops"];
    $rows[$count]["pkgbase"] = $row["pkgbase"];
    if ($row["dependencies_pending"]=="1")
      $rows[$count]["pkgbase_print"] = "(" . $rows[$count]["pkgbase"] . ")";
    else
      $rows[$count]["pkgbase_print"] = $rows[$count]["pkgbase"];
    $rows[$count]["git_revision"] = $row["git_revision"];
    $rows[$count]["mod_git_revision"] = $row["mod_git_revision"];
    $rows[$count]["name"] = $row["name"];
    if (isset($last_log))
      $rows[$count]["print_trials"]="<a href=\"/build-logs/error/".$last_log."\">". $rows[$count]["trials"] ."</a>";
    else
      $rows[$count]["print_trials"]=$rows[$count]["trials"];
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
//  print "<th>dependent</th>";
  print "<th>build error</th>";
  print "<th>blocked</th>";
  print "</tr>\n";

  foreach($rows as $row) {

    print "<tr>";

    print "<td><a href=\"/graphs/".$row["pkgbase"].".png\">".$row["pkgbase_print"]."</a></td>";
    print "<td><p style=\"font-size:8px\">".$row["git_revision"]."</p></td>";
    print "<td><p style=\"font-size:8px\">".$row["mod_git_revision"]."</p></td>";
    print "<td>".$row["name"]."</td>";
    print "<td>".$row["print_trials"]."</td>";
    print "<td>".$row["loops"]."</td>";
//    <td>0</td>
    print "<td>".$row["fail_reasons"]."</td>";
    print "<td>".$row["is_blocked"]."</td>";

    print "</tr>\n";
  }

  print "</table>\n";
}

?>
</body>
</html>
