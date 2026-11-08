<?php
	// Code shared by the web UI pages. Produces no output, so it can be
	// included before anything is sent (e.g. ahead of an ajax reply).
	// Not reachable over HTTP: the nginx site config gives 404 for inc_*.

	setlocale(LC_CTYPE, "en_US.UTF-8");

	// How long to wait for pagerd to accept a command and to reply, seconds
	define('PAGERD_TIMEOUT', 5);

	// Contents of the pagerd config, read once per request; false if unreadable
	function getPagerdConfig() {
		static $config = null;
		if ($config === null) {
			$config = @file_get_contents(__DIR__ . '/../pagerd_config.py');
		}
		return $config;
	}

	// Station name from the pagerd config. Deliberately simple parser;
	// any problem (no file, no setting, empty value) gives "Unknown".
	function getInstallationName() {
		$config = getPagerdConfig();
		if ($config !== false &&
				preg_match('/^\s*INSTALLATION_NAME\s*=\s*([\'"])(.*?)\1/m', $config, $m) &&
				$m[2] !== '') {
			return $m[2];
		}
		return 'Unknown';
	}

	// Control socket port from the pagerd config, parsed the same simple way;
	// any problem gives the default port.
	function getControlPort() {
		$config = getPagerdConfig();
		if ($config !== false &&
				preg_match('/^\s*CONTROL_PORT\s*=\s*(\d+)\s*(#.*)?$/m', $config, $m) &&
				$m[1] > 0 && $m[1] < 65536) {
			return (int)$m[1];
		}
		return 16032;
	}

	// Send one command to pagerd over its control socket. pagerd handles a
	// single command per connection and closes it after replying.
	// Returns the reply without the trailing newline, or true if $waitReply
	// is false; on failure returns false and puts the reason into $error.
	function pagerdCommand($cmd, $waitReply = true, &$error = null) {
		$fp = @stream_socket_client('tcp://127.0.0.1:' . getControlPort(),
				$errno, $errstr, PAGERD_TIMEOUT);
		if (!$fp) {
			$error = "$errstr ($errno)";
			return false;
		}
		stream_set_timeout($fp, PAGERD_TIMEOUT);
		if (fwrite($fp, $cmd . "\n") === false) {
			fclose($fp);
			$error = 'write failed';
			return false;
		}
		if (!$waitReply) {
			fclose($fp);
			return true;
		}
		$reply = stream_get_contents($fp);
		$timedOut = stream_get_meta_data($fp)['timed_out'];
		fclose($fp);
		if ($reply === false || $timedOut) {
			$error = 'no reply';
			return false;
		}
		return rtrim($reply, "\n");
	}
?>
