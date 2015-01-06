var hostTimer;
var clickTimeout;
var hostOpen = false;

function themeReady() {
	// Add nice search filter to filters
	$('input[id="filter"]').after("<i class='fa fa-search filter'/>").attr('autocomplete', 'off').attr('placeholder', 'Enter a search term').parent('td').css('white-space', 'nowrap');

	$('#host').autocomplete({
		source: 'graphs.php?action=ajax_hosts',
		autoFocus: true,
		minLength: 0,
		select: function(event,ui) {
			$('#host_id').val(ui.item.id);
			applyFilter();
		}
	}).addClass('ui-selectmenu-text').css('border', 'none').css('background-color', 'transparent');

	$('#host_click').css('z-index', '4');
	$('input#filter').addClass('ui-state-default ui-corner-all');

	$('input[type="text"], input[type="password"], input[type="checkbox"], textarea').not('image').addClass('ui-state-default ui-corner-all');

	$.ui.selectmenu.prototype._renderItem = function(ui, item) {
		if (item.element.closest('select').hasClass('colordropdown')) {
			if (item.label != 'None') {
				var li = $("<li>").css( "background-color", '#'+item.label );
			}else{
				var li = $("<li>").css( "background-color", '' );
			}
		}else{
			var li = $("<li>");
		}

		if (item.disabled) {
			li.addClass("ui-state-disabled");
		}
		this._setText(li, item.label);
		return li.appendTo(ui);
	}

	$('select').selectmenu({
		change: function(event, ui) {
			$(this).val(ui.item.value).change();
		},
		position: {
			my: "left top",
			at: "left bottom",
			collision: "flip"
		},
	}).each(function() {
		id = $(this).attr('id');
		$('#'+id+'-button').css('min-width', '0px').css('max-width', '400px').css('width','');
		$('#'+id+'-menu').css('max-height', '250px');
	});

	$('#host_wrapper').dblclick(function() {
		hostOpen = false;
		clearTimeout(hostTimer);
		clearTimeout(clickTimeout);
		$('#host').autocomplete('close');
	}).click(function() {
		if (hostOpen) {
			$('#host').autocomplete('close');
			clearTimeout(hostTimer);
			hostOpen = false;
		}else{
			clickTimeout = setTimeout(function() {
				$('#host').autocomplete('search', '');
				clearTimeout(hostTimer);
				hostOpen = true;
			}, 200);
		}
	}).on('mouseleave', function() {
		console.log('Leaving');
		hostTimer = setTimeout(function() { $('#host').autocomplete('close'); }, 800);
	});

	$('ul[id="ui-id-1"]').on('mouseover', function() {
		console.log('Entering Hidden');
		clearTimeout(hostTimer);
	}).on('mouseout', function() {
		console.log('Leaving Hidden');
		hostTimer = setTimeout(function() { $('#host').autocomplete('close'); }, 800);
	});

	$('#host_wrapper').on('mouseenter', function() { 
		$(this).addClass('ui-state-hover'); 
		$('input#host').addClass('ui-state-hover');
	}).on('mouseleave', function() { 
		$(this).removeClass('ui-state-hover'); 
		$('input#host').removeClass('ui-state-hover');
	});
}
