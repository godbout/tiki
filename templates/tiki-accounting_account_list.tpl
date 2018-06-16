{* $Id$ *}
<table class="table">
	<tr>
		<th>{tr}Account{/tr}</th>
		<th>{tr}Account name{/tr}</th>
		<th>{tr}Notes{/tr}</a></th>
		<th>{tr}Budget{/tr}</th>
		<th>{tr}Locked{/tr}</th>
		<th>{tr}Debit{/tr}</th>
		<th>{tr}Credit{/tr}</th>
		<th>{tr}Tax{/tr}</th>
		{if $tiki_p_acct_manage_accounts=='y'}
			<th></th>
		{/if}
	</tr class="action">
	{foreach from=$accounts item=a}{cycle values="odd,even" assign="style"}
		<tr class="{$style}">
			<td style="text-align:right"><a href="tiki-accounting_account.php?bookId={$bookId}&accountId={$a.accountId}">{$a.accountId}</a></td>
			<td><a href="tiki-accounting_account.php?bookId={$bookId}&accountId={$a.accountId}">{$a.accountName|escape}</a></td>
			<td>{$a.accountNotes|escape}</td>
			<td style="text-align:right">
				{if $book.bookCurrencyPos==-1}{$book.bookCurrency}{/if}
				{$a.accountBudget|number_format:$book.bookDecimals:$book.bookDecPoint:$book.bookThousand}
				{if $book.bookCurrencyPos==1}{$book.bookCurrency}{/if}
			</td>
			<td>{if $a.accountLocked==1}{tr}Yes{/tr}{else}{tr}No{/tr}{/if}</td>
			<td style="text-align:right">
				{if $book.bookCurrencyPos==-1}{$book.bookCurrency}{/if}
				{$a.debit|number_format:$book.bookDecimals:$book.bookDecPoint:$book.bookThousand}
				{if $book.bookCurrencyPos==1}{$book.bookCurrency}{/if}
			</td>
			<td style="text-align:right">
				{if $book.bookCurrencyPos==-1}{$book.bookCurrency}{/if}
				{$a.credit|number_format:$book.bookDecimals:$book.bookDecPoint:$book.bookThousand}
				{if $book.bookCurrencyPos==1}{$book.bookCurrency}{/if}
			</td>
			<td>{$a.accountTax}</td>
			{if $tiki_p_acct_manage_accounts=='y'}
				<td class="action">
					{capture name=account_actions}
						{strip}
							{$libeg}<a href="tiki-accounting_account.php?bookId={$bookId|escape:'attr'}&accountId={$a.accountId|escape:'attr'}&action=edit" class="iconmenu">
								{icon name="edit" _menu_text='y' _menu_icon='y' alt="{tr}Edit account{/tr}"}
							</a>{$liend}
							{$libeg}<form action="tiki-accounting_account.php" class="form-inline" method="post">
								<input type="hidden" name="accountId" value="{$a.accountId|escape:'attr'}">
								<input type="hidden" name="bookId" value="{$bookId|escape:'attr'}">
								{ticket}
								{* doesn't need to be confirmed since action is easily undone, but need to check for ticket expiry *}
								<button name="action" value="lock" type="submit" class="btn btn-link iconmenu" onclick="checkTimeout()">
									{if $a.accountLocked==1}
										{icon name="unlock"  _menu_text='y' _menu_icon='y' alt="{tr}Unlock account{/tr}"}
									{else}
										{icon name="lock"  _menu_text='y' _menu_icon='y' alt="{tr}Lock account{/tr}"}
									{/if}
								</button>{$liend}
							</form>{$liend}
							{$libeg}<form action="tiki-accounting_account.php" class="form-inline" method="post">
								<input type="hidden" name="accountId" value="{$a.accountId|escape:'attr'}">
								<input type="hidden" name="bookId" value="{$bookId|escape:'attr'}">
								{ticket}
								<button name="action" value="delete" type="submit" class="btn btn-link iconmenu" onclick="confirmSimple(event, '{tr _0="{$a.accountName|escape}" _1="{$book.bookName|escape}"}Delete account %0 from book %1?{/tr}')">
									{icon name="remove"  _menu_text='y' _menu_icon='y' alt="{tr}Remove account{/tr}"}
								</button>
							</form>{$liend}
						{/strip}
					{/capture}
					{include file="templates/includes/tiki-actions_link.tpl" capturedActions="account_actions"}
				</td>
			{/if}
		</tr>
	{/foreach}
</table>
{button href="tiki-accounting_account.php?action=new&bookId={$bookId|escape:'attr'}" _text="{tr}Create a new account{/tr}"}
<a class="icon" href="tiki-accounting_export.php?action=print&bookId={$bookId|escape:'attr'}&what=accounts" target="new">
	{icon name="print" alt="{tr}printable version{/tr}"}
</a>
<a class="icon" href="tiki-accounting_export.php?action=settings&bookId={$bookId|escape:'attr'}&what=accounts">
	{icon name="export" alt="{tr}export table{/tr}"}
</a>
