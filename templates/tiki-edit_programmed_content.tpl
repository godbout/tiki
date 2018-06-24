{title url="tiki-edit_programmed_content.php?contentId=$contentId"}{tr}Program dynamic content for block:{/tr} {$contentId}{/title}

<div class="t_navbar">
	{button href="?contentId=$contentId" class="btn btn-primary" _text="{tr}Create New Block{/tr}"}
	{button href="tiki-list_contents.php" class="btn btn-primary" _text="{tr}Return to block listing{/tr}"}
</div>

<h2>{tr}Block description: {/tr}{$description}</h2>

<h3>
	{if $data}
		{tr}Edit{/tr}
	{else}
		{tr}Create{/tr}
	{/if}
	{tr}content{/tr}
</h3>

{if $pId}
	{tr}You are editing block:{/tr} {$pId}<br>
{/if}
<br>
<form action="tiki-edit_programmed_content.php" method="post">
	<input type="hidden" name="contentId" value="{$contentId|escape}">
	<input type="hidden" name="pId" value="{$pId|escape}">

	<div class="form-group row">
		<label class="col-sm-3 col-form-label">{tr}Content Type{/tr}</label>
		<div class="col-sm-7">
			<select name="content_type" class="form-control type-selector">
				<option value="static"{if $info.content_type eq 'static'} selected="selected"{/if}>{tr}Text area{/tr}</option>
				<option value="page"{if $info.content_type eq 'page'} selected="selected"{/if}>{tr}Wiki Page{/tr}</option>
			</select>
		</div>
	</div>
	<div class="form-group type-cond for-page">
		<label class="col-sm-3 col-form-label">{tr}Page Name{/tr}</label>
		<div class="col-sm-7">
			<input type="text" name="page_name" value="{$info.page_name|escape}" class="form-control">
		</div>
	</div>
	<div class="form-group row">
		<label class="col-sm-3 col-form-label">{tr}Content{/tr}</label>
		<div class="col-sm-7">
			<textarea rows="5" cols="40" name="data" class="form-control">{$info.data|escape}</textarea>
		</div>
	</div>
	<div class="form-group row">
		<label class="col-sm-3 col-form-label">{tr}Publising Date{/tr}</label>
		<div class="col-sm-7">
			{html_select_date time=$publishDate end_year="+1" field_order=$prefs.display_field_order}
			{tr}at{/tr} {html_select_time time=$publishDate display_seconds=false use_24_hours=$use_24hr_clock}
		</div>
	</div>
	<div class="form-group row">
		<label class="col-sm-3 col-form-label"></label>
		<div class="col-sm-7">
			<input type="submit" class="btn btn-primary" name="save" value="{tr}Save{/tr}">
		</div>
	</div>
	{jq}
		$('.type-selector').change( function( e ) {
			$('.type-cond').hide();
			var val = $('.type-selector').val();
			$('.for-' + val).show();
		} ).trigger('change');
	{/jq}
</form>

<h2>{tr}Versions{/tr}</h2>

{if $listpages or ($find ne '')}
	{include file='find.tpl'}
{/if}

<div class="{if $js}table-responsive{/if}"> {* table-responsive class cuts off css drop-down menus *}
	<table class="table">
		<tr>
			<th>{self_link _sort_arg='sort_mode' _sort_field='pId'}{tr}Id{/tr}{/self_link}</th>
			<th>{self_link _sort_arg='sort_mode' _sort_field='publishDate'}{tr}Publishing Date{/tr}{/self_link}</th>
			<th>{self_link _sort_arg='sort_mode' _sort_field='data'}{tr}Data{/tr}{/self_link}</th>
			<th></th>
		</tr>
		{section name=changes loop=$listpages}
			{if $actual eq $listpages[changes].publishDate}
				{assign var=class value=third}
			{else}
				{if $actual > $listpages[changes].publishDate}
					{assign var=class value=odd}
				{else}
					{assign var=class value=even}
				{/if}
			{/if}
			<tr class="{$class}">
				<td class="id">&nbsp;{$listpages[changes].pId}&nbsp;</td>
				<td class="date">&nbsp;{$listpages[changes].publishDate|tiki_short_datetime}&nbsp;</td>
				<td class="text">&nbsp;{$listpages[changes].data|escape:'html'|nl2br}&nbsp;</td>
				<td class="action">
					{actions}
						{strip}
							<action>
								<a href="tiki-edit_programmed_content.php?offset={$offset}&amp;sort_mode={$sort_mode}&amp;contentId={$contentId}&amp;edit={$listpages[changes].pId}">
									{icon name='edit' _menu_text='y' _menu_icon='y' alt="{tr}Edit{/tr}"}
								</a>
							</action>
							<action>
								<a href="tiki-edit_programmed_content.php?offset={$offset}&amp;sort_mode={$sort_mode}&amp;contentId={$contentId}&amp;remove={$listpages[changes].pId}">
									{icon name='remove' _menu_text='y' _menu_icon='y' alt="{tr}Remove{/tr}"}
								</a>
							</action>
						{/strip}
					{/actions}
				</td>
			</tr>
		{sectionelse}
			{norecords _colspan=4}
		{/section}
	</table>
</div>

{pagination_links cant=$cant step=$prefs.maxRecords offset=$offset}{/pagination_links}
