<!DOCTYPE html>
<html>
<head>
	<title>Are they VAC-ed?</title>
	<link rel="stylesheet" type="text/css" href="mystyle.css">
	<meta charset="UTF-8">
</head>
<body>
	<h1 >Are They Vac-ed?</h1>
	<ul id="navbar">
		<li><a href="homepage.html">Homepage</a></li>
		<li><a href="info.php">Statistics</a></li>
    <li><a href="about.html">About/Contact Us</a></li>
	</ul>
  </br>
  <?php
    $host = "localhost";
    $username = "root";
    $password = "fakepasswordplaceholder";
    //create connection
    $conn = new mysqli($host, $username, $password, "vacinfo");
    //check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    for ($i=0; $i<=2; $i++) {
      if ($i == 2) { //have highest section as 2+
        $sql = "SELECT stepsRemoved
                FROM users
                WHERE stepsRemoved >= 2;";
        $result = $conn->query($sql);
        echo mysqli_num_rows($result) . " users are 2 or more steps removed from a banned user </br>";
      }
      else {
        $sql = "SELECT stepsRemoved
                FROM users
                WHERE stepsRemoved = " . $i . ";";
        $result = $conn->query($sql);
        if ($i == 0) {
          echo mysqli_num_rows($result) . " users are banned </br>";
        }
        if ($i == 1) {
          echo mysqli_num_rows($result) . " users are 1 step removed from a banned user </br>";
        }
      }
    }
  ?>
</body>
</html>
