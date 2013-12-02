<?php
/* Norska -- Copyright (C) No Parking 2013 - 2013 */

require_once dirname(__FILE__)."/../inc/require.inc.php";

class tests_Norska_Integration extends NorskaTestCase {
	function test_run() {
		if (defined("STDERR")) {
			$this->integration->run();
			$this->assertPattern("/Hello world!/", $this->integration->run);
		}
	}

	function test_send_subject() {
		$this->integration->run = "[???] FAILURE test";
		$this->assertEqual($this->integration->send_subject(), "FAILURE test");

		$this->integration->run = "[???] FAILURE long test contenant beaucoup\nde lignes inutiles";
		$this->assertEqual($this->integration->send_subject(), "FAILURE long test contenant beaucoup");

		$this->integration->run = "[???] OK tests passed";
		$this->assertEqual($this->integration->send_subject(), "SUCCESS !!!");

		$this->integration->run = "[???] ERRORS";
		$this->assertEqual($this->integration->send_subject(), "INCONNU");
	}

	function test_paths() {
		$this->assertEqual($this->integration->run_file(), $this->project_path."/run.php");
		$this->assertEqual($this->integration->hooks_file(), $this->project_path."/inc/hooks.inc.php");
	}

	function test_hook() {
		$this->assertEqual($this->integration->hook("test"), "test");
	}
}
