var mcFileManager = {
	callSettings : null,
	args : null,
	baseURL : null,
	settings : {
		document_base_url : null,
		relative_urls : false,
		remove_script_host : false,
		use_url_path : true,
		path : null,
		rootpath : null,
		remember_last_path : 'auto',
		custom_data : null,
		target_elements : '',
		target_form : '',
		handle : 'file'
	},

	init : function(s) {
		this.settings = this.merge(s, this.settings);
	},

	preInit : function() {
		var baseDir = document.location.href;

		if (baseDir.indexOf('?') != -1)
			baseDir = baseDir.substring(0, baseDir.indexOf('?'));

		baseDir = baseDir.substring(0, baseDir.lastIndexOf('/') + 1);

		this.settings.document_base_url = unescape(baseDir);
	},

	merge : function(s, a) {
		var n, o = {};

		for (n in a)
			o[n] = a[n];

		if (s) {
			for (n in s)
				o[n] = s[n];
		}

		return o;
	},

	filebrowserCallBack : function(field_name, url, type, win, asked) {
		var s, i, hl, fo;

		function getParam(n, d) {
			if (tinyMCE.getParam)
				return tinyMCE.getParam(n, d);
			else
				return tinyMCE.activeEditor.getParam(n, d);
		};

		// Is image manager included, ask it first
		if (window.mcImageManager && !asked) {
			hl = mcImageManager.settings.handle;

			hl = hl.split(',');
			for (i=0; i<hl.length; i++) {
				if (type == hl[i])
					fo = 1;
			}

			if (fo && mcImageManager.filebrowserCallBack(field_name, url, type, win, 1))
				return;
		}

		// Save away
		this.field = field_name;
		this.callerWindow = win;
		this.inTinyMCE = true;

		// Setup instance specific settings
		s = {
			path : getParam("filemanager_path"),
			rootpath : getParam("filemanager_rootpath"),
			remember_last_path : getParam("filemanager_remember_last_path"),
			custom_data : getParam("filemanager_custom_data"),
			document_base_url : getParam("document_base_url"),
			insert_filter : getParam("filemanager_insert_filter"),
			is_callback : true
		};

		// Open browser
		this.open(0, field_name, url, function(url, info) {
			mcFileManager.insertFileToTinyMCE(url, info);
		}, s);

		return true;
	},

	insertFileToTinyMCE : function(url, info) {
		var url, f = this.callerWindow.document.forms[0], names = ['alt', 'title', 'linktitle'], i;

		// Handle old and new style
		if (typeof(TinyMCE_convertURL) != "undefined")
			url = TinyMCE_convertURL(url, null, true);
		else if (tinyMCE.convertURL)
			url = tinyMCE.convertURL(url, null, true);
		else
			url = tinyMCE.activeEditor.convertURL(url, null, true);

		// Set URL
		f.elements[this.field].value = url;

		// Set alt and title info
		if (info.custom && info.custom.description) {
			for (i=0; i<names.length; i++) {
				if (f.elements[names[i]])
					f.elements[names[i]].value = info.custom.description;
			}
		}

		// Try to fire the onchange event
		try {
			f.elements[this.field].onchange();
		} catch (e) {
			// Skip it
		}
	},

	selectFile : function(d) {
		this.insertFileToForm(d);
	},

	insertFileToForm : function(d) {
		var t = this, s = t.merge(t.callSettings, t.settings), elements, i, elm, base, f, url;

		if (s.insert_filter)
			s.insert_filter(d);

		url = d.url;

		// Convert to relative
		if (s.relative_urls) {
			url = this.parseURL(url);
			base = this.parseURL(s.document_base_url);
			url = this.absToRel(base.path, url.path) + (url.query ? "?" + url.query : '') + (url.hash ? "#" + url.hash : '');
		}

		// Remove proto and host
		if (s.remove_script_host)
			url = this.removeHost(url);

		if (s.insert_callback) {
			s.insert_callback(url, d);
			return;
		}

		// Set URL to all form fields
		if (s.target_elements) {
			elements = s.target_elements.split(',');

			for (i=0; i<elements.length; i++) {
				f = document.forms[s.target_form];

				if (f)
					elm = f.elements[elements[i]];
				else
					elm = document.getElementById(elements[i]);

				if (!elm)
					continue;

				if (elm && typeof elm != "undefined")
					elm.value = url;

				// Try to fire the onchange event
				try {
					elm.onchange();
				} catch (e) {
					// Skip it
				}
			}
		}
	},

	removeHost : function(url) {
		return url.replace(/^(\w+:\/\/)([^\/]+)/, '');
	},

	parseURL : function(u, b) {
		var l = document.location;

		// Force absolute
		if (u.indexOf('://') == -1) {
			if (!b)
				b = l.pathname;

			if (u.charAt(0) != '/')
				u = this.relToAbs(b.replace(/\/[^\/]+$/, ''), u);

			u = l.protocol + '//' + l.host + (l.port ? ':' + l.port : '') + u;
		}

		u = /^((\w+):\/\/)?((\w+):?(\w+)?@)?([^\/\?:]+):?(\d+)?(\/?[^\?#]+)?\??([^#]+)?#?(\w*)/.exec(u);

		return {
			protocol : u[2],
			username : u[3],
			password : u[4],
			host : u[5],
			port : u[6],
			domain : u[7],
			path : u[8],
			query : u[9],
			hash : u[10]
		};
	},

	absToRel : function(base_url, url_to_relative) {
		var strTok1, strTok2, breakPoint = 0, outputString = "", i;

		// Crop away last path part
		base_url = base_url.substring(0, base_url.lastIndexOf('/'));
		strTok1 = base_url.split('/');
		strTok2 = url_to_relative.split('/');

		if (strTok1.length >= strTok2.length) {
			for (i=0; i<strTok1.length; i++) {
				if (i >= strTok2.length || strTok1[i] != strTok2[i]) {
					breakPoint = i + 1;
					break;
				}
			}
		}

		if (strTok1.length < strTok2.length) {
			for (i=0; i<strTok2.length; i++) {
				if (i >= strTok1.length || strTok1[i] != strTok2[i]) {
					breakPoint = i + 1;
					break;
				}
			}
		}

		if (breakPoint == 1)
			return url_to_relative;

		for (i=0; i<(strTok1.length-(breakPoint-1)); i++)
			outputString += "../";

		for (i=breakPoint-1; i<strTok2.length; i++) {
			if (i != (breakPoint-1))
				outputString += "/" + strTok2[i];
			else
				outputString += strTok2[i];
		}

		return outputString;
	},

	relToAbs : function(base_path, rel_path) {
		var end = '', i, newBaseURLParts = [], newRelURLParts = [], numBack, len, absPath;

		rel_path = rel_path.replace(/[?#].*$/, function(a) {
			end = a;

			return '';
		});

		// Split parts
		baseURLParts = base_path.split('/');
		relURLParts = rel_path.split('/');

		// Remove empty chunks
		for (i=baseURLParts.length-1; i>=0; i--) {
			if (baseURLParts[i].length == 0)
				continue;

			newBaseURLParts[newBaseURLParts.length] = baseURLParts[i];
		}

		baseURLParts = newBaseURLParts.reverse();

		// Merge relURLParts chunks
		numBack = 0;
		for (i=relURLParts.length-1; i>=0; i--) {
			if (relURLParts[i].length == 0 || relURLParts[i] == ".")
				continue;

			if (relURLParts[i] == '..') {
				numBack++;
				continue;
			}

			if (numBack > 0) {
				numBack--;
				continue;
			}

			newRelURLParts[newRelURLParts.length] = relURLParts[i];
		}

		relURLParts = newRelURLParts.reverse();

		// Remove end from absolute path
		len = baseURLParts.length-numBack;
		absPath = (len <= 0 ? "" : "/") + baseURLParts.slice(0, len).join('/') + "/" + relURLParts.join('/');

		return absPath + end;
	},

	getArgs : function() {
		return this.args;
	},

	/**#@-*/

	openInIframe : function(iframe_id, form_name, element_names, file_url, js, s) {
		this.settings.iframe = iframe_id;
		this.open(form_name, element_names, file_url, js, s);
	},

	open : function(form_name, element_names, file_url, js, s) {
		var x, y, w, h, win, val, pa = '';

		s = this.merge(s, this.settings);

		w = 800;
		h = 500;
		x = parseInt(screen.width / 2.0) - (w / 2.0);
		y = parseInt(screen.height / 2.0) - (h / 2.0);

		if (document.attachEvent) {
			// Pesky MSIE + XP SP2
			w += 15;
			h += 35;
		}

		this.args = {
			path : s.path,
			rootpath : s.rootpath,
			custom_data : s.custom_data,
			remember_last_path : s.remember_last_path
		};

		if (((form_name && element_names) || file_url) && s.use_url_path) {
			val = file_url ? file_url : document.forms[form_name].elements[element_names.split(',')[0]].value;

			if (val)
				this.args.url = this.parseURL(val, this.parseURL(s.document_base_url).path).path;
		}

		s.target_form = form_name;
		s.target_elements = element_names;
		s.insert_callback = typeof(js) == 'function' ? js : eval(js);

		this.callSettings = s;
		if (window.mcImageManager)
			mcImageManager.callSettings = s;

		if (s.session_id)
			pa += '&sessionid=' + s.session_id;

		if (s.custom_data)
			pa += '&custom_data=' + escape(s.custom_data);

		if (s.iframe) {
			win = document.getElementById(s.iframe);
			win.contentWindow.document.location = this.getBaseURL() + '/../index.php?type=fm';
			return;
		} else if (window.tinymce && tinyMCE.activeEditor)
			tinyMCE.activeEditor.windowManager.open({url : this.getBaseURL() + '/../index.php?type=fm' + pa, width : w, height : h, resizable : 1, scrollbars : 1, statusbar : 0, inline : 1});
		else
			win = window.open(this.getBaseURL() + '/../index.php?type=fm' + pa, 'mcFileManagerWin', 'left=' + x + ',top=' + y + ',width=' + w + ',height=' + h + ',scrollbars=yes,resizable=yes,statusbar=no');

		try {
			win.focus();
		} catch (ex) {
		}
	},

	getBaseURL : function() {
		if (this.baseURL)
			return this.baseURL;

		return this.baseURL = this.findBaseURL(/mcfilemanager\.js/g);
	},

	findBaseURL : function(k) {
		var o, d = document, cp = [];

		function get(nl) {
			var i, n;

			for (i=0; i<nl.length; i++) {
				n = nl[i];

				cp.push(n);

				if (n.src && k.test(n.src))
					return n.src.substring(0, n.src.lastIndexOf('/'));
			}
		};

		o = d.documentElement;
		if (o && (o = get(o.getElementsByTagName('script'))))
			return o;

		o = d.getElementsByTagName('script');
		if (o && (o = get(o)))
			return o;

		o = d.getElementsByTagName('head')[0];
		if (o && (o = get(o.getElementsByTagName('script'))))
			return o;

		return null;
	}
};

mcFileManager.preInit();
