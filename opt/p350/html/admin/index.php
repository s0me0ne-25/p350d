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
<style type="text/css">
	body {
		margin: auto;
		max-width:800px;
	}
	
	.pager_body {
		padding: 10px 7px 30px 20px;
		border-radius: 20px;
		border-style: outset;
		border-width: 10px;
		border-color: rgba(80, 80, 80, .6);
		background-color: #0f0f0f;
		background-image: url(/plastic.jpg);
	}
	
	.pager_display {
		width: 500px;
		display: block;
		border-radius: 14px;
		padding: 15px 4px 15px 15px;
		margin: 4px 20px 0 0;
		background-color: #00ff88;
		border-style: groove;
		border-width: 6px;
		border-color: rgba(80, 80, 80, .6);
		outline: none;
		position: relative;
	}
	
	.display_small {
		border-radius: 4px;
		background-color: #00ff88;
		border-style: groove;
		border-width: 3px;
		border-color: rgba(80, 80, 80, .6);
		outline: none;
		position: relative;
		width: 29px;
		padding: 9px 0 9px 9px;
		margin-left: 5px;
		display: inline-block;
		font: 12pt bold monospace;
	}
	
	.display_top {
		width: 100%;
		padding: 0 15px 0 0;
		margin-bottom: -6px;
	}
	
	.label {
		font: italic 15pt sans-serif;
		color: rgba(213, 179, 0, .8);
		vertical-align: bottom;
	}
	
	.indicators {
		text-align: right;
		vertical-align: middle;
		padding: 0 10px 0 0;
	}
	
	tr.spacer {
		margin-bottom: 10px;
		display: grid;
	}
	
	.led {
		display: inline-block;
		border-style: groove;
		border-width: 5px;
		border-color: rgba(30, 30, 30, .8);
		border-radius: 9px;
		width: 20px;
		height: 7px;
		background-color: #505050;
	}
	
	.btn {
		text-align: center;
		display: inline-block;
		background-color: #0f0f0f;
		color: rgba(213, 179, 0, .8);
		border-style: groove;
		border-color: rgba(80, 80, 80, .6);
		outline: none;
		-webkit-touch-callout: none;
		-webkit-user-select: none;
		-khtml-user-select: none;
		-moz-user-select: none;
		-ms-user-select: none;
		user-select: none;
	}
	
	.btn_station_cfg {
		font: 10pt sans-serif;
		color: rgba(213, 179, 0, .8);
		vertical-align: middle;
		border-width: 3px;
		border-radius: 4px;		
		padding:5px 0;		
		width: 100px;
		margin-top:2px;
		margin-bottom:2px;
	}
	
	.w150 {
		width: 150px;
	}
	
	.mb {
		margin-bottom: 4px;
	}
	
	#btn_silent_restart {
		margin-top:4px;
	}
		
	.btn_filter {
		width:32px;
		height: 32px;
		font: 21px sans-serif;
		border-width:3px;
		border-radius: 4px;		
	}
	
	.btn_txstate {
		width: 44px;
		height: 10px;
		font: bold 9px sans-serif;
		border-width: 3px;
		border-radius: 4px;
	}
		
	.btgreen {
		color: #30B32D !important;
	}

	.btred {
		color: #F03E3E !important;
	}

	.btblue {
		color: #1f2fff !important;
	}
	
	.btn_down, .btn_txstate_down {
		border-style: inset !important;
	}
	
	.btn_txstate_down {
		margin-bottom: -3px;
		margin-right: -1px;
	}
	
	.filter_btns {
		vertical-align: bottom;
		text-align:right;
	}
	
	.txstate_btns {
		text-align: center;
		padding: 0 2px 0 0;
	}
		
	.meter_box {
		position: relative;
	}
	
	.power_icon {
		position: absolute;
		bottom: 18px;
		width: 30px;
		height: 30px;
		display:inline-block;
		padding: 1px;
	}
	
	.pi_right {
		right:14px;
	}

	.pi_left {
		left:14px;
	}
	
	.pi_center {
		bottom: 2px;
		left: 50%;
		transform: translate(-50%, -50%);
	}
	
	.pi_overlay {
		background-color: rgba(15, 15, 15, .95);
		z-index: 1;
	}
	
	.meter_panel {
		border-style: inset;
		border-width:4px;
		border-color: rgba(60, 60, 60, .6);
		border-radius: 8px;
		background-color: rgba(0, 0, 0, .6);		
		width: 150px;
		display: inline-block;
	}
	
	#voltmeter {
		height: 120px;
	}
	
	#txstate {
		height:47px;
	}
	
	#text {
		width:500px;
		display: block;
		font: 10pt monospace;
		resize: none;
		background-color: rgba(0,0,0,0);
		border-style: none;
		outline: none;
	}
	
	.label_small {
		font: bold italic 10pt sans-serif;
		color: rgba(213, 179, 0, .8);
		padding: 0 10px 6px 0;
		border-width: 0 0 3px 0;
		border-style: groove;
		border-color: rgba(60, 60, 60, .6);
	}
	
	textarea::-webkit-scrollbar {
		width: 16px;
		background-color: rgba(0, 0, 0, 0);
	}

	textarea::-webkit-scrollbar-thumb {
		background-color: rgba(0,25,10, .65);
	}
	
	.table_glued {
		padding: 0;
		border-spacing: 0;
		width: 100%;
	}
	
	h3 		{ font-family: sans-serif; font-size: 14px; color:#800000; }
</style>

<script>
	var gauge;
	var logCommandChanged = false;
	var logCommand = 'all';
	var txStateChanged = false;
	var txStateValue = '';
	var pendingClrq = false;
	var pendingClra = false;
	var silentRestart = true;
	var silentRestartChanged = false;
	
	function init() {
		updateLog(true);
		
		var opts = {
		  angle: 0.22, // The span of the gauge arc
		  lineWidth: 0.25, // The line thickness
		  radiusScale: 0.9, // Relative radius
		  pointer: {
			length: 0.6, // // Relative to gauge radius
			strokeWidth: 0.02, // The thickness
			color: '#1f2fff' // Fill color
		  },
		  limitMax: false,     // If false, max value increases automatically if value > maxValue
		  limitMin: false,     // If true, the min value of the gauge will be fixed
		  colorStart: '#6FADCF',   // Colors
		  colorStop: '#8FC0DA',    // just experiment with them
		  strokeColor: '#000000',  // to see which ones work best for you
		  generateGradient: true,
		  highDpiSupport: true,     // High resolution support
		  // renderTicks is Optional
		  renderTicks: {
			divisions: 5,
			divWidth: 3.3,
			divLength: 0.64,
			divColor: '#333333',
			subDivisions: 5,
			subLength: 0.32,
			subWidth: 1,
			subColor: '#333333'
		  },
		  staticLabels: {
			  font: "10px monospace",  // Specifies font
			  labels: [10.2,11,12,13,14,14.8],  // Print labels at these values
			  color: "#ffffff",  // Optional: Label text color
			  fractionDigits: 0  // Optional: Numerical precision. 0=round off.
},
		  staticZones: [
		   {strokeStyle: "#F03E3E", min: 10, max: 11.2, height: 1.0}, // Red
		   {strokeStyle: "#FFDD00", min: 11.2, max: 12.0, height: 1.0}, // Yellow
		   {strokeStyle: "#30B32D", min: 12.0, max: 14.5, height: 1.0}, // Green
		   {strokeStyle: "#F03E3E", min: 14.5, max: 15, height: 1.0},  // Red

		   // Battery cutoff zone
		   // Fill
   		   {strokeStyle: "#F03E3E", min: 10.8, max: 11.2, height: 1.2},
		   // Border
   		   {strokeStyle: "#333333", min: 10.76, max: 10.84, height: 1.2},
   		   {strokeStyle: "#333333", min: 11.16, max: 11.24, height: 1.2},

		  ],
		};
		
		var target = document.getElementById('voltmeter'); // your canvas element
		gauge = new Gauge(target).setOptions(opts); // create sexy gauge!
		gauge.maxValue = 15.0; // set max gauge value
		gauge.setMinValue(10.0);  // Prefer setter over gauge.minValue = 0
		gauge.animationSpeed = 10; // set animation speed (32 is default value)
		gauge.set(10); // set actual value
	}
	
	function setLog(text, scrollAnyway) {
		log = document.getElementById("text");
		log.value = text;
		scrollFollows = scrollAnyway || (log.scrollHeight - (log.scrollTop + log.clientHeight) < 200);
		
		if (scrollFollows) {
			log.scrollTop = log.scrollHeight - log.clientHeight;
		}
	}
	
	function updateLog() {
		updateLog(false);
	}
	
	debug_simulateNetFail = false;
	function processAjaxResponse(jps, scrollAnyway, errTimer) {
		if (debug_simulateNetFail) return;
		// Voltage
		if (jps.voltage >= 10 && jps.voltage <= 16) {  //Filter out obviously incorrect readings
			gauge.set(jps.voltage)
		}
		
		// Log
		setLog(jps.log.join("\n"), scrollAnyway)
		
		// Power state indicator
		const STATE_AC = 21;
		const STATE_BATT = 22;
		
		var ac = document.getElementById("ac");
		var batt = document.getElementById("batt");
		
		ac.style.zIndex = +(jps.pwrstate == STATE_AC)<<1;
		batt.style.zIndex = +(jps.pwrstate == STATE_BATT)<<1;
		
		// Transmitter state
		var txon = document.getElementById("txon");
		var txauto = document.getElementById("txauto");
		var txoff = document.getElementById("txoff");
		
		var btn_txon = document.getElementById("btn_txon");
		var btn_txauto = document.getElementById("btn_txauto");
		var btn_txoff = document.getElementById("btn_txoff");
		
		txauto.style.zIndex = +(jps.tx_isauto)<<1;
		txon.style.zIndex = +(jps.tx_isenabled)<<1;
		txoff.style.zIndex = +(!jps.tx_isenabled)<<1;

		if (!txStateChanged) {
			if (jps.tx_isauto) {
				btn_txauto.classList.add("btn_txstate_down");
				btn_txon.classList.remove("btn_txstate_down");
				btn_txoff.classList.remove("btn_txstate_down");
				txStateValue = 'auto';
			} else {
				btn_txauto.classList.remove("btn_txstate_down");
				if (jps.tx_isenabled) {
					btn_txon.classList.add("btn_txstate_down");
					btn_txoff.classList.remove("btn_txstate_down");
					txStateValue = '1';
				} else {
					btn_txon.classList.remove("btn_txstate_down");
					btn_txoff.classList.add("btn_txstate_down");
					txStateValue = '0';
				}
			}
		}
		
		// Queues
		var display_qsize = document.getElementById("display_qsize");
		var display_asize = document.getElementById("display_asize");
		
		display_qsize.innerHTML = jps.qsize;
		display_asize.innerHTML = jps.asize;
		
		// Silent_restart
		if (!silentRestartChanged) {
			silentRestart = jps.silent_restart;
			processSilentRestart();
		}
		
		// Start ajax over
		clearTimeout(errTimer);
		led.style.backgroundColor = "#30B32D";
		setTimeout(updateLog, 200);
	}
	
	function processSilentRestart() {
		var btn_silent_restart = document.getElementById("btn_silent_restart");
		if (silentRestart) {
			btn_silent_restart.classList.add("btn_down");
		} else {
			btn_silent_restart.classList.remove("btn_down");
		}
	}
	
	function ajaxTimeout() {
		led.style.backgroundColor = "#F03E3E";
		setTimeout(updateLog(true), 3000);
	}
	
	function updateLog(scrollAnyway) {
		log = document.getElementById("text");
		led = document.getElementById("act_led");
	
		var errTimer = setTimeout(ajaxTimeout, 5000);
		
		var cmd = '/admin?ajax=' + logCommand;
		if (txStateChanged) {
			cmd += '&txstate=' + txStateValue;
			txStateChanged = false;
		}
		if (logCommandChanged) {
			scrollAnyway = true;
			logCommandChanged = false;
		}
		if (silentRestartChanged) {
			cmd += '&silent_restart=' + (silentRestart ? 1 : 0);
			silentRestartChanged = false;
		}
		if (pendingClra) {
			cmd += '&clra';
			pendingClra = false;
		}
		if (pendingClrq) {
			cmd += '&clrq';
			pendingClrq = false;
		}
		
		fetch(cmd)
			.then(response => response.json())
			.then(result => processAjaxResponse(result, scrollAnyway, errTimer));
	};
	
	function setFilter(btn) {
		btns = document.getElementsByClassName("btn_filter");
		for (i = 0; i < btns.length; i++) {
			if (btns[i] == btn) {
				btns[i].classList.add("btn_down");
			} else {
				btns[i].classList.remove("btn_down");
			}
		}
		
		if (logCommand != btn.getAttribute('command')) {
			logCommand = btn.getAttribute('command');
			logCommandChanged = true;
		}
	}
	
	function setTxState(btn) {
		btns = document.getElementsByClassName("btn_txstate");
		for (i = 0; i < btns.length; i++) {
			if (btns[i] == btn) {
				btns[i].classList.add("btn_txstate_down");
			} else {
				btns[i].classList.remove("btn_txstate_down");
			}
		}
		
		if (txStateValue != btn.getAttribute('command')) {
			txStateValue = btn.getAttribute('command');
			txStateChanged = true;
		}		
	}
	
	function clrBtnDown(btn) {
		btn.classList.add("btn_down");
	}
	
	function clrBtnUp(btn) {
		btn.classList.remove("btn_down");
	}
	
	function clraBtnClick() {
		pendingClra = true;
	}
	
	function clrqBtnClick() {
		pendingClrq = true;
	}
	
	function btnSilentRestartClick() {
		silentRestartChanged = true;
		silentRestart = !silentRestart;
		processSilentRestart();
	}
</script>
</head>
<body onload="init()">
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
					<textarea id="text" rows="30" name="txdata" readonly></textarea>
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
