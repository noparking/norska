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

		require_once dirname(__FILE__) . '/project_config.php';

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

		$integration->install();
		$integration->run();
		try {
			$integration->send();
		} catch (Exception $e) {
			echo $e->getMessage()."\n";
		}
		$integration->uninstall();
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
