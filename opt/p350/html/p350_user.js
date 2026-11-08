// p350d message form (/): send a page to the station.

function focusText() {
	document.getElementById('text').focus();
}

function hideStatus() {
	document.getElementById("status").className = "hide";
}

// Called from body onload, when the whole page is in place
function init() {
	focusText();
	setTimeout(hideStatus, 3000);
}

var alreadySubmit = false;
function checksubmit() {
	focusText();
	if (alreadySubmit) return false;

	var text = document.getElementById("text").value.trim();
	if (text == "") return false;

	var btn = document.getElementById("send_btn");
	btn.classList.add("btn_down")
	alreadySubmit = true;
}
