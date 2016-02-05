/**
 * $Id: TinyMCE_DOMUtils.class.js 91 2006-10-02 14:53:22Z spocke $
 *
 * @author Moxiecode
 * @copyright Copyright © 2004-2006, Moxiecode Systems AB, All rights reserved.
 */

mox.require([
	'mox.Event',
	'mox.DOM',
	'mox.List',
	'mox.String',
	'mox.dom.Layer',
	'mox.lang.LangPack',
	'mox.geom.Rect',
	'mox.util.Dispatcher'
], function() {
	// Shorten class names
	var Event = mox.Event;
	var DOM = mox.DOM;
	var List = mox.List;
	var String = mox.String;
	var Layer = mox.dom.Layer;
	var LangPack = mox.lang.LangPack;
	var Dispatcher = mox.util.Dispatcher;

	/**#@+
	 * @member mox.ui.WindowManager
	 * @abstract
	 */
	mox.create('static mox.ui.WindowManager', {
		windows : [],
		lastWin : null,
		alertWin : null,
		progressTheme : null,
		zindex : 0,
		settings : null,
		onopen : null,
		onclose : null,

		/**
		 * Initializes the WindowManager with global settings.
		 *
		 * @param {Object} s Name/Value object with settings.
		 */
		init : function(s) {
			var n;

			// Default settings
			this.settings = {
			};

			for (n in s)
				this.settings[n] = s[n];
		},

		/**
		 * Opens a new window.
		 *
		 * @param {string} u URL of page to open.
		 * @param {string} id Name/id of new window.
		 * @param {string} f Features/options for the new window.
		 * @param {string} args Window user arguments to be extrated later by window page.
		 * @return {mox.ui.Window} New window instance.
		 */
		open : function(u, id, f, args) {
			var w = this.get(id);

			// Close old window
			if (w)
				w.close();

			// Add new window
			this.lastWin = w = new mox.ui.Window(id, this._splitNameValue(f));

			if (this.onbeforeopen)
				this.onbeforeopen(w);

			w.setArgs(args);

			// Open it
			w.open();
			w.setURL(u);

			this.remove(w);
			this.windows.push(w);

			if (this.onopen)
				this.onopen(w);

			return w;
		},

		/**
		 * Opens a error message dialog with the specified message string.
		 *
		 * @param {string} msg Message dialog string to display.
		 * @param {string/object} f Name/Value array of window features to pass to dialog.
		 * @param {function} cb Optional callback to execute when the user presses ok.
		 */
		error : function(msg, ti, f, cb) {
			this.alert('error', ti, msg, f, cb);
		},

		/**
		 * Opens a warning message dialog with the specified message string.
		 *
		 * @param {string} msg Message dialog string to display.
		 * @param {string/object} f Name/Value array of window features to pass to dialog.
		 * @param {function} cb Optional callback to execute when the user presses ok.
		 */
		warn : function(msg, ti, f, cb) {
			this.alert('warning', ti, msg, f, cb);
		},

		/**
		 * Opens a info message dialog with the specified message string.
		 *
		 * @param {string} msg Message dialog string to display.
		 * @param {string/object} f Name/Value array of window features to pass to dialog.
		 * @param {function} cb Optional callback to execute when the user presses ok.
		 */
		info : function(msg, ti, f, cb) {
			this.alert('info', ti, msg, f, cb);
		},

		/**
		 * Opens a alert message dialog with the specified message string.
		 *
		 * @param {string} l Error level, fatal, error, warning or info.
		 * @param {string} msg Message dialog string to display.
		 * @param {string/object} f Name/Value array of window features to pass to dialog.
		 * @param {function} cb Optional callback to execute when the user presses ok.
		 */
		alert : function(l, ti, msg, f, cb) {
			var id = 'alert';

			this.hideProgress();

			f = this._splitNameValue(f);

			f = !f ? {} : f;

			f.blockevents = true;
			f.type = 'alert';
			f.bigicon = l;
			f.onclose = cb;
			f.width = 400;
			f.height = 130;
			f.resizable = false;

			w = this.open(null, id, f);

			w.setTitle(ti);
			w.setContent('<div class="message">' + msg +'</div><a href="javascript:mox.ui.WindowManager.get(\'' + id + '\').close();" class="action ok">{#common.alert_ok}</a>');
		},
	
		/**
		 * Opens a confirm message dialog and executes
		 * the specified callback if the user presses yes.
		 *
		 * @param {string} msg Message to display in confirm dialog.
		 * @param {function} yes_cb Yes callback, to be executed when user presses yes.
		 * @param {function} no_cb Optional no callback, to be executed when user presses no.
		 */
		confirm : function(msg, yes_cb, ti, f, no_cb) {
			var id = 'confirm';

			this.hideProgress();

			f = this._splitNameValue(f);

			f = !f ? {} : f;

			f.blockevents = true;
			f.type = 'confirm';
			f.bigicon = 'ask';
			f.width = 400;
			f.height = 130;
			f.resizable = false;

			w = this.open(null, id, f);

			w.onconfirm = yes_cb;
			w.oncancel = no_cb;
			w.setTitle(ti);
			w.setContent('<div class="message">' + msg +'</div><a href="javascript:mox.ui.WindowManager.get(\'' + id + '\').ok();" class="action ok">{#common.confirm_yes}</a><a href="javascript:mox.ui.WindowManager.get(\'' + id + '\').close();" class="action cancel">{#common.confirm_no}</a>');
		},

		status : function(msg, status, type, ti, f, cb) {
			var id = 'status';

			if (!msg || !status)
				return true;

			this.hideProgress();

			f = this._splitNameValue(f);

			type = !type ? 'error' : type;
			f = !f ? {} : f;

			f.blockevents = true;
			f.type = 'status';
			f.bigicon = type;
			f.onclose = cb;
			f.width = 400;
			f.height = 300;
			f.resizable = false;

			w = this.open(null, id, f);

			w.setTitle(ti);
			w.setContent('<div class="message">' + msg +'</div><div class="statustext">' + status +'</div><a id="' + id + '_ok_btn" href="javascript:mox.ui.WindowManager.get(\'' + id + '\').close();" class="action ok">{#common.alert_ok}</a>');
			//DOM.get(id + '_ok_btn').focus();

			return false;
		},

		showProgress : function(msg, theme) {
			var id, vp = DOM.getViewPort();

			theme = !theme ? 'clearlooks2' : theme;
			id = theme + '_progress';

			if (!DOM.get(theme + '_event_blocker'))
				document.body.appendChild(DOM.createTag('div', {id : theme + '_event_blocker', 'class' : theme + '_event_blocker'}));

			DOM.addClass(theme + '_event_blocker', theme + '_visible_event_blocker');
			DOM.show(theme + '_event_blocker');

			if (!DOM.get(id)) {
				DOM.addTags(document.body,
					['div', {id : id, 'class' : theme + '_progress'},
						['div', {id : id + '_msg', 'class' : 'message'}]
					]
				);
			}

			DOM.get(id + '_msg').innerHTML = msg;
			DOM.show(id);
			DOM.setStyles(id, {left : (vp.w / 2) - (DOM.get(id).clientWidth / 2) + 'px', top : (vp.h / 2) - (DOM.get(id).clientHeight /  2) + 'px'});

			this.progressTheme = theme;
		},

		hideProgress : function() {
			var th = this.progressTheme;

			DOM.hide(th + '_event_blocker');
			DOM.hide(th + '_progress');
		},

		/**
		 * Returns an array with window instances.
		 *
		 * @return {Array} Array with window instances.
		 */
		getWindows : function() {
			return this.windows;
		},

		get : function(n) {
			var w = null;

			List.map(this.windows, function(i, v) {
				if (v.name == n)
					w = v;
			});

			return w;
		},

		getLastWindow : function() {
			return this.lastWin;
		},

		/**
		 * Close all windows and dialogs.
		 */
		closeAll : function() {
			List.map(this.windows, function(i, v) {
				v.close();
			});
		},

		blurAll : function() {
			List.map(this.windows, function(i, v) {
				v.blur();
			});
		},

		remove : function(w) {
			this.windows = List.filter(this.windows, function(i, v) {
				return w.name != v.name;
			});
		},

		moveToFront : function(w) {
			this.remove(w);
			this.windows.push(w);
		},

		// Private methods

		_splitNameValue : function(s, f) {
			var o = {}, i, c;

			if (typeof(s) != 'string')
				return s;

			s = s.split(',');

			for (i=0; i<s.length; i++) {
				c = s[i].split('=');

				// If no key, then call function to create one
				if (c.length < 2)
					f && (o[f(c[0])] = c[0]);
				else
					o[c[0]] = c[1];
			}

			return o;
		}

		/**#@-*/
	});

	/**#@+
	 * @member mox.ui.Window
	 * @base mox.dom.Layer
	 */
	mox.create('mox.ui.Window:mox.dom.Drag', {
		name : null,
		url : null,
		features : null,
		args : null,
		borders : null,
		dragAction : null,
		placeHolder : null,
		zIndexes : null,
		buttons : null,

		Window : function(name, f) {
			var id = 'mcwindow_' + name, t = this, vp = DOM.getViewPort(), n, co, en = 'div'; // Set to input

			this.features = {
				top : 'center',
				left : 'center',
				width : 320,
				height : 240,
				type : 'window',
				resizable : false,
				theme : 'clearlooks2'
			};

			for (n in f)
				this.features[n] = f[n];

			f = this.features;

			this.buttons = ['close', 'min', 'max', 'move', 'resize_n', 'resize_nw', 'resize_ne', 'resize_w', 'resize_e', 'resize_s', 'resize_sw', 'resize_se'];

			if (f.type == 'window')
				mc = ['iframe', {id : id + '_content', src : 'about:blank', 'class' : 'middle', frameBorder : '0'}];
			else
				mc = ['div', {id : id + '_content', 'class' : 'middle'}, ' '];

			DOM.addTags(document.body,
				['div', {id : id, 'class' : f.theme + ' window'},
					['div', {id : id + '_container', 'class' : 'statusbar'},
						['div', {id : id + '_top', 'class' : 'windowtop'},
							['div', {'class' : 'left'}],
							['div', {'class' : 'middle'}],
							['div', {'class' : 'right'}],
							['div', {id : id + '_title', 'class' : 'title'}, ' '],

							['a', {id : id + '_min', href : 'javascript:mox.ui.WindowManager.get(\'' + name + '\').minimize();', 'class' : 'action min'}],
							['a', {id : id + '_max', href : 'javascript:mox.ui.WindowManager.get(\'' + name + '\').maximize();', 'class' : 'action max'}],
							['a', {id : id + '_close', href : 'javascript:mox.ui.WindowManager.get(\'' + name + '\').close();', 'class' : 'action close'}],
							[en, {id : id + '_move', 'class' : 'action move', tabindex : '-1'}],
							[en, {id : id + '_resize_n', 'class' : 'action resize resize-n', tabindex : '-1'}],
							[en, {id : id + '_resize_nw', 'class' : 'action resize resize-nw', tabindex : '-1'}],
							[en, {id : id + '_resize_ne', 'class' : 'action resize resize-ne', tabindex : '-1'}]
						],

						['div', {id : id + '_middle', 'class' : 'windowmiddle'},
							['div', {'class' : 'left'}],
							mc, // Middle section depending on type
							['div', {'class' : 'right'}],
							['div', {id : id + '_bigicon', 'class' : 'bigicon'}],

							[en, {id : id + '_resize_w', 'class' : 'action resize resize-w', tabindex : '-1'}],
							[en, {id : id + '_resize_e', 'class' : 'action resize resize-e', tabindex : '-1'}]
						],

						['div', {id : id + '_bottom', 'class' : 'windowbottom'},
							['div', {'class' : 'left'}],
							['div', {'class' : 'middle'}],
							['div', {'class' : 'right'}],
							['div', {id : id + '_statustext', 'class' : 'statustext'}, ' '],

							[en, {id : id + '_resize_s', 'class' : 'action resize resize-s', tabindex : '-1'}],
							[en, {id : id + '_resize_sw', 'class' : 'action resize resize-sw', tabindex : '-1'}],
							[en, {id : id + '_resize_se', 'class' : 'action resize resize-se', tabindex : '-1'}]
						]
					]
				]
			);

			co = DOM.get('mcwindow_container');

			if (!co)
				co = document.body.appendChild(DOM.createTag('div', {id : 'mcwindow_container', style : 'position: absolute; left: 0; top: 0; z-index: 100000; display: none;'}));

			co.appendChild(DOM.createTag('div', {id : id + '_placeholder', 'class' : f.theme + '_placeholder'}));
			this.placeHolder = new Layer(id + '_placeholder');

			this.parent(id, {
				drag_handle : id + '_' + ['move', 'resize_n', 'resize_nw', 'resize_ne', 'resize_w', 'resize_e', 'resize_s', 'resize_sw', 'resize_se'].join(',' + id + '_')
			});

			this.onDragStart.add(this.dragStartEvent);
			this.onDragEnd.add(this.dragEndEvent);
			this.onDrag.add(this.dragEvent);

			this.name = name;

			DOM.addClass(id + '_container', f.type);

			if (f.bigicon)
				DOM.addClass(id + '_bigicon', f.bigicon);

			if (f.chromeless)
				DOM.addClass(id + '_container', 'chromeless');

			if (f.resizable)
				DOM.addClass(id + '_container', 'resizable');

			if (f.statusbar) {
				DOM.addClass(id + '_container', 'statusbar');
				DOM.addClass(id + '_bottom', 'statusbarbottom');
			}

			this.setBounderies(0, 0, vp.w - 2, vp.h - 2);

			this.onClose = new Dispatcher(this);
		},

		setTitle : function(h) {
			if (h) {
				// Trim away IE host prefix
				h = ('' + h).replace(/^(http|https):\/\/[a-z0-9._\-\s]+-/gi, '');
				DOM.get(this.id + '_title').innerHTML = h;
			}
		},

		setContent : function(h) {
			if (h)
				DOM.get(this.id + '_content').innerHTML = LangPack.translate(h);
		},

		setStatus : function(t) {
			if (t)
				DOM.get(this.id + '_statustext').innerHTML = t;
		},

		getDocumentRect : function() {
			var w, h, d = document;

			w = h = 0;

			if (d.scrollWidth > w)
				w = d.scrollWidth;

			if (d.scrollHeight > h)
				h = d.scrollHeight;

			if (d.documentElement.scrollWidth > w)
				w = d.documentElement.scrollWidth;

			if (d.documentElement.scrollHeight > h)
				h = d.documentElement.scrollHeight;

			if (d.body.scrollWidth > w)
				w = d.body.scrollWidth;

			if (d.body.scrollHeight > h)
				h = d.body.scrollHeight;

			return new mox.geom.Rect(0, 0, w, h);
		},

		focus : function() {
			var id = this.id, f = this.features, r = this.getDocumentRect();

			if (!DOM.get(f.theme + '_event_blocker'))
				document.body.appendChild(DOM.createTag('div', {id : f.theme + '_event_blocker', 'class' : f.theme + '_event_blocker'}));

			mox.ui.WindowManager.blurAll();
			mox.ui.WindowManager.zindex++;

			if (f.blockevents) {
				if (mox.isIE) {
					DOM.selectElements(document, 'select', function (n) {
						n.oldDisabled = n.disabled;
						n.disabled = true;
					});
				}

				this.setZIndex(100010);
				DOM.setStyles(f.theme + '_event_blocker', {width : r.w + 'px', height : r.h + 'px'});
				DOM.show(f.theme + '_event_blocker');
				DOM.addClass(f.theme + '_event_blocker', f.theme + '_visible_event_blocker');
			} else {
				this.setZIndex(mox.ui.WindowManager.zindex);
				DOM.removeClass(f.theme + '_event_blocker', f.theme + '_visible_event_blocker');
			}

			DOM.addClass(id + '_container', 'focus');
			DOM.addClass(id + '_top', 'focustop');
			DOM.addClass(id + '_middle', 'focusmiddle');
			DOM.addClass(id + '_bottom', 'focusbottom');

			mox.ui.WindowManager.moveToFront(this);
		},

		blur : function() {
			DOM.removeClass(this.id + '_container', 'focus');
			DOM.removeClass(this.id + '_top', 'focustop');
			DOM.removeClass(this.id + '_middle', 'focusmiddle');
			DOM.removeClass(this.id + '_bottom', 'focusbottom');
		},

		setZIndex : function(z) {
			var t = this;

			DOM.setStyle(t.id, 'z-index', z);

			List.map(this.buttons, function (i, v) {
				DOM.setStyle(t.id + '_' + v, 'z-index', z + t.zIndexes[v]);
			});
		},

		setStatusbar : function(s) {
			if (s) {
				DOM.addClass(this.id + '_container', 'statusbar');
				DOM.addClass(this.id + '_top', 'statusbartop');
				DOM.addClass(this.id + '_middle', 'statusbartop');
				DOM.addClass(this.id + '_bottom', 'statusbartop');
			} else {
				DOM.removeClass(this.id + '_container', 'statusbar');
				DOM.removeClass(this.id + '_top', 'statusbartop');
				DOM.removeClass(this.id + '_middle', 'statusbarmiddle');
				DOM.removeClass(this.id + '_bottom', 'statusbarbottom');
			}
		},

		minimize : function() {
			this.setStatusbar(false);
			this.resizeTo(this.w, 28);
		},

		maximize : function() {
			var vp = DOM.getViewPort();

			this.moveTo(vp.x, vp.y);
			this.resizeTo(vp.w, vp.h);
		},

		setupBlockMode : function() {
			var ua;

			// Check if it's a old IE version and enable iframe to block select or object window controls
			ua = navigator.userAgent;
			this.setBlockMode(ua.indexOf('MSIE 5') > 0 || ua.indexOf('MSIE 6') > 0);
			this.update();
		},

		dragStartEvent : function(el) {
			var f = this.features;

			this.focus();
			this.dragAction = DOM.getAttrib(el, 'class').replace(/(action\s+|resize\s+)/g, '');
			this.placeHolder.moveTo(this.x, this.y);
			this.placeHolder.resizeTo(this.w, this.h);
			this.placeHolder.show();
			this.hide();
			this.update();

			DOM.show(f.theme + '_event_blocker');
			DOM.show('mcwindow_container');
		},

		dragEndEvent : function() {
			var p = this.placeHolder, f = this.features;

			this.moveTo(p.x, p.y);
			this.resizeTo(p.w, p.h);
			this.show();
			this.placeHolder.hide();

			if (!f.blockevents)
				DOM.hide(f.theme + '_event_blocker');

			DOM.hide('mcwindow_container');

			this.setupBlockMode();
		},

		dragEvent : function(dx, dy, x, y) {
			var sr = this.startRect, l = this.placeHolder;

			switch (this.dragAction) {
				case 'resize-n':
					l.moveTo(sr.x, y);
					l.resizeTo(sr.w, sr.h - dy);
				break;

				case 'resize-nw':
					l.moveTo(x, y);
					l.resizeTo(sr.w - dx, sr.h - dy);
				break;

				case 'resize-ne':
					l.moveTo(sr.x, y);
					l.resizeTo(sr.w + dx, sr.h - dy);
				break;

				case 'resize-w':
					l.moveTo(x, sr.y);
					l.resizeTo(sr.w - dx, sr.h);
				break;

				case 'resize-e':
					l.resizeTo(sr.w + dx, sr.h);
				break;

				case 'resize-s':
					l.resizeTo(sr.w, sr.h + dy);
				break;

				case 'resize-sw':
					l.moveTo(sr.x + dx, sr.y);
					l.resizeTo(sr.w - dx, sr.h + dy);
				break;

				case 'resize-se':
					l.resizeTo(sr.w + dx, sr.h + dy);
				break;

				case 'move':
					l.moveTo(x, y);
				break;
			}
		},

		setURL : function(u) {
			this.url = u;

			if (u) {
				if (DOM.get(this.id + '_content').contentDocument)
					DOM.get(this.id + '_content').contentDocument.location = u;
				else
					DOM.get(this.id + '_content').contentWindow.document.location = u;
			}
		},

		getInnerWin : function() {
			return DOM.get(this.id + '_content').contentWindow;
		},

		setFeatures : function(f) {
			var n;

			for (n in f)
				this.features[n] = f[n];
		},

		setArgs : function(a) {
			this.args = a;
		},

		getArgs : function() {
			return this.args;
		},

		ok : function() {
			if (this.onconfirm)
				this.onconfirm(this);

			this.close();
		},

		open : function() {
			// Hide it
			this.moveTo(-1000, -1000);
			this.show();

			// Opera needs a moment
			if (mox.isOpera)
				window.setTimeout(mox.bind(this, this.setupWindow), 1000);
			else
				this.setupWindow();
		},

		setupWindow : function() {
			var w, h, t = this, f = this.features, id = this.id, iw, ih, vp = DOM.getViewPort(), wm = mox.ui.WindowManager;

			// Setup zindexes
			t.zIndexes = [];

			List.map(this.buttons, function (i, v) {
				var s = DOM.getStyle(t.id + '_' + v, 'z-index');

				t.zIndexes[v] = s || 0; // Safari fix
			});

			// Setup borders
			iw = ih = 0;

			iw += parseInt(DOM.getStyle(DOM.getByClass(id + '_middle', 'div', 'left')[0], 'width'));
			iw += parseInt(DOM.getStyle(DOM.getByClass(id + '_middle', 'div', 'right')[0], 'width'));
			ih += parseInt(DOM.getStyle(id + '_top', 'height'));
			ih += parseInt(DOM.getStyle(id + '_bottom', 'height'));

			this.borders = {w : iw, h : ih - 1};

			// Setup window dimensions
			w = f.width;
			h = f.height;

			if (f.fullscreen == 'yes') {
				f.left = f.top = 0;
				w = vp.w;
				h = vp.h;

				Event.add(window, 'resize', this.onresize, this);
			}

			if (w == 'max')
				w = vp.w;

			if (h == 'max')
				h = vp.h;

			if (f.left == 'center')
				f.left = vp.x + (vp.w / 2) - parseInt(f.width / 2);

			if (f.top == 'center')
				f.top = vp.y + (vp.h / 2) - parseInt(f.height / 2);

			// Resize and move div
			this.setupBlockMode();
			this.moveTo(parseInt(f.left), parseInt(f.top));
			this.resizeTo(w, h);
			this.focus();
			this.update();
		},

		close : function() {
			var wm = mox.ui.WindowManager, wl, f = this.features;

			// Onclosed canceled
			if (this.onClose.dispatch() === false)
				return;

			Event.remove(window, 'resize', this.onresize, this);

			DOM.remove(this.id);
			DOM.remove(this.id + "_blocker");
			wm.remove(this);

			wl = wm.windows;
			if (wl.length > 0)
				wl[wl.length - 1].focus();

			if (f.blockevents) {
				if (mox.isIE) {
					DOM.selectElements(document, 'select', function (n) {
						n.disabled = n.oldDisabled;
					});
				}
			}

			DOM.hide(f.theme + '_event_blocker');

			if (f.onclose)
				f.onclose(this);

			if (wm.onclose)
				wm.onclose(this);
		},

		resizeTo : function(x, y) {
			var b = this.borders;

			x = parseInt(x);
			y = parseInt(y);

			this.parent(x, y);

			DOM.setStyle(this.id, 'visibility', 'hidden'); // Force repaint Opera
			DOM.setStyles(this.id + '_content', {width : (x - b.w) + 'px', height : (y - b.h) + 'px'});
			DOM.setStyle(this.id, 'visibility', 'visible');  // Force repaint Opera
		},

		resizeBy : function(x, y) {
			var b = this.borders;

			this.parent(parseInt(x), parseInt(y));

			DOM.setStyle(this.id, 'visibility', 'hidden'); // Force repaint Opera
			DOM.setStyles(this.id + '_content', {width : (this.w - b.w) + 'px', height : (this.h - b.h) + 'px'});
			DOM.setStyle(this.id, 'visibility', 'visible');  // Force repaint Opera
		},

		onresize : function(e) {
			var vp = DOM.getViewPort();

			this.resizeTo(vp.w, vp.h);
		}

		/**#@-*/
	});
});
