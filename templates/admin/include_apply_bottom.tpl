{* $Id$ *}
{if empty($title)}
	{$title=":{tr}Apply changes{/tr}"}
{/if}
{if empty($value)}
	{$value="{tr}Apply{/tr}"}
{/if}
<br>
<div class="row">
	<div class="formgroup collg12 clearfix">
		<div class="textcenter">
			<input
					type="submit"
					{if !empty($form)}form="{$form|escape:'attr'}"{/if}
					class="btn btnprimary tips"
					title="{$title|escape:'attr'}"
					value="{$value|escape:'attr'}"
					onclick="checkTimeout()"
			>
		</div>
	</div>
</div>
