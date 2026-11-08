<?php
	setlocale(LC_CTYPE, "en_US.UTF-8");

	// Station name from the pagerd config. Deliberately simple parser;
	// any problem (no file, no setting, empty value) gives "Unknown".
	function getInstallationName() {
		$config = @file_get_contents(__DIR__ . '/../../pagerd_config.py');
		if ($config !== false &&
				preg_match('/^\s*INSTALLATION_NAME\s*=\s*([\'"])(.*?)\1/m', $config, $m) &&
				$m[2] !== '') {
			return $m[2];
		}
		return 'Unknown';
	}

	if (isset($_GET['ajax'])) {
		
		// Handle txstate commands
		if (isset($_GET['txstate']) && 
				($_GET['txstate'] == '0') || ($_GET['txstate'] == '1') || ($_GET['txstate'] == 'auto') ) {
			exec('echo txenable ' . $_GET['txstate'] . ' | nc localhost 16032');
		}
		
		// Handle clearing commands
		if (isset($_GET['clrq'])) {
			exec('echo clrq | nc localhost 16032');
		}
		if (isset($_GET['clra'])) {
			exec('echo clra | nc localhost 16032');
		}
		
		// Handle txstate commands
		if (isset($_GET['silent_restart']) && 
				($_GET['silent_restart'] == '0') || ($_GET['silent_restart'] == '1') ) {
			exec('echo silent-restart ' . $_GET['silent_restart'] . ' | nc localhost 16032');
		}
		
		// First get power data and parse it
		$response = exec('echo jps | nc localhost 16032');
		$response = json_decode($response, true);
		
		// Get persistent config
		$pconf = exec('echo get-pconf | nc localhost 16032');
		$pconf = json_decode($pconf, true);
		
		// Add pconf data to response
		$response['silent_restart'] = $pconf['silent_restart'];

		$qsize = 0;
		if (is_countable($pconf['qmsg_mem'])) {
			$qsize = count($pconf['qmsg_mem']) - 1;	// Subtracting 1 to exclude digest
		}
		$response['qsize'] = $qsize;
		
		$asize = 0;
		if (is_countable($pconf['emerman']['archive'])) {
			$asize = count($pconf['emerman']['archive']);
		}
		$response['asize'] = $asize;
		
		// Get log data
		$logArr = [];
		if ($_GET['ajax'] == 'msg') {
			//exec("journalctl -u p350d -n2000 -o cat --no-pager | egrep '(Paged text|Retx)'", $logArr);
			exec("cat /tmp/p350-msg.log", $logArr);
		} else if ($_GET['ajax'] == 'all') {
			//exec("journalctl -u p350d -n500 -o cat --no-pager", $logArr);
			exec("cat /tmp/p350-all.log", $logArr);
		}
		
		// Add log to JSON
		$response['log'] = $logArr;
		
		// Output
		die(json_encode($response));
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
<script type="text/javascript" src="/gauge.min.js"></script>
<link rel="stylesheet" href="/p350.css">

<script type="text/javascript" src="/admin/p350_admin.js"></script>
</head>
<body class="page_admin" onload="init()">
<center>
<img src="/logo.png" width="400px"/>
<h3><i>Административный интерфейс</i></h3>

<div class="pager_body">
	<table>
		<tbody>
			<tr>
				<td>
					<table class="display_top">
						<tr>
							<td class="label">ЖУРНАЛ СТАНЦИИ</td>
							<td class="filter_btns">
								<div class="btn btn_filter btn_down" id="btn_all" onclick="setFilter(this)" command="all">✱</div>
								<div class="btn btn_filter" id="btn_msg" onclick="setFilter(this)" command="msg">✉</div>
								<!--div class="btn"></div>-->

							</td>
						</tr>
					</table>
				</td>
				<td class="indicators">
					<div class="led" id="act_led"></div
				</td>
			</tr>
			<tr valign="top">
				<td class="pager_display">
					<textarea id="text" class="log_view" rows="30" name="txdata" readonly></textarea>
				</td><td>
					<table>
						<tr><td class="meter_box">
							<img class="power_icon pi_left" id="batt" src="/batt.svg">
							<img class="power_icon pi_right" id="ac" src="/ac.svg">
							<div class="power_icon pi_left pi_overlay"></div>
							<div class="power_icon pi_right pi_overlay"></div>
							<canvas id="voltmeter" class="meter_panel" />
						</td></tr>	
						<tr class="spacer">
							<td align="right" class="label_small">ПИТАНИЕ</td>
						</tr>
						<tr>
							<td class="txstate_btns">
								<!--<div class="btn btn_txstate btgreen" id="btn_txon" onclick="setTxState(this)" command="1">⸺</div>
								<div class="btn btn_txstate btblue" id="btn_txauto" onclick="setTxState(this)" command="auto">⸺</div>
								<div class="btn btn_txstate btred" id="btn_txoff" onclick="setTxState(this)" command="0">⸺</div>-->
								<table><tr>
								<td class="btn btn_txstate btgreen" id="btn_txon" onclick="setTxState(this)" command="1">⸺</td>
								<td class="btn btn_txstate btblue" id="btn_txauto" onclick="setTxState(this)" command="auto">⸺</td>
								<td class="btn btn_txstate btred" id="btn_txoff" onclick="setTxState(this)" command="0">⸺</td>
								</tr></table>
							</td>
						</tr>						
						<tr>
							<td class="meter_box">
									<div class="meter_panel" id="txstate"></div>
									<img class="power_icon pi_left" id="txon" src="/txon.svg"/>
									<img class="power_icon pi_center" id="txauto" src="/txauto.svg"/>
									<img class="power_icon pi_right" id="txoff" src="/txoff.svg"/>
									<div class="power_icon pi_left pi_overlay"></div>
									<div class="power_icon pi_right pi_overlay"></div>
									<div class="power_icon pi_center pi_overlay"></div>
							</td>
						</tr>

						<tr class="spacer">
							<td align="right" class="label_small">ПЕРЕДАТЧИК</td>
						</tr>
						
						<tr>
							<td class="btn btn_station_cfg w150" id="btn_silent_restart" onclick="btnSilentRestartClick()">ТИХИЙ РЕСТАРТ</td>
						</tr>

						<tr>
							<td class="table_glued">
								<table class="table_glued"><tr>
									<td class="btn btn_station_cfg" id="btn_clrq" onmousedown="clrBtnDown(this)" onmouseup="clrBtnUp(this)" onclick="clrqBtnClick()">СБРОС<br />ОЧЕРЕДИ</td>
									<td id="display_qsize" class="display_small">-</td>
								</tr></table>
							</td>
						</tr>
						
						<tr>
							<td class="table_glued">
								<table class="table_glued mb"><tr>
									<td class="btn btn_station_cfg" id="btn_clra" onmousedown="clrBtnDown(this)" onmouseup="clrBtnUp(this)" onclick="clraBtnClick()">СБРОС<br />АРХИВА</td>
									<td id="display_asize" class="display_small">-</td>
								</tr></table>
							</td>
						</tr>

						<tr class="spacer">
							<td align="right" class="label_small">СТАНЦИЯ</td>
						</tr>
					</table>
				</td>
	</tr>
	</tbody></table>
</form>
</center>
</body>
</html>
