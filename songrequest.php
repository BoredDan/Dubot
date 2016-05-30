<?php
	require("dubot-utility.php");
	
	init();
	echo songRequest($_GET["song"]);
	close();
	
	function songRequest($song) {
		Global $ini;
		
		//Look for requested song
		$search = songSearch($_GET["song"], $ini["Dubtrack"]["searchType"]);
		if($search["code"] == 200) {
			if(empty($search["data"])) {
				return "Could not find \"".$_GET["song"]."\" on ".$ini["Dubtrack"]["searchType"]."!";
			} else {
				$fkid = songSearchFilter($search, $_GET["song"], $ini["Dubtrack"]["searchType"])["fkid"];
			}
		} else {
			return "Failed during search for \"".$_GET["song"]."\"!";
		}
		
		$queue = queueSong($ini["Dubtrack.User"]["roomName"], $fkid, $ini["Dubtrack"]["searchType"]);
		if($queue["code"] == 200) {
			$details = songDetails($queue["data"]["songid"]);
			if($details["code"] == 200) {
				return "Queued \"".$details["data"]["name"]."\"!";
			} else {
				return "Queued \"".$_GET["song"]."\"!";
			}
		} else {
				return "Failed to queue \"".$_GET["song"]."\"!";
		}
	}
?>