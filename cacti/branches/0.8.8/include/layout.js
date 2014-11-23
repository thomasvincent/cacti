/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2014 The Cacti Group                                 |
 |                                                                         |
 | This program is free software; you can redistribute it and/or           |
 | modify it under the terms of the GNU General Public License             |
 | as published by the Free Software Foundation; either version 2          |
 | of the License, or (at your option) any later version.                  |
 |                                                                         |
 | This program is distributed in the hope that it will be useful,         |
 | but WITHOUT ANY WARRANTY; without even the implied warranty of          |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the           |
 | GNU General Public License for more details.                            |
 +-------------------------------------------------------------------------+
 | Cacti: The Complete RRDTool-based Graphing Solution                     |
 +-------------------------------------------------------------------------+
 | This code is designed, written, and maintained by the Cacti Group. See  |
 | about.php and/or the AUTHORS file for specific developer information.   |
 +-------------------------------------------------------------------------+
 | http://www.cacti.net/                                                   |
 +-------------------------------------------------------------------------+
*/

var theme;

/** basename - this function will return the basename
 *  of the php script called
 *  @args path - the document.url
 *  @args suffix - remove the named suffix from the file */
function basename(path, suffix) {
	var b = path;
	var lastChar = b.charAt(b.length - 1);

	if (lastChar === '/' || lastChar === '\\\\') {
		b = b.slice(0, -1);
	}

	b = b.replace(/^.*[\\/\\\\]/g, '');

	if (typeof suffix === 'string' && b.substr(b.length - suffix.length) == suffix) {
		b = b.substr(0, b.length - suffix.length);
	}

	return b;
}

/** getQueryString - this function will return the value
 *  of the get request variable defined as input.
 *  @args name - the variable name to return */
function getQueryString(name) {
	var match = RegExp('[?&]' + name + '=([^&]*)').exec(window.location.search);
	return match && decodeURIComponent(match[1].replace(/\+/g, ' '));
}

/** applySelectorVisibility - This function set's the initial visibility
 *  of graphs for creation. Is will scan the against preset variables
 *  taking action as required to enable or disable rows. */
function applySelectorVisibilityAndActions() {
	// Apply disabled/enabled status first for Graph Templates
	$('tr[id^=gt_line]').each(function(data) {
		var id = $(this).attr('id');
		var search = id.substr(7);
		//console.log('The id is : '+id+', The search is : '+search);
		if ($.inArray(search, gt_created_graphs) >= 0) {
			//console.log('The id is : '+id+', The search is : '+search+', Result found');
			$(this).addClass('disabled');
			$(this).css('color', '#999999');
			$(this).find(':checkbox').prop('disabled', true);
		}
	});

	// Create Actions for Rows
	$('tr.selectable').find('td').not('.checkbox').each(function(data) {
		if (!$(this).parent().hasClass('disabled')) {
			$(this).click(function(data) {
				$(this).parent().toggleClass('selected');
				var $checkbox = $(this).parent().find(':checkbox');
				$checkbox.prop('checked', !$checkbox.is(':checked'));
			});
		}
	});

	// Create Actions for Checkboxes
	$('tr.selectable').find('.checkbox').click(function(data) {
		if (!$(this).is(':disabled')) {
			$(this).parent().toggleClass('selected');
			var checked = $(this).is(':checkbox');
			$(this).prop('checked', checked);
		}
	});
}

/** dqUpdateDeps - When a user changes the Graph dropdown for a data query
 *  we have to check to see if those graphs are already created.
 *  @arg snmp_query_id - The snmp query id the is current */
function dqUpdateDeps(snmp_query_id) {
	dqResetDeps(snmp_query_id);

	var snmp_query_graph_id = $('#sgg_'+snmp_query_id).val();

	// Next for Data Queries
	$('tr[id^=line'+snmp_query_id+'_]').each(function(data) {
		var id = $(this).attr('id');
		var pieces = id.split('_');
		var dq = pieces[0].substr(4);
		var hash = pieces[1];

		if ($.inArray(hash, created_graphs[snmp_query_graph_id]) >= 0) {
			$(this).addClass('disabled');
			$(this).find(':checkbox').prop('disabled', true);
		}
	});
}

