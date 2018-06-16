{* $Id$ *}
<a class="icon" href="tiki-accounting_export.php?action=print&what=stack&bookId={$bookId}" target="new">
	{icon name="print" alt="{tr}printable version{/tr}"}
</a>
<a class="icon" href="tiki-accounting_export.php?action=settings&what=stack&bookId={$bookId}">
	{icon name="export" alt="{tr}export table{/tr}"}
</a>
<table class="table">
	<tr>
		<th rowspan="2">{tr}Id{/tr}</th>
		<th rowspan="2">{tr}Date{/tr}</th>
		<th rowspan="2">{tr}Description{/tr}</th>
		<th colspan="3">{tr}Debit{/tr}</th>
		<th colspan="3">{tr}Credit{/tr}</th>
		<th rowspan="2">&nbsp;</th>
	</tr>
	<tr>
		<th>{tr}Account{/tr}</th>
		<th>{tr}Amount{/tr}</th>
		<th>{tr}Text{/tr}</th>
		<th>{tr}Account{/tr}</th>
		<th>{tr}Amount{/tr}</th>
		<th>{tr}Text{/tr}</th>
	</tr>
	{foreach from=$stack item=s}{cycle values="odd,even" assign="style"}
		<tr class="{$style}">
			<td class="journal"{if $s.maxcount>1} rowspan="{$s.maxcount}"{/if} style="text-align:right">
				<a href="tiki-accounting_stack.php?bookId={$bookId}&stackId={$s.stackId}">{$s.stackId}</a>
			</td>
			<td class="journal"{if $s.maxcount>1} rowspan="{$s.maxcount}"{/if} style="text-align:right">{$s.stackDate|date_format:"%Y-%m-%d"}</td>
			<td class="journal"{if $s.maxcount>1} rowspan="{$s.maxcount}"{/if}>{$s.stackDescription|escape}</td>
		{section name=posts loop=$s.maxcount}{assign var='i' value=$smarty.section.posts.iteration-1}
			{if !$smarty.section.posts.first}<tr class="{$style}">{/if}
				<td class="journal" style="text-align:right">{if $i<$s.debitcount}{$s.debit[$i].stackItemAccountId}{/if}&nbsp;</td>
				<td class="journal" style="text-align:right">
					{if $i<$s.debitcount}
						{if $book.bookCurrencyPos==-1}{$book.bookCurrency} {/if}
						{$s.debit[$i].stackItemAmount|number_format:$book.bookDecimals:$book.bookDecPoint:$book.bookThousand}
						{if $book.bookCurrencyPos==1} {$book.bookCurrency}{/if}&nbsp;
					{/if}
				</td>
				<td class="journal">{if $i<$s.debitcount}{$s.debit[$i].stackItemText|escape}{/if}&nbsp;</td>
				<td class="journal" style="text-align:right">{if $i<$s.creditcount}{$s.credit[$i].stackItemAccountId}{/if}&nbsp;</td>
				<td class="journal" style="text-align:right">
					{if $i<$s.creditcount}
						{if $book.bookCurrencyPos==-1}{$book.bookCurrency} {/if}
						{$s.credit[$i].stackItemAmount|number_format:$book.bookDecimals:$book.bookDecPoint:$book.bookThousand}
						{if $book.bookCurrencyPos==1} {$book.bookCurrency}{/if}&nbsp;
					{/if}
				</td>
				<td class="journal">{if $i<$s.creditcount}{$s.credit[$i].stackItemText|escape}{/if}&nbsp;</td>
				{if $smarty.section.posts.first}
					<td rowspan="{$s.maxcount}">
						<form action="tiki-accounting_stack.php" method="post">
							<input type="hidden" name="bookId" value="{$bookId|escape:'attr'}">
							<input type="hidden" name="stackId" value="{$s.stackId|escape:'attr'}">
							{ticket}
							<button
								name="action"
								value="delete"
								type="submit"
								class="btn btn-link"
								style="float:left;padding:unset;border:none"
								onclick="confirmSimple(event, '{tr _0="{$s.stackId|escape:'attr'}" _1="{$book.bookName|escape:'attr'}"}Delete stack %0 from book %1?{/tr}')"
							>
								{icon name="remove" title=":{tr}Remove transaction{/tr}" class="tips"}
							</button>
							{if $canBook}
								<button
									name="action"
									value="confirm"
									type="submit"
									class="btn btn-link"
									style="float:left;padding:unset;border:none"
									onclick="confirmSimple(event, '{tr _0="{$s.stackId|escape:'attr'}" _1="{$book.bookName|escape:'attr'}"}Confirm stack %0 for book %1?{/tr}')"
								>
									{icon name="ok" title=":{tr}Confirm transaction{/tr}" class="tips"}
								</button>
							{/if}
						</form>
					</td>
				{/if}
			</tr>
		{/section}
	{foreachelse}
		{norecords _colspan=10}
	{/foreach}
</table>
