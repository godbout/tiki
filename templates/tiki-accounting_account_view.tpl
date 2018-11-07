{* $Id$ *}
{title help="accounting"}
	{$book.bookName}:
	{tr}View account{/tr} {$account.accountId} {$account.accountName}
{/title}
<form id="account-view-form" method="post" action="tiki-accounting_account.php">
	<input type="hidden" name="bookId" value="{$bookId}">
	<input type="hidden" name="accountId" value="{$account.accountId}">
	{ticket}
	<div id="account_view">
		<dl class="row mx-0">
			<dt class="col-sm-3">{tr}Account number{/tr}</dt><dd class="col-sm-9">{$account.accountId}</dd>
			<dt class="col-sm-3">{tr}Account name{/tr}</dt><dd class="col-sm-9">{$account.accountName}</dd>
			<dt class="col-sm-3">{tr}Notes{/tr}</dt><dd class="col-sm-9">{$account.accountNotes}</dd>
			<dt class="col-sm-3">{tr}Budget{/tr}</dt><dd class="col-sm-9">{if $book.bookCurrencyPos==-1}{$book.bookCurrency} {/if}{$account.accountBudget|number_format:$book.bookDecimals:$book.bookDecPoint:$book.bookThousand}{if $book.bookCurrencyPos==1} {$book.bookCurrency}{/if}</dd>
			<dt class="col-sm-3">{tr}Locked{/tr}</dt><dd class="col-sm-9">{if $account.accountLocked==1}{tr}Yes{/tr}{else}{tr}No{/tr}{/if}</dd>
		</dl>
		{button href="tiki-accounting.php?bookId=$bookId" _text="Back to book page"}
		{if $tiki_p_acct_manage_accounts=='y'}
			<button type="submit" class="btn btn-primary btn-sm" name="action" value="edit">
				{tr}Edit this account{/tr}
			</button>
			{if $account.changeable==1}
				<button
					type="submit"
					class="btn btn-warning btn-sm"
					name="action"
					value="delete"
					onclick="confirmSimple(event, '{tr _0="{$account.accountName|escape:'attr'}" _1="{$book.bookName|escape:'attr'}"}Delete account %0 from book %1?{/tr}')"
				>{tr}Delete{/tr}</button>
			{/if}
		{/if}
	</div>
</form>
{if isset($journal)}
	<div id="account_journal">
		{include file='tiki-accounting_journal.tpl'}
	</div>
{/if}

