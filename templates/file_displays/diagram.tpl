{* $Id$ *}
{foreach from=$data item=$diagram name=diagrams}
	<div id="graph-container{if isset($index)}-{$index}{/if}-{$smarty.foreach.diagrams.iteration|escape}" class="diagram text-{{$alignment}}" page="{$page_name}"></div>
{/foreach}
{block name="diagram_extra"}{/block}

{jq notonready=true}
	mxUtils.getAll([STYLE_PATH + '/default.xml'], function(xhr) {
		var diagramsXml = JSON.parse('{{$data|json_encode|escape:"javascript" nofilter}}');
		var themes = new Object();
		themes[Graph.prototype.defaultThemeName] = xhr[0].getDocumentElement();

		for (var i = 0; i < diagramsXml.length; i++) {
			var diagramIteration = i + 1;
			var parsedDiagram = '<mxfile>' + diagramsXml[i] + '</mxfile>'

			mxGraphMain(document.getElementById('graph-container{{if isset($index)}}-{{$index}}{{/if}}-' + diagramIteration), parsedDiagram, themes);
		}
	});
{/jq}
