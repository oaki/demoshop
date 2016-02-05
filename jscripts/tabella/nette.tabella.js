	/* TODO: how to create an object in jQuery? something like
	 * $.tabella.currentUrl = '';
	 * $("#foo").tabella.fade();
	 */
	 
	$(document).ready( function() {
		$(".tabella .dateFilter").tabellaDatePicker();
		
		$(".tabella a.ajax").live( "click", function() {
			$(this).tabellaFadeBody();
		});
		
		$(".tabella .filter").live( "change", function() {
			$(this).tabellaFadeBody();
			focused = $(this).attr("name");
			name = $(this).tabellaEl().attr("name");

			filters = "?do="+name+"-reset&";

			$(".tabella .filter").each( function() {
				filters += name+"-filter["+$(this).attr("name")+"]="+encodeURIComponent($(this).val())+"&";
			});
			
			$.get( window.location.pathname+filters, null, function( payload ) {
				$.nette.success( payload );
				$("div[name='"+name+"']").find( "input[name='"+focused+"']" ).focus();
			});
		});
		
		// bindings for inline editing
		$(".tabella .editable").live( "click", function() {
			// starting the edition
			row = $(this).parents("tr");
			if( !row.hasClass( "edited" ) ) {
				row.tabellaFinishEdit();
				row.tabellaStartEdit();
				$(this).find("input, select, textarea").focus().click().click();
			}
		});
		
		$(".tabella .button").live( "click", function() {
			row = $(this).parents( "tr" );
			if( $(this).hasClass( "save" ) ) {
				row.tabellaFade();
				var data = "";
				
				// tabella name
				var name = $(this).tabellaName();
				
				// creating the request
				var payload = new Object();
				row.find( "input, textarea, select" ).each( function() {
					var pom = $(this).attr("name");
					
					var key = name + "-" + pom;
					payload[key] = $(this).val();
				});
				// saving the inline edit
				$.post( $(this).tabellaEl().attr("data-submit-url"), payload );				
			} else {
				if( row.attr( "data-id" ) == 0 ) {
					row.tabellaEl().find( ".delete" ).show();
					row.remove();
				}
			}
			// removing the inline edit elements
			row.tabellaFinishEdit();
		});
		
		$(".tabella .delete").live( "click", function() {
			
			tr = $(this).parents("tr");
			tr.tabellaFade();
			if( !confirm( "Naozaj chcete položku vymazať?" ) ) {
				tr.css( "opacity", "1" );
				return;
			}
			$.post( $(this).tabellaEl().attr( "data-submit-url" ), 
						$(this).tabellaName()+'-deleteId='+tr.attr("data-id") );
		});
		$(".tabella .add").live( "click", function() {
			tr = $("<tr data-id=0>");
			tabellaParams = eval( $(this).tabellaName()+"Params" );
			console.log( tabellaParams );
			$.each( tabellaParams["cols"], function( key, val ) {
				td = $("<td>");

				params = val["params"];
				
				td.attr("data-format", params[params['type']+"Format"]);
				td.css("width", params['width']+"px");
				if( params["type"] == "delete" )
					return;
				$.each( params["class"], function( key, cl ) {
					td.addClass( cl );
				});
				if( params["editable"] )
					td.addClass( "editable" );
				td.attr( "data-type", params["type"] )
				  .attr( "data-name", val["colName"] );

				tr.append( td );
			});
			tr.prependTo( $(this).tabellaEl().find(".tabella-body") );
			tr.tabellaStartEdit();	
		});
	 });
  	jQuery.fn.extend({
//		tabella: {
			tabellaName: function() {
				return $(this).tabellaEl().attr("name");
			},
			tabellaEl: function() {
				return $(this).parents(".tabella");
			},
			tabellaFadeBody: function() {
				$(this).tabellaEl().find(".tabella-body").tabellaFade();
			},
			tabellaFade: function() {
				$(this).css( "opacity", "0.5" );
			},
			tabellaStartEdit: function() {
				$(this).addClass( "edited" );
				$(this).tabellaEl().find( ".delete" ).hide();
				$(this).find( ".editable" ).each( function() {
					var cell;
					tabellaParams = eval( $(this).tabellaName()+"Params" );
					switch( $(this).attr("data-type") ) {
						case "text": 
							cell = $("<input type=text>");
							break;
						case "date":
							cell = $("<input type=text>")
										.attr( "name", $(this).attr( "data-name" ) )
										.val( $(this).text() );
							$(this).html( cell );
							cell.css( "width", ($(this).css("width").match(/\d+/)[0]*1+4)+"px !important" );
							cell.tabellaDatePicker();
							cell = null;
							break;
						case "select":
							cell = $("<select>");
							$.each( tabellaParams["cols"][$(this).attr("data-name")]["params"]["options"], 
								function( key, val ) {
									cell.append( $("<option>").attr("value",key).html(val) );
								});
							break;
					}
					if( cell ) {		
						cell.attr( "name", $(this).attr( "data-name" ) )
							.val( $(this).attr("data-editable") );	
						$(this).html( cell );
						cell.css( "width", ($(this).css("width").match(/\d+/)[0]*1+4)+"px !important" );
					}
				});
				$(this).find( ".editable:first" ).append( $("<input name=id type=hidden>").attr("value", $(this).attr( "data-id" ) ) );
				$(this).append( '<td class="button save"></td><td class="button cancel"></td>' );
			},
			tabellaFinishEdit: function() {
				$(this).tabellaEl().find( ".delete" ).show();
				$(this).parent().find(".edited").each( function() {
					$(this).removeClass("edited");
					$(this).find( ".button" ).remove();
					$(this).find( ".editable" ).each( function() {
						$(this).html( $(this).attr( "data-shown" ) );
					});
				});
			},
			// function to run the date picker tool
			tabellaDatePicker: function() {
				format = $(this).parent().attr("data-format");
				if(!format){
					return false
				}
		        $.dpText = {
					TEXT_PREV_MONTH		:	'',
					TEXT_NEXT_MONTH		:	'',
					TEXT_CLOSE			:	'',
					TEXT_CHOOSE_DATE	:	'',
					HEADER_FORMAT	    :   'mmmm yyyy'
				}
				
		      	Date.firstDayOfWeek = 1;
		      	format = format.replace( '%d', 'dd' )
		      		  .replace( '%m', 'mm' )
		      		  .replace( '%y', 'yy' )
		      		  .replace( '%Y', 'yyyy' );
		      	if( format.match(/yyyy/) ) {
			      	d = new Date( "01/01/1970" );			
		      	} else {
			      	d = new Date( "01/01/2000" );			
		      	}
				Date.format = format;
				d = d.asString();
				$(this).datePicker({ 
					clickInput: true,
					startDate: d,
		      		createButton: false
		      	}).bind('focus', function(event, message) {
					if (message == $.dpConst.DP_INTERNAL_FOCUS) 
						return true;
					var dp = this;
					var $dp = $(this);
					$dp.dpDisplay();
					$('*').bind( 'focus.datePicker', function(event) {
						var $focused = $(this);
						if (!$focused.is('.dp-applied')) { // don't close the focused date picker if we just opened a new one!
		 
							// if the newly focused element isn't inside the date picker and isn't the original element which triggered
							// the opening of the date picker
		 
							if ($focused.parents('#dp-popup').length == 0 && this != dp && !($.browser.msie && this == document.body)) {
								$('*').unbind('focus.datePicker');
								$dp.dpClose();
							}
						}
					});
					return false;
				}).bind( 'dpClosed', function(event, selected) {
					$('*').unbind('focus.datePicker');
				});
				
			}
//		}
	});