	/* TODO: how to create an object in jQuery? something like
	 * $.tabella.currentUrl = '';
	 * $("#foo").tabella.fade();
	 */
	 
	$(document).ready( function() {
		$(".tabella a.ajax").live( "click", function() {
//			$(this).tabellaEl().attr( "data-url", $(this).attr("href") );
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
				// remembering current url
//				$.tabellaEl().attr("data-url", window.location.pathname+filters );
				$.nette.success( payload );
				$("div[name='"+name+"']").find( "input[name='"+focused+"']" ).focus();//.setCursorPosition( cursorPosition );
			});
		});
		
		// bindings for inline editing
		$(".tabella .editable").live( "click", function() {
			// starting the edition
			row = $(this).parents("tr");
			if( !row.hasClass( "edited" ) ) {
				row.tabellaFinishEdit();
				row.tabellaStartEdit();
				$(this).find("input").focus();
			}
		});
		
		$(".tabella .button").live( "click", function() {
			row = $(this).parents( "tr" );
			if( $(this).hasClass( "save" ) ) {
				row.tabellaFade();
				var data = "";
				
				// tabella name
				var name = $(this).tabellaEl().attr("name");
				
				// creating the request
				row.find( "input, textarea, select" ).each( function() {
					data += name+"-"+$(this).attr("name")+"="+$(this).val() +"&";
				});
				
				// saving the inline edit
				$.post( $(this).tabellaEl().attr("data-submit-url"), data );				
			}
			// removing the inline edit elements
			row.tabellaFinishEdit();
		});
	});
	  	
  	jQuery.fn.extend({
//		tabella: {
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
				$(this).find( ".editable" ).each( function() {
					var cell;
					switch( $(this).attr("data-type") ) {
						case "text": 
							cell = $("<input type=text>");
							break;
						case "select":
							cell = $("<select>");
							$.each( tabellaParams[$(this).tabellaEl().attr("name")]['columnInfo'][$(this).attr("data-name")], 
								function( key, val ) {
									cell.append( $("<option>").attr("value",key).html(val) );
								});
							break;
					}
					if( cell ) {
					
					cell.attr( "name", $(this).attr( "data-name" ) ).val( $(this).attr("data-editable") );
					$(this).html( cell );
					}
				});
				$(this).find( ".editable:first" ).append( $("<input name=id type=hidden>").attr("value", $(this).attr( "data-id" ) ) );
				$(this).append( '<td class="button save"></td><td class="button cancel"></td>' );
			},
			tabellaFinishEdit: function() {
				$(this).parent().find(".edited").each( function() {
					$(this).removeClass("edited");
					$(this).find( ".button" ).remove();
					$(this).find( ".editable" ).each( function() {
						$(this).html( $(this).attr( "data-shown" ) );
					});
				});
			}
//		}
	});