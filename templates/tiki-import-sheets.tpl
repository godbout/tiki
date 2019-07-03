
{title help="Spreadsheet"}{$title}{/title}

<div>
	{$description|escape}
</div>

<div class="t_navbar mb-4">
	{if $tiki_p_view_sheet eq 'y' || $tiki_p_admin_sheet eq 'y' || $tiki_p_admin eq 'y'}
		{button href="tiki-sheets.php" class="btn btn-primary" _text="{tr}List Sheets{/tr}"}
	{/if}

	{if $tiki_p_view_sheet eq 'y' || $tiki_p_admin_sheet eq 'y' || $tiki_p_admin eq 'y'}
		{button href="tiki-view_sheets.php?sheetId=$sheetId" class="btn btn-primary" _text="{tr}View{/tr}"}
	{/if}

	{if $tiki_p_edit_sheet eq 'y' || $tiki_p_admin_sheet eq 'y' || $tiki_p_admin eq 'y'}
		{button href="tiki-view_sheets.php?sheetId=$sheetId&amp;readdate=$read_date&amp;mode=edit" class="btn btn-primary" _text="{tr}Edit{/tr}"}
	{/if}

	{if $tiki_p_view_sheet_history eq 'y' || $tiki_p_admin_sheet eq 'y' || $tiki_p_admin eq 'y'}
		{button href="tiki-history_sheets.php?sheetId=$sheetId" class="btn btn-primary" _text="{tr}History{/tr}"}
	{/if}

	{if $tiki_p_view_sheet eq 'y' || $tiki_p_admin_sheet eq 'y' || $tiki_p_admin eq 'y'}
		{button href="tiki-export_sheet.php?sheetId=$sheetId" class="btn btn-primary" _text="{tr}Export{/tr}"}
	{/if}

	{if $chart_enabled eq 'y'}
		{button href="tiki-graph_sheet.php?sheetId=$sheetId" class="btn btn-primary" _text="{tr}Graph{/tr}"}
	{/if}
</div>

{if $page_mode eq 'submit'}
	{$grid_content}

{else}
	<form method="post" action="tiki-import_sheet.php?mode=import&sheetId={$sheetId}" enctype="multipart/form-data" class="mb-4">
		<h2>{tr}Import From File{/tr}</h2>
		<div class="form-group row">
			<label class="col-form-label col-sm-3">{tr}Format:{/tr}</label>
			<div class="col-sm-6">
				<select name="handler" class="form-control">
					{section name=key loop=$handlers}
						<option value="{$handlers[key].class}">{$handlers[key].name} V. {$handlers[key].version}</option>
					{/section}
				</select>
			</div>
		</div>

		<div class="form-group row">
			<label class="col-form-label col-sm-3">{tr}Charset encoding:{/tr}</label>
			<div class="col-sm-6">
				<select name="encoding" class="form-control">
					<!--<option value="">{tr}Autodetect{/tr}</option>-->
					{section name=key loop=$charsets}
						<option value="{$charsets[key]}">{$charsets[key]}</option>
					{/section}
				</select>
			</div>
		</div>

		<div class="form-group row">
			<label class="col-form-label col-sm-3">{tr}File to import:{/tr}</label>
			<div class="col-sm-6">
				<input type="file" name="file" class="form-control">
			</div>
		</div>
		<div class="form-group row">
			<div class="col-sm-6 offset-sm-3">
				<input type="submit" class="btn btn-primary" value="{tr}Import{/tr}">
			</div>
		</div>
	</form>

	<form method="post" action="tiki-import_sheet.php?mode=import&sheetId={$sheetId}">
		<h2>{tr}Grab Wiki Tables{/tr}</h2>
		<div class="form-group row">
			<div class="col-sm-6">
				<input id="querypage" type="text" name="page" class="form-control">
				<input type="hidden" name="handler" value="TikiSheetWikiTableHandler">
			</div>
			<div class="col-sm-6">
				<input type="submit" class="btn btn-primary" value="Import">
			</div>
		</div>
	</form>
	{if $prefs.javascript_enabled eq 'y' and $prefs.feature_jquery_autocomplete eq 'y'}
		{jq}
			$("#querypage").tiki("autocomplete", "pagename");
		{/jq}
	{/if}
{/if}
