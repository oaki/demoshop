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

	mox.create('mox.data.SelectableResultSet:mox.data.ResultSet', {
		selected : null,
		lastSelected : -1,

		SelectableResultSet : function(result) {
			this.parent(result);
			this.selected = [];
		},

		selectRows : function(s, e) {
			var i;

			for (i=s; i<=e; i++)
				this.selectRow(i);

			for (i=e; i<s; i++)
				this.selectRow(i);
		},

		selectRow : function(idx) {
			var sl = this.selected;

			if (List.indexOf(sl, idx) == -1)
				sl.push(idx);

			this.lastSelected = idx;
		},

		unselectRow : function(idx) {
			this.selected = List.filter(this.selected, function(i, v) {
				return idx != v;
			});
		},

		unselectAll : function() {
			this.selected = [];
		},

		selectAll : function() {
			this.selected = [];
			this.selectRows(0, this.getRowCount());
		},

		getLastSelectedRow : function() {
			return this.getRow(this.lastSelected);
		},

		getSelectedRows : function() {
			var i, sl, o = [];

			for (i=0, sl = this.selected; i<sl.length; i++)
				o.push(this.getRow(sl[i]));

			return o;
		},

		getSelectedIndexes : function() {
			return this.selected;
		}

		/**#@-*/
	});
});
