<?php
/* Norska -- Copyright (C) No Parking 2013 - 2013 */

require_once dirname(__FILE__)."/../inc/require.inc.php";

class tests_Norska_Project_Config extends NorskaTestCase {
	function test_get_config() {
		$project_config = new Norska_Project_Config($this->project_path, $this->project_name);

		$this->assertEqual(count($project_config->get_config()), 2);

		$parameters = $project_config->get_config('parameters');
		$this->assertEqual($parameters['root_url'], "http://127.0.0.1/norska");
		$this->assertEqual($parameters['owner'], "www-data");
	}
}
