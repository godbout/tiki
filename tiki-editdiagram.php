<?php

use Tiki\Package\VendorHelper;

require_once('tiki-setup.php');

$xmlContent = isset($_POST['xml']) ? $_POST['xml'] : false;
$page = isset($_POST['page']) ? $_POST['page'] : false;
$index = isset($_POST['index']) ? $_POST['index'] : null;

$galleryId = isset($_REQUEST['galleryId']) ? $_REQUEST['galleryId'] : 0;
$backLocation = '';

if ($xmlContent) {
	$xmlContent = base64_decode($xmlContent);
}

$newDiagram = isset($_REQUEST['newDiagram']) ?: false;
if ($newDiagram && ! $xmlContent) {
	$xmlContent = '<mxGraphModel dx="1190" dy="789" grid="1" gridSize="10" guides="1" tooltips="1" connect="1" arrows="1" fold="1" page="1" pageScale="1" pageWidth="827" pageHeight="1169" math="0" shadow="0"><root><mxCell id="0"/><mxCell id="1" parent="0"/></root></mxGraphModel>';
}

if ($newDiagram) {
	$smarty = TikiLib::lib('smarty');
	$smarty->loadPlugin('smarty_modifier_sefurl');
	$backLocation = smarty_modifier_sefurl($page ?: $galleryId, $page ? 'wikipage' : 'filegallery');
}

$fileId = isset($_POST['fileId']) ? $_POST['fileId'] : 0;
$fileName = 0;

if (! empty($fileId)) {
	$userLib = TikiLib::lib('user');
	$file = \Tiki\FileGallery\File::id($fileId);
	if (! $file->exists() || ! $userLib->user_has_perm_on_object($user, $file->fileId, 'file', 'tiki_p_download_files')) {
		Feedback::error(tr('Forbidden'));
		$smarty->display('tiki.tpl');
		exit();
	}

	$xmlContent = $file->getContents();
	$xmlContent = preg_replace('/\s+/', ' ', $xmlContent);
	$fileName = $file->getParam('name');
}

if (empty($xmlContent)) {
	Feedback::error(tr('Invalid request'));
	$smarty->display('tiki.tpl');
	exit();
}

$xmlDiagram = $xmlContent;
$access->setTicket();
$ticket = $access->getTicket();

$ticket2 = null;
if ($page && $galleryId) {
	$access->setTicket();
	$ticket2 = $access->getTicket();
}

$saveModal = $smarty->fetch('mxgraph/save_modal.tpl');
$saveModal = preg_replace('/\s+/', ' ', $saveModal);

$headerlib = TikiLib::lib('header');

$oldVendorPath = VendorHelper::getAvailableVendorPath('mxgraph', 'xorti/mxgraph-editor', false);
if ($oldVendorPath) {
	$errorMessageToAppend = 'Previous xorti/mxgraph-editor package has been deprecated.<br/>';
}

$vendorPath = VendorHelper::getAvailableVendorPath('diagram', 'tikiwiki/diagram', false);
if (! $vendorPath) {
	$accesslib = TikiLib::lib('access');
	$accesslib->display_error('tiki-display.php', tr($errorMessageToAppend . 'To edit diagrams Tiki needs the tikiwiki/diagram package. If you do not have permission to install this package, ask the site administrator.'));
}

$headerlib->add_js_config("var diagramVendorPath = '{$vendorPath}';");
$headerlib->add_jsfile('lib/jquery_tiki/tiki-mxgraph.js', true);

// Clear Tiki CSS files (just use drawio css)
$headerlib->cssfiles = [];
$headerlib->add_css(".geMenubar a.geStatus { display: none;}");
$headerlib->add_cssfile($vendorPath . '/tikiwiki/diagram/styles/grapheditor.css');
$headerlib->add_jsfile($vendorPath . '/tikiwiki/diagram/js/app.min.js', true);

