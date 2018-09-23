{* $Id$ *}
{if empty($title)}
	{$title=":{tr}Apply changes{/tr}"}
{/if}
{if empty($value)}
	{$value="{tr}Apply{/tr}"}
{/if}
<br>
<div class="row actions" style="position: fixed;">
	<div class="card-header" id="bottom-actions"></div>
	<div class="container container-content">
		<div class="form-group col-lg-12 clearfix">
			<div class="text-center">
				<input
					type="submit"
					{if !empty($form)}form="{$form|escape:'attr'}"{/if}
					class="btn btn-primary tips"
					title="{$title|escape:'attr'}"
					value="{$value|escape:'attr'}"
					onclick="checkTimeout()"
				>
			</div>
		</div>		
	</div>
</div>
