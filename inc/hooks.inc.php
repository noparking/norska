<?php
/* Norska -- Copyright (C) No Parking 2013 - 2013 */

interface Norska_Hooks {
	function __construct(Norska_Project_Config $config);

	function install_before();

	function install_after();

	function uninstall_before();

	function uninstall_after();

	function send_subject_after($subject);

	function send_body_after($body);
}

