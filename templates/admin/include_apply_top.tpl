{* $Id$ *}
{if empty($title)}
	{$title=":{tr}Apply changes{/tr}"}
{/if}
{if empty($value)}
	{$value="{tr}Apply{/tr}"}
{/if}
<div class="float-sm-right">
	<input
		type="submit"
		{if !empty($form)}form="{$form|escape:'attr'}"{/if}
		class="btn btn-primary tips"
		title="{$title|escape:'attr'}"
		value="{$value|escape:'attr'}"
		onclick="checkTimeout()"
	>
</div>
