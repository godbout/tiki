<body>
{include file='tiki-calendar_edit_item.tpl'}
{if $headerlib}
	{$headerlib->output_js()}
	{if isset($smarty.request.full)}
		{$headerlib->output_js_files()}
	{else}
		<script type="text/javascript" src="lib/jquery_tiki/calendar_edit_item.js"></script>
	{/if}
{/if}
</body>
