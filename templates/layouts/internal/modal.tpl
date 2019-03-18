{* $Id$ *}<!DOCTYPE html>
{if ! isset($noheader) || $noheader !== 'y'}
	<div class="modal-header">
		<h4 class="modal-title" id="myModalLabel">{$title|escape}{block name=subtitle}{/block}</h4>
		<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
	</div>
{/if}
<div class="modal-body">
	{block name=content}{/block}
	{if $headerlib}
		{$headerlib->output_js_config()}
		{$headerlib->output_js_files()}
		{$headerlib->output_js()}
	{/if}
	{if $prefs.feature_debug_console eq 'y' and not empty($smarty.request.show_smarty_debug)}
		{debug}
	{/if}
</div>
<div class="modal-footer">
	{block name=buttons}
		<button type="button" class="btn btn-secondary btn-dismiss" data-dismiss="modal">{tr}Close{/tr}</button>
	{/block}
</div>
