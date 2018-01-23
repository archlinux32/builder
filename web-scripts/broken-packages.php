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
  "`upstream_repositories`.`name` " .
  "FROM `build_assignments` " .
  "JOIN `package_sources` ON `build_assignments`.`package_source` = `package_sources`.`id` " .
  "JOIN `upstream_repositories` ON `package_sources`.`upstream_package_repository` = `upstream_repositories`.`id` " .
  "WHERE `build_assignments`.`is_broken` OR `build_assignments`.`is_blocked` IS NOT NULL"
);
if ($result -> num_rows > 0) {
  print "<table>\n";
  print "<tr>";
  print "<th>package</th>";
  print "<th>git revision</th>";
  print "<th>modification git revision</th>";
  print "<th>package repository</th>";
  print "<th>compilations</th>";
//  print "<th>dependent</th>";
  print "<th>build error</th>";
  print "<th>blocked</th>";
  print "</tr>\n";

  while($row = $result->fetch_assoc()) {

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
    $trials = $fail_result -> num_rows;
    if ($trials > 0) {
      while($fail_row = $fail_result->fetch_assoc()) {
        $reasons[$fail_row["name"]]=$fail_row["name"];
        $last_log=$fail_row["log_file"];
      }
    }
    if (isset($reasons)) {
      $to_print="";
      foreach ($reasons as $reason) {
        $to_print=$to_print.", ".$reason;
      }
      $fail_reasons=substr($to_print,2);
    } else {
      $fail_reasons="&nbsp;";
    }

    print "<tr>";

    print "<td><a href=\"/graphs/".$row["pkgbase"].".png\">".$row["pkgbase"]."</a></td>";
    print "<td><p style=\"font-size:8px\">".$row["git_revision"]."</p></td>";
    print "<td><p style=\"font-size:8px\">".$row["mod_git_revision"]."</p></td>";
    print "<td>".$row["name"]."</td>";
    if (isset($last_log))
      print "<td><a href=\"/build-logs/error/".$last_log."\">". $trials ."</a></td>";
    else
      print "<td>". $trials ."</td>";
//    <td>0</td>
    print "<td>".$fail_reasons."</td>";
    if ($row["is_blocked"]=="") {
      $row["is_blocked"]="&nbsp;";
    }
    $row["is_blocked"] = preg_replace(
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
    print "<td>".$row["is_blocked"]."</td>";

    print "</tr>\n";
  }

  print "</table>\n";
}

?>
</body>
</html>
