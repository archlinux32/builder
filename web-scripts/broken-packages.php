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
  "`package_sources`.`pkgbase`," .
  "`package_sources`.`git_revision`," .
  "`package_sources`.`mod_git_revision`," .
  "`upstream_repositories`.`name` " .
  "FROM `build_assignments` " .
  "JOIN `package_sources` ON `build_assignments`.`package_source` = `package_sources`.`id` " .
  "JOIN `upstream_repositories` ON `package_sources`.`upstream_package_repository` = `upstream_repositories`.`id` " .
  "WHERE `build_assignments`.`is_broken`"
);
if ($result -> num_rows > 0) {
  print "<table>\n";
  print "<tr>";
  print "<th>package</th>";
  print "<th>git revision</th>";
  print "<th>modification git revision</th>";
  print "<th>package repository</th>";
//  print "<th>compilations</th>";
//  print "<th>dependent</th>";
//  print "<th>build error</th>";
//  print "<th>blocked</th>";
  print "</tr>\n";

  while($row = $result->fetch_assoc()) {
    print "<tr>";

    print "<td><a href=\"/graphs/".$row["pkgbase"].".png\">".$row["pkgbase"]."</a></td>";
    print "<td><p style=\"font-size:8px\">".$row["git_revision"]."</p></td>";
    print "<td><p style=\"font-size:8px\">".$row["mod_git_revision"]."</p></td>";
    print "<td>".$row["name"]."</td>";
//    <td><a href="build-logs/error/sagemath-doc.b4604cdd084578c93db065037f5b027e50d3cf61.23974cf846b850fa9b272ee779d3c6e2dd5f18db.community.2017-12-16T08:53:14.build-log.gz">2</a></td>
//    <td>0</td>
//    <td>build()</td>
//    <td>wait for <a href="https://bugs.archlinux32.org/index.php?do=details&task_id=20">FS32#20</a></td>

    print "</tr>\n";
  }

  print "</table>\n";
}

?>
</body>
</html>
