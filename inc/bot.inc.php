<?php
/* Norska -- Copyright (C) No Parking 2013 - 2013 */

class Norska_Bot {
	public $project;

	private $root_path;

	function __construct ($root_path) {
		$this->root_path = $root_path;
	}

	function help () {
		$help = Norska::__("Methods available with Norska_Bot:") . "\n";
		$ReflectionClass = new ReflectionClass("Norska_Bot");
		foreach ($ReflectionClass->getMethods() as $method) {
			if (!in_array($method->getName(), array ("help", "__construct"))) {
				$help .= "--" . $method->getName() . "\n";
			}
		}

		return $help;
	}

	function integrate($arguments)	{
		$parameters = array ();
		foreach ($arguments as $value) {
			$elements = explode("=", $value);
			$parameters[$elements[0]] = $elements[1];
		}

		$project_path = $this->root_path."/projects/".$parameters['project'];
		$project_name = $parameters['project'];
		if (isset($parameters['project_name'])) {
			$project_name = $parameters['project_name'];
		}

		$norska_config = new Norska_Project_Config($project_path, $project_name);
		$norska_config->email = $parameters['email'];
		if (isset($parameters['smtp'])) {
			$norska_config->smtp = $parameters['smtp'];
		}

		$integration = new Norska_Integration($norska_config);

		$integration->do_update();
		$integration->run();
		try {
			$integration->send();
		} catch (Exception $e) {
			echo $e->getMessage().PHP_EOL;
		}
	}

	function integrate_last($arguments) {
		$parameters = array ();
		foreach ($arguments as $value) {
			$elements = explode("=", $value);
			$parameters[$elements[0]] = $elements[1];
		}

		$projects_dir = $this->root_path."/projects";
		$project_names = array();

		foreach (scandir($projects_dir) as $entry) {
			$project_path = $projects_dir."/".$entry;
			if ($entry != "." and $entry != ".." and is_dir($project_path)) {
				if (file_exists($project_path."/cfg/config.cfg.php")) {
					$project_names[] = $entry;
				}
			}
		}

		$projects = new Norska_Projects($projects_dir, $project_names);
		$projects->email = $parameters['email'];
		$projects->load();
		$projects->sort();

		foreach ($projects->projects as $project) {
			if ($project->norska_config->need_update()) {
				list($last_commit_id, $last_commit_timestamp) = $project->do_update();

				$meta_last_commit_id = isset($project->norska_config->meta['last_commit_id']) ? $project->norska_config->meta['last_commit_id'] : null;

				if ($last_commit_id != $meta_last_commit_id) {
					$project->run();
					try {
						$project->send();
					} catch (Exception $e) {
						echo $e->getMessage().PHP_EOL;
					}

					$project->norska_config->meta['last_commit_id'] = $last_commit_id;
					$project->norska_config->meta['last_commit_timestamp'] = $last_commit_timestamp;

					$project->norska_config->save_meta();
					break;
				}
			}

			$project->norska_config->save_meta();
		}
	}

	function unlock($arguments) {
		$parameters = array ();
		foreach ($arguments as $value) {
			$elements = explode("=", $value);
			$parameters[$elements[0]] = $elements[1];
		}

		require_once dirname(__FILE__) . '/project_config.php';

		$project_path = $this->root_path."/projects/".$parameters['project'];
		$project_name = $parameters['project'];
		if (isset($parameters['project_name'])) {
			$project_name = $parameters['project_name'];
		}
		$norska_config = new Norska_Project_Config($project_path, $project_name);

		$integration = new Norska_Integration($norska_config);
		$integration->unlock();
	}
}
