<?php
require_once('tiki-setup.php');
global $headerlib, $smarty;

TikiLib::lib("sheet")->setup_jquery_sheet();

if (!empty($_REQUEST['preview'])) {
	echo UniversalReports_Builder::load($_REQUEST['preview'])
		->setValuesFromRequest($_REQUEST['values'])
		->outputSheet();
	die;
}

if (!empty($_REQUEST['load'])) {
	echo json_encode(UniversalReports_Builder::load($_REQUEST['load'])->input);
	die;
}

if (!empty($_REQUEST['exportcsv'])) {
	echo UniversalReports_Builder::load($_REQUEST['exportcsv'])
		->setValuesFromRequest(json_decode(urldecode($_REQUEST['values'])))
		->outputCSV(true);
	die;
}

$headerlib->add_jsfile( 'lib/core/UniversalReports/Builder.js' , 'urb');

$headerlib->add_jq_onready("
	$('#universalReportsType')
		.change(function() {
			$('#universalReportsEditor').html('');
			if ($(this).val()) {
				$.getJSON('tiki-edit_universal_reports.php?',{load: $(this).val()}, function(data) {
					$('#universalReportsEditor').universalReportsBuilder({
						definition: data
					});
				});
			}
		})
		.change();
	
	$('#universalReportsPreview').click(function() {
		$.post('tiki-edit_universal_reports.php', {
			values: $('#universalReportsEditor').serializeArray(),
			preview: $('#universalReportsType').val()
		}, function(o) {
			var jS = $('#universalReportsDebug').getSheet();
			if (jS) {
				jS.openSheet(o);
			} else {
				$('#universalReportsDebug')
					.html($(o).attr('title', tr('Preview')))
					.sheet({
						buildSheet: true,
						editable: false
					});
			}
		});
		
		return false;
	});
	
	$('#universalReportsExportCSV').click(function() {
		$.download('tiki-edit_universal_reports.php', { 
			values: JSON.stringify($('#universalReportsEditor').serializeArray()),
			exportcsv: $('#universalReportsType').val()
		}, 'post');
		
		return false;
	});
");

$smarty->assign('definitions', UniversalReports_Builder::listDefinitions());
$smarty->assign('mid', 'tiki-edit_universal_reports.tpl');
$smarty->display("tiki.tpl");
