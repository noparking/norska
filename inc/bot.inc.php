<?php
/* Norska -- Copyright (C) No Parking 2013 - 2013 */

class Norska_Bot {
	public $project;

	function __construct () {
		defined('NORSKA_PATH')
			|| define('NORSKA_PATH', realpath(dirname(__FILE__) . '/../'));
	}

	function help () {
		$help = Norska::__("Methods available with Norska_Bot:") . "\n";
		$ReflectionClass = new ReflectionClass("Norska_Bot");
		foreach ($ReflectionClass->getMethods() as $method)
		{
			if (!in_array($method->getName(), array ("help", "__construct")))
			{
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

		$project_path = NORSKA_PATH."/projects/".$parameters['project'];
		$norska_config = new Norska_Project_Config($project_path);

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
			echo $e->getMessage();
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

		$project_path = NORSKA_PATH."/projects/".$parameters['project'];
		$norska_config = new Norska_Project_Config($project_path);

		$integration = new Norska_Integration($norska_config);
		$integration->unlock();
	}
}
