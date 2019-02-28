<?php

require_once('tiki-setup.php');

if (empty($_POST['xml'])) {
	Feedback::error(tr('Invalid request'));
	$smarty->display('tiki.tpl');
	exit();
}

$xmlDiagram = base64_decode($_POST['xml']);
$access->setTicket();
$ticket = $access->getTicket();

$fileId = isset($_POST['fileId']) ? $_POST['fileId'] : 0;
$fileName = isset($_POST['fileName']) ? $_POST['fileName'] : 0;

$page = isset($_POST['page']) ? $_POST['page'] : null;
$index = isset($_POST['index']) ? $_POST['index'] : null;

$saveModal = $smarty->fetch('mxgraph/save_modal.tpl');
$saveModal = preg_replace('/\s+/', ' ', $saveModal);

$headerlib = $tikilib::lib('header');

$headerlib->add_cssfile('vendor/xorti/mxgraph-editor/grapheditor/styles/grapheditor.css');
$headerlib->add_css("*, *::before, *::after { box-sizing: unset;}");
$headerlib->add_jsfile('lib/jquery_tiki/tiki-mxgraph.js', false);
$headerlib->add_jsfile('vendor/xorti/mxgraph-editor/grapheditor/js/Init.js');
$headerlib->add_jsfile('vendor/xorti/mxgraph-editor/grapheditor/deflate/pako.min.js', true);
$headerlib->add_jsfile('vendor/xorti/mxgraph-editor/grapheditor/deflate/base64.js');
$headerlib->add_jsfile('vendor/xorti/mxgraph-editor/grapheditor/jscolor/jscolor.js');
$headerlib->add_jsfile('vendor/xorti/mxgraph-editor/grapheditor/sanitizer/sanitizer.min.js', true);
$headerlib->add_jsfile('vendor/xorti/mxgraph-editor/mxClient.min.js', true);
$headerlib->add_jsfile('vendor/xorti/mxgraph-editor/grapheditor/js/EditorUi.js');
$headerlib->add_jsfile('vendor/xorti/mxgraph-editor/grapheditor/js/Editor.js');
$headerlib->add_jsfile('vendor/xorti/mxgraph-editor/grapheditor/js/Sidebar.js');
$headerlib->add_jsfile('vendor/xorti/mxgraph-editor/grapheditor/js/Graph.js');
$headerlib->add_jsfile('vendor/xorti/mxgraph-editor/grapheditor/js/Format.js');
$headerlib->add_jsfile('vendor/xorti/mxgraph-editor/grapheditor/js/Shapes.js');
$headerlib->add_jsfile('vendor/xorti/mxgraph-editor/grapheditor/js/Actions.js');
$headerlib->add_jsfile('vendor/xorti/mxgraph-editor/grapheditor/js/Menus.js');
$headerlib->add_jsfile('vendor/xorti/mxgraph-editor/grapheditor/js/Toolbar.js');
$headerlib->add_jsfile('vendor/xorti/mxgraph-editor/grapheditor/js/Dialogs.js');

$js = "(function()
	{
		var editorUiInit = EditorUi.prototype.init;

		EditorUi.prototype.init = function()
		{
			editorUiInit.apply(this, arguments);

			// Remove menu child option on File
			delete this.actions.actions.new;
			delete this.actions.actions.open;
			delete this.actions.actions.import;
			delete this.actions.actions.export;
			delete this.actions.actions.saveAs;
			delete this.actions.actions.print;
			delete this.actions.actions.pageSetup;

			var editorUi = this.actions.editorUi;
			var editor = editorUi.editor;
			
			this.actions.addAction('save', function() {

				var content = mxUtils.getPrettyXml(editor.getGraphXml());
				var fileId = {$fileId};

				var saveElem = $('{$saveModal}')[0];
				editorUi.showDialog(saveElem, 400, 200, true, false, null, true);

				if (fileId) {
					var blob = new Blob([content]);
					content = window.btoa(content);

					var data = {
						controller: 'file',
						action: 'upload',
						ticket: '{$ticket}',
						name: '{$fileName}',
						type: 'text/plain',
						size: blob.size,
						data: content,
						fileId: '{$fileId}',
					};
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

				$.ajax({
					type: 'POST',
					url: 'tiki-ajax_services.php',
					dataType: 'json',
					data: data,
					success: function(){
						editor.modified = false;
						$('div.diagram-saving').hide();
						$('div.diagram-saved').show();
						setTimeout(function(){
							window.close();
							window.opener.location.reload(false);
						}, 3000);
					},
					error: function(xhr, status, message) {
						$('div.diagram-saving').hide();
						$('p.diagram-error-message').html(message);
						
						$('div.diagram-error button').on('click', function() {
							editorUi.hideDialog();
						});
						
						$('div.diagram-error').show();
					}
				});
			}, null, null, Editor.ctrlKey + '+S');
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
			var xml = `{$xmlDiagram}`;
			handleXmlData(ui.editor.graph, `{$xmlDiagram}`);

		}, function()
		{
			document.body.innerHTML = '<div class=\"mt-5 text-center alert alert-danger\">Error loading resource files. Please check browser console.</div>';
		});
	})();";

$headerlib->add_js($js);

$smarty->assign('title', tr('Edit diagram'));
$smarty->display('mxgraph/editor.tpl');
