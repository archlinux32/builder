<html>
<head>
<?php

print "<title>Build master status</title>\n";
print "<link rel=\"stylesheet\" type=\"text/css\" href=\"/static/style.css\">\n";
print "</head>\n";
print "<body>\n";
print "<a href=\"/\">Start page</a><br>\n";

$mysql = new mysqli("localhost", "webserver", "empty", "buildmaster");
if ($mysql->connect_error) {
  die("Connection failed: " . $mysql->connect_error);
}

if ( ! $result = $mysql -> query(
    "SELECT MAX(`package_sources`.`commit_time`) AS `last`" .
    "FROM `package_sources`"
  ))
  die($mysql->error);

if ($result -> num_rows > 0) {

  $row = $result->fetch_assoc();
  print "latest package source is from " . $row["last"] . ".<br>\n";
}

?>
</body>
</html>
