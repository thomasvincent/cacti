/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2012 The Cacti Group                                 |
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

/* requirements:
	jQuery 1.7.x or above
	jQuery UI 1.8.x or above
*/

(function($){
	$.fn.zoom = function(options) {

		/* +++++++++++++++++++++++ Global Variables +++++++++++++++++++++++++ */

		// default values of the different options being offered
		var defaults = {
			inputfieldStartTime	: '',                                           // ID of the input field that contains the start date
			inputfieldEndTime	: '',                                           // ID of the input field that contains the end date
			submitButton		: 'button_refresh_x',                           // ID of the submit button
			cookieName			: 'cacti_zoom'                                  // default name required for session cookie
		};

		// define global variables / objects here
		var zoom = {
			// "image" means the image tag and its properties
			image: { top:0, left:0, width:0, height:0 },
			// "graph" stands for the rrdgraph itself excluding legend, graph title etc.
			graph: { timespan:0, secondsPerPixel:0 },
			// "box" describes the area in front of the graph whithin jQueryZoom will allow interaction
			box: { top:0, left:0, right:0, width:0, height:0 },
			// "markers" are selectors useable within the advanced mode
			marker: { 1 : {}, 2 : {} },
			// "custom" holds the local configuration done by the user
			custom: {},
			// "options" contains the start input parameters
			options: $.extend(defaults, options),
			// "attributes" holds all values that will describe the selected area
			attr: { start:'none', end:'none', action:'left2right', location: window.location.href.split("?") }
		};


		/* ++++++++++++++++++++++++ Initialization ++++++++++++++++++++++++++ */

		// use a cookie to support local settings
		zoom.custom =  $.cookie(zoom.options.cookieName) ? unserialize( $.cookie(zoom.options.cookieName) ) : {};
		if(zoom.custom.zoomMode == undefined) zoom.custom.zoomMode = 'quick';
		if(zoom.custom.zoomOutPositioning == undefined) zoom.custom.zoomOutPositioning = 'center';
		if(zoom.custom.zoomOutFactor == undefined) zoom.custom.zoomOutFactor = '2';
		if(zoom.custom.zoomMarkers == undefined) zoom.custom.zoomMarkers = true;
		if(zoom.custom.zoomTimestamps == undefined) zoom.custom.zoomTimestamps = true;
		if(zoom.custom.zoom3rdMouseButton == undefined) zoom.custom.zoom3rdMouseButton = false;

		// create or update a session cookie
		$.cookie( zoom.options.cookieName, serialize(zoom.custom), {expires: null} );

		// support jQuery's concatination
		return this.each(function() { zoom_init( $(this) );	});


		/* ++++++++++++++++++++++++ Functions +++++++++++++++++++++++++++++++ */

		/* init zoom */
		function zoom_init(image) {
			var $this = image;
			$this.mouseenter(
				function(){
					zoomFunction_init($this);
				}
			);
		}


		/* check if image has been already loaded or if broken */
		function isReady(image){
			if(typeof image[0].naturalWidth !== undefined && image[0].naturalWidth == 0) {
				return false;
			}
			// support older versions of IE(6-8)
			if(!image[0].complete) {
				return false;
			}
			return true;
		}


		function zoomFunction_init(image) {

			var $this = image;

			// exit if image has not been already loaded or if image is not available
			if(isReady($this)) {
				// update zoom.image object with the attributes of this image
				zoom.image.width	= parseInt($this.width());
				zoom.image.height	= parseInt($this.height());
				zoom.image.top		= parseInt($this.offset().top);
				zoom.image.left		= parseInt($this.offset().left);
			}else {
				return;
			}

			// get all graph parameters and merge results with zoom.graph object
			$.extend(zoom.graph, getUrlVars( $this.attr("src") ));
			zoom.graph.timespan			= zoom.graph.end - zoom.graph.start;
			zoom.graph.secondsPerPixel 	= zoom.graph.timespan/zoom.graph.width;

			if((zoom.graph.title_font_size <= 0) || (zoom.graph.title_font_size == "")) {
				zoom.graph.title_font_size = 10;
			}

			if(zoom.graph.nolegend != undefined) {
				zoom.graph.title_font_size	*= .70;
			}

			// update all zoom box attributes. Unfortunately we have to use that best fit way
			// to support RRDtool 1.2 and below. With RRDtool 1.3 or higher there would be a
			// much more elegant solution available. (see RRDdtool graph option "graphv")
			zoom.box.width		= zoom.graph.width;
			zoom.box.height		= zoom.graph.height;

			if(zoom.graph.title_font_size == null) {
				zoom.box.top = 32 - 1;
			}else {
				//default multiplier
				var multiplier = 2.4;
				// array of "best fit" multipliers
				multipliers = new Array("-5", "-2", "0", "1.7", "1.6", "1.7", "1.8", "1.9", "2", "2", "2.1", "2.1", "2.2", "2.2", "2.3", "2.3", "2.3", "2.3", "2.3");
				if(multipliers[Math.round(zoom.graph.title_font_size)] != null) {
					multiplier = multipliers[Math.round(zoom.graph.title_font_size)];
				}
				zoom.box.top = zoom.image.top + parseInt(Math.abs(zoom.graph.title_font_size) * multiplier) + 15;
			}

			zoom.box.bottom = zoom.box.top + zoom.box.height;
			zoom.box.right	= zoom.image.left + zoom.image.width - 30;
			zoom.box.left	= zoom.box.right - zoom.graph.width;

			// add all additional HTML elements to the DOM if necessary and register
			// the individual events needed. Once added we will only reset
			// and reposition these elements.

			// add the "zoomBox"
			if($("#zoomBox").length == 0) {
				// Please note: IE does not fire hover or click behaviors on completely transparent elements.
				// Use a background color and set opacity to 1% as a workaround.(see CSS file)
				$("<div id='zoomBox'></div>").appendTo("body");
			}
			$("#zoomBox").off().css({ cursor:'crosshair', width:zoom.box.width + 'px', height:zoom.box.height + 'px', top:zoom.box.top+'px', left:zoom.box.left+'px' });
			$("#zoomBox").bind('contextmenu', function(e) { zoomContextMenu_show(e); return false;} );

			// add the "zoomSelectedArea"
			if($("#zoomArea").length == 0) {
				$("<div id='zoomArea'></div>").appendTo("body");
			}
			$("#zoomArea").off().css({ top:zoom.box.top+'px', height:zoom.box.height+'px' });

			// add two markers for the advanced mode
			if($("#zoom-marker-1").length == 0) {
				$('<div id="zoom-excluded-area-1" class="zoomExcludedArea"></div>').appendTo("body");
				$('<div class="zoom-marker" id="zoom-marker-1"><div class="zoom-marker-arrow-down"></div><div class="zoom-marker-arrow-up"></div></div>').appendTo("body");
				$('<div id="zoom-marker-tooltip-1" class="zoom-marker-tooltip"><div id="zoom-marker-tooltip-1-arrow-left" class="test-arrow-left"></div><span id="zoom-marker-tooltip-value-1" class="zoom-marker-tooltip-value">-</span><div id="zoom-marker-tooltip-1-arrow-right" class="test-arrow-right"></div></div>').appendTo('body');
			}
			if($("#zoom-marker-2").length == 0) {
				$('<div id="zoom-excluded-area-2" class="zoomExcludedArea"></div>').appendTo("body");
				$('<div class="zoom-marker" id="zoom-marker-2"><div class="zoom-marker-arrow-down"></div><div class="zoom-marker-arrow-up"></div></div>').appendTo("body");
				$('<div id="zoom-marker-tooltip-2" class="zoom-marker-tooltip"><div id="zoom-marker-tooltip-2-arrow-left" class="test-arrow-left"></div><span id="zoom-marker-tooltip-value-2" class="zoom-marker-tooltip-value">-</span><div id="zoom-marker-tooltip-2-arrow-right" class="test-arrow-right"></div></div>').appendTo('body');
			}
			$(".zoom-marker-arrow-up").css({ top:(zoom.box.height-6) + 'px' });

			// add right click menu if not being defined so far
			if($("#zoom-menu").length == 0) {
				$('<div id="zoom-menu" class="zoom-menu">'
					+ '<div class="first_li">'
					+ 		'<div class="ui-icon ui-icon-zoomin"></div><span>Zoom In</span>'
					+ '</div>'
					+ '<div class="first_li">'
					+ 		'<div class="ui-icon ui-icon-zoomout"></div>'
					+ 		'<span class="zoomContextMenuAction__zoom_out">Zoom Out (2x)</span>'
					+ 		'<div class="inner_li advanced_mode">'
					+ 			'<span class="zoomContextMenuAction__zoom_out__2">2x</span>'
					+ 			'<span class="zoomContextMenuAction__zoom_out__4">4x</span>'
					+ 			'<span class="zoomContextMenuAction__zoom_out__8">8x</span>'
					+ 			'<span class="zoomContextMenuAction__zoom_out__16">16x</span>'
					+ 			'<span class="zoomContextMenuAction__zoom_out__32">32x</span>'
					+ 		'</div>'
					+ '</div>'
					+ '<div class="sep_li"></div>'
					+ '<div class="first_li">'
					+ 		'<div class="ui-icon ui-icon-empty"></div><span>Zoom Mode</span>'
					+ 		'<div class="inner_li">'
					+ 			'<span class="zoomContextMenuAction__set_zoomMode__quick">Quick</span>'
					+ 			'<span class="zoomContextMenuAction__set_zoomMode__advanced">Advanced</span>'
					+ 		'</div>'
					+ '</div>'
					+ '<div class="first_li advanced_mode">'
					+ 		'<div class="ui-icon ui-icon-wrench"></div><span>Settings</span>'
					+ 			'<div class="inner_li">'
					+ 				'<div class="sec_li"><span>Markers</span>'
					+ 					'<div class="inner_li advanced_mode">'
					+ 						'<span class="zoomContextMenuAction__set_zoomMarkers__on">Enabled</span>'
					+ 						'<span class="zoomContextMenuAction__set_zoomMarkers__off">Disabled</span>'
					+ 					'</div>'
					+				'</div>'
					+ 				'<div class="sec_li"><span>Timestamps</span></span>'
					+ 					'<div class="inner_li advanced_mode">'
					+ 						'<span class="zoomContextMenuAction__set_zoomTimestamps__on">Enabled</span>'
					+ 						'<span class="zoomContextMenuAction__set_zoomTimestamps__off">Disabled</span>'
					+ 					'</div>'
					+				'</div>'
					+ 				'<div class="sep_li"></div>'
					+ 				'<div class="sec_li"><span>Zoom Out Factor</span>'
					+ 					'<div class="inner_li advanced_mode">'
					+ 						'<span class="zoomContextMenuAction__set_zoomOutFactor__2">2x</span>'
					+ 						'<span class="zoomContextMenuAction__set_zoomOutFactor__4">4x</span>'
					+ 						'<span class="zoomContextMenuAction__set_zoomOutFactor__8">8x</span>'
					+ 						'<span class="zoomContextMenuAction__set_zoomOutFactor__16">16x</span>'
					+ 						'<span class="zoomContextMenuAction__set_zoomOutFactor__32">32x</span>'
					+ 					'</div>'
					+ 				'</div>'
					+ 				'<div class="sec_li"><span>Zoom Out Positioning</span>'
					+ 					'<div class="inner_li advanced_mode">'
					+ 						'<span class="zoomContextMenuAction__set_zoomOutPositioning__begin">Begin with</span>'
					+ 						'<span class="zoomContextMenuAction__set_zoomOutPositioning__center">Center</span>'
					+ 						'<span class="zoomContextMenuAction__set_zoomOutPositioning__end">End with</span>'
					+ 					'</div>'
					+				'</div>'
					+ 				'<div class="sec_li"><span>3rd Mouse Button</span>'
					+ 					'<div class="inner_li advanced_mode">'
					+ 						'<span class="zoomContextMenuAction__set_zoom3rdMouseButton__zoom_in">Zoom in</span>'
					+ 						'<span class="zoomContextMenuAction__set_zoom3rdMouseButton__zoom_out">Zoom out</span>'
					+ 						'<span class="zoomContextMenuAction__set_zoom3rdMouseButton__off">Disabled</span>'
					+ 					'</div>'
					+				'</div>'
					+ 			'</div>'
					+ 		'</div>'
					+ '<div class="sep_li"></div>'
					+ '<div class="first_li">'
					+ 		'<div class="ui-icon ui-icon-close"></div><span class="zoomContextMenuAction__close">Close</span>'
					+ '</div>').appendTo('body');
			}

			zoomContextMenu_init();

			init_ZoomAction(image);
		}


		/*
		* splits off the parameters of a given url
		*/
		function getUrlVars(url) {
			var parameters = [], name, value;

			urlBaseAndParameters = url.split("?");
			urlBase = urlBaseAndParameters[0];
			urlParameters = urlBaseAndParameters[1].split("&");
			parameters["urlBase"] = urlBase;

			for(var i=0; i<urlParameters.length; i++) {
				parameter = urlParameters[i].split("=");
				parameters[parameter[0].replace(/^graph_/, "")] = $.isNumeric(parameter[1]) ? +parameter[1] : parameter[1];
			}
			return parameters;
		}

		/**
		 *
		 * @access public
		 * @return void
		 **/
		function serialize(object){
			var str = "";
			for(var key in object) { str += (key + '=' + object[key] + ','); }
			return str.slice(0, -1);
		}

		/**
		 *
		 * @access public
		 * @return void
		 **/
		function unserialize(string){
			var obj = new Array();
			pairs = string.split(',');

			for(var i=0; i<pairs.length; i++) {
				pair = pairs[i].split("=");
				if(pair[1] == "true") {
					pair[1] = true;
				}else if(pair[1] == "false") {
					pair[1] = false;
				}
				obj[pair[0]] = pair[1];
			}
			return obj;
		}

		/*
		* registers all the different mouse click event handler
		*/
		function init_ZoomAction(image) {

			if(zoom.custom.zoomMode == 'quick') {

				$("#zoomBox").mousedown( function(e) {
					switch(e.which) {
						/* clicking the left mouse button will initiates a zoom-in */
						case 1:
							zoomContextMenu_hide();
							// reset the zoom area
							zoom.attr.start = e.pageX;
							if(zoom.custom.zoomMode != 'quick') {
								$("#zoom-marker-1").css({ height:zoom.box.height+'px', top:zoom.box.top+'px', left:zoom.attr.start+'px', display:'block' });
								$("#zoom-marker-tooltip-1").css({ top:zoom.box.top+'px', left:zoom.attr.start+'px'});
								//$(".zoom-marker-tooltip").css({ display:'block' });
							}
							$("#zoomBox").css({ cursor:'e-resize' });
							$("#zoomArea").css({ width:'0px', left:zoom.attr.start+'px' });
						break;
					}
				});

			/* register all mouse up events */
			$("#zoomBox").mouseup(function(e) {
				switch(e.which) {
					case 3:
						//zoomAction_zoom_out()();
					break;
				}
			});

			/* register all mouse up events */
			$("body").mouseup( function(e) {
				switch(e.which) {

					/* leaving the left mouse button will execute a zoom in */
					case 1:
						/* execute a simple click if the parent node is an anchor */
					//	if(image.parent().attr("href") !== undefined && zoom.attr.start == e.pageX) {
					//		open(image.parent().attr("href"), "_self");
					//		return false;
					//	}

						if(zoom.custom.zoomMode == 'quick' && zoom.attr.start != 'none') {
							dynamicZoom(image);
							//$("#zoom-marker-2").css({ height:zoom.box.height + 'px', top:zoom.box.top+'px', left:(zoom.box.left+parseInt(zoom.box.width)-1)+'px' });
						}
					break;
				}
			});

			/* stretch the zoom area in that direction the user moved the mouse pointer */
			$("#zoomBox").mousemove( function(e) { drawZoomArea(e) } );

			/* stretch the zoom area in that direction the user moved the mouse pointer.
			   That is required to get it working faultlessly with Opera, IE and Chrome	*/
			$("#zoomArea").mousemove( function(e) { drawZoomArea(e); } );

			/* moving the mouse pointer quickly will avoid that the mousemove event has enough time to actualize the zoom area */
			$("#zoomBox").mouseout( function(e) { drawZoomArea(e) } );














			}else{

				$("#zoomBox").off("mousedown").on("mousedown", function(e) {
					switch(e.which) {
						case 1:
							/* hide context menu if open */
							zoomContextMenu_hide();

							/* find out which marker has to be added */
							if($("#zoom-marker-1").is(":visible") && $("#zoom-marker-2").is(":visible")) {
								/* both markers are in - do nothing */
								return;
							}else {
								var marker = $("#zoom-marker-1").is(":hidden") ? 1 : 2;
								var secondmarker = (marker == 1) ? 2 : 1;
							}


							/* select marker */
							var $this = $("#zoom-marker-" + marker);

							/* place the marker and make it visible */
							$this.css({ height:zoom.box.height+'px', top:zoom.box.top+'px', left:e.pageX+'px', display:'block' });

							/* make the excluded areas visible directly in that moment both markers are set */
							if($("#zoom-marker-1").is(":visible") && $("#zoom-marker-2").is(":visible")) {
								zoom.marker[1].left		= $("#zoom-marker-1").position().left;
								zoom.marker[2].left		= $("#zoom-marker-2").position().left;
								zoom.marker.distance	= zoom.marker[1].left - zoom.marker[2].left;

								$("#zoom-excluded-area-1").css({
									height:zoom.box.height+'px',
									top:zoom.box.top+'px',
									left: (zoom.marker.distance > 0) ? zoom.marker[1].left : zoom.box.left,
									width: (zoom.marker.distance > 0) ? zoom.box.right - zoom.marker[1].left : zoom.marker[1].left - zoom.box.left,
									display:'block'
								});

								$("#zoom-excluded-area-2").css({
									height:zoom.box.height+'px',
									top:zoom.box.top+'px',
									left: (zoom.marker.distance < 0) ? zoom.marker[2].left : zoom.box.left,
									width: (zoom.marker.distance < 0) ? zoom.box.right - zoom.marker[2].left : zoom.marker[2].left - zoom.box.left,
									display:'block'
								});
							}


							/* make it draggable */
							$this.draggable({
								containment:[ zoom.box.left-1, 0 , zoom.box.left+parseInt(zoom.box.width), 0 ],
								axis: "x",
								start:
									function(event, ui) {
										$("#zoom-marker-tooltip-" + marker).css({ top: ( (marker == 1) ? zoom.box.top+3 : zoom.box.bottom-30 )+'px', left:ui.position["left"]+'px'}).fadeIn(250);
									},
								drag:
									function(event, ui) {
										zoom.marker[marker].left = ui.position["left"];

										/* update the timestamp shown in tooltip */
										$("#zoom-marker-tooltip-value-" + marker).html(
											unixTime2Date(parseInt(parseInt(zoom.graph.start) + (zoom.marker[marker].left + 1 - zoom.box.left)*zoom.graph.secondsPerPixel)).replace(" ", "<br>")
										);

										zoom.marker[marker].width = $("#zoom-marker-tooltip-" + marker).width();

										/* show the execludedArea if both markers are in */
										if($("#zoom-marker-" + marker).is(":visible") && $("#zoom-marker-" + secondmarker).is(":visible")) {
											zoom.marker.distance = $("#zoom-marker-" + marker).position().left - $("#zoom-marker-" + secondmarker).position().left;

											if( zoom.marker.distance > 0 ) {
												zoom.marker[marker].excludeArea = 'right';
												zoom.marker[secondmarker].excludeArea = 'left';
											}else {
												zoom.marker[marker].excludeArea = 'left';
												zoom.marker[secondmarker].excludeArea = 'right';
											}
										}

										/* let the tooltip follow its marker - this has to be done for both markers too */
										$("#zoom-marker-tooltip-" + marker).css({ left: zoom.marker[marker].left + ( (zoom.marker[marker].excludeArea == 'right') ? (2) : (-2-zoom.marker[marker].width) ) });
										$("#zoom-marker-tooltip-" + secondmarker ).css({ left: zoom.marker[secondmarker].left + ( (zoom.marker[secondmarker].excludeArea == 'right') ? (2) : (-2-zoom.marker[secondmarker].width) ) });

										//$("#zoom-marker-tooltip-1-arrow-left").css({display: (zoom.marker[marker].excludeArea == 'right') ?

										$("#zoom-excluded-area-" + marker).css({ left: (zoom.marker.distance > 0) ? zoom.marker[marker].left : zoom.box.left, width: (zoom.marker.distance > 0) ? zoom.box.right - zoom.marker[marker].left : zoom.marker[marker].left - zoom.box.left});
										$("#zoom-excluded-area-" + secondmarker).css({ left: (zoom.marker.distance > 0) ? zoom.box.left : zoom.marker[secondmarker].left, width: (zoom.marker.distance > 0) ? zoom.marker[secondmarker].left - zoom.box.left : zoom.box.right - zoom.marker[secondmarker].left});
									},
								stop:
									function(event,ui) {

									}

							});

							break;
						case 2:
							if(zoom.custom.zoom3rdMouseButton != false) {
								zoomContextMenu_hide();
								alert("double");
							}
							break;
					}
					return false;

				});

			}
		}


		/*
		* executes a dynamic zoom in
		*/
		function dynamicZoom(image){

			var newGraphStartTime 	= (zoom.attr.action == 'left2right')
									? parseInt(parseInt(zoom.graph.start) + (zoom.attr.start - zoom.box.left)*zoom.graph.secondsPerPixel)
									: parseInt(parseInt(zoom.graph.start) + (zoom.attr.end - zoom.box.left)*zoom.graph.secondsPerPixel);
			var newGraphEndTime 	= (zoom.attr.action == 'left2right')
									? parseInt(newGraphStartTime + (zoom.attr.end-zoom.attr.start)*zoom.graph.secondsPerPixel)
									: parseInt(newGraphStartTime + (zoom.attr.start-zoom.attr.end)*zoom.graph.secondsPerPixel);

			if(zoom.options.inputfieldStartTime != '' & zoom.options.inputfieldEndTime != ''){
				$('#' + zoom.options.inputfieldStartTime).val(unixTime2Date(newGraphStartTime));
				$('#' + zoom.options.inputfieldEndTime).val(unixTime2Date(newGraphEndTime));

				image.unbind();
				$("#zoomBox").unbind();
				$("#zoomArea").unbind();
				$("#zoomBox").remove();
				$("#zoomArea").remove();

				zoom.graph.local_graph_id	= 0;
				zoom.image.top		= 0;
				zoom.image.left	= 0;

				zoom.box.top	= 0;
				zoom.box.right	= 0;
				zoom.box.left	= 0;

				zoom.attr.start	= 'none';
				zoom.attr.end		= 'none';
				zoomAction		= 'left2right';

				zoom.graph.width		= 0;

				$("input[name='" + zoom.options.submitButton + "']").trigger('click');

				return false;
			}else {
				open(zoom.attr.location[0] + "?action=" + zoom.graph.action + "&local_graph_id=" + zoom.graph.local_graph_id + "&rra_id=" + zoom.graph.rra_id + "&view_type=" + zoom.graph.view_type + "&graph_start=" + newGraphStartTime + "&graph_end=" + newGraphEndTime + "&graph_height=" + zoom.graph.height + "&graph_width=" + zoom.graph.width + "&title_font_size=" + zoom.graph.title_font_size, "_self");
			}
		}


		/*
		* converts a Unix time stamp to a formatted date string
		*/
		function unixTime2Date(unixTime){
			var date	= new Date(unixTime*1000);
			var year	= date.getFullYear();
			var month	= ((date.getMonth()+1) < 9 ) ? '0' + (date.getMonth()+1) : date.getMonth()+1;
			var day		= (date.getDate() > 9) ? date.getDate() : '0' + date.getDate();
			var hours	= (date.getHours() > 9) ? date.getHours() : '0' + date.getHours();
			var minutes	= (date.getMinutes() > 9) ? date.getMinutes() : '0' + date.getMinutes();
			var seconds	= (date.getSeconds() > 9) ? date.getSeconds() : '0' + date.getSeconds();

			var formattedTime = year + '-' + month + '-' + day + ' ' + hours + ':' + minutes + ':' + seconds;
			return formattedTime;
		}


		/*
		* executes a static zoom out (as right click event)
		*/
		function zoomAction_zoom_out(multiplier){

			multiplier--;
			if(zoom.custom.zoomOutPositioning == 'begin') {
				var newGraphStartTime = parseInt(zoom.graph.start);
				var newGraphEndTime = parseInt(parseInt(zoom.graph.end) + (multiplier * zoom.graph.timespan));
			}else if(zoom.custom.zoomOutPositioning == 'end') {
				var newGraphStartTime = parseInt(parseInt(zoom.graph.start) - (multiplier * zoom.graph.timespan));
				var newGraphEndTime = parseInt(zoom.graph.end);
			}else {
				// define the new start and end time, so that the selected area will be centered per default
				var newGraphStartTime = parseInt(parseInt(zoom.graph.start) - (0.5 * multiplier * zoom.graph.timespan));
				var newGraphEndTime = parseInt(parseInt(zoom.graph.end) + (0.5 * multiplier * zoom.graph.timespan));
			}

			if(zoom.options.inputfieldStartTime != '' & zoom.options.inputfieldEndTime != ''){
				$('#' + zoom.options.inputfieldStartTime).val(unixTime2Date(newGraphStartTime));
				$('#' + zoom.options.inputfieldEndTime).val(unixTime2Date(newGraphEndTime));
				$('#' + zoom.options.inputfieldStartTime).closest("form").submit();
			}else {
				open(zoom.attr.location[0] + "?action=" + zoom.graph.action + "&local_graph_id=" + zoom.graph.local_graph_id + "&rra_id=" + zoom.graph.rra_id + "&view_type=" + zoom.graph.view_type + "&graph_start=" + newGraphStartTime + "&graph_end=" + newGraphEndTime + "&graph_height=" + zoom.graph.height + "&graph_width=" + zoom.graph.width + "&title_font_size=" + zoom.graph.title_font_size, "_self");
			}
		}


		/*
		* updates the css parameters of the zoom area to reflect user's interaction
		*/
		function drawZoomArea(event) {

			if(zoom.attr.start == 'none') { return; }

			/* mouse has been moved from right to left */
			if((event.pageX-zoom.attr.start)<0) {
				zoom.attr.action = 'right2left';
				zoom.attr.end = (event.pageX < zoom.box.left) ? zoom.box.left : event.pageX;
				$("#zoomArea").css({ background:'red', left:(zoom.attr.end+1)+'px', width:Math.abs(zoom.attr.start-zoom.attr.end-1)+'px' });
			/* mouse has been moved from left to right*/
			}else {
				zoom.attr.action = 'left2right';
				zoom.attr.end = (event.pageX > zoom.box.right) ? zoom.box.right : event.pageX;
				$("#zoomArea").css({ background:'red', left:zoom.attr.start+'px', width:Math.abs(zoom.attr.end-zoom.attr.start-1)+'px' });
			}
			/* move second marker if necessary */
			if(zoom.custom.zoomMode != 'quick') {
				$("#zoom-marker-2").css({ left:(zoom.attr.end+1)+'px' });
				$("#zoom-marker-tooltip-2").css({ top:zoom.box.top+'px', left:(zoom.attr.end-5)+'px' });
			}
		}

		/**
		 *
		 * @access public
		 * @return void
		 **/
		function zoomContextMenu_init(){

			/* sync menu with cookie parameters */
			$(".zoomContextMenuAction__set_zoomMode__" + zoom.custom.zoomMode).addClass("ui-state-highlight");
			$(".zoomContextMenuAction__set_zoomMarkers__" + ((zoom.custom.zoomMarkers === true) ? "on" : "off") ).addClass("ui-state-highlight");
			$(".zoomContextMenuAction__set_zoomTimestamps__" + ((zoom.custom.zoomTimestamps) ? "on" : "off") ).addClass("ui-state-highlight");
			$(".zoomContextMenuAction__set_zoomOutFactor__" + zoom.custom.zoomOutFactor).addClass("ui-state-highlight");
			$(".zoomContextMenuAction__set_zoomOutPositioning__" + zoom.custom.zoomOutPositioning).addClass("ui-state-highlight");
			$(".zoomContextMenuAction__set_zoom3rdMouseButton__" + ((zoom.custom.zoom3rdMouseButton === false) ? "off" : zoom.custom.zoom3rdMouseButton) ).addClass("ui-state-highlight");

			if(zoom.custom.zoomMode == "quick") {
				$("#zoom-menu > .advanced_mode").hide();
			}else {
				$(".zoomContextMenuAction__zoom_out").text("Zoom Out (" + zoom.custom.zoomOutFactor + "x)");
			}

			/* init click on events */
			$('[class*=zoomContextMenuAction__]').off().on('click', function() {
				var zoomContextMenuAction = false;
				var zoomContextMenuActionValue = false;
				var classList = $(this).attr('class').trim().split(/\s+/);

				$.each( classList, function(index, item){
					if( item.search("zoomContextMenuAction__") != -1) {
						zoomContextMenuActionList = item.replace("zoomContextMenuAction__", "").split("__");
						zoomContextMenuAction = zoomContextMenuActionList[0];
						if(zoomContextMenuActionList[1] == 'undefined' || zoomContextMenuActionList[1] == 'off') {
							zoomContextMenuActionValue = false;
						}else if(zoomContextMenuActionList[1] == 'on') {
							zoomContextMenuActionValue = true;
						}else {
							zoomContextMenuActionValue = zoomContextMenuActionList[1];
						}
						return( false );
					}
				});

				if( zoomContextMenuAction ) {
					if( zoomContextMenuAction.substring(0,8) == "set_zoom") {
						zoomContextMenuAction_set( zoomContextMenuAction.replace("set_zoom", "").toLowerCase(), zoomContextMenuActionValue);
					}else {
						zoomContextMenuAction_do( zoomContextMenuAction, zoomContextMenuActionValue);
					}
				}
			});

			/* init hover events */
			$(".first_li , .sec_li, .inner_li span").hover(
				function () {
					$(this).css({backgroundColor : '#E0EDFE' , cursor : 'pointer'});
					if ( $(this).children().size() >0 )
						if(zoom.custom.zoomMode == "quick") {
							$(this).children('.inner_li:not(.advanced_mode)').show();
						}else {
							$(this).children('.inner_li').show();
						}
					},
				function () {
					$(this).css('background-color' , '#fff' );
					$(this).children('.inner_li').hide();
				}
			);
		};

		/**
		 *
		 * @access public
		 * @return void
		 **/
		function zoomContextMenuAction_set(object, value){
			switch(object) {
				case "mode":
					if( zoom.custom.zoomMode != value) {
						zoom.custom.zoomMode = value;
						$('[class*=zoomContextMenuAction__set_zoomMode__]').toggleClass("ui-state-highlight");

						if(value == "quick") {
							// reset menu
							$("#zoom-menu > .advanced_mode").hide();
							$(".zoomContextMenuAction__zoom_out").text("Zoom Out (2x)");

							zoom.custom.zoomMode			= 'quick';
							$.cookie( zoom.options.cookieName, serialize(zoom.custom));
						}else {
							// switch to advanced mode
							$("#zoom-menu > .advanced_mode").show();
							$(".zoomContextMenuAction__zoom_out").text("Zoom Out (" +  + zoom.custom.zoomOutFactor + "x)");

							zoom.custom.zoomMode			= 'advanced';
							$.cookie( zoom.options.cookieName, serialize(zoom.custom));
						}
						init_ZoomAction(rrdgraph);
					}
					break;
				case "markers":
					if( zoom.custom.zoomMarkers != value) {
						zoom.custom.zoomMarkers = value;
						$.cookie( zoom.options.cookieName, serialize(zoom.custom));
						$('[class*=zoomContextMenuAction__set_zoomMarkers__]').toggleClass('ui-state-highlight');
					}
					break;
				case "timestamps":
					if( zoom.custom.zoomTimestamps != value) {
						zoom.custom.zoomTimestamps = value;
						$.cookie( zoom.options.cookieName, serialize(zoom.custom));
						$('[class*=zoomContextMenuAction__set_zoomTimestamps__]').toggleClass('ui-state-highlight');
					}
					break;
				case "outfactor":
					if( zoom.custom.zoomOutFactor != value) {
						zoom.custom.zoomOutFactor = value;
						$.cookie( zoom.options.cookieName, serialize(zoom.custom));
						$('[class*=zoomContextMenuAction__set_zoomOutFactor__]').removeClass('ui-state-highlight');
						$('.zoomContextMenuAction__set_zoomOutFactor__' + value).addClass('ui-state-highlight');
						$('.zoomContextMenuAction__zoom_out').text('Zoom Out (' + value + 'x)');
					}
					break;
				case "outpositioning":
					if( zoom.custom.zoomOutPositioning != value) {
						zoom.custom.zoomOutPositioning = value;
						$.cookie( zoom.options.cookieName, serialize(zoom.custom));
						$('[class*=zoomContextMenuAction__set_zoomOutPositioning__]').removeClass('ui-state-highlight');
						$('.zoomContextMenuAction__set_zoomOutPositioning__' + value).addClass('ui-state-highlight');
					}
					break;
				case "3rdmousebutton":
					if( zoom.custom.zoom3rdMouseButton != value) {
						zoom.custom.zoom3rdMouseButton = value;
						$.cookie( zoom.options.cookieName, serialize(zoom.custom));
						$('[class*=zoomContextMenuAction__set_zoom3rdMouseButton__]').removeClass('ui-state-highlight');
						$('.zoomContextMenuAction__set_zoom3rdMouseButton__' + ((value === false) ? "off" : value)).addClass('ui-state-highlight');
					}
					break;
			}
		}

		function zoomContextMenuAction_do(action, value){
			switch(action) {
				case "close":
					zoomContextMenu_hide();
					break;
				case "zoom_out":
					if(value == undefined) {
						value = (zoom.custom.zoomMode != "quick") ? zoom.custom.zoomOutFactor : 2;
					}
					zoomAction_zoom_out(value);
					break;
			}
		}

		function zoomContextMenu_show(e){
			$("#zoom-menu").css({ left: e.pageX, top: e.pageY, zIndex: '101' }).show();
		};

		function zoomContextMenu_hide(){
			$('#zoom-menu').hide();
		}

	};

})(jQuery);