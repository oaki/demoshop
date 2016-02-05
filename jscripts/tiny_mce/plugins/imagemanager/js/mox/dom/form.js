/**
 * $Id: TinyMCE_DOMUtils.class.js 91 2006-10-02 14:53:22Z spocke $
 *
 * @author Moxiecode
 * @copyright Copyright © 2004-2006, Moxiecode Systems AB, All rights reserved.
 */

mox.require([
	'mox.DOM'
], function() {
	// Shorten class names
	var DOM = mox.DOM;

	/**#@+
	 * @class This class contains various form utility methods.
	 * @member mox.dom.Form
	 * @static
	 */
	mox.create('static mox.dom.Form', {
		/**#@+
		 * @method
		 */

		/**
		 * Selects a option element inside a select list by value.
		 *
		 * @param {Form} f Form HTML element containing the select element.
		 * @param {string} n Name of select element to find option in.
		 * @param {string} v Option value to look for.
		 * @return {Element} Option HTML element if it was found or null if it wasn't.
		 */
		selectOption : function(f, n, v) {
			var i, ol = f.elements[n].options;

			for (i=0; i<ol.length; i++) {
				if (ol[i].value == v) {
					ol[i].selected = true;

					return ol[i];
				}
			}

			return null;
		},

		/**
		 * Add a new option element to a select element.
		 *
		 * @param {Form} f Form HTML element containing the select element.
		 * @param {string} n Name of select element to option to.
		 * @param {string} on Option name/title to add.
		 * @param {string} on Option value to add.
		 * @return {Element} Option HTML element that was added.
		 */
		addOption : function(f, n, on, ov) {
			var e = f.elements[n], ol = e.options;

			o.selected = true;

			return ol.push(new Option(on, ov));
		},

		getRadioValue : function(f, n) {
			var i, nl;

			for (i=0, nl = f.elements[n]; i<nl.length; i++) {
				if (nl[i].checked)
					return nl[i].value;
			}

			return null;
		},

		/**
		 * Returns a form item value this will also handle select
		 * elements/checkboxes and radio buttons.
		 *
		 * @param {Form} f Form element name to check item value in.
		 * @param {string} n Name of form item to get value from.
		 * @return {string} Form item value or null if it wasn't found.
		 */
		getValue : function(f, n) {
			var e = f.elements[n];

			switch (e.type) {
				case 'SELECT':
					return e.options[e.selectedIndex].value;
			}

			return e.value;
		}

		/**#@-*/
	});
});
