<?php
/* Norska -- Copyright (C) No Parking 2013 - 2013 */

class Norska_Project_Config {
	public $project_path;
	public $email;
	public $smtp = null;

	private $config = null;

	function __construct($project_path) {
		$this->project_path = $project_path;
	}

	function get_config($key = null) {
		if ($this->config === null) {
			$config = &$this->config;
			require $this->project_path."/cfg/config.cfg.php";
		}

		if ($key !== null) {
			if (!isset($this->config[$key])) {
				throw new Exception(Norska::__("This config not exist (%s)", $key));
			}

			return $this->config[$key];
		}

		return $this->config;
	}

	function get_project_name() {
		$parts = explode("/", $this->project_path);

		return $parts[count($parts)-1];
	}
}
