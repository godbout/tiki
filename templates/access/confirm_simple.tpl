{block name="title" hide}
{/block}
{block name="content"}
	<h4>{$text|escape}</h4>
	<form id="confirm-simple" action="{$formAction|escape:'attr'}" method="post">
		{foreach $hidden as $name => $value}
			<input type="hidden" name="{$name|escape:'attr'}" value="{$value|escape:'attr'}">
		{/foreach}
		{ticket mode='confirm'}
	</form>
{/block}
{block name=buttons}
	<button type="button" class="btn btn-secondary btn-dismiss bogus" data-dismiss="modal">{tr}Close{/tr}</button>
	<button type='submit' form="confirm-simple" class="btn btn-success">
		{tr}OK{/tr}
	</button>
{/block}