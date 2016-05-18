<?php
	require("dubot-utility.php");
	
	init();
	$queue = queueSong($ini["roomName"], $_GET["song"], "youtube");
	if($queue["code"] == 200) {
		$details = songDetails($queue["data"]["songid"]);
		if($details["code"] == 200) {
			echo "Queued \"".$details["data"]["name"]."\"!";
		} else {
			echo "Queued ".$_GET["song"]."!";
		}
	} else {
		echo "Failed to queue requested song!";
	}
	close();
?>