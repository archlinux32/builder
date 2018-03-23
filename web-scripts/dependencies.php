<?php

$mysql = new mysqli("localhost", "webserver", "empty", "buildmaster");
if ($mysql->connect_error) {
  die("Connection failed: " . $mysql->connect_error);
}

$match = "";

if (isset($_GET["a"]))
  $match .= " AND `architectures`.`name`=from_base64(\"" . base64_encode($_GET["a"]) . "\")";
if (isset($_GET["b"]))
  $match .= " AND `package_sources`.`pkgbase`=from_base64(\"" . base64_encode($_GET["b"]) . "\")";
if (isset($_GET["p"]))
  $match .= " AND `binary_packages`.`pkgname`=from_base64(\"" . base64_encode($_GET["p"]) . "\")";
if (isset($_GET["r"]))
  $match .= " AND `repositories`.`name`=from_base64(\"" . base64_encode($_GET["r"]) . "\")";

$ignore_install_targets = " AND NOT `install_targets`.`name` IN (\"base\",\"base-devel\")";

$colors["stable"]="#000000";
$colors["testing"]="#008000";
$colors["staging"]="#00ff00";
$colors["standalone"]="#000000";
$colors["unbuilt"]="#ff0000";
$colors["forbidden"]="#808080";
$colors["virtual"]="#800080";

$limit=200;

if (! $result = $mysql -> query(
  "CREATE TEMPORARY TABLE `cons` (" .
    "`dep` BIGINT, " .
    "`itp` BIGINT, " .
    "UNIQUE KEY `content` (`dep`,`itp`)" .
  ")"))
  die($mysql->error);

if (! $result = $mysql -> query(
  "INSERT IGNORE INTO `cons` (`dep`,`itp`)" .
  " SELECT `dependencies`.`id`,`install_target_providers`.`id`".
  " FROM `binary_packages`" .
  " JOIN `repositories` ON `binary_packages`.`repository`=`repositories`.`id`" .
  " JOIN `repository_stabilities` ON `repositories`.`stability`=`repository_stabilities`.`id`" .
  " JOIN `architectures` ON `binary_packages`.`architecture`=`architectures`.`id`" .
  " JOIN `build_assignments` ON `binary_packages`.`build_assignment`= `build_assignments`.`id`" .
  " JOIN `package_sources` ON `build_assignments`.`package_source`= `package_sources`.`id`" .
  $match .
  " JOIN `dependencies` ON `dependencies`.`dependent`=`binary_packages`.`id`" .
  " JOIN `dependency_types` ON `dependencies`.`dependency_type`=`dependency_types`.`id`" .
  " JOIN `install_targets` ON `dependencies`.`depending_on`=`install_targets`.`id`" .
  $ignore_install_targets .
  " JOIN `install_target_providers` ON `install_target_providers`.`install_target`=`dependencies`.`depending_on`" .
  " WHERE (`dependency_types`.`relevant_for_binary_packages` OR `repository_stabilities`.`name`=\"unbuilt\")" .
  " LIMIT " . $limit
  ))
  die($mysql->error);

if (! $result = $mysql -> query(
  "INSERT IGNORE INTO `cons` (`dep`,`itp`)" .
  " SELECT `dependencies`.`id`,`install_target_providers`.`id`".
  " FROM `binary_packages`" .
  " JOIN `repositories` ON `binary_packages`.`repository`=`repositories`.`id`" .
  " JOIN `architectures` ON `binary_packages`.`architecture`=`architectures`.`id`" .
  " JOIN `build_assignments` ON `binary_packages`.`build_assignment`= `build_assignments`.`id`" .
  " JOIN `package_sources` ON `build_assignments`.`package_source`= `package_sources`.`id`" .
  $match .
  " JOIN `install_target_providers` ON `install_target_providers`.`package`=`binary_packages`.`id`" .
  " JOIN `dependencies` ON `install_target_providers`.`install_target`=`dependencies`.`depending_on`" .
  " JOIN `binary_packages` AS `d_bp` ON `dependencies`.`dependent`=`d_bp`.`id`" .
  " JOIN `repositories` AS `d_r` ON `d_bp`.`repository`=`d_r`.`id`" .
  " JOIN `repository_stabilities` AS `d_rs` ON `d_r`.`stability`=`d_rs`.`id`" .
  " JOIN `dependency_types` ON `dependencies`.`dependency_type`=`dependency_types`.`id`" .
  " WHERE (`dependency_types`.`relevant_for_binary_packages` OR `d_rs`.`name`=\"unbuilt\")" .
  " LIMIT " . $limit
  ))
  die($mysql->error);

unset($knots);
unset($edges);

