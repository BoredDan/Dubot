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
		
		public static function getURL($command, $inline_ids = array()) {
			global $ini;
			
			$url = $ini["api_url"].$ini["api_".$command];
			
			$search = array_map(function($key){ return "{".$key."}"; }, array_keys($inline_ids));
			$replace = array_values($inline_ids);
			
			$url = str_replace($search, $replace, $url);
			
			return $url;
		}
		
		public static function setURL($command, $inline_ids = array()) {
			$url = self::getURL($command, $inline_ids);
			
			curl_setopt(self::$ch, CURLOPT_URL, $url);
			
			return $url;
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
			if(self::$ch == null)
				self::$ch = curl_init();
				
			return self::$ch;
		}
		
		public static function handle() {
			return self::$ch;
		}
		
		public static function close() {
			curl_close(self::$ch);
		}
	}
	
	//Room functions
	
	function roomDetails($room) {
		global $ini;
		
		HTTP::init();
		HTTP::setURL("room_details", array("room" => $room));
		
		$json = json_decode(HTTP::get(), true);
		
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