<?php
	$url="https://api.dubtrack.fm";
	$roomuri = "boreddan-test";
	$getroom = "/room/" . $roomuri;
	echo $url . $getroom;
	
	$ch = curl_init($url.$getroom);
	
	curl_exec($ch);
	
	curl_close($ch);
?>