if (! $result = $mysql -> query(
  "SELECT DISTINCT `install_target_providers`.`install_target`,`install_target_providers`.`package`" .
  " FROM `cons`" .
  " JOIN `install_target_providers` ON `cons`.`itp`=`install_target_providers`.`id`"
  ))
  die($mysql->error);

if ($result -> num_rows > 0)
  while ($row = $result->fetch_assoc())
    $edges .= "\"p" . $row["package"] . "\" -> \"i" . $row["install_target"] . "\" [color = \"#000080\"];\n";

if (! $result = $mysql -> query(
  "SELECT DISTINCT `dependencies`.`dependent`,`dependencies`.`depending_on`,`dependency_types`.`name`" .
  " FROM `cons`" .
  " JOIN `dependencies` ON `cons`.`dep`=`dependencies`.`id`" .
  " JOIN `dependency_types` ON `dependencies`.`dependency_type`=`dependency_types`.`id`"
  ))
  die($mysql->error);

if ($result -> num_rows > 0)
  while ($row = $result->fetch_assoc())
    $edges .= "\"i" . $row["depending_on"] . "\" -> \"p" . $row["dependent"] . "\" [taillabel = \"" . $row["name"] . "\"];\n";

if (! $result = $mysql -> query(
  "SELECT DISTINCT `install_targets`.`id`,`install_targets`.`name`" .
  " FROM `cons`" .
  " JOIN `dependencies` ON `cons`.`dep`=`dependencies`.`id`" .
  " JOIN `install_targets` ON `dependencies`.`depending_on`=`install_targets`.`id`"
  ))
  die($mysql->error);

if ($result -> num_rows > 0)
  while ($row = $result->fetch_assoc())
    $knots .= "\"i" . $row["id"] . "\" [label = \"" . $row["name"] . "\", fontcolor = \"#000080\"];\n";

$pkgfile_query =
  "CONCAT(".
    "`repositories`.`name`,\"/\"," .
    "`binary_packages`.`pkgname`,\"-\"," .
    "IF(`binary_packages`.`epoch`=0,\"\",CONCAT(`binary_packages`.`epoch`,\":\"))," .
    "`binary_packages`.`pkgver`,\"-\"," .
    "`binary_packages`.`pkgrel`,\".\"," .
    "`binary_packages`.`sub_pkgrel`,\"-\"," .
    "`architectures`.`name`" .
  ") AS `filename`";

if (! $result = $mysql -> query(
  "SELECT DISTINCT " .
  "`binary_packages`.`id`," .
  "`repository_stabilities`.`name` AS `stability`," .
  $pkgfile_query .
  " FROM `cons`" .
  " JOIN `dependencies` ON `cons`.`dep`=`dependencies`.`id`" .
  " JOIN `binary_packages` ON `dependencies`.`dependent`=`binary_packages`.`id`" .
  " JOIN `architectures` ON `architectures`.`id`=`binary_packages`.`architecture`" .
  " JOIN `repositories` ON `repositories`.`id`=`binary_packages`.`repository`" .
  " JOIN `repository_stabilities` ON `repository_stabilities`.`id`=`repositories`.`stability`"
  ))
  die($mysql->error);

if ($result -> num_rows > 0)
  while ($row = $result->fetch_assoc())
    $knots .= "\"p" . $row["id"] . "\" [label = \"" . $row["filename"] . "\", fontcolor = \"" . $colors[$row["stability"]] . "\"];\n";

if (! $result = $mysql -> query(
  "SELECT DISTINCT " .
  "`binary_packages`.`id`," .
  "`repository_stabilities`.`name` AS `stability`," .
  $pkgfile_query .
  " FROM `cons`" .
  " JOIN `install_target_providers` ON `cons`.`itp`=`install_target_providers`.`id`" .
  " JOIN `binary_packages` ON `install_target_providers`.`package`=`binary_packages`.`id`" .
  " JOIN `architectures` ON `architectures`.`id`=`binary_packages`.`architecture`" .
  " JOIN `repositories` ON `repositories`.`id`=`binary_packages`.`repository`" .
  " JOIN `repository_stabilities` ON `repository_stabilities`.`id`=`repositories`.`stability`"
  ))
  die($mysql->error);

if ($result -> num_rows > 0)
  while ($row = $result->fetch_assoc())
    $knots .= "\"p" . $row["id"] . "\" [label = \"" . $row["filename"] . "\", fontcolor = \"" . $colors[$row["stability"]] . "\"];\n";

$knots = str_replace("\$","\\\$",$knots);
$edges = str_replace("\$","\\\$",$edges);

header ("Content-type: image/png");
passthru(
  "dot -Tpng -o/dev/stdout /dev/stdin <<EOF\n" .
  "digraph dependencies {\n" .
  "rankdir=LR;\n" .
  "fontname=dejavu;\n" .
  $knots .
  $edges .
  "}\n" .
  "EOF\n"
);

?>
