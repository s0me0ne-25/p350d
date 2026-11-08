<?php
	require_once __DIR__ . '/../inc_common.php';

	if (isset($_GET['ajax'])) {
		
		// Commands from the page controls. pagerd's replies are not needed.
		if (isset($_GET['txstate']) && in_array($_GET['txstate'], ['0', '1', 'auto'], true)) {
			pagerdCommand('txenable ' . $_GET['txstate']);
		}
		if (isset($_GET['clrq'])) {
			pagerdCommand('clrq');
		}
		if (isset($_GET['clra'])) {
			pagerdCommand('clra');
		}
		if (isset($_GET['silent_restart']) && in_array($_GET['silent_restart'], ['0', '1'], true)) {
			pagerdCommand('silent-restart ' . $_GET['silent_restart']);
		}
		
		// First get power data and parse it. If pagerd does not answer,
		// the reply carries empty values, as it always did, plus the log.
		$response = json_decode((string)pagerdCommand('jps'), true);
		if (!is_array($response)) {
			$response = [];
		}
		
		// Get persistent config
		$pconf = json_decode((string)pagerdCommand('get-pconf'), true);
		if (!is_array($pconf)) {
			$pconf = [];
		}
		
		// Add pconf data to response
		$response['silent_restart'] = $pconf['silent_restart'] ?? null;

		$qsize = 0;
		if (is_countable($pconf['qmsg_mem'] ?? null)) {
			$qsize = count($pconf['qmsg_mem']) - 1;	// Subtracting 1 to exclude digest
		}
		$response['qsize'] = $qsize;
		
		$asize = 0;
		if (is_countable($pconf['emerman']['archive'] ?? null)) {
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

	$pageClass = 'page_admin';
	$pageScripts = ['/gauge.min.js', '/admin/p350_admin.js'];
	$pageSubtitle = 'Административный интерфейс';
	require_once __DIR__ . '/../inc_header.php';
?>

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
					<div class="led" id="act_led"></div>
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
							<canvas id="voltmeter" class="meter_panel"></canvas>
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
</div>
<?php require_once __DIR__ . '/../inc_footer.php'; ?>
