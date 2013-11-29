<?php
/* Norska -- Copyright (C) No Parking 2013 - 2013 */

class Norska_Repository_Svn {
	private $svn_path;
	private $repository = array();

	private $number_revision;

	function __construct($repository, $svn_path = null) {
		if ($svn_path === null) {
			include dirname(__FILE__)."/../cfg/config.inc.php";
			$svn_path = $config['parameters']['svn'];
		}

		$this->svn_path = $svn_path;
		$this->repository = $repository;
		$this->repository['md5'] = md5($this->repository['url']);
		$this->number_revision();
	}

	function info() {
		$data = $this->exec("log", "-r " . $this->number_revision() . " -v --xml");
		$logentry = simplexml_load_string($data);
		foreach ($logentry->xpath("//logentry[@revision='" . $this->number_revision() . "']") as $log) {
			$message = Norska::__("Author: %s", array ($log->author)) . "\n";
			$message .= Norska::__("Date: %s", array (date("d/m/Y à H:i", strtotime($log->date)))) . "\n";
			$message .= Norska::__("Comment: %s", array ($log->msg)) . "\n\n";
			$message .= Norska::__("Files:") . "\n";
			foreach ($logentry->xpath("//logentry[@revision='" . $this->number_revision() . "']/paths/path") as $path) {
				$message .= $path['action'] . " : " . $path . "\n";
			}
		}

		return $message;
	}

	function number_revision() {
		if (!isset($this->number_revision)) {
			$data = $this->exec("info", "--xml");
			$xml = simplexml_load_string($data);
			if ($xml === false) {
				$this->repository['revision'] = Norska::__("unknown");
			} else {
				$rev = $xml->xpath("//info/entry/commit");
				$this->repository['revision'] = $rev[0]['revision'];
			}
			$this->number_revision = (int)$this->repository['revision'];
		}
		return $this->number_revision;
	}

	function commit_timestamp($commit_id) {
		if (!isset($this->timestamp_revision)) {
			$data = $this->exec("info", "--xml");
			$xml = simplexml_load_string($data);
			if ($xml === false) {
				$this->repository['timestamp'] = 0;
			} else {
				$date = $xml->xpath("//info/entry/commit/date");
				list(, $date) = each($date);
				$this->repository['timestamp'] = strtotime($date);
			}

			$this->timestamp_revision = (int)$this->repository['timestamp'];
		}

		return $this->timestamp_revision;
	}

	function install($path, $revision = 0) {
		$revision = $revision ? $revision : $this->repository['revision'];
		return $this->exec("checkout", "--force -r " . $revision . " " . $path);
	}

	function update($path) {
		return $this->exec("update", $path);
	}

	function uninstall($path) {
		foreach (glob($path . "/*") as $file) {
			if (is_dir($file)) {
				$this->uninstall($file);
			} else {
				unlink($file);
			}
		}
		foreach (glob($path . "/.*") as $file) {
			if (is_dir($file) and !preg_match("/\.$/", $file)) {
				$this->uninstall($file);
			} elseif (is_file($file)) {
				unlink($file);
			}
		}
		rmdir($path);
	}

	function repository_md5() {
		return $this->repository['md5'];
	}

	function repository_url() {
		return $this->repository['url'];
	}

	function exec($command, $options = "") {
		$cmd = $this->svn_path." " . $command . " --no-auth-cache --non-interactive --username " . $this->repository['user'] . " --password " . $this->repository['pass'];

		if ($command != "update") {
			$cmd .= " " . $this->repository['url'];
		}

		$cmd .= $options ? " " . $options : "";

		return shell_exec($cmd);
	}
}
