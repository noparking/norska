<?php
/* Norska -- Copyright (C) No Parking 2013 - 2013 */

abstract class NorskaTestCase extends UnitTestCase {
	protected $project_path;
	protected $project_name;
	protected $project_config;
	protected $integration;

	function __construct() {
		parent::__construct();

		$this->project_path = dirname(__FILE__)."/../unit/project";
		$this->project_name = "test";
	}

	function setUp() {
		$this->project_config = new Norska_Project_Config($this->project_path, $this->project_name);
		$this->integration = new Norska_Integration($this->project_config);
	}
}
