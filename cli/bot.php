<?php
/* Norska -- Copyright (C) No Parking 2013 - 2013 */

require dirname(__FILE__) . "/../inc/require.inc.php";

if (isset($argv)) {
	$arguments = $argv;
	array_shift($arguments);
} else {
	$arguments = $_GET;
}

$bot = new Norska_Bot();

$method = array_shift($arguments);
if (preg_match("/^--/", $method)) {
	$method = str_replace("-", "", $method);
	if (method_exists($bot, $method)) {
		$return = $bot->$method($arguments);
		if ($return === true or $return === false) {
			return $return;
		} else {
			echo $return;
		}
	}
	else {
		echo $bot->help();
	}
} else {
	echo $bot->help();
}
