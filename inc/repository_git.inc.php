<?php
/* Norska -- Copyright (C) No Parking 2013 - 2013 */

class Norska_Repository_Git {
	private $repository = array();

	function __construct($repository) {
		$this->repository = $repository;
	}

	function install($path, $commit_id = 0) {
		$url = $this->repository['url'];

		if ($commit_id != 0) {
			return $this->exec($path, "clone", "--recursive", "-b", $commit_id, $url, $path);
		} else {
			return $this->exec($path, "clone", "--recursive", $url, $path);
		}
	}

	function commit_id($path) {
		$commit_id = $this->exec($path, "rev-parse", "HEAD");

		return trim($commit_id);
	}

	function info($path) {
		return $this->exec($path, "show", "--name-status", $this->commit_id($path));
	}

	function uninstall ($path) {
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
		if (file_exists($path)) {
			rmdir($path);
		}
	}

	function exec() {
		$args = func_get_args();
		$path = array_shift($args);

		$subcommand = $args[0];

		$command = "git ";

		if ($subcommand != "clone") {
			$command .= "--git-dir=".$path.".git ";
		}

		$command .= implode(" ", $args);
		$result = shell_exec($command);

		return $result;
	}
}
