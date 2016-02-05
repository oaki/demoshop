var logEnabled = true, mox = parent.mox, moxiecode = parent.moxiecode, DevKit = moxiecode.manager.DevKit;
var startTime;

function init() {
	var logRows = DevKit._log, i, t = DevKit._type;

	for (i=0; i<logRows.length; i++)
		log(logRows[i]);

	DevKit._log = [];

	DevKit._iframe = window;

	get('envframe').src = '../../../../stream/' + mox.defaultDoc + '?cmd=' + t + '.viewServerInfo';
	get('envnewwin').href = '../../../../stream/' + mox.defaultDoc + '?cmd=' + t + '.viewServerInfo';
	get('envdownload').href = '../../../../stream/' + mox.defaultDoc + '?cmd=' + t + '.downloadServerInfo';

	get('log_view_error').src = '../../../../stream/' + mox.defaultDoc + '?cmd=' + t + '.viewLog&level=error#end';
	get('errorlognewwin').href = '../../../../stream/' + mox.defaultDoc + '?cmd=' + t + '.viewLog&level=error#end';
	get('errorlogclear').href = '../../../../stream/' + mox.defaultDoc + '?cmd=' + t + '.clearLog&level=error#end';
	get('errorlogrefresh').href = 'javascript:doRefresh(\'../../../../stream/' + mox.defaultDoc + '?cmd=' + t + '.viewLog&level=error\',\'log_view_error\');';

	get('log_view_debug').src = '../../../../stream/' + mox.defaultDoc + '?cmd=' + t + '.viewLog&level=debug#end';
	get('debuglognewwin').href = '../../../../stream/' + mox.defaultDoc + '?cmd=' + t + '.viewLog&level=debug#end';
	get('debuglogclear').href = '../../../../stream/' + mox.defaultDoc + '?cmd=' + t + '.clearLog&level=debug#end';
	get('debuglogrefresh').href = 'javascript:doRefresh(\'../../../../stream/' + mox.defaultDoc + '?cmd=' + t + '.viewLog&level=debug\',\'log_view_debug\');';

	startTime = new Date().getTime();

	document.body.spellcheck = false;
}

function doRefresh(u, t) {
	window.open(u + '&rnd=' + new Date().getTime(), t);
}

function get(id) {
	return document.getElementById(id);
}

function toggleLog(s) {
	logEnabled = s;
}

function clearLog() {
	document.getElementById('log').value = '';
	startTime = new Date().getTime();
}

function log(a) {
	var d, l, n, m = Array.prototype.join.call(a, ', '), now = new Date().getTime();

	if (!logEnabled || !new RegExp(document.forms[0].logfilter.value, 'gi').test(m))
		return;

	if (!this.startTime)
		this.startTime = now;

	d = document;
	l = d.getElementById('log');
	//n = d.createElement('span');

	//l.appendChild(d.createTextNode('[' + (now - startTime) + '] ' + m + '\r\n'));
	l.value += '[' + (now - startTime) + '] ' + m + '\r\n';
	//n.innerHTML = '[' + (now - startTime) + '] ' + mox.String.xmlEncode(m).replace(/\n/g, '<br />').replace(/\t/g, '&nbsp;&nbsp;&nbsp;');

	//l.appendChild(n);
	l.scrollTop = l.scrollHeight;
}

function toggleFlip() {
	var d = parent.document;

	if (d.getElementById('devkit').className == 'devkitdown')
		d.getElementById('devkit').className = 'devkitup';
	else
		d.getElementById('devkit').className = 'devkitdown';
}

function renderSettings() {
	var se = document.getElementById('settings');
	var man = new moxiecode.manager.BaseManager();

	man.execRPC(DevKit._type + '.getConfig', {debug : true, path : '{0}'}, function(d) {
		var h = '', sn, sv, config = d.result;

		if (d.error) {
			se.innerHTML = d.error.errstr;
			return;
		}

		h += '<table border="0" cellpadding="0" cellspacing="0" class="data">';

		for (n in config)
			h += '<tr><td class="col1">' + mox.String.xmlEncode(n) + '</td><td><input type="text" value="' + mox.String.xmlEncode(config[n]) + '" /></td></tr>';

		h += '</table>';

		se.innerHTML = h;
	});
}

function renderLocalSettings(path) {
	var se = document.getElementById('localsettings');
	var man = new moxiecode.manager.BaseManager();

	man.execRPC(DevKit._type + '.getConfig', {debug : true, path : path}, function(d) {
		var h = '', sn, sv, config = d.result;

		if (d.error) {
			se.innerHTML = d.error.errstr;
			return;
		}

		h += '<table border="0" cellpadding="0" cellspacing="0" class="data">';

		for (n in config)
			h += '<tr><td class="col1">' + mox.String.xmlEncode(n) + '</td><td><input type="text" value="' + mox.String.xmlEncode(config[n]) + '" /></td></tr>';

		h += '</table>';

		se.innerHTML = h;
	});
}