<?php
	$ini = array_merge(
		parse_ini_file("dubot.ini"),
		parse_ini_file("dubot-user.ini")
	);
	
	function roomDetails($room) {
		global $ini;
	
		$ch = curl_init(str_replace("{room}", $room, $ini["api_url"].$ini["api_room"]));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		
		$json = json_decode(curl_exec($ch), true);
		
		curl_close($ch);
		
		return $json;
	}
	
	function roomId($room) {
		$json = roomDetails($room);
		return $json['data']['_id'];
	}
?>