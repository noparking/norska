<?php
/* Norska -- Copyright (C) No Parking 2013 - 2013 */

class Norska_Integration {
	const LOCKED_TIME = 720;

	public $project;
	public $run;
	public $info;
	public $config;

	public $norska_config;

	protected $smtp = null;
	protected $email = null;

	protected $hook_object;

	function __construct(Norska_Project_Config $config) {
		$this->norska_config = $config;
		$this->project = $config->project_name;
		$this->smtp = $config->smtp;
		$this->email = $config->email;
		$this->config = $config->get_config();

		$this->init_repository();
	}

	function init_repository() {
		if (isset($this->config['svn'])) {
			$svn_path = $this->config['parameters']['svn'];
			$this->svn = new Norska_Repository_Svn($this->config['svn'], $svn_path);
		}
		if (isset($this->config['git'])) {
			$git_path = $this->config['parameters']['git'];
			$this->git = new Norska_Repository_Git($this->config['git'], $git_path);
		}
	}

	function lock() {
		echo Norska::__("Locked (%s)", $this->project).PHP_EOL;
		register_shutdown_function(array ($this, "unlock"));
		return file_put_contents($this->lock_file(), "locked");
	}

	function unlock() {
		if (file_exists($this->lock_file())) {
			echo Norska::__("Unlocked (%s)", $this->project).PHP_EOL;
			return unlink($this->lock_file());
		}
	}

	function install() {
		while ($this->is_locked()) {
			echo "Waiting ".$this->get_remaining_locktime()." sec ...".PHP_EOL;
			sleep(10);
		}

		$this->lock();

		echo Norska::__("Start Installation of \"%s\"...", $this->project).PHP_EOL;

		$this->hook("install_before");

		if (isset($this->svn)) {
			$this->svn->install($this->config['parameters']['path']);
		}

		if (isset($this->git)) {
			$this->git->install($this->config['parameters']['path']);
		}

		$this->hook("install_after");

		echo Norska::__("Installation of \"%s\" complete", $this->project).PHP_EOL;
		$this->unlock();
	}

	function install_db() {
		if (isset($this->config['mysql'])) {
			if (!isset($this->mysql)) {
				$this->mysql = new Norska_Database_Mysql($this->config['mysql']);
			}
			$this->mysql->install();
		}
	}

	function update() {
		while ($this->is_locked()) {
			echo "Waiting ".$this->get_remaining_locktime()." sec ...".PHP_EOL;
			sleep(10);
		}

		$this->lock();

		echo Norska::__("Start update of \"%s\"...", $this->project).PHP_EOL;

		if (isset($this->svn)) {
			$this->svn->update($this->config['parameters']['path']);
		}

		if (isset($this->git)) {
			$this->git->update($this->config['parameters']['path']);
		}

		echo Norska::__("update of \"%s\" complete", $this->project).PHP_EOL;
		$this->unlock();
	}

	function uninstall() {
		echo Norska::__("Start Uninstall process (%s)", $this->project).PHP_EOL;
		$do_uninstall = $this->hook("uninstall_before");

		if (isset($this->svn) and $do_uninstall !== false) {
			$this->svn->uninstall($this->config['parameters']['path']);
		}

		if (isset($this->git) and $do_uninstall !== false) {
			$this->git->uninstall($this->config['parameters']['path']);
		}

		$this->hook("uninstall_after");
		echo Norska::__("Uninstall process complete (%s)", $this->project).PHP_EOL;
	}

	function uninstall_db() {
		if (isset($this->config['mysql'])) {
			if (!isset($this->mysql)) {
				$this->mysql = new Norska_Database_Mysql($this->config['mysql']);
			}
			$this->mysql->uninstall();
		}
	}

	function do_update() {
		if (!$this->is_installed()) {
			$this->install();
		} else {
			$this->update();
		}

		$last_commit_id = $this->last_commit_id();
		$last_commit_timestamp = (int)$this->last_commit_timestamp();

		return array($last_commit_id, $last_commit_timestamp);
	}

	function is_installed() {
		return file_exists($this->config['parameters']['path']);
	}

	function run() {
		echo Norska::__("Start Run process (%s)", $this->project).PHP_EOL;
		$this->install_db();

		$cmd = "php ".$this->run_file();
		$timeout = $this->config['parameters']['timeout'];

		$stdout = "";
		$stderr = "";
		$this->execute($cmd, null, $stdout, $stderr, $timeout);

		$this->run .= "-----> STDOUT".PHP_EOL;
		$this->run .= $stdout.PHP_EOL;

		$this->run .= "-----> STDERR".PHP_EOL;
		$this->run .= $stderr.PHP_EOL;

		$this->uninstall_db();
		echo Norska::__("Run process complete (%s)", $this->project).PHP_EOL;
	}

