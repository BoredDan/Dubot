<?php
	require("dubot-utility.php");
	HTTP::init();
	echo roomId($ini["room"]);
	HTTP::close();
?>