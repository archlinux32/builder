<html>
<head>
<title>List of packages to be deleted</title>
<link rel="stylesheet" type="text/css" href="/static/style.css">
</head>
<body>
<?php

$mysql = new mysqli("localhost", "webserver", "empty", "buildmaster");
if ($mysql->connect_error) {
  die("Connection failed: " . $mysql->connect_error);
}

$result = $mysql -> query(
  "SELECT DISTINCT " .
  "`repositories`.`name` AS `repo`," .
  "`is_there`.`pkgname`," .
  "`is_there`.`epoch`," .
  "`is_there`.`pkgver`," .
  "`is_there`.`pkgrel`," .
  "`is_there`.`sub_pkgrel`," .
  "`architectures`.`name` AS `arch` " .
  "FROM `binary_packages`AS `is_there` " .
  "JOIN `binary_packages` AS `to_delete` ON `to_delete`.`pkgname`=`is_there`.`pkgname` " .
  "JOIN `architectures` ON `is_there`.`architecture`=`architectures`.`id` " .
  "JOIN `repositories` ON `is_there`.`repository`=`repositories`.`id` " .
  "WHERE `to_delete`.`repository`=10 " .
  "AND NOT `is_there`.`repository` IN (4,9,10)"
);
if ($result -> num_rows > 0) {

  $count = 0;

  while ($row = $result->fetch_assoc()) {
    $rows[$count] =
      $row["repo"] . "/" .
      $row["pkgname"] . "-";
    if ($row["epoch"] != "0")
      $rows[$count] =
        $rows[$count] .
        $row["epoch"] . ":";
    $rows[$count] =
      $rows[$count] .
      $row["pkgver"] . "-" .
      $row["pkgrel"] . "." .
      $row["sub_pkgrel"] . "-" .
      $row["arch"] . ".pkg.tar.xz";
    $count++;
  }

  sort($rows);

  foreach ($rows as $row) {
    print $row."<br>\n";
  }
} else {
  print "No packages are to be deleted.\n";
}

?>
</body>
</html>
