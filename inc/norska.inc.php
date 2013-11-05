<?php
/* Norska -- Copyright (C) No Parking 2013 - 2013 */


class Norska {
	static private $_norskaPath;
	static private $_projectsPath = null;
	static private $_projectName;
	static private $_config = array();
	static private $_langSet = false;

	/**
	 * @param string $string
	 * @param array $replacements
	 * @return string
	 */
	static public function __($string, $replacements = null) {
		if (isset($GLOBALS['__'][$string])) {
			$string = $GLOBALS['__'][$string];
		} else {
// 			trigger_error("Translation '".$string."' is missing.", E_USER_WARNING);
		}

		switch (true) {
			case $replacements === null:
				return $string;
			case is_array($replacements):
				return vsprintf($string, $replacements);
			case is_string($replacements):
				$args = func_get_args();
				array_shift($args);
				return vsprintf($string, $args);
		}
	}
}