	function execute($cmd, $stdin = null, &$stdout, &$stderr, $timeout = false) {
		echo "Running (timeout={$timeout}s): {$cmd}".PHP_EOL;

		$pipes = array();
		$descriptors = array(
			array("pipe", "r"),
			array("pipe", "w"),
			array("pipe", "w"),
		);
		$process = proc_open($cmd, $descriptors, $pipes);

		$start = time();
		$stdout = "";
		$stderr = "";

		if (is_resource($process)) {
			stream_set_blocking($pipes[0], 0);
			stream_set_blocking($pipes[1], 0);
			stream_set_blocking($pipes[2], 0);

			fwrite($pipes[0], $stdin);
			fclose($pipes[0]);
		}

		while (is_resource($process)) {
			$stdout .= stream_get_contents($pipes[1]);

			$stderr_content = stream_get_contents($pipes[2]);
			$stderr .= $stderr_content;
			fwrite(STDERR, $stderr_content);

			$cur = time();
			$diff = $cur - $start;

			if ($timeout !== false and $diff > $timeout) {
				$stderr .= "Timeout ({$timeout}s).".PHP_EOL;
				proc_terminate($process, 9);
				return 1;
			}

			$status = proc_get_status($process);
			if (!$status['running']) {
				fclose($pipes[1]);
				fclose($pipes[2]);
				proc_close($process);

				return $status['exitcode'];
			}

			usleep(1 * 1000000); // 1s
		}

		return 1;
	}

	function info() {
		if (isset($this->svn)) {
			return $this->svn->info();
		}
		if (isset($this->git)) {
			return $this->git->info($this->config['parameters']['path']);
		}
	}

	function last_commit_id() {
		if (isset($this->svn)) {
			return $this->svn->number_revision();
		}
		if (isset($this->git)) {
			return $this->git->commit_id($this->config['parameters']['path']);
		}
	}

	function last_commit_timestamp() {
		if (isset($this->svn)) {
			return $this->svn->commit_timestamp($this->last_commit_id());
		}
		if (isset($this->git)) {
			return $this->git->commit_timestamp($this->config['parameters']['path'], $this->last_commit_id());
		}
	}

	function send() {
		echo Norska::__("Start Send process (%s)", $this->project).PHP_EOL;
		if (isset($this->email)) {
			require_once dirname(__FILE__).'/../libraries/phpmailer/class.phpmailer.php';

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
		echo Norska::__("Send process complete (%s)", $this->project).PHP_EOL;
	}

 	/**
 	 * @return string
 	 */
 	function send_subject() {
		if (strstr($this->run, "FAILURE")) {
			$subject = substr($this->run, strpos($this->run, "FAILURE"));
			$subject = str_replace("\n", " ", $subject);
		} elseif (strstr($this->run, "OK")) {
			$subject = "SUCCESS !!!";
		} elseif (strstr($this->run, "Timeout")) {
			$subject = "TIMEOUT";
		} else {
			$subject = "INCONNU";
		}
		$result_hook = $this->hook("send_subject_after", $subject);

		if ($result_hook !== false and $result_hook !== null) {
			$subject = $result_hook;
		}
		return $subject;
	}

 	/**
 	 * @return string
 	 */
 	function send_body() {
		$body = $this->info()."\n\n******\n\n".$this->run;
		$result_hook = $this->hook("send_body_after", $body);
		if ($result_hook !== false) {
			$body = $result_hook;
		}
		return $body;
	}

	function project_hashed() {
		return md5($this->project);
	}

	function run_file() {
		return $this->norska_config->project_path."/run.php";
	}

	function hooks_file() {
		return $this->norska_config->project_path."/inc/hooks.inc.php";
	}

	function lock_file() {
		return "/tmp/".$this->project_hashed()."_lock";
	}

	function is_locked() {
		if (file_exists($this->lock_file()) && $this->get_remaining_locktime() > 0) {
			return true;
		} else {
			return false;
		}
	}

	function get_remaining_locktime() {
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
	function hook($method, $parameters = null) {
		$class_hook = "Norska_".ucfirst($this->project)."_Hooks";

		if ($this->hook_object === null) {
			$file_hook = $this->hooks_file();
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
