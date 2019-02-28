{* $Id$ *}
<div id="graph-container-{$index}" class="diagram"></div>
{if $allow_edit}
<div class="text-right">
	<form id="edit-diagram-{$index}" target="_blank" action="tiki-editdiagram.php" method="post">
		<input type="hidden" value="{$graph_data_base64}" name="xml">
		{if $file_id}
			<input type="hidden" value="{$file_id}" name="fileId">
			<input type="hidden" value="{$file_name}" name="fileName">
		{else}
			<input type="hidden" value="{$sourcepage}" name="page">
			<input type="hidden" value="{$index}" name="index">
		{/if}
		<a class="btn btn-link" href="javascript:void(0)" onclick="$('#edit-diagram-{$index}').submit()">{icon name="pencil"} Edit diagram</a>
	</form>
</div>
{/if}
{jq notonready=true}
	var xml = '{{$graph_data}}';

	mxUtils.getAll([STYLE_PATH + '/default.xml'], function(xhr) {
		var themes = new Object();
		themes[Graph.prototype.defaultThemeName] = xhr[0].getDocumentElement();

		mxGraphMain(document.getElementById('graph-container-{{$index}}'), xml, themes);
	});
{/jq}

