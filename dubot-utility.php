<?php
	$ini = array_merge(
		parse_ini_file("dubot.ini"),
		parse_ini_file("dubot-user.ini")
	);
	
	function getURL($command, $inline_ids = array()) {
		global $ini;
		
		$url = $ini["api_url"].$ini["api_".$command];
		
		$search = array_map(function($key){ return "{".$key."}"; }, array_keys($inline_ids));
		$replace = array_values($inline_ids);
		
		$url = str_replace($search, $replace, $url);
		
		return $url;
	}
	
	function roomDetails($room) {
		global $ini;
		
		$url = getURL("room_details", array("room" => $room));
	
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		
		$json = json_decode(curl_exec($ch), true);
		
		curl_close($ch);
		
		return $json;
	}
	
	function roomId($room) {
		static $id = null;
	
		if($id == null) {
			$json = roomDetails($room);
			$id = $json['data']['_id'];
		}
		
		return $id;
	}
?>