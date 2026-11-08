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
<style type="text/css">
	body {
		margin: auto;
		max-width:700px;
	}
	.pager_body {
		padding: 10px 20px 30px 20px; 
		border-radius:30px;
		border-style: outset;
		border-width:15px;
		border-color: rgba(80, 80, 80, .6);
		background-color: #0f0f0f;
		background-image: url(/plastic.jpg);
	}
	
	.pager_display {
		width:450px;
		display: inline;
		font: 16pt monospace;
		resize: none;
		border-radius: 20px;
		border-width: 2px;
		padding:15px;
		margin: 0;
		background-color: #00ff66;
		border-style: groove;
		border-width:7px;
		border-color: rgba(80, 80, 80, .6);
		outline: none;
	}
	
	.label {
		font: bold italic 20pt sans-serif;
		color: rgba(213, 179, 0, .8);
		padding:7px;
	}
	
	.send_btn {
		width:100px;
		height: 100px;
		display: inline;
		font: bold 40pt sans-serif;
		border-radius: 20px;
		margin: 4px 0 4px 8px;
		background-color: #0f0f0f;
		color: rgba(213, 179, 0, .8);
		border-style: groove;
		border-width:7px;
		border-color: rgba(80, 80, 80, .6);
		outline: none;
	}
	
	.btn_down {
		border-style: inset !important;
	}
	
	h3 		{ font-family: sans-serif; font-size: 14px; color:#00a8de; }
	h3.hide { color:white; }
	h3.err 	{ color:red; }
	h3.ok	{ color:green; }
</style>
</head>
<body onload="focus()">
<center>
<script>
	function focus() {
		document.getElementById('text').focus()
	}
	
    function hideStatus() {
        document.getElementById("status").className="hide";
    }
    setTimeout(hideStatus, 3000);
    
    var alreadySubmit = false;
    function checksubmit() {
		focus();
		if (alreadySubmit) return false;
		
		var text = document.getElementById("text").value.trim();
		if (text == "") return false;
		
		var btn = document.getElementById("send_btn");
		btn.classList.add("btn_down")
		alreadySubmit = true;
	}
</script>
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
