/**
 * $Id: TinyMCE_DOMUtils.class.js 91 2006-10-02 14:53:22Z spocke $
 *
 * @author Moxiecode
 * @copyright Copyright © 2004-2006, Moxiecode Systems AB, All rights reserved.
 */

mox.require([
	'mox.DOM',
	'mox.util.Dispatcher'
], function() {
	// Shorten class names
	var DOM = mox.DOM;
	var Dispatcher = mox.util.Dispatcher;

	/**#@+
	 * @member mox.dom.Tween
	 */
	mox.create('mox.dom.Tween', {
		timer : null,
		settings : null,
		items : null,
		target : null,
		noNeg : /opacity|width|height|padding|border\-width/i,

		Tween : function(t, a, s) {
			var n, o, tr;

			this.target = t;

			// Default settings
			this.settings = {
				fps : 100,
				frames : 100,
				transition : this.linear.easeNone,
				time : 0
			};

			tr = s.transition;
			if (typeof(tr) == 'string') {
				tr = tr.split('.');
				s.transition = this[tr[0]][tr[1]];
				s.transitionScope = this[tr[0]];
			}

			// Override
			for (n in s)
				this.settings[n] = s[n];

			s = this.settings;

			if (s.time > 0)
				s.frames = Math.round(s.time / 1000.0 * s.fps);

			for (n in a) {
				o = a[n];

				if (typeof(o.from) == 'undefined')
					o.from = parseInt(DOM.getStyle(t, n));

				if (typeof(o.to) == 'undefined')
					o.to = parseInt(DOM.getStyle(t, n));
			}

			this.items = a;
			this.onStart = new Dispatcher();
			this.onEnd = new Dispatcher();
		},

		onStart : null,

		onEnd : null,

		start : function(e) {
			var t = this, s = t.settings, st, fc = 0, fs = 1;

			if (this.timer)
				return false;

			st = new Date().getTime();

			if (this.onStart.dispatch() === false)
				return;

			this.update(0);

			// Setup timer
			this.timer = window.setInterval(function() {
				var f = (new Date().getTime() - st) / 1000.0 * s.fps;

				// Calculate frame skip
				if (f > fc)
					fs = f - fc;

				// Count up current frame
				if ((fc += fs) > s.frames) {
					fc = s.frames;
					t.update(fc);
					t.stop();
					return;
				}

				//DOM.get('data').innerHTML += f + ',' + fc + '<br>';

				// Update
				t.update(fc);
			}, 1000 / this.settings.fps);

			return true;
		},

		stop : function(e) {
			if (this.timer) {
				window.clearInterval(this.timer);
				this.timer = null;
				this.onEnd.dispatch();
			}
		},

		update : function(fc) {
			var n, v, il = this.items, it, s = this.settings;

			for (n in il) {
				it = il[n];

				it.value = s.transition.call(s.transitionScope, fc, it.from, it.to - it.from, this.settings.frames);

				if (n == 'opacity')
					v = it.value / 100.0;
				else
					v = Math.round(it.value) + (it.unit ? it.unit : 'px');

				if (v < 0 && this.noNeg.test(n))
					v = Math.abs(v);

				DOM.setStyle(this.target, n, v);
			}
		},

		// Tweening calculations by Robert Penner http://www.robertpenner.com/easing/

		back : {
			easeIn : function(t, b, c, d, s) {
				if (s == undefined) s = 1.70158;
				return c*(t/=d)*t*((s+1)*t - s) + b;
			},

			easeOut : function(t, b, c, d, s) {
				if (s == undefined) s = 1.70158;
				return c*((t=t/d-1)*t*((s+1)*t + s) + 1) + b;
			},

			easeInOut : function(t, b, c, d, s) {
				if (s == undefined) s = 1.70158; 
				if ((t/=d/2) < 1) return c/2*(t*t*(((s*=(1.525))+1)*t - s)) + b;
				return c/2*((t-=2)*t*(((s*=(1.525))+1)*t + s) + 2) + b;
			}
		},

		bounce : {
			easeOut : function(t, b, c, d) {
				if ((t/=d) < (1/2.75)) {
					return c*(7.5625*t*t) + b;
				} else if (t < (2/2.75)) {
					return c*(7.5625*(t-=(1.5/2.75))*t + .75) + b;
				} else if (t < (2.5/2.75)) {
					return c*(7.5625*(t-=(2.25/2.75))*t + .9375) + b;
				} else {
					return c*(7.5625*(t-=(2.625/2.75))*t + .984375) + b;
				}
			},

			easeIn : function(t, b, c, d) {
				return c - this.easeOut (d-t, 0, c, d) + b;
			},

			easeInOut : function(t, b, c, d) {
				if (t < d/2) return this.easeIn (t*2, 0, c, d) * .5 + b;
				else return this.easeOut (t*2-d, 0, c, d) * .5 + c*.5 + b;
			}
		},

		circ : {
			easeIn : function(t, b, c, d) {
				return -c * (Math.sqrt(1 - (t/=d)*t) - 1) + b;
			},

			easeOut : function(t, b, c, d) {
				return c * Math.sqrt(1 - (t=t/d-1)*t) + b;
			},

			easeInOut : function(t, b, c, d) {
				if ((t/=d/2) < 1) return -c/2 * (Math.sqrt(1 - t*t) - 1) + b;
				return c/2 * (Math.sqrt(1 - (t-=2)*t) + 1) + b;
			}
		},

		cubic : {
			easeIn : function(t, b, c, d) {
				return c*(t/=d)*t*t + b;
			},

			easeOut : function(t, b, c, d) {
				return c*((t=t/d-1)*t*t + 1) + b;
			},

			easeInOut : function(t, b, c, d) {
				if ((t/=d/2) < 1) return c/2*t*t*t + b;
				return c/2*((t-=2)*t*t + 2) + b;
			}
		},

		elastic : {
			easeIn : function(t, b, c, d, a, p) {
				if (t==0) return b;  if ((t/=d)==1) return b+c;  if (!p) p=d*.3;
				if (!a || a < Math.abs(c)) { a=c; var s=p/4; }
				else var s = p/(2*Math.PI) * Math.asin (c/a);
				return -(a*Math.pow(2,10*(t-=1)) * Math.sin( (t*d-s)*(2*Math.PI)/p )) + b;
			},

			easeOut : function(t, b, c, d, a, p) {
				if (t==0) return b;  if ((t/=d)==1) return b+c;  if (!p) p=d*.3;
				if (!a || a < Math.abs(c)) { a=c; var s=p/4; }
				else var s = p/(2*Math.PI) * Math.asin (c/a);
				return (a*Math.pow(2,-10*t) * Math.sin( (t*d-s)*(2*Math.PI)/p ) + c + b);
			},

			easeInOut : function(t, b, c, d, a, p) {
				if (t==0) return b;  if ((t/=d/2)==2) return b+c;  if (!p) p=d*(.3*1.5);
				if (!a || a < Math.abs(c)) { a=c; var s=p/4; }
				else var s = p/(2*Math.PI) * Math.asin (c/a);
				if (t < 1) return -.5*(a*Math.pow(2,10*(t-=1)) * Math.sin( (t*d-s)*(2*Math.PI)/p )) + b;
				return a*Math.pow(2,-10*(t-=1)) * Math.sin( (t*d-s)*(2*Math.PI)/p )*.5 + c + b;
			}
		},

		expo : {
			easeIn : function(t, b, c, d) {
				return (t==0) ? b : c * Math.pow(2, 10 * (t/d - 1)) + b;
			},

			easeOut : function(t, b, c, d) {
				return (t==d) ? b+c : c * (-Math.pow(2, -10 * t/d) + 1) + b;
			},

			easeInOut : function(t, b, c, d) {
				if (t==0) return b;
				if (t==d) return b+c;
				if ((t/=d/2) < 1) return c/2 * Math.pow(2, 10 * (t - 1)) + b;
				return c/2 * (-Math.pow(2, -10 * --t) + 2) + b;
			}
		},

		linear : {
			easeNone : function(t, b, c, d) {
				return c*t/d + b;
			},

			easeIn : function(t, b, c, d) {
				return c*t/d + b;
			},

			easeOut : function(t, b, c, d) {
				return c*t/d + b;
			},

			easeInOut : function(t, b, c, d) {
				return c*t/d + b;
			}
		},

		quad : {
			easeIn : function(t, b, c, d) {
				return c*(t/=d)*t + b;
			},

			easeOut : function(t, b, c, d) {
				return -c *(t/=d)*(t-2) + b;
			},

			easeInOut : function(t, b, c, d) {
				if ((t/=d/2) < 1) return c/2*t*t + b;
				return -c/2 * ((--t)*(t-2) - 1) + b;
			}
		},

		quart : {
			easeIn : function(t, b, c, d) {
				return c*(t/=d)*t*t*t + b;
			},

			easeOut : function(t, b, c, d) {
				return -c * ((t=t/d-1)*t*t*t - 1) + b;
			},

			easeInOut : function(t, b, c, d) {
				if ((t/=d/2) < 1) return c/2*t*t*t*t + b;
				return -c/2 * ((t-=2)*t*t*t - 2) + b;
			}
		},

		quint : {
			easeIn : function(t, b, c, d) {
				return c*(t/=d)*t*t*t*t + b;
			},

			easeOut : function(t, b, c, d) {
				return c*((t=t/d-1)*t*t*t*t + 1) + b;
			},

			easeInOut : function(t, b, c, d) {
				if ((t/=d/2) < 1) return c/2*t*t*t*t*t + b;
				return c/2*((t-=2)*t*t*t*t + 2) + b;
			}
		},

		sine : {
			easeIn : function(t, b, c, d) {
				return -c * Math.cos(t/d * (Math.PI/2)) + c + b;
			},

			easeOut : function(t, b, c, d) {
				return c * Math.sin(t/d * (Math.PI/2)) + b;
			},

			easeInOut : function(t, b, c, d) {
				return -c/2 * (Math.cos(Math.PI*t/d) - 1) + b;
			}
		}

		/**#@-*/
	});
});
