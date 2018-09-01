{* $Id$ *}
<div id="graph-container" class="diagram">
</div>

{jq notonready=true}
	var xml = `{{$graph_data}}`;

	mxUtils.getAll([STYLE_PATH + '/default.xml'], function(xhr) {
		var themes = new Object();
		themes[Graph.prototype.defaultThemeName] = xhr[0].getDocumentElement();

		mxGraphMain(document.getElementById('graph-container'), xml, themes);
	});
{/jq}

