<?php
	//Loads configuration settings
	$ini = array_merge_recursive(
		parse_ini_file("dubot.ini", true),
		parse_ini_file("dubot-user.ini", true)
	);
	
	//HTTP
	
	class HTTP {
		private function __construct() {}
		private static $ch = null;
		private static $apiSection = "";
		
		public static function setAPI($api) {
			switch($api) {
				case "dubtrack":
					self::$apiSection = "Dubtrack.API";
					break;
				case "youtube":
					self::$apiSection = "Youtube.API";
					break;
				default:
					self::$apiSection = $api;
					break;
			}
		}
		
		public static function getURL($command, $inline_ids = array(), $args = array()) {
			global $ini;
			
			$url = $ini[self::$apiSection]["url"].$ini[self::$apiSection][$command];
			
			$search = array_keys($inline_ids);
			$replace = array_values($inline_ids);
			
			$url = str_replace($search, $replace, $url);
			
			if(is_array($ini[self::$apiSection][$command."Args"]))
				$args = array_merge($ini[self::$apiSection][$command."Args"], $args);
			
			if(is_array($ini[self::$apiSection]["globalArgs"]))
				$args = array_merge($args, $ini[self::$apiSection]["globalArgs"]);
			
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
		
		public static function init($api = null) {
			global $ini;
			
			if(self::$ch == null) {
				self::$ch = curl_init();
				curl_setopt(self::$ch, CURLOPT_COOKIEJAR, $ini["Cookies"]["cookie_storage"]);
				curl_setopt(self::$ch, CURLOPT_COOKIEFILE, $ini["Cookies"]["cookie_storage"]);
			}
			
			if($api) {
				self::setAPI($api);
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
		
		HTTP::init("dubtrack");
		HTTP::setURL("login");
		HTTP::setPostData(array("username" => $ini["Dubtrack.User"]["username"], "password" => $ini["Dubtrack.User"]["password"]));
		
		return json_decode(HTTP::post(), true);
	}
	
	function sessionInfo() {
		global $ini;
		
		HTTP::init("dubtrack");
		HTTP::setURL("session");
		
		return json_decode(HTTP::get(), true);
	}
	
	function init() {
		global $ini;
		
		HTTP::init("dubtrack");
		
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
		HTTP::init("dubtrack");
		
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
		HTTP::init("dubtrack");
		
		HTTP::setURL("roomUsers", array(":id" => roomId($room)));
		
		return json_decode(HTTP::post(), true);
	}
	
	function queueSong($room, $song, $type) {
		joinRoom($room);
	
		HTTP::init("dubtrack");
		
		HTTP::setURL("roomQueue", array(":id" => roomId($room)));
		HTTP::setPostData(array("songId" => $song, "songType" => $type));
		
		
		return json_decode(HTTP::post(), true);
	}
	
	function songDetails($songid, $api = "dubtrack") {
		HTTP::init($api);
		
		switch($api) {
			case "dubtrack":
				HTTP::setURL("songDetails", array(":id" => $songid));
				break;
			case "youtube":
				HTTP::setURL("songDetails", array(), array("id" => $songid));
				break;
			default:
				HTTP::setURL("songDetails");
				break;
		}
		
		return json_decode(HTTP::get(), true);
	}
	
	function songSearch($song, $type) {
		HTTP::init("dubtrack");
		
		HTTP::setURL("song", array(), array("name" => $song, "type" => $type));
		
		return json_decode(HTTP::get(), true);
	}
	
	function songSearchFilterFkid($songs, $searched) {
		foreach($songs as $song) {
			if($song["fkid"]==$searched){
				return $song;
			}
		}
		return null;
	}
	
	function songSearchFilterURL($songs, $searched, $type) {
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
		return null;
	}
	
	function songSearchFilterPopularity($songs, $searched, $type) {
		switch($type) {
			case "youtube":
				$mostPlays = -1;
				return array_reduce($songs,
					function($song1, $song2) use ($searched, &$mostPlays) {
						$details = songDetails($song2["fkid"], "youtube")["items"][0];
						if($details["statistics"]["viewCount"] > $mostPlays) {
							$mostPlays = $details["statistics"]["viewCount"];
							return $song2;
						} else {
							return $song1;
						}
					}, null
				);
				break;
		}
		return $songs[0];
	}
	
	function songSearchFilterExact($songs, $searched, $case_sensitive = false) {
		return array_values(array_filter($songs, function($song) use ($searched, $case_sensitive) {
			if($case_sensitive) {
				return strcmp(trim($song["name"]), trim($searched)) == 0; 
			} else {
				return strcasecmp(trim($song["name"]), trim($searched)) == 0; 
			}
		}));
	}
	
	function songSearchFilter($songs, $searched, $type) {
		$filteredSong = songSearchFilterFkid($songs, $searched);
		if($filteredSong)
			return $filteredSong;
			
		$filteredSong = songSearchFilterUrl($songs, $searched, type);
		if($filteredSong)
			return $filteredSong;
		
		$filteredSongs = songSearchFilterExact($songs, $searched, true);
		if(count($filteredSongs) == 1) {
			return $filteredSongs[0];
		} else if(count($filteredSongs) > 1) {
			$songs = $filteredSongs;
		} else {
			$filteredSongs = songSearchFilterExact($songs, $searched, false);
			if(count($filteredSongs) == 1) {
				return $filteredSongs[0];
			} else if(count($filteredSongs) > 1) {
				$songs = $filteredSongs;
			}
		}
		
		return songSearchFilterPopularity($songs, $searched, $type);
	}
	
	function stupidLog($stupid) {
		print_r($stupid);
		echo "<br><br>";
	}
	
	function stupidLogPrettyJson($stupid) {
		stupidLog("<pre>".json_encode($stupid, JSON_PRETTY_PRINT)."</pre>");
	}
?>