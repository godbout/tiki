{* $Id$ *}
{extends file="file_displays/diagram.tpl"}
{block name="diagram_extra"}
	{if $allow_edit}
		<div class="text-right">
			<form id="edit-diagram-{$index}" target="_blank" action="tiki-editdiagram.php" method="post">
				{if $file_id}
					<input type="hidden" value="{$file_id}" name="fileId">
				{else}
					<input type="hidden" value="{$graph_data_base64}" name="xml">
					<input type="hidden" value="{$sourcepage}" name="page">
					<input type="hidden" value="{$index}" name="index">
				{/if}
				<a class="btn btn-link" href="javascript:void(0)" onclick="$('#edit-diagram-{$index}').submit()">{icon name="pencil"} Edit diagram</a>
			</form>
		</div>
	{/if}
{/block}