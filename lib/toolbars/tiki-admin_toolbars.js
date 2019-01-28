// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
// 
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

/* Include for tiki-admin_toolbars.php
 * 
 * Selector vars set up in tiki-admin_toolbars.php:
 * 
 * var toolbarsadmin_rowStr = '#row-1,#row-2,#row-3... etc'
 * var toolbarsadmin_fullStr = '#full-list-w,#full-list-p,#full-list-c';
 * var toolbarsadmin_delete_text = tra('Are you sure you want to delete this custom tool?')
 */


$(document).ready(function () {
	
	$(toolbarsadmin_rowStr).sortable({
		connectWith: toolbarsadmin_fullStr + ', .row',
		forcePlaceholderSize: true,
		forceHelperSize: true,
		placeholder: 'toolbars-placeholder',
		stop: function (event, ui) {
		},
		start: function (event, ui) {
		},
		receive: function(event, ui) {
		}
	}).disableSelection();

	$(toolbarsadmin_fullStr).sortable({
		connectWith: '.row, #full-list-c',
		forcePlaceholderSize: true,
		forceHelperSize: true,
		placeholder: 'toolbars-placeholder',
		remove: function (event, ui) {	// special handling for separator to allow duplicates
			if ($(ui.item).text() === '-' || $(ui.item).text() === '|') {
				$(this).prepend($(ui.item).clone());	// leave a copy at the top of the full list
			}
		},
		receive: function (event, ui) {
			$(ui.item).css('float', '');
			if ($(ui.item).text() === '-') {
				$(this).children().remove('.qt--');				// remove all seps
				$(this).prepend($(ui.item).clone());			// put one back at the top
	
			} else if ($(this).attr('id') === 'full-list-c') {	// dropped in custom list
				$(ui.item).dblclick(function () { showToolEditForm(ui.item); });
				$(ui.item).trigger('dblclick');
			}
			sortList(this);
		},
		stop: function (event, ui) {
			sortList(this);
		}
	}).disableSelection();
	var sortList = function (list) {
		var arr = $(list).children().get(), item, labelA, labelB;
		arr.sort(function(a, b) {
			labelA = $(a).text().toUpperCase();
			labelB = $(b).text().toUpperCase();
			if (labelA < labelB) { return -1; }
			if (labelA > labelB) { return 1; }
			return 0;
		});
		$(list).empty();
		for (item = 0; item < arr.length; item++) {
			$(list).append(arr[item]);
		}
		if ($(list).attr("id") === "full-list-c") {
			$('.qt-custom').dblclick(function () { showToolEditForm(this); });
		}
	};
	$('.qt-custom').dblclick(function () { showToolEditForm(this); });

	var $toolbarEditDiv = $('#toolbar_edit_div');
	
	// show edit form dialogue
	var showToolEditForm = function (item) {

		if (item) {
			$('#tool_name', $toolbarEditDiv).val($.trim($(item).text())); //.attr('disabled','disabled');
			$('#tool_label', $toolbarEditDiv).val($.trim($(item).text()));
			if ($(item).children('img').length && $(item).children('img').attr('src') !== 'img/icons/shading.png') {
				$('#tool_icon', $toolbarEditDiv).val($(item).children('img').attr('src'));
			} else {
				var iconname = $("span.icon", item).attr("class").match(/icon-(\w*)/);
				if (iconname) {
					$('#tool_icon', $toolbarEditDiv).val(iconname[1]);
				}
			}
			$('#tool_token', $toolbarEditDiv).val($(item).find('input[name=token]').val());
			$('#tool_syntax', $toolbarEditDiv).val($(item).find('input[name=syntax]').val());
			$('#tool_type', $toolbarEditDiv).val($(item).find('input[name=type]').val());
			if ($(item).find('input[name=type]').val() === 'Wikiplugin') {
				$('#tool_plugin', $toolbarEditDiv).val($(item).find('input[name=plugin]').val());
			} else {
				$('#tool_plugin', $toolbarEditDiv).attr('disabled', 'disabled');
			}
		}
		$toolbarEditDiv.dialog('open');
	};
	// handle plugin select on edit dialogue
	$('#tool_type').change( function () {
		if ($('#tool_type').val() === 'Wikiplugin') {
			$('#tool_plugin').removeAttr('disabled');
		} else {
			$('#tool_plugin').attr('disabled', 'disabled').val("");
		}
	});
	
	$toolbarEditDiv.dialog({
		bgiframe: true,
		autoOpen: false,
	//	height: 300,
		modal: true,
		buttons: {
			Cancel: function () {
				$(this).dialog('close');
			},
			'Save': function() {
				var bValid = true;
				$(this).find('input[type=text]').removeClass('ui-state-error');
	
				bValid = bValid && checkLength($('#tool_name', $toolbarEditDiv),"Name",2,16);
				bValid = bValid && checkLength($('#tool_label', $toolbarEditDiv),"Label",1,80);
				
				if (bValid) {
					$("#save_tool", $toolbarEditDiv).val('Save');
					$("form", $toolbarEditDiv).submit();
					$(this).dialog('close');
				}
			},
			Delete: function () {
				if (confirm(toolbarsadmin_delete_text)) {
					$("#delete_tool").val('Delete');
					$("form").submit();
				}
				$(this).dialog('close');
			}
		},
		close: function () {
			$(this).find('input[type=text]').val('').removeClass('ui-state-error');
		}
	});

	var checkLength = function (o, n, min, max) {
		if (o.val().length > max || o.val().length < min) {
			o.addClass('ui-state-error');
			o.prev("label").find(".dialog_tips").text(" Length must be between " + min + " and " + max).addClass('ui-state-highlight');
			setTimeout(function () {
				o.prev("label").find(".dialog_tips").removeClass('ui-state-highlight', 1500);
			}, 500);
			return false;
		} else {
			return true;
		}
	};

	// view mode filter (still doc.ready)

	var $viewMode = $('#view_mode');
	if ($("#section").val() === "sheet") {
		$viewMode.val("sheet");
	}

	$viewMode.change(function setViewMode() {
		if ($viewMode.val() === 'both') {
			$('.qt-wyswik').addClass("d-none").removeClass("d-flex");
			$('.qt-wiki').removeClass("d-none").addClass("d-flex");
			$('.qt-wys').removeClass("d-none").addClass("d-flex");
			$('.qt-sheet').addClass("d-none").removeClass("d-flex");
		} else if ($viewMode.val() === 'wiki') {
			$('.qt-wyswik').addClass("d-none").addClass("d-flex");
			$('.qt-wys').addClass("d-none").removeClass("d-flex");
			$('.qt-wiki').removeClass("d-none").addClass("d-flex");
			$('.qt-sheet').addClass("d-none").removeClass("d-flex");
		} else if ($viewMode.val() === 'wysiwyg') {
			$('.qt-wyswik').addClass("d-none").removeClass("d-flex");
			$('.qt-wiki').addClass("d-none").removeClass("d-flex");
			$('.qt-wys').removeClass("d-none").addClass("d-flex");
			$('.qt-sheet').addClass("d-none").removeClass("d-flex");
		} else if ($viewMode.val() === 'wysiwyg_wiki') {
			$('.qt-wiki').addClass("d-none").removeClass("d-flex");
			$('.qt-wys').addClass("d-none").removeClass("d-flex");
			$('.qt-sheet').addClass("d-none").removeClass("d-flex");
			$('.qt-wyswik').removeClass("d-none").addClass("d-flex");
			$('.qt--').removeClass("d-none").addClass("d-flex");
		} else if ($viewMode.val() === 'sheet') {
			$('.qt-wyswik').addClass("d-none").removeClass("d-flex");
			$('.qt-wys').addClass("d-none").removeClass("d-flex");
			$('.qt-wiki').removeClass("d-none").addClass("d-flex");
			$('.qt-sheet').removeClass("d-none").addClass("d-flex");
		}
	}).change();

	$('#toolbar_add_custom').click(function () {
		showToolEditForm();
		return false;
	});

});	// end doc ready

// save toolbars
function saveRows() {
	var ser, text;
	ser = $('.toolbars-admin ul.row').map(function (){	/* do this on everything of class 'row' inside toolbars-admin div */
		return $(this).children().map(function (){	/* do this on each child node */
			text = "";
			if ($(this).hasClass('qt-plugin')) { text += 'wikiplugin_'; }
			text += $.trim($(this).text());
			return text;
		}).get().join(",").replace(",|", "|").replace("|,", "|");			/* put commas inbetween */
	});
	if (typeof(ser) === 'object' && ser.length > 1) {
		ser = $.makeArray(ser).join('/');			// row separators
	} else {
		ser = ser[0];
	}
	$('#qt-form-field').val(ser.replace(',,', ','));
}



