<?php
	// Station name from the pagerd config. Deliberately simple parser;
	// any problem (no file, no setting, empty value) gives "Unknown".
	function getInstallationName() {
		$config = @file_get_contents(__DIR__ . '/../pagerd_config.py');
		if ($config !== false &&
				preg_match('/^\s*INSTALLATION_NAME\s*=\s*([\'"])(.*?)\1/m', $config, $m) &&
				$m[2] !== '') {
			return $m[2];
		}
		return 'Unknown';
	}
?>
<!doctype html>
<html lang=en>
<head>
<meta charset=utf-8>
<title><?php echo htmlspecialchars(getInstallationName()); ?></title>
<link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
<link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
<link rel="icon" type="image/png" sizes="192x192" href="/android-chrome-192x192.png">
<link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
<link rel="manifest" href="/site.webmanifest">
<link rel="mask-icon" href="/safari-pinned-tab.svg" color="#5bbad5">
<meta name="msapplication-TileColor" content="#90c73d">
<meta name="msapplication-TileImage" content="/mstile-144x144.png">
<meta name="theme-color" content="#ffffff">
<link rel="stylesheet" href="/p350.css">
<script type="text/javascript" src="/p350_user.js"></script>
</head>
<body class="page_user" onload="init()">
<center>
<img src="/logo.png" width="400px"/>
<h3><i>Девочка тинейджер позвони на пейджер! :-P</i></h3>
<h3 id="status"<?php
	setlocale(LC_CTYPE, "en_US.UTF-8");
	if (isset($_POST['txdata'])) {
			$HOST = "localhost";
			$PORT = "16032";
            $fp = stream_socket_client("tcp://" . $HOST . ":" . $PORT . "/", $errno, $errstr, 30);
            if (!$fp) {
            ?> class="err">Ошибка отправки: <?php echo "$errstr ($errno)"; ?></h3> <?php
            } else {
                fwrite($fp, "page " . $_POST['txdata']);
                fclose($fp);
            ?> class="ok">Отправлено!</h3> <?php
            }
    } else {
		?> class="hide">holder</h3><?php
	}
?>

<form action="/" method="POST" class="pager_body" onsubmit="return checksubmit()">
	<table>
		<tbody>
			<tr>
				<td colspan="2" class="label">СООБЩЕНИЕ</td>
			</tr>
			<tr valign="bottom">
				<td><textarea id="text" name="txdata" rows="8" class="pager_display"></textarea></td>
				<td><button type="submit" class="send_btn" id="send_btn">&#9654;</button></td>
	</tr>
	</tbody></table>
</form>
</center>
</body>
</html>
