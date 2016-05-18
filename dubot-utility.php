<?php
	//Loads configuration settings
	$ini = array_merge(
		parse_ini_file("dubot.ini"),
		parse_ini_file("dubot-user.ini")
	);
	
	//HTTP
	
	class HTTP {
		private function __construct() {}
		private static $ch = null;
		
		public static function getURL($command, $inline_ids = array(), $args = array()) {
			global $ini;
			
			$url = $ini["url"].$ini[$command];
			
			$search = array_keys($inline_ids);
			$replace = array_values($inline_ids);
			
			$url = str_replace($search, $replace, $url);
			
			if(!empty($args))
				$url .= "?".http_build_query($args);
			
			return $url;
		}
		
		public static function setURL($command, $inline_ids = array(), $args = array()) {
			$url = self::getURL($command, $inline_ids, $args);
			
			curl_setopt(self::$ch, CURLOPT_URL, $url);
			
			return $url;
		}
		
		public static function setPostData($postfields) {
			curl_setopt(self::$ch, CURLOPT_POSTFIELDS, http_build_query($postfields));
		}
		
		public static function post() {
			curl_setopt(self::$ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt(self::$ch, CURLOPT_POST, true);
			return curl_exec(self::$ch);
		}
		
		public static function get() {
			curl_setopt(self::$ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt(self::$ch, CURLOPT_HTTPGET , true);
			return curl_exec(self::$ch);
		}
		
		public static function init() {
			global $ini;
			
			if(self::$ch == null) {
				self::$ch = curl_init();
				curl_setopt(self::$ch, CURLOPT_COOKIEJAR, $ini["cookie_storage"]);
				curl_setopt(self::$ch, CURLOPT_COOKIEFILE, $ini["cookie_storage"]);
			}
				
			return self::$ch;
		}
		
		public static function handle() {
			return self::$ch;
		}
		
		public static function close() {
			curl_close(self::$ch);
		}
	}
	
	//Login and authorization functions
	function login() {
		global $ini;
		
		HTTP::init();
		HTTP::setURL("login");
		HTTP::setPostData(array("username" => $ini["username"], "password" => $ini["password"]));
		
		return json_decode(HTTP::post(), true);
	}
	
	function sessionInfo() {
		global $ini;
		
		HTTP::init();
		HTTP::setURL("session");
		
		return json_decode(HTTP::get(), true);
	}
	
	function init() {
		global $ini;
		
		HTTP::init();
		
		$sessionJSON = sessionInfo();
		if($sessionJSON)
			return $sessionJSON;
		else
			return login();
	}
	
	function close() {
		HTTP::close();
	}
	
	//Room functions
	
	function roomDetails($room) {
		HTTP::init();
		
		HTTP::setURL("roomDetails", array(":id" => $room));
		
		return json_decode(HTTP::get(), true);
	}
	
	function roomId($room) {
		static $ids = array();
	
		if(!array_key_exists($room, $ids)) {
			$json = roomDetails($room);
			$ids[$room] = $json['data']['_id'];
		}
		
		return $ids[$room];
	}
	
	function joinRoom($room) {
		HTTP::init();
		
		HTTP::setURL("roomUsers", array(":id" => roomId($room)));
		
		return json_decode(HTTP::post(), true);
	}
	
	function queueSong($room, $song, $type) {
		joinRoom($room);
	
		HTTP::init();
		
		HTTP::setURL("roomQueue", array(":id" => roomId($room)));
		HTTP::setPostData(array("songId" => $song, "songType" => $type));
		
		
		return json_decode(HTTP::post(), true);
	}
	
	function songDetails($songid) {
		HTTP::init();
		
		HTTP::setURL("songDetails", array(":id" => $songid));
		
		return json_decode(HTTP::get(), true);
	}
	
	function songSearch($song, $type) {
		HTTP::init();
		
		HTTP::setURL("song", array(), array("name" => $song, "type" => $type));
		
		return json_decode(HTTP::get(), true);
	}
	
	function songSearchFilter($results, $searched, $type) {
		$songs = $results['data'];
		foreach($songs as $song) {
			if($song["fkid"]==$searched){
				return $song;
			}
		}
		
		switch($type) {
			case "youtube":
				foreach($songs as $song) {
					if(preg_match("/".$song["fkid"]."$/", $searched)) {
						return $song;
					}
				}
				break;
			case "soundcloud":
				foreach($songs as $song) {
					if($song["permalinkUrl"] == $searched || $song["streamUrl"] == $searched){
						return $song;
					}
				}
				break;
		}
		
		return $songs[0];
	}
	
	function stupidLog($stupid) {
		print_r($stupid);
		echo "<br><br>";
	}
	
	function stupidLogPrettyJson($stupid) {
		stupidLog("<pre>".json_encode($stupid, JSON_PRETTY_PRINT)."</pre>");
	}
?>