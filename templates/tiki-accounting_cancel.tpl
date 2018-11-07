{* $Id$ *}
{title help="accounting"}
	{$book.bookName}
{/title}
<div id="original">
	<h4>{tr}Canceled Journal{/tr}</h4>
	<dl class="row mx-0">
		<dt class="col-sm-3">{tr}ID{/tr}</dt><dd class="col-sm-9">{$entry.journalId}</dd>
		<dt class="col-sm-3">{tr}Booking Date{/tr}</dt><dd class="col-sm-9">{$entry.journalDate|date_format:"%Y-%m-%d"}</dd>
		<dt class="col-sm-3">{tr}Description{/tr}</dt><dd class="col-sm-9">{$entry.journalDescription}</dd>
	</dl>
	<div id="debit">
		<h4>{tr}Debit{/tr}</h4>
		<table id="tbl_debit" class="table">
			<tr><th>{tr}Text{/tr}</th><th>{tr}Account{/tr}</th><th>{tr}Amount{/tr}</th></tr>
			{foreach from=$entry.debit item=d}{cycle values="odd,even" assign="style"}
				<tr class="{$style}">
					<td>{$d.itemText}</td>
					<td>{$d.itemAccountId}</td>
					<td>
						{if $book.bookCurrencyPos==-1}{$book.bookCurrency}{/if}
						{$d.itemAmount|number_format:$book.bookDecimals:$book.bookDecPoint:$book.bookThousand}
						{if $book.bookCurrencyPos==1}{$book.bookCurrency}{/if}
					</td>
				</tr>
			{/foreach}
		</table>
	</div>
	<div id="credit">
		<h4>{tr}Credit{/tr}</h4>
		<table id="tbl_credit" class="table">
			<tr><th>{tr}Text{/tr}</th><th>{tr}Account{/tr}</th><th>{tr}Amount{/tr}</th></tr>
			{foreach from=$entry.credit item=c}{cycle values="odd,even" assign="style"}
				<tr>
					<td>{$c.itemText}</td>
					<td>{$c.itemAccountId}</td>
					<td>
						{if $book.bookCurrencyPos==-1}{$book.bookCurrency}{/if}
						{$c.itemAmount|number_format:$book.bookDecimals:$book.bookDecPoint:$book.bookThousand}
						{if $book.bookCurrencyPos==1}{$book.bookCurrency}{/if}
					</td>
				</tr>
			{/foreach}
		</table>
	</div>
</div>
{button href="tiki-accounting.php?bookId=$bookId" _text="{tr}Back to book page{/tr}"}
