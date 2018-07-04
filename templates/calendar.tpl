<body>
{include file='tiki-calendar_edit_item.tpl'}
{if $headerlib}
	{if isset($smarty.request.full)}
		{$headerlib->output_js_files()}
	{else}
		<script type="text/javascript" src="lib/jquery_tiki/calendar_edit_item.js"></script>
	{/if}
	{$headerlib->output_js()}
{/if}
</body>
