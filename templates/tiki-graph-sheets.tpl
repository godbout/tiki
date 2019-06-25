{title help="Spreadsheet"}{$title}{/title}

<p>
	{$description|escape}
</p>

{if ($mode eq 'graph')}
	<h2>{tr}Select Graphic Type{/tr}</h2>
	<form method="get" action="tiki-graph_sheet.php">
		<input type="hidden" name="sheetId" value="{$sheetId}">
		<div class="form-group row mt-4">
			<div class="col-sm-3 mb-3">
				<div class="form-check">
					<label for='g_pie' class="form-check-label">
						<input type="radio" name="graphic" class="form-check-input" id="g_pie" value="PieChartGraphic">
						{tr}Pie Chart{/tr}
					</label>
				</div>
				<img src="img/graph.pie.png" alt="Pie Chart">
			</div>

			<div class="col-sm-3 mb-3">
				<div class="form-check">
					<label for='g_mline' class="form-check-label">
						<input type="radio" name="graphic" class="form-check-input" id="g_mline" value="MultilineGraphic">
						{tr}Multiline{/tr}
					</label>
				</div>
				<img src="img/graph.multiline.png" alt="Multiline">
			</div>

			<div class="col-sm-3 mb-3">
				<div class="form-check">
					<label for='g_mbar' class="form-check-label">
						<input type="radio" name="graphic" class="form-check-input" id="g_mbar" value="MultibarGraphic">
						{tr}Multibar{/tr}
					</label>
				</div>
				<img src="img/graph.multibar.png" alt="Multibar">
			</div>

			<div class="col-sm-3 mb-3">
				<div class="form-check">
					<label for='g_stack' class="form-check-label">
						<input type="radio" name="graphic" class="form-check-input" id="g_stack" value="BarStackGraphic">
						{tr}Bar Stack{/tr}
					</label>
				</div>
				<img src="img/graph.barstack.png" alt="Bar Stack">
			</div>
		</div>

		{if $haspdflib or $hasps}
			<div class="form-group row">
				<div class="col-sm-4">
					<select name="format" class="form-control">
						<option>Letter</option>
						<option>Legal</option>
						<option>A4</option>
						<option>A3</option>
					</select>
				</div>
				<div class="col-sm-4">
					<select name="orientation" class="form-control">
						<option value="landscape">{tr}Landscape{/tr}</option>
						<option value="portrait">{tr}Portrait{/tr}</option>
					</select>
				</div>
				<div class="col-sm-4">
				{if $haspdflib}
					<input type="submit" class="btn btn-primary" name="renderer" value="PDF">
				{/if}
				{if $hasps}
					<input type="submit" class="btn btn-primary" name="renderer" value="PS">
				{/if}
				</div>
			</div>
		{/if}
		{if $hasgd}
			<div class="form-group row">
				<div class="col-sm-2 mb-2">
					<input type="text" name="width" value="500" size="4" class="form-control">
				</div>
				<div class="col-sm-2 mb-2">
					<input type="text" name="height" value="400" size="4" class="form-control">
				</div>
				<div class="col-sm-8">
					<input type="submit" class="btn btn-primary" name="renderer" value="PNG">
					<input type="submit" class="btn btn-primary" name="renderer" value="JPEG">
				</div>
			</div>
		{/if}
	</form>
{/if}

