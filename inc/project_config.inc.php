<?php
/* Norska -- Copyright (C) No Parking 2013 - 2013 */

class Norska_Project_Config {
	public $project_path;
	public $project_name;
	public $email;
	public $smtp = null;

	private $config = null;

	function __construct($project_path, $project_name) {
		$this->project_path = $project_path;
		$this->project_name = $project_name;

		$this->meta = array();
	}

	function get_config($key = null) {
		if ($this->config === null) {
			$this->load_config();
		}

		if ($key !== null) {
			if (!isset($this->config[$key])) {
				throw new Exception(Norska::__("This config not exist (%s)", $key));
			}

			return $this->config[$key];
		}

		return $this->config;
	}

	function load_config() {
		$default_config = array();
		$project_config = array();

		$config = &$default_config;
		require dirname(__FILE__)."/../cfg/config.inc.php";

		$config = &$project_config;
		require $this->project_path."/cfg/config.cfg.php";

		$this->config = array_merge_smart($default_config, $project_config);
	}

	function meta_filename() {
		return $this->project_path."/norska_meta.json";
	}

	function load_meta() {
		if (file_exists($this->meta_filename())) {
			$data = file_get_contents($this->meta_filename());
			$this->meta = json_decode($data, true);
		}
	}

	function save_meta() {
		$data = json_encode($this->meta, JSON_PRETTY_PRINT);
		file_put_contents($this->meta_filename(), $data);
	}

	function delete_meta() {
		unlink($this->meta_filename());
	}

	function need_update() {
		$current = time();
		$last = isset($this->meta['last_check']) ? (int)$this->meta['last_check'] : 0;
		$delta = $current - $last;

		$check_interval = (int)$this->config['parameters']['check_interval'];
		if ($check_interval == -1) {
			return false;
		}

		$this->meta['last_check'] = time();
		return $delta >= $check_interval;
	}
}
