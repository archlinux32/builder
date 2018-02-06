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
  "SELECT " .
  "`repositories`.`name` AS `repo`," .
  "`binary_packages`.`pkgname`," .
  "`binary_packages`.`epoch`," .
  "`binary_packages`.`pkgver`," .
  "`binary_packages`.`pkgrel`," .
  "`binary_packages`.`sub_pkgrel`," .
  "`architectures`.`name` AS `arch` " .
  "FROM `binary_packages` " .
  "JOIN `architectures` ON `binary_packages`.`architecture`=`architectures`.`id` " .
  "JOIN `repositories` ON `binary_packages`.`repository`=`repositories`.`id` " .
  "WHERE `binary_packages`.`is_to_be_deleted` " .
  "AND NOT `repositories`.`name` IN (\"build-support\",\"build-list\",\"deletion-list\")"
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
