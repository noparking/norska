<?php
/* Norska -- Copyright (C) No Parking 2013 - 2013 */

class Norska_Repository_Svn {
	private $repository = array();

	private $number_revision;

	function __construct($repository) {
		$this->repository = $repository;
		$this->repository['md5'] = md5($this->repository['url']);
		$this->number_revision();
	}

	function info() {
		$logentry = simplexml_load_string($this->exec("log", "-r " . $this->number_revision() . " -v --xml"));
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
			$xml = simplexml_load_string($this->exec("info", "--xml"));
			if ($xml === false) {
				$this->repository['revision'] = Norska::__("unknown");
			} else {
				$rev = $xml->xpath("//info/entry/commit");
				$this->repository['revision'] = $rev[0]['revision'];
			}
			$this->number_revision = $this->repository['revision'];
		}
		return $this->number_revision;
	}

	function install($path, $revision = 0) {
		$revision = $revision ? $revision : $this->repository['revision'];
		return $this->exec("export --force", "-r " . $revision . " " . $path);
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
		$cmd = "svn " . $command . " --username " . $this->repository['user'] . " --password " . $this->repository['pass'] . " " . $this->repository['url'];
		$cmd .= $options ? " " . $options : "";

		return shell_exec($cmd);
	}
}