/** dqResetDeps - This function will make all rows selectable.
 *  It is done just before a new data query is checked.
 *  @arg snmp_query_id - The snmp query id the is current */
function dqResetDeps(snmp_query_id) {
	var prefix = 'sg_' + snmp_query_id + '_'

	$('tr[id^=line'+snmp_query_id+'_]').removeClass('disabled').removeClass('selected').find(':checkbox').prop('disabled', false);
}

/** SelectAll - This function will select all non-disabled rows
 *  @arg attrib - The Graph Type either graph template, or data query */
function SelectAll(attrib, checked) {
	//console.log("Im Here the attrib is "+attrib);
	if (attrib == 'sg') {
		if (checked == true) {
			$('tr[id^=gt_line]').each(function(data) {
				if (!$(this).hasClass('disabled')) {
					$(this).addClass('selected');
					$(this).find(':checkbox').prop('checked', true);
				}
			});
		}else{
			$('tr[id^=gt_line]').each(function(data) {
				if (!$(this).hasClass('disabled')) {
					$(this).removeClass('selected');
					$(this).find(':checkbox').prop('checked', false);
				}
			});
		}
	}else{
		var attribSplit = attrib.split('_');
		var dq = attribSplit[1];

		if (checked == true) {
			$('tr[id^=line'+dq+'_]').each(function(data) {
				if (!$(this).hasClass('disabled')) {
					$(this).addClass('selected');
					$(this).find(':checkbox').prop('checked', true);
				}
			});
		}else{
			$('tr[id^=line'+dq+'_]').each(function(data) {
				if (!$(this).hasClass('disabled')) {
					$(this).removeClass('selected');
					$(this).find(':checkbox').prop('checked', false);
				}
			});
		}
	}
}

/** SelectAllGraphs - Right now, need to remediate...
 *  It is done just before a new data query is checked.
 *  @arg prefix - Ok, prefix */
function SelectAllGraphs(prefix, checkbox_state) {
	for (var i = 0; i < document.graphs.elements.length; i++) {
		if ((document.graphs.elements[i].name.substr(0, prefix.length) == prefix) && (document.graphs.elements[i].style.visibility != 'hidden')) {
			document.graphs.elements[i].checked = checkbox_state;
		}
	}
}

/* calendar stuff */
// Initialize the calendar
var calendar=null;

// This function displays the calendar associated to the input field 'id'
function showCalendar(id) {
	var el = document.getElementById(id);
	if (calendar != null) {
		// we already have some calendar created
		calendar.hide();  // so we hide it first.
	} else {
		// first-time call, create the calendar.
		var cal = new Calendar(true, null, selected, closeHandler);
		cal.weekNumbers = false;  // Do not display the week number
		cal.showsTime = true;     // Display the time
		cal.time24 = true;        // Hours have a 24 hours format
		cal.showsOtherMonths = false;    // Just the current month is displayed
		calendar = cal;                  // remember it in the global var
		cal.setRange(1900, 2070);        // min/max year allowed.
		cal.create();
	}

	calendar.setDateFormat('%Y-%m-%d %H:%M');    // set the specified date format
	calendar.parseDate(el.value);                // try to parse the text in field
	calendar.sel = el;                           // inform it what input field we use

	// Display the calendar below the input field
	calendar.showAtElement(el, 'Br');        // show the calendar

	return false;
}

// This function update the date in the input field when selected
function selected(cal, date) {
	cal.sel.value = date;      // just update the date in the input field.
}

// This function gets called when the end-user clicks on the 'Close' button.
// It just hides the calendar without destroying it.
function closeHandler(cal) {
	cal.hide();                        // hide the calendar
	calendar = null;
}

/* graph filtering */
function applyTimespanFilterChange(objForm) {
	strURL = '?predefined_timespan=' + objForm.predefined_timespan.value;
	strURL = strURL + '&predefined_timeshift=' + objForm.predefined_timeshift.value;
	document.location = strURL;
}

function cactiReturnTo(location) {
	if (location != '') {
		document.location = location;
	}else{
		document.history.back();
	}
}

