<?php

$mysql = new mysqli("localhost", "webserver", "empty");
if ($mysql->connect_error) {
  die("Connection failed: " . $mysql->connect_error);
}

$result = $mysql -> query("SELECT * FROM buildmaster.binary_packages");
if ($result -> num_rows > 0) {
  while($row = $result->fetch_assoc()) {
    foreach ($row as $key => $val) {
      print $key .": ".$val." - ";
    }
    print "<br>\n";
  }
}

print 'OK';

?>
