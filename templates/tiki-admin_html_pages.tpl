{title help="Html Pages"}{tr}Admin HTML pages{/tr}{/title}

{if $pageName ne ''}
	<div class="navt_bar">
		{button _icon_name="create" _text="{tr}Create{/tr}"}
	</div>
{/if}

{remarksbox type="tip" title="{tr}Tip{/tr}"}{tr}Use {literal}{ed id=name}{/literal} or {literal}{ted id=name}{/literal} to insert dynamic zones{/tr}{/remarksbox}

{if $preview eq 'y'}
	<h2>{tr}Preview{/tr}</h2>
	<div class="wikitext">{$parsed}</div>
{/if}

{if $pageName eq ''}
	<h2>{tr}Create new HTML page{/tr}</h2>
{else}
	<h2>{tr}Edit this HTML page:{/tr} {$pageName}</h2>
{/if}

<form action="tiki-admin_html_pages.php" method="post" id="editpageform">
	{ticket}
	<input type="hidden" name="pageName" value="{$pageName|escape}">
	<div class="form-group row">
		<label class="col-sm-3 col-form-label">{tr}Page name{/tr}</label>
		<div class="col-sm-7 offset-sm-1 mb-3">
			<input type="text" maxlength="255" size="40" name="pageName" value="{$info.pageName|escape}" class="form-control">
		</div>
	</div>
	{if $tiki_p_use_content_templates eq 'y'}
	<div class="form-group row">
		<label class="col-sm-3 col-form-label">{tr}Apply template{/tr}</label>
		<div class="col-sm-7 offset-sm-1 mb-3">
			<select name="templateId"{if !$templates} disabled="disabled"{/if} onchange="javascript:document.getElementById('editpageform').submit();" class="form-control">
				<option value="0">{tr}none{/tr}</option>
				{section name=ix loop=$templates}
					<option value="{$templates[ix].templateId|escape}">{tr}{$templates[ix].name}{/tr}</option>
				{/section}
			</select>
		</div>
	</div>
	{/if}
	<div class="form-group row">
		<label class="col-sm-3 col-form-label">{tr}Type{/tr}</label>
		<div class="col-sm-7 offset-sm-1 mb-3">
			<select name="type" class="form-control">
				<option value='d'{if $info.type eq 'd'} selected="selected"{/if}>{tr}Dynamic{/tr}</option>
				<option value='s'{if $info.type eq 's'} selected="selected"{/if}>{tr}Static{/tr}</option>
			</select>
		</div>
	</div>
	<div class="form-group row">
		<label class="col-sm-3 col-form-label">{tr}Refresh rate (if dynamic){/tr}</label>
		<div class="col-sm-7 offset-sm-1 mb-3">
			<input type="text" name="refresh" value="{$info.refresh|escape}" class="form-control"> 
			<div class="form-text">
				{tr}seconds{/tr}
			</div>
		</div>
	</div>
	<div class="form-group row">
		<label class="col-sm-3 col-form-label">{tr}Content{/tr}</label>
		<div class="col-sm-7 offset-sm-1 mb-3">
			<textarea name="content" id="htmlcode" rows="15" class="form-control">{$info.content|escape}</textarea>
		</div>
	</div>
	<div class="form-group row">
		<div class="col-sm-3"></div>
		<div class="col-sm-7 offset-sm-1 mb-3">
			<input type="submit" class="btn btn-secondary" name="preview" value="{tr}Preview{/tr}">
			<input type="submit" class="btn btn-primary" name="save" value="{tr}Save{/tr}">
		</div>
	</div>
 </form>

<br>
<h2>{tr}HTML pages{/tr}</h2>
{if $channels}
	{include file='find.tpl'}
{/if}
<div class="{if $js}table-responsive{/if}"> {* table-responsive class cuts off css drop-down menus *}
	<table class="table table-striped table-hover">
		<tr>
			<th>
				<a href="tiki-admin_html_pages.php?offset={$offset}&amp;sort_mode={if $sort_mode eq 'name_desc'}name_asc{else}name_desc{/if}">{tr}Name{/tr}</a>
			</th>
			<th>
				<a href="tiki-admin_html_pages.php?offset={$offset}&amp;sort_mode={if $sort_mode eq 'type_desc'}type_asc{else}type_desc{/if}">{tr}Type{/tr}</a>
			</th>
			<th>
				<a href="tiki-admin_html_pages.php?offset={$offset}&amp;sort_mode={if $sort_mode eq 'created_desc'}created_asc{else}created_desc{/if}">{tr}Last Modif{/tr}</a>
			</th>
			<th style="width:100px;"></th>
		</tr>


		{section name=user loop=$channels}
			<tr>
				<td class="text">{$channels[user].pageName}</td>
				<td class="text">{$channels[user].type} {if $channels[user].type eq 'd'}({$channels[user].refresh} secs){/if}</td>
				<td class="date">{$channels[user].created|tiki_short_datetime}</td>
				<td class="action">
					{actions}
						{strip}
							<action>
								<a href="tiki-page.php?pageName={$channels[user].pageName|escape:"url"}" title="View">
									{icon name='view' _menu_text='y' _menu_icon='y' alt="{tr}View{/tr}"}
								</a>
							</action>
							<action>
								<a href="tiki-admin_html_pages.php?offset={$offset}&amp;sort_mode={$sort_mode}&amp;pageName={$channels[user].pageName|escape:"url"}">
									{icon name='edit' _menu_text='y' _menu_icon='y' alt="{tr}Edit{/tr}"}
								</a>
							</action>
							<action>
								<a href="tiki-admin_html_page_content.php?pageName={$channels[user].pageName|escape:"url"}" title="{tr}Admin dynamic zones{/tr}">
									{icon name='cog' _menu_text='y' _menu_icon='y' alt="{tr}Admin dynamic zones{/tr}"}
								</a>
							</action>
							<action>
								<a href="tiki-admin_html_pages.php?offset={$offset}&amp;sort_mode={$sort_mode}&amp;remove={$channels[user].pageName|escape:"url"}" onclick="confirmSimple(event, '{tr}Remove HTML page?{/tr}', '{ticket mode=get}')">
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

{pagination_links cant=$cant_pages step=$prefs.maxRecords offset=$offset}{/pagination_links}
