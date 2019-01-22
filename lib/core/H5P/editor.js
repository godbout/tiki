(function ($) {
	H5PEditor.init = function () {
		H5PEditor.$ = H5P.jQuery;
		H5PEditor.basePath = H5PIntegration.editor.libraryUrl;
		H5PEditor.fileIcon = H5PIntegration.editor.fileIcon;
		H5PEditor.ajaxPath = H5PIntegration.editor.ajaxPath;
		H5PEditor.filesPath = H5PIntegration.editor.filesPath;
		H5PEditor.apiVersion = H5PIntegration.editor.apiVersion;
		H5PEditor.copyrightSemantics = H5PIntegration.editor.copyrightSemantics;
		H5PEditor.metadataSemantics = H5PIntegration.editor.metadataSemantics;
		H5PEditor.assets = H5PIntegration.editor.assets;
		H5PEditor.baseUrl = '';
		if (H5PIntegration.editor.nodeVersionId !== undefined) {
			H5PEditor.contentId = H5PIntegration.editor.nodeVersionId;
		}

		var $editor = $('.h5p-editor');
		var $library = $('input[name="library"]');
		var $params = $('input[name="parameters"]');
		var h5peditor = new ns.Editor($library.val(), $params.val(), $editor[0]);

		$('.content-form').submit(function () {
			if (h5peditor !== undefined) {
				var params = h5peditor.getParams();
				if (params !== undefined) {
					// Validate mandatory main title. Prevent submitting if that's not set.
					// Deliberatly doing it after getParams(), so that any other validation
					// problems are also revealed
					if (!h5peditor.isMainTitleSet()) {
						return event.preventDefault();
					}

					// Set main library
					$library.val(h5peditor.getLibrary());

					// Set params
					$params.val(JSON.stringify(params));

					// TODO - Calculate & set max score
					// $maxscore.val(h5peditor.getMaxScore(params.params));
				}
			}
		});
	};

	H5PEditor.getAjaxUrl = function (action, parameters) {
		var url = H5PIntegration.editor.ajaxPath + action.replace(/-/g, "_");

		if (parameters !== undefined) {
			for (var property in parameters) {
				if (parameters.hasOwnProperty(property)) {
					url += '&' + property + '=' + parameters[property];
				}
			}
		}

		return url;
	};

	$(document).ready(H5PEditor.init);
})(H5P.jQuery);
