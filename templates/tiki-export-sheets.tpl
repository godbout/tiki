
{title}{tr}{$title}{/tr}{/title}

<div>
	{$description|escape}
</div>

<div class="t_navbar mb-4">
	{if $tiki_p_edit_sheet eq 'y' || $tiki_p_admin_sheet eq 'y' || $tiki_p_admin eq 'y'}
		{button href="tiki-view_sheets.php?sheetId=$sheetId&amp;readdate=$read_date&amp;parse=edit" class="btn btn-primary" _text="{tr}Edit{/tr}"}
	{/if}
	{if $tiki_p_admin_sheet eq 'y' || $tiki_p_admin eq 'y'}
		{button href="tiki-import_sheet.php?sheetId=$sheetId" class="btn btn-primary" _text="{tr}Import{/tr}"}
	{/if}
	{if $tiki_p_view_sheet eq 'y' || $tiki_p_admin_sheet eq 'y' || $tiki_p_admin eq 'y'}
		{button href="tiki-sheets.php" class="btn btn-info" _text="{tr}List Sheets{/tr}"}
	{/if}
	{if $tiki_p_view_sheet eq 'y' || $tiki_p_admin_sheet eq 'y' || $tiki_p_admin eq 'y'}
		{button href="tiki-view_sheets.php?sheetId=$sheetId" class="btn btn-info" _text="{tr}View{/tr}"}
	{/if}
	{if $chart_enabled eq 'y'}
		{button href="tiki-graph_sheet.php?sheetId=$sheetId" class="btn btn-primary" _text="{tr}Graph{/tr}"}
	{/if}
	{if $tiki_p_view_sheet_history eq 'y' || $tiki_p_admin_sheet eq 'y' || $tiki_p_admin eq 'y'}
		{button href="tiki-history_sheets.php?sheetId=$sheetId" class="btn btn-info" _text="{tr}History{/tr}"}
	{/if}
</div>

{if $page_mode eq 'submit'}
{$grid_content}

{else}
	<form method="post" action="tiki-export_sheet.php?mode=export&sheetId={$sheetId}" enctype="multipart/form-data">
		<h2>{tr}Export to file{/tr}</h2>
		<div class="form-group row">
			<label class="col-form-label col-sm-3">{tr}Version:{/tr}</label>
			<div class="col-sm-6">
				<select name="readdate" class="form-control">
					{section name=key loop=$history}
						<option value="{$history[key].stamp}">{$history[key].prettystamp}</option>
					{/section}
				</select>
			</div>
		</div>

		<div class="form-group row">
			<label class="col-form-label col-sm-3">{tr}Format:{/tr}</label>
			<div class="col-sm-6">
				<input type="hidden" value="{$sheetId}" name="sheetId">
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
			<div class="col-sm-6 offset-sm-3">
				<input type="submit" class="btn btn-primary" value="{tr}Export{/tr}">
			</div>
		</div>
	</form>
{/if}