$js = "(function()
	{
		// Disable communication to external services
		urlParams['stealth'] = 1;
		urlParams['embed'] = 1;
		
		var editorUiInit = EditorUi.prototype.init;
		EditorUi.prototype.init = function()
		{
			editorUiInit.apply(this, arguments);
			var editorUi = this.actions.editorUi;
			var editor = editorUi.editor;
			
			this.saveFile = function(forceDialog) {
				let node = editorUi.getXmlFileData();
				var content = mxUtils.getXml(node);
				var fileId = {$fileId};
				var galleryId = {$galleryId};
				var newDiagram = '{$newDiagram}';
				var backLocation = '{$backLocation}';

				var saveElem = $('{$saveModal}')[0];
				editorUi.showDialog(saveElem, 400, 200, true, false, null, true);

				if (fileId || galleryId) {
					var blob = new Blob([content]);
					content = window.btoa(content);

					var name = galleryId ? 'New Diagram' : '{$fileName}';

					var data = {
						controller: 'file',
						action: 'upload',
						ticket: '{$ticket}',
						name: name,
						type: 'text/plain',
						size: blob.size,
						data: content,
						fileId: '{$fileId}',
					};

					if (galleryId) {
						data.galleryId = '{$galleryId}';
					}
				} else {
					//calling ajax edit plugin function
					var data = {
						controller: 'plugin',
						action: 'replace',
						ticket: '{$ticket}',
						page: '{$page}',
						message: 'Modified by mxGraph',
						type: 'diagram',
						content: content,
						index: '{$index}'
					};
				}

				// Show Modal after Save diagram
				function showModalAfterSave() {
					editor.modified = false;
					$('div.diagram-saving').hide();
					$('div.diagram-saved').show();
					setTimeout(function(){
						if (newDiagram) {
							window.location.href = backLocation;
						} else {
							window.close();
							window.opener.location.reload(false);
						}
					}, 3000);
				}

				// Show Errors
				function showErrorMessage(message) {
					$('div.diagram-saving').hide();
					$('p.diagram-error-message').html(message);

					$('div.diagram-error button').on('click', function() {
						editorUi.hideDialog();
					});

					$('div.diagram-error').show();
				}

				$.ajax({
					type: 'POST',
					url: 'tiki-ajax_services.php',
					dataType: 'json',
					data: data,
					success: function(result){
						if ('{$page}' && result.fileId) {
							// if new file and from page
							var data = {
								controller: 'plugin',
								action: 'replace',
								ticket: '{$ticket2}',
								page: '{$page}',
								message: 'Modified by mxGraph',
								type: 'diagram',
								content: '',
								index: '{$index}',
								params: {'fileId': result.fileId}
							};

							$.ajax({
								type: 'POST',
								url: 'tiki-ajax_services.php',
								dataType: 'json',
								data: data,
								success: function(){
									showModalAfterSave();
								},
								error: function(xhr, status, message) {
									showErrorMessage(message);
								}
							});
							
						} else {
							showModalAfterSave();
						}
					},
					error: function(xhr, status, message) {
						showErrorMessage(message);
					}
				});
			}
		};

		// Adds required resources (disables loading of fallback properties, this can only
		// be used if we know that all keys are defined in the language specific file)
		mxResources.loadDefaultBundle = false;
		var bundle = mxResources.getDefaultBundle(RESOURCE_BASE, mxLanguage) ||
			mxResources.getSpecialBundle(RESOURCE_BASE, mxLanguage);

		// Fixes possible asynchronous requests
		mxUtils.getAll([bundle, STYLE_PATH + '/default.xml'], function(xhr)
		{
			// Adds bundle text to resources
			mxResources.parse(xhr[0].getText());

			// Configures the default graph theme
			var themes = new Object();
			themes[Graph.prototype.defaultThemeName] = xhr[1].getDocumentElement();

			// Main
			var ui = new EditorUi(new Editor(urlParams['chrome'] == '0', themes));
			var xml = '{$xmlDiagram}';
			ui.openLocalFile(xml, 'tiki diagram', true);

		}, function()
		{
			document.body.innerHTML = '<div class=\"mt-5 text-center alert alert-danger\">Error loading resource files. Please check browser console.</div>';
		});
	})();";

$headerlib->add_js($js);

$title = $newDiagram ? tr('New diagram') : tr('Edit diagram');
$smarty->assign('title', $title);
$smarty->display('mxgraph/editor.tpl');
