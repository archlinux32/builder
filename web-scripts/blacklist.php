<html>
<head>
<title>Blacklisted packages</title>
<link rel="stylesheet" type="text/css" href="/static/style.css">
</head>
<body>
<table>
<tr><th>architecture</th><th>package</th><th>reason</th></tr>
<?php

$mysql = new mysqli("localhost", "webserver", "empty", "buildmaster");
if ($mysql->connect_error) {
  die("Connection failed: " . $mysql->connect_error);
}
if ( ! $result = $mysql -> query(
  "SELECT DISTINCT `architectures`.`name` AS `architecture`,`package_sources`.`pkgbase`,`build_assignments`.`is_black_listed` " .
  "FROM `build_assignments` " .
  "JOIN `architectures` ON `build_assignments`.`architecture`=`architectures`.`id` " .
  "JOIN `package_sources` ON `build_assignments`.`package_source`=`package_sources`.`id` " .
  "WHERE `build_assignments`.`is_black_listed` IS NOT NULL " .
  "ORDER BY `package_sources`.`pkgbase`")) {
  die($mysql->error);
}
if ($result -> num_rows > 0) {
  while($row = $result->fetch_assoc()) {
    print "<tr><td>";
    print $row["architecture"];
    print "</td><td>";
    print $row["pkgbase"];
    print "</td><td>";
    print preg_replace(
      array (
        "/FS32#(\\d+)/",
        "/FS#(\\d+)/"
      ),
      array (
        "<a href=\"https://bugs.archlinux32.org/index.php?do=details&task_id=$1\">$0</a>",
        "<a href=\"https://bugs.archlinux.org/task/$1\">$0</a>"
      ),
      $row["is_black_listed"]
    );
    print "</td></tr>\n";
  }
}
?>
</table>
</body>
</html>
