<?php

$mysql = new mysqli("localhost", "webserver", "empty", "buildmaster");
if ($mysql->connect_error) {
  die("Connection failed: " . $mysql->connect_error);
}

$result = $mysql -> query(
  "SELECT DISTINCT " .
  "`todos`.`id`," .
  "`todos`.`file`," .
  "`todos`.`line`," .
  "`todos`.`description` " .
  "FROM `todos`;"
);

if ($result -> num_rows > 0) {

  while ($row = $result->fetch_assoc())
    $knot_rows[$row["id"]] =
      $row["file"]. " (line ".$row["line"]."):\\n".$row["description"];

  unset($knots);
  foreach ($knot_rows as $knot)
    $knots=$knots . "\"" . $knot . "\";\n";

}

$result = $mysql -> query(
  "SELECT DISTINCT " .
  "`todo_links`.`dependent`," .
  "`todo_links`.`depending_on` " .
  "FROM `todo_links`;"
);

if ($result -> num_rows > 0) {
  $count = 0;
  while ($row = $result->fetch_assoc()) {
    $link_rows[$count]["dependent"] =
      $knot_rows[$row["dependent"]];
    $link_rows[$count]["depending_on"] =
      $knot_rows[$row["depending_on"]];
    $count++;
  }

  unset($edges);
  foreach ($link_rows as $link)
    $edges=$edges . "\"" . $link["depending_on"] . "\" -> \"" . $link["dependent"] . "\";\n";
}

header ("Content-type: image/png");
passthru(
  "dot -Tpng -o/dev/stdout /dev/stdin <<EOF\n".
  "digraph dependencies {\n" .
  "rankdir=LR;\n" .
  "fontname=dejavu;\n" .
  $knots .
  $edges .
  "}\n" .
  "EOF\n"
);

?>
