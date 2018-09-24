
<!--[if IE]><meta http-equiv="X-UA-Compatible" content="IE=5,IE=9" ><![endif]-->
<!DOCTYPE html>
<html>
<head>
	<title>Grapheditor</title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
	<link rel="stylesheet" type="text/css" href="vendor/xorti/mxgraph-editor/grapheditor/styles/grapheditor.css">
	<script type="text/javascript">
		// Parses URL parameters. Supported parameters are:
		// - lang=xy: Specifies the language of the user interface.
		// - touch=1: Enables a touch-style user interface.
		// - storage=local: Enables HTML5 local storage.
		// - chrome=0: Chromeless mode.
		var urlParams = (function(url)
		{
			var result = new Object();
			var idx = url.lastIndexOf('?');

			if (idx > 0)
			{
				var params = url.substring(idx + 1).split('&');

				for (var i = 0; i < params.length; i++)
				{
					idx = params[i].indexOf('=');

					if (idx > 0)
					{
						result[params[i].substring(0, idx)] = params[i].substring(idx + 1);
					}
				}
			}

			return result;
		})(window.location.href);

		// Default resources are included in grapheditor resources
		mxLoadResources = false;
	</script>
	<script type="text/javascript" src="vendor_bundled/vendor/components/jquery/jquery.js"></script>
	<script type="text/javascript" src="lib/jquery_tiki/tiki-mxgraph.js"></script>
	<script type="text/javascript" src="vendor/xorti/mxgraph-editor/grapheditor/js/Init.js"></script>
	<script type="text/javascript" src="vendor/xorti/mxgraph-editor/grapheditor/deflate/pako.min.js"></script>
	<script type="text/javascript" src="vendor/xorti/mxgraph-editor/grapheditor/deflate/base64.js"></script>
	<script type="text/javascript" src="vendor/xorti/mxgraph-editor/grapheditor/jscolor/jscolor.js"></script>
	<script type="text/javascript" src="vendor/xorti/mxgraph-editor/grapheditor/sanitizer/sanitizer.min.js"></script>
	<script type="text/javascript" src="vendor/xorti/mxgraph-editor/mxClient.js"></script>
	<script type="text/javascript" src="vendor/xorti/mxgraph-editor/grapheditor/js/EditorUi.js"></script>
	<script type="text/javascript" src="vendor/xorti/mxgraph-editor/grapheditor/js/Editor.js"></script>
	<script type="text/javascript" src="vendor/xorti/mxgraph-editor/grapheditor/js/Sidebar.js"></script>
	<script type="text/javascript" src="vendor/xorti/mxgraph-editor/grapheditor/js/Graph.js"></script>
	<script type="text/javascript" src="vendor/xorti/mxgraph-editor/grapheditor/js/Format.js"></script>
	<script type="text/javascript" src="vendor/xorti/mxgraph-editor/grapheditor/js/Shapes.js"></script>
	<script type="text/javascript" src="vendor/xorti/mxgraph-editor/grapheditor/js/Actions.js"></script>
	<script type="text/javascript" src="vendor/xorti/mxgraph-editor/grapheditor/js/Menus.js"></script>
	<script type="text/javascript" src="vendor/xorti/mxgraph-editor/grapheditor/js/Toolbar.js"></script>
	<script type="text/javascript" src="vendor/xorti/mxgraph-editor/grapheditor/js/Dialogs.js"></script>
</head>
<body class="geEditor">
<script type="text/javascript">
	// Extends EditorUi to update I/O action states based on availability of backend
	(function()
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

			var editor = this.actions.editorUi.editor;

			this.actions.addAction('save', function() {

				var content = mxUtils.getPrettyXml(editor.getGraphXml());

				//calling ajax edit plugin function
				$.ajax({
					type: 'POST',
					url: 'tiki-ajax_services.php',
					dataType: 'json',
					data: {
						controller: 'plugin',
						action: 'replace',
						ticket: `<?php echo $_POST['ticket']; ?>`,
						page: `<?php echo $_POST['page']; ?>`,
						message:"Modified by mxGraph",
						type: 'diagram',
						content: content,
						index: `<?php echo $_POST['index']; ?>`
					},
					success: function(){
						editor.modified = false;
						window.close();
						window.opener.location.reload(false);
					},
					error: function() {
						//@todo implement
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
			var xml = `<?php echo base64_decode($_POST['xml']);?>`;
			var doc = mxUtils.parseXml(xml);

			// Executes the layout
			var codec = new mxCodec(doc);
			codec.decode(doc.documentElement, ui.editor.graph.getModel());

		}, function()
		{
			document.body.innerHTML = '<center style="margin-top:10%;">Error loading resource files. Please check browser console.</center>';
		});
	})();
</script>
</body>
</html>
