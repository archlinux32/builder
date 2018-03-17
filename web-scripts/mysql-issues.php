<html>
<head>
<title>More and less critical issues with the database</title>
<link rel="stylesheet" type="text/css" href="/static/style.css">
</head>
<body>
<a href="/">Start page</a><br>
<?php

  if (isset($_GET["ignore-haskell"]))
    $ignore = " AND `install_targets`.`name` NOT LIKE \"libHS%\"";
  else
    $ignore = "";

  $mysql = new mysqli("localhost", "webserver", "empty", "buildmaster");
  if ($mysql->connect_error) {
    die("Connection failed: " . $mysql->connect_error);
  }

  if (! $result = $mysql -> query(
    "SELECT CONCAT(" .
    "`repositories`.`name`,\"/\"," .
    "`binary_packages`.`pkgname`,\"-\"," .
    "IF(`binary_packages`.`epoch`=0,\"\",CONCAT(`binary_packages`.`epoch`,\":\"))," .
    "`binary_packages`.`pkgver`,\"-\"," .
    "`binary_packages`.`pkgrel`,\".\"," .
    "`binary_packages`.`sub_pkgrel`,\"-\"," .
    "`architectures`.`name`) AS `pkgfile`," .
    "`install_targets`.`name` AS `install_target`," .
    "IF(`binary_packages`.`is_to_be_deleted`,1,0) AS `is_to_be_deleted`," .
    "`subst_r`.`name` AS `subst_repository`," .
    "`subst_buildlist_bp`.`id` AS `subst_buildlist`" .
    " FROM `binary_packages`" .
    " JOIN `repositories` ON `binary_packages`.`repository`=`repositories`.`id`" .
    " AND `repositories`.`is_on_master_mirror`" .
    " JOIN `dependencies` ON `dependencies`.`dependent`=`binary_packages`.`id`" .
    " JOIN `dependency_types` ON `dependencies`.`dependency_type`=`dependency_types`.`id`" .
    " AND `dependency_types`.`relevant_for_binary_packages`" .
    " JOIN `install_targets` ON `dependencies`.`depending_on`=`install_targets`.`id`" .
    " JOIN `architectures` ON `binary_packages`.`architecture`=`architectures`.`id`" .
    " LEFT JOIN (`binary_packages` AS `subst_bp`" .
    " JOIN `repositories` AS `subst_r` ON `subst_bp`.`repository`=`subst_r`.`id`" .
    " JOIN `repository_stability_relations` ON `repository_stability_relations`.`less_stable`=`subst_r`.`id`" .
    ")" .
    " ON `subst_bp`.`pkgname`=`binary_packages`.`pkgname`" .
    " AND `subst_bp`.`id`!=`binary_packages`.`id`" .
    " AND `repository_stability_relations`.`more_stable`=`repositories`.`id`" .
    " LEFT JOIN (`binary_packages` AS `subst_buildlist_bp`" .
    " JOIN `repositories` AS `subst_buildlist_r`" .
    " ON `subst_buildlist_bp`.`repository`=`subst_buildlist_r`.`id`" .
    " AND `subst_buildlist_r`.`name`=\"build-list\"".
    ") ON `subst_buildlist_bp`.`pkgname`=`binary_packages`.`pkgname`" .
    " WHERE NOT EXISTS (" .
      "SELECT * FROM `install_target_providers`" .
      " WHERE `install_target_providers`.`install_target` = `dependencies`.`depending_on`" .
    ")" .
    $ignore .
    " ORDER BY `is_to_be_deleted`, `binary_packages`.`pkgname`"
  ))
    die($mysql -> error);

  print "Found " . ($result -> num_rows) . " serious issues.<br>\n";

  if ($result -> num_rows > 0) {

    while ($row = $result->fetch_assoc()) {
      if ($row["is_to_be_deleted"]==1)
        print "<font color=\"#00ff00\">(marked as to-be-deleted) ";
      else
        print "<font color=\"#ff0000\">";
      print $row["pkgfile"] . " depends on " . $row["install_target"] . " which is not provided by any package";
      if (isset($row["subst_repository"]))
        print " - but can be replaced by the one in " . $row["subst_repository"];
      elseif (isset($row["subst_buildlist"]))
        print " - but is already rescheduled";
      print ".<br>";
      print "</font>\n";
    }

  }

  if (! $result = $mysql -> query(
    "SELECT CONCAT(" .
    "`repositories`.`name`,\"/\"," .
    "`binary_packages`.`pkgname`,\"-\"," .
    "IF(`binary_packages`.`epoch`=0,\"\",CONCAT(`binary_packages`.`epoch`,\":\"))," .
    "`binary_packages`.`pkgver`,\"-\"," .
    "`binary_packages`.`pkgrel`,\".\"," .
    "`binary_packages`.`sub_pkgrel`,\"-\"," .
    "`architectures`.`name`) AS `pkgfile`," .
    "`install_targets`.`name` AS `install_target`," .
    "`repository_stabilities`.`name` AS `stability`," .
    "IF(`binary_packages`.`is_to_be_deleted`,1,0) AS `is_to_be_deleted`" .
    " FROM `binary_packages`" .
    " JOIN `repositories` ON `binary_packages`.`repository`=`repositories`.`id`" .
    " AND `repositories`.`is_on_master_mirror`" .
    " JOIN `repository_stabilities` ON `repositories`.`stability`=`repository_stabilities`.`id`" .
    " JOIN `dependencies` ON `dependencies`.`dependent`=`binary_packages`.`id`" .
    " JOIN `dependency_types` ON `dependencies`.`dependency_type`=`dependency_types`.`id`" .
    " AND `dependency_types`.`relevant_for_binary_packages`" .
    " JOIN `install_targets` ON `dependencies`.`depending_on`=`install_targets`.`id`" .
    " JOIN `architectures` ON `binary_packages`.`architecture`=`architectures`.`id`" .
    " WHERE EXISTS (" .
      "SELECT * FROM `install_target_providers`" .
      " WHERE `install_target_providers`.`install_target` = `dependencies`.`depending_on`" .
    ")" .
    " AND NOT EXISTS (" .
      "SELECT * FROM `install_target_providers`" .
      " JOIN `binary_packages` AS `prov_bp` ON `prov_bp`.`id`=`install_target_providers`.`package`" .
      " JOIN `repositories` AS `prov_r` ON `prov_bp`.`repository`=`prov_r`.`id`" .
      " JOIN `repository_stability_relations` ON `prov_r`.`stability`=`repository_stability_relations`.`more_stable`" .
      " WHERE `install_target_providers`.`install_target` = `dependencies`.`depending_on`" .
      " AND `repositories`.`stability`=`repository_stability_relations`.`less_stable`" .
    ")" .
    $ignore .
    " ORDER BY `is_to_be_deleted`, `binary_packages`.`pkgname`"
  ))
    die($mysql -> error);

  print "Found " . ($result -> num_rows) . " stability issues.<br>\n";

  if ($result -> num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
      if ($row["is_to_be_deleted"]==1)
        print "<font color=\"#00ff00\">(marked as to-be-deleted) ";
      else
        print "<font color=\"#800000\">";
      print $row["pkgfile"] . " depends on " . $row["install_target"] . " which is not provided by any package installable from enabled " . $row["stability"] . " repositories.<br>";
      print "</font>\n";
    }

  }

?>
</body>
</html>
