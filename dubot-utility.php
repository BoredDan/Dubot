<?php
	$ini = array_merge(
		parse_ini_file("dubot.ini"),
		parse_ini_file("dubot-user.ini")
	);
?>