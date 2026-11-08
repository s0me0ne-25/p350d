// p350d admin page (/admin): live station status and controls.

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
	if (debug_simulateNetFail) throw new Error("Simulated network failure");
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

// A request that is not completed within this time is aborted
const AJAX_TIMEOUT_MS = 5000;
// Pause before the next request after a failed one
const AJAX_RETRY_MS = 3000;

// Called exactly once per failed request: timeout, network error, bad reply
// or an exception while processing it. Keeps a single polling chain alive.
function ajaxFailed(errTimer) {
	clearTimeout(errTimer);
	led.style.backgroundColor = "#F03E3E";
	setTimeout(function() { updateLog(true); }, AJAX_RETRY_MS);
}

function updateLog(scrollAnyway) {
	log = document.getElementById("text");
	led = document.getElementById("act_led");

	// Abort, not just abandon, a request that takes too long: a late reply
	// must not start a second polling chain next to the retry one.
	var controller = new AbortController();
	var errTimer = setTimeout(function() { controller.abort(); }, AJAX_TIMEOUT_MS);
	
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
	
	fetch(cmd, { signal: controller.signal })
		.then(response => response.json())
		.then(result => processAjaxResponse(result, scrollAnyway, errTimer))
		.catch(() => ajaxFailed(errTimer));
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
