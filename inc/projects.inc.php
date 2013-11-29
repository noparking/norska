<?php
/* Norska -- Copyright (C) No Parking 2013 - 2013 */

class Norska_Projects {
	public $projects = array();

	public $projects_dir;
	public $project_names;

	public $email = "";

	function __construct($projects_dir, $project_names) {
		$this->projects_dir = $projects_dir;
		$this->project_names = $project_names;
	}

	function load() {
		$this->projects = array();
		foreach ($this->project_names as $project_name) {
			$project_path = $this->projects_dir."/".$project_name;

			$project_config = new Norska_Project_Config($project_path, $project_name);
			$project_config->email = $this->email;
			$project_config->get_config();
			$project_config->load_meta();

			$project = new Norska_Integration($project_config);

			$this->projects[] = $project;
		}
	}

	function compare_last_commit_timestamp($project_a, $project_b) {
		$timestamp_a = isset($project_a->meta['last_commit_timestamp']) ? (int)$project_a->meta['last_commit_timestamp'] : 0;
		$timestamp_b = isset($project_b->meta['last_commit_timestamp']) ? (int)$project_b->meta['last_commit_timestamp'] : 0;

		return $timestamp_b - $timestamp_a;
	}

	function sort() {
		usort($this->projects, array($this, "compare_last_commit_timestamp"));
	}
}
