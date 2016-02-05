/**
 * $Id: TinyMCE_DOMUtils.class.js 91 2006-10-02 14:53:22Z spocke $
 *
 * @author Moxiecode
 * @copyright Copyright © 2004-2006, Moxiecode Systems AB, All rights reserved.
 */

mox.require([
	'mox.List',
	'mox.geom.Point',
	'mox.geom.Rect'
], function() {
	var List = mox.List;

	/**#@+
	 * @class This class contains common event handling logic.
	 * @member mox.DOM
	 * @static
	 */
	mox.create('mox.DOMUtils', {
		_counter : 0,
		_doc : null,

		/**
		 * Constructs a new DOM instance.
		 *
		 * @param {Document} doc DOM document to peform all methods on.
		 */
		DOMUtils : function(doc) {
			this._doc = doc;
		},

		/**#@+
		 * @method
		 */

		/**
		 * Returns a array of elements when the specified function matches a node.
		 *
		 * @param {Node} n Node to select children from.
		 * @param {string} na Element name(s) to search for separated by commas.
		 * @param {function} f Function that returns true/false if the node is to be added or not.
		 * @return {Array} Array with selected elements.
		 */
		selectElements : function(n, na, f) {
			var i, a = [], nl, x;

			n = this.get(n);

			for (x=0, na = na.split(','); x<na.length; x++)
				for (i=0, nl = n.getElementsByTagName(na[x]); i<nl.length; i++)
					f(nl[i]) && a.push(nl[i]);

			return a;
		},

		/**
		 * #id
		 * p
		 * p, div
		 * p.class
		 * div p
		 * p[@id=3]
		 * p#id
		 */
		select : function(p, s) {
			var o = [], r = [], i, t = this, pl, ru, pu, x, u, xp;

			s = !s ? this._doc.body : this.get(s);

			// Parse pattern into rules
			for (i=0, pl=p.split(','); i<pl.length; i++) {
				ru = [];
				xp = '.';

				for (x=0, pu=pl[i].split(' '); x<pu.length; x++) {
					u = pu[x];

					if (u != '') {
						u = u.match(/^([\w\\]+)?(?:#([\w\\]+))?(?:\.([\w\\\.]+))?(?:\[\@([\w\\]+)([\^\$\*!]?=)([\w\\]+)\])?(?:\:([\w\\]+))?/i);

						// Build xpath if supported
						if (document.evaluate) {
							xp += u[1] ? '//' + u[1] : '//*';

							// Id
							if (u[2])
								xp += "[@id='" + u[2] + "']";

							// Class
							if (u[3]) {
								List.each(u[3].split('.'), function(i, n) {
									xp += "[@class = '" + n + "' or contains(concat(' ', @class, ' '), ' " + n + " ')]";
								});
							}

							// Attr
							if (u[4]) {
								// Comparators
								if (u[5]) {
									if (u[5] == '^=')
										xp += "[starts-with(@" + u[4] + ",'" + u[6] + "')]";
									else if (u[5] == '$=')
										xp += "[contains(concat(@" + u[4] + ",'\0'),'" + u[6] + "\0')]";
									else if (u[5] == '*=')
										xp += "[contains(@" + u[4] + ",'" + u[6] + "')]";
									else if (u[5] == '!=')
										xp += "[@" + u[4] + "!='" + u[6] + "']";
								} else
									xp += "[@" + u[4] + "='" + u[6] + "']";
							}

							//console.debug(u);
						} else
							ru.push({ tag : u[1], id : u[2], cls : u[3] ? new RegExp('\\b' + u[3].replace(/\./g, '|') + '\\b') : null, attr : u[4], val : u[5] });
					}
				}

				//console.debug(xp);

				if (xp.length > 1)
					ru.xpath = xp;

				r.push(ru);
			}

			// Used by IE since it doesn't support XPath
			function find(e, rl, p) {
				var nl, i, n, ru = rl[p];

				for (i=0, nl = e.getElementsByTagName(!ru.tag ? '*' : ru.tag); i<nl.length; i++) {
					n = nl[i];

					if (ru.id && n.id != ru.id)
						continue;

					if (ru.cls && !ru.cls.test(n.className))
						continue;

					if (ru.attr && t.getAttrib(n, ru.attr) != ru.val)
						continue;

					if (p < rl.length - 1)
						find(n, rl, p + 1);
					else
						o.push(n);
				}
			};

			// Find elements based on rules
			for (i=0; i<r.length; i++) {
				if (r[i].xpath) {
					ru = this._doc.evaluate(r[i].xpath, s, null, 4, null);

					while (u = ru.iterateNext())
						o.push(u);
				} else
					find(s, r[i], 0);
			}

			return o;
		},

		/**
		 * Returns the viewport of the window.
		 *
		 * @param {Window} w Optional window to get viewport of.
		 * @return {Object} Viewport object with fields top, left, width and height.
		 */
		getViewPort : function(w) {
			var d, b;

			w = !w ? window : w;
			d = w.document;
			b = d.compatMode == 'CSS1Compat' ? d.documentElement : d.body;

			return new mox.geom.Rect(
				w.pageXOffset || b.scrollLeft,
				w.pageYOffset || b.scrollTop,
				w.innerWidth || b.clientWidth,
				w.innerHeight || b.clientHeight
			);
		},

		/**
		 * Returns a node by the specified selector function. This function will
		 * loop through all parent nodes and call the specified function for each node.
		 * If the function then returns true it will stop the execution and return that node.
		 *
		 * @param {Node} n HTML node to search parents on.
		 * @param {function} f Selection function to execute on each node.
		 * @param {Node} r Optional root element, never go below this point.
		 * @return {Node} DOMNode or null if it wasn't found.
		 */
		getParent : function(n, f, r) {
			n = this.get(n);

			while (n) {
				if (n == r)
					return null;

				if (f(n))
					return n;

				n = n.parentNode;
			}

			return null;
		},

		add : function(p, n, a, h) {
			var t = this, e;

			e = this._doc.createElement(n);

			for (n in a) {
				if (a.hasOwnProperty(n))
					t.setAttrib(e, n, a[n]);
			}

			if (h) {
				if (h.nodeType)
					e.appendChild(h);
				else
					e.innerHTML = h;
			}

			return p ? p.appendChild(e) : e;
		},

		create : function(n, a, h) {
			return this.add(0, n, a, h);
		},

		/**
		 * Creates a tag by name and attributes array. This will create a DOM node out of the specified
		 * data.
		 *
		 * @param {string} tn Tag name to create.
		 * @param {Array} a Optional name/Value array of attributes.
		 * @param {string} h Optional inner HTML of new tag, raw HTML code.
		 */
		createTag : function(tn, a, h) {
			var n, o = this._doc.createElement(tn);

			if (a) {
				for (n in a) {
					if (typeof(a[n]) != 'function' && a[n] != null) {
						this.setAttrib(o, n, a[n]);
					}
				}
			}

			if (h)
				o.innerHTML = h;

			return o;
		},

		/**
		 * Adds a whole tree of DOM elements by grabing the data from an array.
		 *
		 * @example
		 *  DOM.addTags(
		 *    ['div', {id : 'someid', 'class' : 'someclass'}, 'Some text content'
		 *      ['span', {'class' : 'someclass'},
		 *          ['span', {'class' : 'someclass'}, 'Some more content',
		 *          ['span', {'class' : 'someclass'}
		 *      ]
		 *    ]
		 *  );
		 *
		 * @param {Element} te Target element to append tree of nodes to.
		 * @param {Array} ne Array of element to append.
		 */
		addTags : function(te, ne) {
			var i, n;

			te = this.get(te);

			if (typeof(ne) == 'string')
				te.appendChild(this._doc.createTextNode(ne));
			else if (ne.length) {
				te = te.appendChild(this.createTag(ne[0], ne[1]));

				for (i=2; i<ne.length; i++)
					this.addTags(te, ne[i]);
			}
		},

		/**
		 * Sets the attribute value for a specific attribute.
		 *
		 * @param {Element} e HTML element to set attribute on.
		 * @param {string} n Attribute name to set.
		 * @param {string} v Attribute value to set.
		 */
		setAttrib : function(e, n, v) {
			e = this.get(e);

			if (!e)
				return false;

			if (n == "style")
				e.style.cssText = v;

			if (n == "class")
				e.className = v;

			if (v != null && v != "")
				e.setAttribute(n, '' + v);
			else
				e.removeAttribute(n);
		},

		/**
		 * Sets the attributes for a specific attribute.
		 *
		 * @param {Element} e HTML element to set attribute on.
		 * @param {Object} o Name/Value object to set.
		 */
		setAttribs : function(e, o) {
			var n;

			e = this.get(e);

			if (!e)
				return false;

			for (n in o) {
				if (typeof(o[n]) != 'function')
					this.setAttrib(e, n, o[n]);
			}
		},

		/**
		 * Returns a attribute value by name from a HTML element.
		 *
		 * @param {Element} e HTML element to get attribute from.
		 * @param {string} n Name of attribute to retrive.
		 * @param {string} dv Optional default value if the attribute was undefined.
		 * @return {string} Attribute value or empty string if it wasn't found.
		 */
		getAttrib : function(e, n, dv) {
			var v;

			e = this.get(e);

			if (!e)
				return false;

			if (typeof(dv) == "undefined")
				dv = "";

			v = e.getAttribute(n, 2);

			if (!v)
				v = e.attributes[n];

			if (n == "class" && !v)
				v = e.className;

			if (n == "style" && !v)
				v = e.style.cssText;

			return (v && v != "") ? v : dv;
		},

		/**
		 * Sets the CSS style value on a HTML element. The name can be a camelcase string
		 * or the CSS style name like background-color.
		 *
		 * @param {Element} n HTML element to set CSS style value on.
		 * @param {string} na Name of the style value to set.
		 * @param {string} v Value to set on the style.
		 */
		setStyle : function(n, na, v){
			var s, i;

			if (!n)
				return false;

			n = typeof(n) == 'string' ? n.split(',') : [n];

			for (i=0; i<n.length; i++) {
				s = this.get(n[i]);

				if (!s)
					continue;

				s = s.style;

				// Camelcase it, if needed
				na = na.replace(/-(\D)/g, function(a, b){
					return b.toUpperCase();
				});

				if (na == 'opacity') {
					// IE specific opacity
					if (mox.isIE) {
						s.filter = "alpha(opacity=" + (v * 100) + ")";

						if (!n.currentStyle || !n.currentStyle.hasLayout)
							s.display = 'inline-block';
					}

					// Fix for older browsers
					s['-moz-opacity'] = s['-khtml-opacity'] = v;
				}

				s[na] = v;
			}
		},

		/**
		 * Sets multiple CSS styles on the specified element.
		 *
		 * @param {Element} e HTML element to set CSS styles on.
		 * @param {Object} o Name/Value object of styles to set.
		 */
		setStyles : function(e, o) {
			var n;

			if (!e)
				return false;

			for (n in o) {
				if (typeof(o[n]) != 'function')
					this.setStyle(e, n, o[n]);
			}
		},

		/**
		 * Returns the current runtime/computed style value of a element.
		 *
		 * @param {Element} n HTML element to get style from.
		 * @param {string} na Style name to return.
		 * @param {string} d Optional default value.
		 * @return {string} Current runtime/computed style value of a element.
		 */
		getStyle : function(n, na, d) {
			n = this.get(n);

			if (!n)
				return false;

			// Gecko
			if (this._doc.defaultView) {
				try {
					return this._doc.defaultView.getComputedStyle(n, null).getPropertyValue(na);
				} catch (n) {
					// Old safari might fail
					return null;
				}
			}

			// Camelcase it, if needed
			na = na.replace(/-(\D)/g, function(a, b){
				return b.toUpperCase();
			});

			// IE & Opera
			if (n.currentStyle)
				return n.currentStyle[na];

			return false;
		},

		/**
		 * Returns child elements by class name.
		 *
		 * @param {Element} n HTML element to get child elements from.
		 * @param {string} na HTML element(s) to look for can be a comma separated list.
		 * @param {string} c Class names to look for can be a regex chunk link (classA|classB).
		 * @return {Array} Array containing Element instances matching the class.
		 */
		getByClass : function(n, na, c) {
			var t = this;

			return this.selectElements(this.get(n), na, function(no) {
				return t.hasClass(no, c);
			});
		},

		/**
		 * Returns the specified element by id.
		 *
		 * @param {string} n Element id to look for.
		 * @return {Element} Element matching the specified id or null if it wasn't found.
		 */
		get : function(n) {
			return typeof(n) == 'string' ? this._doc.getElementById(n) : n;
		},

		/**
		 * Shows the specified element by ID by setting the "display" style.
		 *
		 * @param {string} id ID of DOM element to show.
		 */
		show : function(id) {
			this.setStyle(id, 'display', 'block');
		},

		/**
		 * Hides the specified element by ID by setting the "display" style.
		 *
		 * @param {string} id ID of DOM element to hide.
		 */
		hide : function(id) {
			this.setStyle(id, 'display', 'none');
		},

		/**
		 * Returns true/false if the element is hidden or not by checking the "display" style.
		 *
		 * @param {string} id Id of element to check.
		 * @return {bool} true/false if the element is hidden or not.
		 */
		isHidden : function(id) {
			return this.getStyle(id, 'display') == 'none';
		},

		/**
		 * Sets the visbility of the current layer. This will set the visibility style attribute of the element.
		 *
		 * @param {string} id ID of DOM element to show.
		 * @param {bool} s Visibility state true/false if it should be visible.
		 * @return {bool} Input state.
		 */
		setVisible : function(id, s) {
			this.setStyle(id, 'visibility', s ? 'visible' : 'hidden');

			return s;
		},

		/**
		 * Returns true/false if the specified element is visible or not by checking the "visibility" style.
		 *
		 * @param {string} id Id of element to check.
		 * @return {bool} true/false if the element is visible or not.
		 */
		isVisible : function(id) {
			return this.getStyle(id, 'visibility') == 'visible';
		},

		/**
		 * Returns the absolute x, y position of a node. The position will be returned in a Point object.
		 *
		 * @param {Node} n HTML element to get x, y position from.
		 * @param {bool} ab Optional state if it should use style left/top as x, y cordinates.
		 * @param {Node} cn Optional HTML element to to stop position calcualtion by.
		 * @return {mox.geom.Point} Absolute position of the specified element.
		 */
		getPos : function(n, ab, cn) {
			var l = 0, t = 0, p, r, d;

			n = this.get(n);

			// Handle absolute layers
			if (ab)
				return new mox.geom.Point(parseInt(this.getStyle(n, 'left')), parseInt(this.getStyle(n, 'top')));

			// IE specific method (less quirks in IE6)
			if (n && n.getBoundingClientRect) {
				r = n.getBoundingClientRect();
				d = document;
				n = d.compatMode == 'CSS1Compat' ? d.documentElement : d.body;

				return new mox.geom.Point(
					r.left + (n.scrollLeft || 0),
					r.top + (n.scrollTop || 0)
				);
			}

			while (n && n != cn) {
				l += n.offsetLeft || 0;
				t += n.offsetTop || 0;
				n = n.offsetParent;

				//p = this.getStyle(n, 'position');

				//if (p == 'absolute' || p == 'relative')
				//	break;
			}

			return new mox.geom.Point(l, t);
		},

		/**
		 * Returns a rectangle instance for a specific element.
		 *
		 * @param {Element} e Element to get rectange from.
		 * @param {bool} ab Optional state if it should use style left/top as x, y cordinates.
		 * @param {mox.geom.Rect} r Optional rectange to fill.
		 * @return {mox.geom.Rect} Rectange instance for specified element.
		 */
		getRect : function(e, ab, r) {
			var p, r;

			e = this.get(e);
			p = this.getPos(e, ab);
			r = !r ? new mox.geom.Rect() : r;

			r.set(
				p.x,
				p.y,
				parseInt(this.getStyle(e, 'width')) || e.offsetWidth,
				parseInt(this.getStyle(e, 'height')) || e.offsetHeight
			);

			return r;
		},

		/**
		 * Removes the specified element by id.
		 *
		 * @param {string} id ID of DOM element to remove, can also be a commaseparated list.
		 */
		remove : function(id) {
			var i, n;

			for (i=0, id = id.split(','); i<id.length; i++) {
				n = this.get(id[i]);

				if (n)
					n.parentNode.removeChild(n);
			}
		},

		/**
		 * Adds a CSS class to the specified element. It will remove any previous item with the same name
		 * so adding a class that already exists will move it to the end.
		 *
		 * @param {Element} e HTML element to add CSS class to.
		 * @param {string] c CSS class to add to HTML element.
		 * @param {boolean] b Optional parameter, if set to true, class will be added to the beginning.
		 * @return {string} Returns the new class attribute value.
		 */
		addClass : function(e, c, b) {
			var o;

			e = this.get(e);

			if (!e)
				return null;

			o = this.removeClass(e, c);

			return e.className = b ? c + (o != '' ? (' ' + o) : '') : (o != '' ? (o + ' ') : '') + c;
		},

		/**
		 * Removes the specified CSS class from the element.
		 *
		 * @param {Element} e HTML element to remove CSS class to.
		 * @param {string] c CSS class to remove to HTML element.
		 * @return {string} Returns the new class attribute value.
		 */
		removeClass : function(e, c) {
			e = this.get(e);

			if (!e)
				return null;

			c = e.className.replace(new RegExp("(^|\\s+)" + c + "(\\s+|$)", "g"), ' ');

			return e.className = c != ' ' ? c : '';
		},

		/**
		 * Replaces the specified class with a new one.
		 *
		 * @param {Element} e HTML element to replace CSS class in.
		 * @param {string] o CSS class to remove from HTML element.
		 * @param {string] n New CSS class to add to HTML element.
		 */
		replaceClass : function(e, o, n) {
			if (this.hasClass(e, o)) {
				this.removeClass(e, o);
				this.addClass(e, n);
			}
		},

		/**
		 * Returns true if the specified element has the specified class.
		 *
		 * @param {Element} n HTML element to check CSS class on.
		 * @param {string] c CSS class to check for.
		 * @return {bool} true/false if the specified element has the specified class.
		 */
		hasClass : function(n, c) {
			n = this.get(n);

			return new RegExp('\\b' + c + '\\b', 'g').test(n.className);
		},

		/**
		 * Returns the outer HTML of a element, this uses the outerHTML
		 * property in MSIE and Opera and a workaround for Gecko.
		 *
		 * @param {Element} e HTML element to get outerHTML from.
		 * @return {string} HTML content string.
		 */
		getOuterHTML : function(e) {
			var d;

			e = this.get(e);

			if (mox.isIE || mox.isOpera)
				return e.outerHTML;

			d = e.ownerDocument.createElement("body");
			d.appendChild(e.cloneNode(true));

			return d.innerHTML;
		},

		/**
		 * Sets the outer HTML of a element, this uses the outerHTML
		 * property in MSIE and Opera and a workaround for Gecko.
		 *
		 * @param {Element} e HTML element to set outerHTML on.
		 * @param {string} h HTML string to set in property.
		 */
		setOuterHTML : function(e, h) {
			var i, nl, t;

			e = this.get(e);

			if (mox.isIE)
				e.outerHTML = h;
			else {
				t = e.ownerDocument.createElement("body");
				t.innerHTML = h;

				for (i=0, nl=t.childNodes; i<nl.length; i++)
					e.parentNode.insertBefore(nl[i].cloneNode(true), e);

				e.parentNode.removeChild(e);
			}
		},

		/**
		 * Imports a CSS file into a allready loaded document. This will add a link element
		 * to the head element of the document.
		 *
		 * @param {string} css CSS File URL to load or comma separated list of files.
		 */
		importCSS : function(css) {
			var i, e, he, d = this._doc;

			css = css.split(',');

			for (i=0; i<css.length; i++) {
				if (!d.createStyleSheet) {
					e = d.createElement("link");

					e.rel = "stylesheet";
					e.href = css[i];

					if ((he = d.getElementsByTagName("head")) != null && he.length > 0)
						he[0].appendChild(e);
				} else
					d.createStyleSheet(css[i]);
			}
		},

		/**
		 * Returns a unique id. This can be useful when generating elements on the fly.
		 * This method will not check if the element allreay exists.
		 *
		 * @param {string} p Optional prefix to add infront of all ids.
		 * @return {string} Unique id.
		 */
		uniqueId : function(p) {
			return (!p ? 'mox_' : p) + (this._counter++);
		}

		/**#@-*/
	});

	// Setup base DOM
	mox.DOM = new mox.DOMUtils(document);

	// Tell it a lie :)
	mox.provide('mox.DOM');
});
