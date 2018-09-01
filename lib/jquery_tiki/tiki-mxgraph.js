// $Id$
// Tiki mxGraph loader and settings

// Rewrite mxgraph base directory
mxLoadResources = false;
mxBasePath = 'vendor/xorti/mxgraph-editor/src';
grapheditorBase = 'vendor/xorti/mxgraph-editor/grapheditor/';
STENCIL_PATH = grapheditorBase + 'stencils';
IMAGE_PATH = grapheditorBase + 'images';
STYLE_PATH = grapheditorBase + 'styles';
CSS_PATH = grapheditorBase + 'styles';

// Function required by Graph
var urlParams = (function (url) {
	var result = new Object();
	var idx = url.lastIndexOf('?');

	if (idx > 0) {
		var params = url.substring(idx + 1).split('&');

		for (var i = 0; i < params.length; i++) {
			idx = params[i].indexOf('=');

			if (idx > 0) {
				result[params[i].substring(0, idx)] = params[i].substring(idx + 1);
			}
		}
	}

	return result;
})(window.location.href);

// Main function that loads and render the graph
function mxGraphMain(container, graph_data, themes) {

	var graph = new Graph(container, null, null, null, themes);

	graph.setEnabled(false);
	graph.setPanning(false);
	graph.autoScroll = false;
	graph.isHtmlLabel = function () {
		return true;
	};

	var xml = graph_data;
	var doc = mxUtils.parseXml(xml);

	// Executes the layout
	var codec = new mxCodec(doc);
	codec.decode(doc.documentElement, graph.getModel());
}
