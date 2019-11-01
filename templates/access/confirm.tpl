{extends 'layout_view.tpl'}
{block name="title"}
	{title}{$title|escape}{/title}
{/block}
{block name="content"}
	<form
		id="confirm-action"{if !isset($confirmClass) || $confirmClass != 'n'} class='confirm-action'{/if}
		action="{service controller="$confirmController" action="$confirmAction"}"
		method="post"
	>
		{include file='access/include_items.tpl'}
		{include file='access/include_hidden.tpl'}
		{include file='access/include_extra_fields.tpl'}
		{include file='access/include_submit.tpl'}
	</form>
	{if !empty($extra.help)}
		<span class="form-text">
			{$extra.help|escape}
		</span>
	{/if}
{/block}
