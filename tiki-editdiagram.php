<?php

use Tiki\Package\VendorHelper;

require_once('tiki-setup.php');

$exportImageCache = (int)($prefs['fgal_export_diagram_on_image_save'] == 'y');

$xmlContent = isset($_POST['xml']) ? $_POST['xml'] : false;
$page = isset($_POST['page']) ? $_POST['page'] : false;
$index = isset($_POST['index']) ? $_POST['index'] : null;
$compressXml = ($prefs['fgal_use_diagram_compression_by_default'] !== 'y') ? false : true;

if (! empty($_POST['compressXmlParam']) && ! empty($_POST['compressXml']) && $_POST['compressXml'] === 'false') {
    $compressXml = false;
}

$galleryId = isset($_REQUEST['galleryId']) ? $_REQUEST['galleryId'] : 0;
$backLocation = '';

if ($xmlContent) {
    $xmlContent = base64_decode($xmlContent);

    $xmlContent = str_replace('<mxfile compressed="false"', '<mxfile', $xmlContent);
    if (! $compressXml) {
        $xmlContent = str_replace('<mxfile', '<mxfile compressed="false"', $xmlContent);
    }
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
$tickets[] = $access->getTicket();

if ($page && $galleryId) {
    $access->setTicket();
    $tickets[] = $access->getTicket();
}

if ($exportImageCache) {
    $access->setTicket();
    $tickets[] = $access->getTicket();
}

$tickets = sprintf('"%s"', implode('","', $tickets));

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
			var self = this;
			var tickets = [{$tickets}];
			var fileId = {$fileId};
			var backLocation = '{$backLocation}';
			var newDiagram = '{$newDiagram}';

			function saveDiagramFlow(closeWindow)
			{
				let compressXml = '{$compressXml}';

				if (compressXml) {
					var node = editorUi.getXmlFileData();
				} else {
					var node = editorUi.getXmlFileData(true, false, true);
				}

				var content = mxUtils.getXml(node);
				var galleryId = {$galleryId};
				var pagesAmount = node.children.length;
				var saveElem = $('{$saveModal}')[0];
				editorUi.showDialog(saveElem, 400, 200, true, false, null, true);
				
				function updatePlugin(content, params, callback) {
					var data = {
						controller: 'plugin',
						action: 'replace',
						ticket: tickets.pop(),
						page: '{$page}',
						message: 'Modified by mxGraph',
						type: 'diagram',
						content: content,
						index: '{$index}',
						params: params
					};
					
					$.ajax({
						type: 'POST',
						url: 'tiki-ajax_services.php',
						dataType: 'json',
						data: data,
						success: function() {
							callback();
						},
						error: function(xhr, status, message) {
							showErrorMessage(message);
						}
					});
				}
				
				function uploadFile(content, callback) {
					var blob = new Blob([content]);
					content = window.btoa(content);

					var name = galleryId ? 'New Diagram' : '{$fileName}';

					var data = {
						controller: 'file',
						action: 'upload',
						ticket: tickets.pop(),
						name: name,
						type: 'text/plain',
						size: blob.size,
						data: content,
						fileId: fileId,
					};
					
					if (galleryId) {
						data.galleryId = '{$galleryId}';
					}
					
					$.ajax({
						type: 'POST',
						url: 'tiki-ajax_services.php',
						dataType: 'json',
						data: data,
						success: function(result) {
							fileId = result.fileId;
							
							if ('{$page}' && result.fileId) {
								updatePlugin('', {'fileId': result.fileId}, function() { callback() });
							} else {
								callback();
							}
						},
						error: function(xhr, status, message) {
							showErrorMessage(message);
						}
					});
				}
				
				function saveCache(callback) {
					var diagramPNGs = {};
					
					let saveImages = function(diagrams) {
						var data = {
							controller: 'diagram',
							action: 'image',
							ticket: tickets.pop(),
							name: 'Preview',
							type: 'image/png',
							content: content,
							fileId: fileId,
							ticketsAmount: 3,
							data: diagrams
						};
						
						$.ajax({
							type: 'POST',
							url: 'tiki-ajax_services.php',
							dataType: 'json',
							data: data,
							success: function(result) {
								tickets = result.new_tickets;
								callback();
							},
							error: function(xhr, status, message) {
								showErrorMessage(message);
							}
						});
					}

					for (var i = 0; i < node.children.length; i++) {
						let id = node.children[i].id;
					
						self.getEmbeddedPng(function(pngData) {
							diagramPNGs[id] = pngData;

							if (Object.keys(diagramPNGs).length === pagesAmount) {
								saveImages(diagramPNGs);
							}
						}, null, '<mxfile>' + node.children[i].outerHTML + '</mxfile>');
					}
				}
				
				function afterSaveDiagramCallback() {
					let exportImageCache = {$exportImageCache};	
					
					if (exportImageCache){
						saveCache(function() {
							showModalAfterSave();
						});
					} else {
						showModalAfterSave();
					}
				}

				if (fileId || galleryId) {
					uploadFile(content, function() {
						if ('{$page}' && fileId) {
						// if new file and from page
							updatePlugin(content, {}, afterSaveDiagramCallback());
						} else {
							afterSaveDiagramCallback();
						}
					});
				} else {
					updatePlugin(content, {}, afterSaveDiagramCallback);
				}

				// Show Modal after Save diagram
				function showModalAfterSave() {
					editor.modified = false;
					editorUi.hideDialog(saveElem);
					
					setTimeout(function() {
						if (newDiagram && closeWindow) {
							window.location.href = backLocation;
						} else if (closeWindow) {
							window.close();
							window.opener.location.reload(false)
						} 
					}, 500);
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
			}

			editorUi.actions.get('exit').funct = function() {
				editorUi.confirm(mxResources.get('allChangesLost'), null, function() {
					editor.modified = false;
					
					if (newDiagram) {
						window.location.href = backLocation;
					} else {
						window.close();
					}
				}, mxResources.get('cancel'), mxResources.get('discardChanges'));
			};

			this.saveFile = function(forceDialog) {
				saveDiagramFlow(false);
			}
			
		    mxResources.parse('saveAndExit=Save and Exit');
		    editorUi.actions.addAction('saveAndExit', function()
		    {
		        saveDiagramFlow(true);
		    });
		    
		    editorUi.keyHandler.bindAction(83, true, 'saveAndExit', true);
		    editorUi.actions.get('saveAndExit').shortcut = Editor.ctrlKey + '+Shift+S';

		    var menu = editorUi.menus.get('file');
		    var oldFunct = menu.funct;
	
	        menu.funct = function(menu, parent)
	        {
	            oldFunct.apply(this, arguments);
             	editorUi.menus.addMenuItem(menu, 'saveAndExit', parent);

	            let submenuItems = $(menu.table).children().children();
	            let saveAndExit = submenuItems.last();
	            
	            for (var i = 0; i < submenuItems.length; i++) {
	                if (submenuItems.get(i).innerText.toLowerCase() == ('Save' + Editor.ctrlKey + '+S').toLowerCase()) {
	                    saveAndExit.insertAfter($(submenuItems.get(i)).before());
	                    break;
	                }
	            }
	        };
	        mxResources.parse(tr('saveUnchanged=Unsaved changes. Click here to save.'));
		
			editorUi.menubar.addMenu(mxResources.get('saveUnchanged'), function(){
				saveDiagramFlow(false);
				$('.geMenubar').children().last().hide();
			 } );
			 
			 $('.geMenubar').children().last().css(
				{'background-color': '#f2dede', 'color': '#a94442 !important', 'padding': '4px 6px 4px 6px',
				'border': '1px solid #ebccd1', 'border-radius': '3px', 'font-size': '12px'}
			 );
			 
			$('.geMenubar').children().last().hide();
			 
			editor.graph.model.addListener(mxEvent.CHANGE, function(sender, evt){
				var changes = evt.getProperty('edit').changes;
				console.log(changes);
				for (var i = 0; i < changes.length; i++)
				{
					var change = changes[i];
					if (change instanceof mxChildChange || change instanceof mxGeometryChange || change instanceof mxStyleChange){
						
						$('.geMenubar').children().last().show();
					}
				}
			});
			
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
