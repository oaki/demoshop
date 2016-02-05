/**#@+
 * @member moxiecode.manager.ImageManager
 * @base moxiecode.manager.BaseManager
 */
mox.require([
	'moxiecode.manager.BaseManager'
], function() {
	mox.create('moxiecode.manager.ImageManager:moxiecode.manager.BaseManager', {
		ImageManager : function(s) {
			this.parent(s);
		},

		open : function(form_name, element_names, file_url, js, s) {
			var x, y, w, h, win;

			s = this.merge(s, this.settings);

			w = 785;
			h = 500;
			x = parseInt(screen.width / 2.0) - (w / 2.0);
			y = parseInt(screen.height / 2.0) - (h / 2.0);

			if (moxiecode.isMSIE) {
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

			s.target_form = form_name;
			s.target_elements = element_names;
			s.insert_callback = typeof(js) == 'function' ? js : eval(js);

			this.callSettings = s;

			win = window.open(moxiecode.baseURL + '/../index.php?type=im', 'mcImageManagerWin', 'left=' + x + ',top=' + y + ',width=' + w + ',height=' + h + ',scrollbars=yes,resizable=yes,statusbar=no');

			try {
			} catch (ex) {
				win.focus();
			}
		}

		/**#@-*/
	});
});
