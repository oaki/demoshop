/**
 * $Id: TinyMCE_DOMUtils.class.js 91 2006-10-02 14:53:22Z spocke $
 *
 * @author Moxiecode
 * @copyright Copyright © 2004-2006, Moxiecode Systems AB, All rights reserved.
 */

mox.require([
	'mox.List'
], function() {
	var List = mox.List;

	function isset(o) {
		return typeof(o) != 'undefined';
	};

	mox.create('mox.data.ResultSet', {
		columns : null,
		data : null,
		header : null,

		ResultSet : function(result) {
			this.header = result.header;
			this.columns = result.columns;
			this.data = result.data;
		},

		each : function(f, i) {
			var i;

			for (i=0; i<this.data.length; i++) {
				if (f(this.getRow(i), i) === false)
					break;
			}
		},

		getColumns : function() {
			return this.columns;
		},

		getHeader : function() {
			return this.header;
		},

		getRow : function(idx) {
			var d, c = this.columns, o = {}, i;

			if (idx < 0)
				return null;

			for (i=0, d = this.data[idx]; i<c.length; i++)
				o[c[i]] = d[i];

			return o;
		},

		getColByName : function(r, n) {
			var sc, c = this.columns;
	
			for (i=0, sc=-1; i<c.length; i++) {
				if (n == c[i]) {
					sc = i;
					break;
				}
			}

			return sc != -1 ? r[sc] : null;
		},

		getRowCount : function() {
			return this.data.length;
		},

		orderBy : function(by, num, desc) {
			var t = this, c = t.columns, i, sc;

			desc = isset(desc) && desc;

			for (i=0, sc=-1; i<c.length; i++) {
				if (by == c[i]) {
					sc = i;
					break;
				}
			}

			function numSort(a, b) {
				a = parseFloat(a[sc]);
				b = parseFloat(b[sc]);

				a = isNaN(a) ? 0 : a;
				b = isNaN(b) ? 0 : b;

				return a - b;
			};

			function strSort(a, b) {
				try {
					a = '' + a[sc].toLowerCase();
					b = '' + b[sc].toLowerCase();

					if (a == b)
						return 0;

					if (a < b)
						return -1;
				} catch (ex) {
					// Ignore
				}

				return 1;
			};

			if (sc != -1)
				t.data = t.data.sort(num ? numSort : strSort);

			if (desc)
				t.data = t.data.reverse();
		},

		toString : function() {
			var i, d = this.data, o = '';

			for (i=0; i<d.length; i++)
				o += d[i].join(',') + '\n';

			return o;
		}

		/**#@-*/
	});
});
