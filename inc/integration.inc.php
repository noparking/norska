<?php
/* Norska -- Copyright (C) No Parking 2013 - 2013 */

class Norska_Integration {
	const LOCKED_TIME = 720;

	public $project;
	public $run;
	public $info;
	public $config;

	protected $norska_config;

	protected $smtp = null;
	protected $email = null;

	protected $hook_object;

	function __construct (Norska_Project_Config $config) {
		$this->norska_config = $config;
		$this->project = $config->get_project_name();
		$this->smtp = $config->smtp;
		$this->email = $config->email;
		$this->config = $config->get_config();

		$this->init_repository();
	}

	function lock () {
		echo Norska::__('Locked (%s)', $this->project) . PHP_EOL;
		register_shutdown_function(array ($this, "unlock"));
		return file_put_contents($this->lock_file(), "locked");
	}

	function install () {
		while ($this->is_locked()) {
			echo "Waiting " . $this->get_remaining_locktime() . " sec ..." . PHP_EOL;
			sleep(10);
		}

		$this->lock();

		echo Norska::__('Start Installation of "%s"...', $this->project) . PHP_EOL;

		$this->hook("install_before");

		if (isset($this->config['svn'])) {
			if (!isset($this->svn)) {
				$this->svn = new Norska_Repository_Svn($this->config['svn']);
			}
			$this->svn->install($this->config['parameters']['path']);
		}

		if (isset($this->config['git'])) {
			if (!isset($this->git)) {
				$this->git = new Norska_Repository_Git($this->config['git']);
			}
			$this->git->install($this->config['parameters']['path']);
		}

		if (isset($this->config['mysql'])) {
			if (!isset($this->mysql)) {
				$this->mysql = new Norska_Database_Mysql($this->config['mysql']);
			}
			$this->mysql->install();
		}

		$this->hook("install_after");

		echo Norska::__('Installation of "%s" complete', $this->project) . PHP_EOL;
	}

	function run () {
		echo Norska::__('Start Run process (%s)', $this->project) . PHP_EOL;
		$this->run = shell_exec("php " . $this->run_file());
		echo Norska::__('Run process complete (%s)', $this->project) . PHP_EOL;
	}

	function info () {
		if (isset($this->svn)) {
			return $this->svn->info();
		}
		if (isset($this->git)) {
			return $this->git->info($this->config['parameters']['path']);
		}
	}

	function send () {
		echo Norska::__('Start Send process (%s)', $this->project) . PHP_EOL;
		if (isset($this->email)) {
			require_once dirname(__FILE__) . '/../libraries/phpmailer/class.phpmailer.php';

			$email = new PHPMailer();
			if ($this->smtp !== null)
			{
				$email->IsSMTP();
				$email->Host = $this->smtp;
				$email->SMTPAuth = false;
			}

			$email->From = 'norska@noparking.net';
			$email->FromName = 'Norska';

			if ($this->email === null)
			{
				throw new Exception(Norska::__("The email to send result is not defined, define it in parameters."));
			}
			$email->AddAddress($this->email);

			$email->Subject = $this->send_subject();
			$email->Body = $this->send_body();

			if ($email->Send() !== true) {
				throw new Exception(Norska::__("Error: Email not send"));
			}
		}
		echo Norska::__('Send process complete (%s)', $this->project) . PHP_EOL;
	}

 	/**
 	 * @return string
 	 */
 	function send_subject () {
		if (strstr($this->run, "FAILURE")) {
			$subject = substr($this->run, strpos($this->run, "FAILURE"));
			$subject = str_replace("\n", " ", $subject);
		} elseif (strstr($this->run, "OK")) {
			$subject = "SUCCESS !!!";
		} else {
			$subject = "INCONNU";
		}
		$result_hook = $this->hook("send_subject_after", $subject);

		if ($result_hook !== false) {
			$subject = $result_hook;
		}
		return $subject;
	}

 	/**
 	 * @return string
 	 */
 	function send_body () {
		$body = $this->info() . "\n\n******\n\n" . $this->run;
		$result_hook = $this->hook("send_body_after", $body);
		if ($result_hook !== false) {
			$body = $result_hook;
		}
		return $body;
	}

 	function uninstall () {
		echo Norska::__('Start Uninstall process (%s)', $this->project) . PHP_EOL;
		$do_uninstall = $this->hook("uninstall_before");

		if (isset($this->svn) and $do_uninstall !== false) {
			$this->svn->uninstall($this->config['parameters']['path']);
		}

		if (isset($this->git) and $do_uninstall !== false) {
			$this->git->uninstall($this->config['parameters']['path']);
		}

		if (isset($this->config['mysql']) and $do_uninstall !== false) {
			if (!isset($this->mysql)) {
				$this->mysql = new Norska_Database_Mysql($this->config['mysql']);
			}
			$this->mysql->uninstall();
		}

		$this->hook("uninstall_after");
		echo Norska::__('Uninstall process complete (%s)', $this->project) . PHP_EOL;
	}

	function unlock () {
		echo Norska::__('Unlocked (%s)', $this->project) . PHP_EOL;
		if (file_exists($this->lock_file())) {
			return unlink($this->lock_file());
		}
	}

	function project_hashed () {
		return md5($this->project);
	}

	function info_file () {
		return dirname(__FILE__) . "/../projects/" . $this->project . "/info.php";
	}

	function run_file () {
		$result = dirname(__FILE__) . "/../projects/" . $this->project . "/run.php";
		return $result;
	}

	function lock_file () {
		return "/tmp/" . $this->project_hashed() . "_lock";
	}

	function is_locked () {
		if (file_exists($this->lock_file()) && $this->get_remaining_locktime() > 0) {
			return true;
		} else {
			return false;
		}
	}

	function init_repository() {
		if (isset($this->config['svn'])) {
			$this->svn = new Norska_Repository_Svn($this->config['svn']);
		}
		if (isset($this->config['git'])) {
			$this->git = new Norska_Repository_Git($this->config['git']);
		}
	}

	function get_remaining_locktime () {
		if (file_exists($this->lock_file())) {
			return filemtime($this->lock_file()) - time() + self::LOCKED_TIME;
		} else {
			return 0;
		}
	}

	/**
	 * @param string $method
	 * @param mixed $parameters
	 * @throws Exception
	 */
	function hook ($method, $parameters = null) {
		$class_hook = "Norska_" . ucfirst($this->project) . "_Hooks";

		if ($this->hook_object === null) {
			$file_hook = dirname(__FILE__) . "/../projects/" . $this->project . "/inc/hooks.inc.php";
			if (file_exists($file_hook)) {
				require_once $file_hook;
				if (class_exists($class_hook)) {
					$this->hook_object = new $class_hook($this->norska_config);
				} else {
					$this->hook_object = false;
				}
			}
		}

		if ($this->hook_object instanceof Norska_Hooks) {
			if (method_exists($class_hook, $method)) {
				return $this->hook_object->$method($parameters);
			} else {
				throw new Exception(Norska::__("Hook method not exist"));
			}
		} elseif ($this->hook_object === false or $this->hook_object === null) {
			/* No hook */
		} else {
			throw new Exception(Norska::__("Hook class is not instance of 'Norska_Hook'"));
		}
	}
}