function applySkin() {
	if (!theme || theme == 'classic') {
		theme = 'classic';
	}else{
		$('input[type=submit], input[type=button]').button();
	}

	// Select All Action for everyone but graphs_new, else do ugly shit
	if (basename(document.location.pathname, '.php') != 'graphs_new') {
		$('.tableSubHeaderCheckbox').find(':checkbox').click(function(data) {
			if ($(this).is(':checked')) {
				$('input[id^=chk_]').prop('checked',true);
				$('tr.selectable').addClass('selected');
			}else{
				$('input[id^=chk_]').prop('checked',false);
				$('tr.selectable').removeClass('selected');
			}
		});

		// Allows selection of a non disabled row
		$('tr.selectable').find('td').not('.checkbox').each(function(data) {
			$(this).click(function(data) {
				$(this).parent().toggleClass('selected');
				var $checkbox = $(this).parent().find(':checkbox');
				$checkbox.prop('checked', !$checkbox.is(':checked'));
			});
		});

		// Generic Checkbox Function
		$('tr.selectable').find('.checkbox').click(function(data) {
			if (!$(this).is(':disabled')) {
				$(this).parent().toggleClass('selected');
				var checked = $(this).is(':checkbox');
				$(this).prop('checked', !checked);
			}
		});
	}else{
		applySelectorVisibilityAndActions();
	}
}

$(function() {
	applySkin();

	var tablesInit = new Array;

	$('#navigation').css('height', ($(window).height())+'px');
	$(window).resize(function() {
		$('#navigation').css('height', ($(window).height())+'px');
	});

	$('.tableHeader th').resizable({
		handles: 'e',

		start: function(event, ui) {
			var page   = basename(location.pathname, '.php');
			var action = getQueryString('action');
			if (action == null) {
				action    = getQueryString('tab');
			}
			var table  = $(this).parentsUntil('table[id^=cactiTable]').parent().attr('id');
			var key    = page+'_'+table+'_'+action;

			// See if sizes have been loaded
			var found = false;
			tablesInit.forEach(function(entry) {
				if (entry == key) {
					found = true;
				}
			});

			if (!found) {
				var sizes = $.cookie(table);
				var items = sizes ? sizes.split(/,/) : new Array();

				var i = 0;
				$(this).parent().find('th').each(function(data) {
					$(this).css('width', items[i]+'px');
				});
			}

			colWidth     = $(this).width();
			originalSize = ui.size.width;
			originalSize = $(this).width();
		 },
 
		resize: function(event, ui) {
			var resizeDelta = ui.size.width - originalSize;
			var newColWidth = colWidth + resizeDelta;
			$(this).css('height', 'auto');
		},

		stop: function(event, ui) {
			var page   = basename(location.pathname, '.php');
			var action = getQueryString('action');
			if (action == null) {
				action    = getQueryString('tab');
			}
			var table  = $(this).parentsUntil('table[id^=cactiTable]').parent().attr('id');
			var key    = page+'_'+table+'_'+action;
			var sizes  = new Array;
			var i = 0;
			$(this).parent().find('th').each(function(data) {
				//console.log('On page '+page+', of Index '+table+', with Column '+i+' with width '+$(this).width()+' wide');
				sizes[i] = $(this).width();
				i++;
			});
			$.cookie(key, sizes, { expires: 31, path: '/cacti/' } );
		}
	});

	// Initialize table width on the page
	$('.cactiTable').each(function(data) {
		var page   = basename(location.pathname, '.php');
		var action = getQueryString('action');
		if (action == null) {
			action    = getQueryString('tab');
		}
		var table  = $(this).attr('id');
		var key    = page+'_'+table+'_'+action;
		var sizes  = $.cookie(key);
		var items  = sizes ? sizes.split(/,/) : new Array();
		//console.log('Setting sizes for '+table+', with sizes: '+sizes);

		var i = 0;
		if (table !== undefined) {
			if (items.length) {
				$(this).find('th').each(function(data) {
					$(this).css('width', items[i]+'px');
					i++;
				});
			}else{
				var sizes = new Array();
				$(this).find('th').each(function(data) {
					sizes[i] = $(this).css('width');
					i++;
				});

				if (i > 0) {
					$.cookie(key, sizes, { expires: 31, path: '/cacti/' } );
				}
			}
		}
	});
});

