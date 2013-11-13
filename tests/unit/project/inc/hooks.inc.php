<?php
/* Norska -- Copyright (C) No Parking 2013 - 2013 */

class Norska_Test_Hooks implements Norska_Hooks
{
	function __construct(Norska_Project_Config $config) {
	}

	function install_before() {
	}

	function install_after() {
	}

	function uninstall_before() {
	}

	function uninstall_after() {

	}

	function send_subject_after($subject) {
		return $subject;
	}

	function send_body_after($body) {
		return $body;
	}

	function test() {
		return "test";
	}
}