{if ($mode eq 'param')}
	{jq}
	{literal}
	function renderWikiPlugin()
	{
		var div = document.getElementById( 'plugin-desc' );

		var params = [
			_renVal( 'id', 'sheetId' ),
			_renVal( 'type', 'graphic' ),
			_renVal( 'format', 'format' ),
			_renVal( 'orientation', 'orientation' ),
	{/literal}
	{if $showgridparam}
			_renValRad( 'independant', 'independant' ),
			_renValRad( 'vertical', 'vertical' ),
			_renValRad( 'horizontal', 'horizontal' ),
	{/if}
	{section name=i loop=$series}
			_renVal( '{$series[i]}', 'series[{$series[i]}]' ),
	{/section}
	{literal}
			_renVal( 'width', 'width' ),
			_renVal( 'height', 'height' )
		];

		div.innerHTML = "{CHART(" + params.join( ", " ) + ")}" + document.chartParam.title.value + "{CHART}";
	}

	function _renVal( dest, control )
	{
		var val = document.chartParam[control].value;

		if( val.indexOf( "," ) != -1 )
			return dest + '=>"' + val + '"';
		else
			return dest + '=>' + val;
	}

	function _renValRad( name )
	{
		var rads = document.chartParam[name];

		for( i = 0; rads.length > i; i++ )
			if( rads[i].checked )
				return name + '=>' + rads[i].value;
	}
	{/literal}
	{/jq}

	<form name="chartParam" method="get" action="tiki-graph_sheet.php">
		<input type="hidden" name="sheetId" value="{$sheetId}">
		<input type="hidden" name="graphic" value="{$graph}">
		<input type="hidden" name="renderer" value="{$renderer}">
		<input type="hidden" name="format" value="{$format}">
		<input type="hidden" name="orientation" value="{$orientation}">
		<input type="hidden" name="width" value="{$im_width}">
		<input type="hidden" name="height" value="{$im_height}">
		<table class="formcolor">
			<tr>
				<td>{tr}Title:{/tr}</td>
				<td><input type="text" name="title" value="{$title}" onchange="renderWikiPlugin()"></td>
			</tr>
			{if $showgridparam}
				<tr>
					<td>{tr}Independant Scale:{/tr}</td>
					<td>
						<input type="radio" name="independant" value="horizontal" id="ind_ori_hori" checked="checked" onchange="renderWikiPlugin()">
						<label for="ind_ori_hori">{tr}Horizontal{/tr}</label>
						<input type="radio" name="independant" value="vertical" id="ind_ori_verti" onchange="renderWikiPlugin()">
						<label for="ind_ori_verti">{tr}Vertical{/tr}</label>
					</td>
				</tr>
				<tr>
					<td>{tr}Horizontal Scale:{/tr}</td>
					<td>
						<input type="radio" name="horizontal" value="bottom" id="hori_pos_bottom" checked="checked" onchange="renderWikiPlugin()">
						<label for="hori_pos_bottom">{tr}Bottom{/tr}</label>
						<input type="radio" name="horizontal" value="top" id="hori_pos_top" onchange="renderWikiPlugin()">
						<label for="hori_pos_top">{tr}Top{/tr}</label>
					</td>
				</tr>
				<tr>
					<td>{tr}Vertical Scale:{/tr}</td>
					<td>
						<input type="radio" name="vertical" value="left" id="verti_pos_left" checked="checked" onchange="renderWikiPlugin()">
						<label for="verti_pos_left">{tr}Left{/tr}</label>
						<input type="radio" name="vertical" value="right" id="verti_pos_right" onchange="renderWikiPlugin()">
						<label for="verti_pos_right">{tr}Right{/tr}</label>
					</td>
				</tr>
			{/if}
			<tr>
				<td colspan="2">{tr}Series:{/tr}</td>
			</tr>
			{section name=i loop=$series}
				<tr>
					<td>{$series[i]}</td>
					<td><input type="text" name="series[{$series[i]}]" onchange="renderWikiPlugin()"></td>
				</tr>
			{/section}
			<tr>
				<td colspan="2"><input type="submit" class="btn btn-primary btn-sm" value="{tr}Show{/tr}" class="button"></td>
			</tr>
		</table>
		<div class="tiki_sheet">
			{$dataGrid}
		</div>
		{button _id="edit_button" _text="{tr}Edit Spreadsheet{/tr}" _htmlelement="role_main" _template="tiki-view_sheets.tpl" parse="edit" _auto_args="*" _class="" _onclick="document.location = 'tiki-view_sheets.php?sheetId=$sheetId&parse=edit'; return false;"}
		{button href="tiki-sheets.php" _class="btn-info" _text="{tr}List Spreadsheets{/tr}"}
	</form>

	<h2>{tr}Wiki plug-in{/tr}</h2>
	<div id="plugin-desc"></div>
{/if}
