<?php
	require("dubot-utility.php");
	echo print_r(init())."<br>";
	echo joinRoom($ini["roomName"])."<br>";
	close();
?>