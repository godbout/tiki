// $Id$
// Tiki mxGraph loader and settings

// Rewrite mxgraph base directory
mxLoadResources = false;
mxBasePath = 'vendor/xorti/mxgraph-editor/src';
grapheditorBase = 'vendor/xorti/mxgraph-editor/drawio/webapp/';
drawioBase = 'vendor/xorti/mxgraph-editor/drawio/webapp/';
TEMPLATE_PATH = drawioBase + 'templates';
STENCIL_PATH = drawioBase + 'stencils';
SHAPES_PATH = drawioBase + 'shapes';
IMAGE_PATH = drawioBase + 'images';
STYLE_PATH = drawioBase + 'styles';
CSS_PATH = drawioBase + 'styles';
RESOURCES_PATH = grapheditorBase + 'resources';
GRAPH_IMAGE_PATH  = IMAGE_PATH;

// isLocalStorage controls access to local storage
window.isLocalStorage = window.isLocalStorage || false;

function handleXmlData(graph, data) {

	var xml = data;
	var doc = mxUtils.parseXml(xml);

	if (doc.documentElement != null && doc.documentElement.nodeName == 'mxfile')
	{
		diagrams = doc.documentElement.getElementsByTagName('diagram');
		configNode = doc.documentElement;

		if (diagrams.length > 0)
		{
			var xmlCompressed = mxUtils.getTextContent(diagrams[0]);
			xml = graph.decompress(xmlCompressed);
			doc = mxUtils.parseXml(xml);
		}
	}

	// Executes the layout
	var codec = new mxCodec(doc);
	codec.decode(doc.documentElement, graph.getModel());
}

// Main function that loads and render the graph
function mxGraphMain(container, graph_data, themes) {

	var graph = new Graph(container, null, null, null, themes);

	graph.setEnabled(false);
	graph.setPanning(false);
	graph.autoScroll = false;
	graph.isHtmlLabel = function () {
		return true;
	};

	handleXmlData(graph, graph_data);

	// Hack to make the svg responsive
	if (!!navigator.userAgent.match(/Trident/g) || !!navigator.userAgent.match(/MSIE/g)) {
		return; // svg responsive hack bellow is not working in IE
	}
	graph.addListener('refresh', function(evt)
	{
		var svg = $($(container).find('svg')[0]);

		var height = svg.css('min-height');
		var width = svg.css('min-width');

		svg.attr('viewBox', '0 0 ' + parseInt(width) + ' ' + parseInt(height));

		svg.css('min-height', '');
		svg.css('min-width', '');
	});

	graph.refresh();
}
