{* $Id$ *}
{title help="accounting"}
	{$book.bookName}
{/title}
{if !empty($errors)}
	<div class="alert alert-warning">
		{icon name='error' alt="{tr}Error{/tr}" style="vertical-align:middle" align="left"}
		{foreach from=$errors item=m name=errors}
			{$m}
			{if !$smarty.foreach.errors.last}<br>{/if}
		{/foreach}
	</div>
{/if}
{tabset}
	{tab name="{tr}General{/tr}"}
		<h2>{tr}General{/tr}</h2>
		<div class="card">
			<h3 class="card-heading">{tr}This book{/tr}</h3>
			<div class="card-body">
				<dl class="row mx-0">
					<dt class="col-sm-3">{tr}Id{/tr}</dt><dd class="col-sm-9">{$book.bookId}</dd>
					<dt class="col-sm-3">{tr}Name{/tr}</dt><dd class="col-sm-9">{$book.bookName}</dd>
					<dt class="col-sm-3">{tr}Start date{/tr}</dt><dd class="col-sm-9">{$book.bookStartDate}</dd>
					<dt class="col-sm-3">{tr}End date{/tr}</dt><dd class="col-sm-9">{$book.bookEndDate}</dd>
					<dt class="col-sm-3">{tr}Closed{/tr}</dt><dd class="col-sm-9">{if $book.bookClosed=='y'}{tr}Yes{/tr}{else}{tr}No{/tr}{/if}</dd>
					<dt class="col-sm-3">{tr}Currency{/tr}</dt><dd class="col-sm-9">{$book.bookCurrency} ({if $book.bookCurrencyPos==-1}{tr}before{/tr}{elseif $book.bookCurrencyPos==1}{tr}after{/tr}{else}{tr}don't display{/tr}{/if})</dd>
					<dt class="col-sm-3">{tr}Decimals{/tr}</dt><dd class="col-sm-9">{$book.bookDecimals}</dd>
					<dt class="col-sm-3">{tr}Decimal Point{/tr}</dt><dd class="col-sm-9">{$book.bookDecPoint}</dd>
					<dt class="col-sm-3">{tr}Thousands separator{/tr}</dt><dd class="col-sm-9">{$book.bookThousand}</dd>
					<dt class="col-sm-3">{tr}Auto Tax{/tr}</dt><dd class="col-sm-9">{if $book.bookAutoTax=='y'}{tr}Yes{/tr}{else}{tr}No{/tr}{/if}</dd>
				</dl>
			</div>
		</div>
		<div class="card">
			<h3 class="card-heading">{tr}Tasks{/tr}</h3>
			<div class="card-body">
				{if $canBook}
					{button href="tiki-accounting_entry.php?bookId={$bookId|escape:'attr'}" _text="{tr}Book new entries{/tr}"}
					{button href="tiki-accounting_stack.php?bookId={$bookId|escape:'attr'}&hideform=1" _text="{tr}Confirm stack entries{/tr}"}
				{/if}
				{if $canStack}
					{button href="tiki-accounting_stack.php?bookId={$bookId|escape:'attr'}" _text="{tr}Book into Stack{/tr}"}
				{/if}
			</div>
		</div>
	{/tab}
	{tab name="{tr}Accounts{/tr}"}
		<h2>{tr}Accounts{/tr}</h2>
		{include file="tiki-accounting_account_list.tpl"}
	{/tab}
	{*{tab name="{tr}Bank accounts{/tr}"}*}
		{*<h2>{tr}Bank accounts{/tr}</h2>*}
	{*{/tab}*}
	{tab name="{tr}Journal{/tr}"}
		<h2>{tr}Journal{/tr}</h2>
		<div style="max-height: 80%; overflow: scroll;">
			{if $journalLimit!=0}
				{button href="tiki-accounting.php?bookId={$bookId|escape:'attr'}&cookietab=4&journalLimit=0" text="{tr}Fetch all{/tr}"}
			{/if}
			{include file="tiki-accounting_journal.tpl"}
		</div>
	{/tab}
{/tabset}
