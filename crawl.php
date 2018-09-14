<!DOCTYPE html>
<html>
<head>
	<title>Results!</title>
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
<?php
  $apikey = "placeholderapi";
  $host = "localhost";
  $username = "root";
  $password = "fakepasswordplaceholder";
  $entry = $_GET["entry"];
  //if custom url submitted, convert entry to id64
  if (!ctype_digit($entry)) {
    $vanity_api_url = "http://api.steampowered.com/ISteamUser/ResolveVanityURL/v0001/?key=" . $apikey . "&vanityurl=" . $entry;
    $vanity_json = file_get_contents($vanity_api_url);
    $vanity_obj = json_decode($vanity_json, true);
    if ($vanity_obj['response']['success'] == 0) {
      echo "ERROR: Invalid Steam Custom URL</br>";
    }
    else {
      $entry = $vanity_obj['response']['steamid'];
    }
  }

  //create connection
  $conn = new mysqli($host, $username, $password, "vacinfo");
  //check connection
  if ($conn->connect_error) {
      die("Connection failed: " . $conn->connect_error);
  }

  //get ban info with 0 iterations
  //prev takes an array of arrays where the first index is the level of iterations from initial call
  getBanInfo(array($entry), 0, NULL, $apikey, $conn);

  //function used to get ban info on an array of users
  function getBanInfo($idArray, $iterations, $prev, $apikey, $conn) {
    $found = false; //boolean to check when a banned player has been found
    $idString = implode(",", $idArray); //combine ID array into string separated by commas
    $api_url = "http://api.steampowered.com/ISteamUser/GetPlayerBans/v1/?key=" . $apikey .
               "&steamids=" . $idString;
    $json = file_get_contents($api_url);
    $obj = json_decode($json, true);
    if (count($obj['players']) == 0) { //ensure real data was returned
      echo "ERROR: Invalid SteamID64</br>";
    }
    else {
      for ($i=0; $i<count($obj['players']) && !$found; $i++) {
        echo $obj['players'][$i]['SteamId'] . "</br>";
        //convert community ban ban into string (php defaults false to empty space)
        $community_ban = "false";
        if ($obj['players'][$i]['CommunityBanned'] == TRUE) {
          $community_ban = "true";
        }
        //convert economy ban string into "boolean sting"
        $economy_ban = "false";
        if ($obj['players'][$i]['EconomyBan'] != "none") {
          $economy_ban = "true";
        }

        //check if user is already in database
        $sql = "SELECT steamid64
                FROM users
                WHERE steamid64 = " . $obj['players'][$i]['SteamId'] . ";";
        $result = $conn->query($sql);
        if (mysqli_num_rows($result) != 0) { //user is already in database
          $sql = "UPDATE users
                  SET banCount = " . $obj['players'][$i]['NumberOfVACBans'] . ", " .
                      "gameCount = " . $obj['players'][$i]['NumberOfGameBans'] . ", " .
                      "communityBan = " . $community_ban . ", " .
                      "economyBan = " . $economy_ban . " " .
                 "WHERE steamid64 = " . $obj['players'][$i]['SteamId'] . ";";
        } else { //create new row for user
          $sql = "INSERT INTO users (steamid64, banCount, gameCount, communityBan, economyBan)
                  VALUES (" . $obj['players'][$i]['SteamId'] . ", "
                            . $obj['players'][$i]['NumberOfVACBans'] . ", "
                            . $obj['players'][$i]['NumberOfGameBans'] . ", "
                            . $community_ban . ", "
                            . $economy_ban . ");";
        }

        //put user info in database
        if ($conn->query($sql) === FALSE) {
          echo "ERROR: SQL Query failed</br>";
        }

        //if player is banned, stop searching
        //depending on type of ban, update details string
        $ban_details = "";
        if ($obj['players'][$i]['NumberOfVACBans'] > 0) {
          $found = TRUE;
          $ban_details .= "</br>" . strval($obj['players'][$i]['NumberOfVACBans']) . " VAC ban(s)";
        }
        if ($obj['players'][$i]['NumberOfGameBans'] > 0) {
          $found = TRUE;
          $ban_details .= "</br>" . strval($obj['players'][$i]['NumberOfGameBans']) . " game ban(s).";
        }
        if ($obj['players'][$i]['CommunityBanned']) {
          $found = TRUE;
          $ban_details .= "</br>Community banned";
        }
        if ($obj['players'][$i]['EconomyBan'] != "none") {
          $found = TRUE;
          $ban_details .= "</br>Economy banned";
        }
        if ($found) {
          //get username from steam id
          $api_url = "http://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/?key=" . $apikey .
                     "&steamids=" . $obj['players'][$i]['SteamId'];
          $summary_json = file_get_contents($api_url);
          $summary_obj = json_decode($summary_json, true);
          //print results to page
          echo "You are " . $iterations . " step(s) away from a banned user</br>";
          echo "Banned user: " . $summary_obj['response']['players'][0]['personaname'] . "</br>";
          echo "<img src=" . $summary_obj['response']['players'][0]['avatarfull'] . " alt='Profile Picture'></br>";
          echo "Details: " . $ban_details;
          for ($j=0; $j<count($prev); $j++) {
            for ($k=0; $k<count($prev[$j]); $k++) {
              $sql = "UPDATE users
                      SET stepsRemoved = " . ($iterations-$j) .
                    " WHERE steamid64 = " . $prev[$j][$k] . ";";
              //put user info in database
              if ($conn->query($sql) === FALSE) {
                echo "ERROR: SQL Query failed</br>";
              }
            }
          }
          //update banned user data
          $sql = "UPDATE users
                  SET stepsRemoved = 0" .
                " WHERE steamid64 = " . $obj['players'][$i]['SteamId'] . ";";
          //put user info in database
          if ($conn->query($sql) === FALSE) {
            echo "ERROR: SQL Query failed</br>";
          }
          header('Location: http://localhost/vac/crawl.php?entry=' . $idArray[rand(0 , count($idArray))] . '&submit=Submit+Query');
        }
      }
      if (!$found) {
        $newArray = array();
        for ($j=0; $j<count($obj['players']); $j++) {
          //ensure their visibility is set to public
          $api_url = "http://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/?key=" . $apikey .
          "&steamids=" . $obj['players'][$j]['SteamId'];
          $summary_json = file_get_contents($api_url);
          $summary_obj = json_decode($summary_json, true);
          if ($summary_obj['response']['players'][0]['communityvisibilitystate'] == 3) {
            //get friends list
            $api_url = "http://api.steampowered.com/ISteamUser/GetFriendList/v0001/?key=" . $apikey .
            "&steamid=" . $obj['players'][$j]['SteamId'];
            $friend_json = file_get_contents($api_url);
            $friend_obj = json_decode($friend_json, true);
            //push friends into array, which is used to recursively call func
            for ($k=0; $k<count($friend_obj['friendslist']['friends']); $k++) {
              array_push($newArray, $friend_obj['friendslist']['friends'][$k]['steamid']);
            }
          }
        }
        if ($prev == NULL) {
          $prev = array($idArray);
        }
        else {
          array_push($prev, $idArray); //update prev array
        }
        getBanInfo($newArray, $iterations+1, $prev, $apikey, $conn);
      }
    }
  }
  //clear null tangent results from database
  $sql = "DELETE FROM users
          WHERE stepsRemoved IS NULL;";
  if ($conn->query($sql) === FALSE) {
    echo "ERROR: SQL Query failed</br>";
  }
  $conn->close(); //close connection
?>
</body>
</html>
