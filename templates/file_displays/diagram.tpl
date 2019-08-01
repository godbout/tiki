{* $Id$ *}
<div id="graph-container{if isset($index)}-{$index}{/if}" class="diagram" page="{$page_name}"></div>
{block name="diagram_extra"}{/block}

{jq notonready=true}
	mxUtils.getAll([STYLE_PATH + '/default.xml'], function(xhr) {
		var xml = '{{$data}}';

		var themes = new Object();
		themes[Graph.prototype.defaultThemeName] = xhr[0].getDocumentElement();
		mxGraphMain(document.getElementById('graph-container{{if isset($index)}}-{{$index}}{{/if}}'), xml, themes);
	});
{/jq}
