<?php
/* Norska -- Copyright (C) No Parking 2013 - 2013 */

function array_merge_smart() {
	$args = func_get_args();

	$arr = array();

	foreach ($args as $arg) {
		foreach ($arg as $key => $val) {
			if (array_key_exists($key, $arr)) {
				if (is_array($val)) {
					$arr[$key] = array_merge_smart($arr[$key], $val);
				} else {
					$arr[$key] = $val;
				}
			} else {
				$arr[$key] = $val;
			}
		}
	}

	return $arr;
}
