mox.loadClasses = function(cl) {
	var d = document, s, o = [], i, u;

	if (cl.length == 0)
		return;

	for (i=0; i<cl.length; i++) {
		if (!this.classes[cl[i]]) {
			this.classes[cl[i]] = 1;
			o.push(cl[i]);
		}
	}

	if (o.length == 0)
		return;

	u = this.baseURL + '/compressor.php?classes=' + o.join(',');

	// Synchronous AJAX load gzip JS file on IE since it has a gzip compression bug
	if (mox.isIE) {
		try {
			s = new ActiveXObject("Microsoft.XMLHTTP");
		} catch (ex) {
			s = new ActiveXObject("Msxml2.XMLHTTP");
		}

		s.open("GET", u.replace(/%2C/g, ','), false);
		s.send(null);

		this.scriptData = s.responseText;
		document.write('<script type="text/javascript">eval(mox.scriptData);mox.scriptData=null;</script>');
	} else {
		s = d.createElement('script');
		s.setAttribute('type', 'text/javascript');
		s.setAttribute('src', u);
		d.getElementsByTagName('head')[0].appendChild(s);
	}
};