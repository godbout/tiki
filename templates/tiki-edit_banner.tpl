{* $Id$ *}
{title help="Banners"}{tr}Create or edit banners{/tr}{/title}

<div class="t_navbar mb-4">
	{button href="tiki-list_banners.php" _class="btn btn-link" _type="link" _icon_name="list" _text="{tr}List banners{/tr}"}
</div>

<form action="tiki-edit_banner.php" method="post" enctype="multipart/form-data" class="form-horizontal mb-4">
	<input type="hidden" name="bannerId" value="{$bannerId|escape}">
	<div class="card">
		<div class="card-body">
			<div class="form-group row">
				<label class="col-sm-3 col-form-label">{tr}URL to link the banner{/tr}</label>
				<div class="col-sm-7 mb-3">
					<input type="text" name="url" value="{$url|escape}" class="form-control">
				</div>
			</div>
			<div class="form-group row">
				<label class="col-sm-3 col-form-label">{tr}Client{/tr}</label>
				<div class="col-sm-7 mb-3">
					{user_selector user=$client name='client'}
				</div>
			</div>
			<div class="form-group row">
				<label class="col-sm-3 col-form-label">{tr}Maximum impressions{/tr}</label>
				<div class="col-sm-7">
					<input type="text" name="maxImpressions" value="{$maxImpressions|escape}" maxlength="7" class="form-control">
					<div class="form-text">
						{tr}-1 for unlimited{/tr}
					</div>
				</div>
			</div>
			<div class="form-group row">
				<label class="col-sm-3 col-form-label">{tr}Maximum number of impressions for a user{/tr}</label>
				<div class="col-sm-7">
					<input type="text" name="maxUserImpressions" value="{$maxUserImpressions|escape}" maxlength="7" class="form-control">
					<div class="form-text">
						{tr}-1 for unlimited{/tr}
					</div>
				</div>
			</div>
			<div class="form-group row">
				<label class="col-sm-3 col-form-label">{tr}Maximum clicks{/tr}</label>
				<div class="col-sm-7">
					<input type="text" name="maxClicks" value="{$maxClicks|escape}" maxlength="7" class="form-control">
					<div class="form-text">
						{tr}-1 for unlimited{/tr}
					</div>
				</div>
			</div>
			<div class="form-group row">
				<label class="col-sm-3 col-form-label">{tr}URIs where the banner appears only{/tr}</label>
				<div class="col-sm-7">
					<input type="text" name="onlyInURIs" value="{$onlyInURIs|escape}" class="form-control">
					<div class="form-text">
						{tr}Type each URI enclosed with the # character. Exemple:#/this_page#/tiki-index.php?page=this_page#{/tr}
					</div>
				</div>
			</div>
			<div class="form-group row">
				<label class="col-sm-3 col-form-label">{tr}URIs where the banner will not appear{/tr}</label>
				<div class="col-sm-7">
					<input type="text" name="exceptInURIs" value="{$exceptInURIs|escape}" class="form-control">
					<div class="form-text">
						{tr}Type each URI enclosed with the # character. Exemple:#/this_page#/tiki-index.php?page=this_page#{/tr}
					</div>
				</div>
			</div>
			<div class="form-group row">
				<label class="col-sm-3 col-form-label">{tr}Zone{/tr}</label>
				<div class="col-sm-7">
					<select name="zone"{if !$zones} disabled="disabled"{/if} class="form-control">
						{section name=ix loop=$zones}
							<option value="{$zones[ix].zone|escape}" {if $zone eq $zones[ix].zone}selected="selected"{/if}>{$zones[ix].zone|escape}</option>
						{sectionelse}
							<option value="" disabled="disabled" selected="selected">{tr}None{/tr}</option>
						{/section}
					</select>
					<div class="form-text">
						{tr}Or, create a new zone{/tr}
					</div>
				</div>
				<label class="col-sm-3 col-form-label">{tr}New Zone{/tr}</label>
				<div class="col-sm-7">
					<input type="text" name="zoneName" maxlength="10" class="form-control">
				</div>
			</div>
			<div class="form-group row">
				<label class="col-sm-3 col-form-label"></label>
				<div class="col-sm-7">
					<input type="submit" class="btn btn-primary btn-sm" name="create_zone" value="{tr}Create{/tr}">
				</div>
			</div>
		</div>
	</div>

	<div class="card">
		<div class="card-body">
			<h4>{tr}Show the banner only between these dates:{/tr}</h4>
			<div class="form-group row">
				<label class="col-sm-3 col-form-label">{tr}From date:{/tr}</label>
				<div class="col-sm-7 short">
					{html_select_date time=$fromDate prefix="fromDate_" end_year="+2" field_order=$prefs.display_field_order}
				</div>
			</div>
			<div class="form-group row">
				<label class="col-sm-3 col-form-label">{tr}To date:{/tr}</label>
				<div class="col-sm-7 short">
					{html_select_date time=$fromDate prefix="fromDate_" end_year="+2" field_order=$prefs.display_field_order}
				</div>
			</div>
			<div class="form-group row">
				<label class="col-sm-3 col-form-label">{tr}Use dates:{/tr}</label>
				<div class="col-sm-7">
					<label><input type="checkbox" name="useDates" {if $useDates eq 'y'}checked='checked'{/if}> {tr}Yes{/tr}</label>
				</div>
			</div>
		</div>
	</div>

	<div class="card">
		<div class="card-body">
			<h4>{tr}Show the banner only in these hours:{/tr}</h4>
			<div class="form-group row">
				<label class="col-sm-3 col-form-label">{tr}from{/tr}</label>
				<div class="col-sm-7 short">
					{html_select_time time=$fromTime display_seconds=false prefix='fromTime' use_24_hours=$use_24hr_clock}
				</div>
			</div>
			<div class="form-group row">
				<label class="col-sm-3 col-form-label">{tr}to{/tr}</label>
				<div class="col-sm-7 short">
					{html_select_time time=$toTime display_seconds=false prefix='toTime' use_24_hours=$use_24hr_clock}
				</div>
			</div>
		</div>
	</div>

	<div class="card">
		<div class="card-body">
			<h4>{tr}Show the banner only on:{/tr}</h4>
			<div class="col-sm-12">
				<div class="form-group row flex-column">
					<label><input type="checkbox" name="Dmon" {if $Dmon eq 'y'}checked="checked"{/if}> {tr}Mon{/tr}</label>
					<label><input type="checkbox" name="Dtue" {if $Dtue eq 'y'}checked="checked"{/if}> {tr}Tue{/tr}</label>
					<label><input type="checkbox" name="Dwed" {if $Dwed eq 'y'}checked="checked"{/if}> {tr}Wed{/tr}</label>
					<label><input type="checkbox" name="Dthu" {if $Dthu eq 'y'}checked="checked"{/if}> {tr}Thu{/tr}</label>
					<label><input type="checkbox" name="Dfri" {if $Dfri eq 'y'}checked="checked"{/if}> {tr}Fri{/tr}</label>
					<label><input type="checkbox" name="Dsat" {if $Dsat eq 'y'}checked="checked"{/if}> {tr}Sat{/tr}</label>
					<label><input type="checkbox" name="Dsun" {if $Dsun eq 'y'}checked="checked"{/if}> {tr}Sun{/tr}</label>
				</div>
			</div>
		</div>
	</div>

	<div class="card">
		<div class="card-body">
			<h4>{tr}Select ONE method for the banner:{/tr}</h4>
			<div class="form-group row">
				<label class="col-sm-3 col-form-label"><label><input type="radio" name="use" value="useHTML" {if $use eq 'useHTML'}checked="checked"{/if}> {tr}Use HTML{/tr}</label></label>
				<div class="col-sm-7">
					<textarea class="form-control" rows="5" name="HTMLData">{if $use ne 'useFlash'}{$HTMLData|escape}{/if}</textarea>
					<div class="form-text">
						{tr}HTML code{/tr}
					</div>
				</div>
			</div>
			<div class="form-group row">
				<label class="col-sm-3 col-form-label"><label><input type="radio" name="use" value="useImage" {if $use eq 'useImage'}checked="checked"{/if}> {tr}Use Image{/tr}</label></label>
				<div class="col-sm-7">
					<input type="hidden" name="imageData" value="{$imageData|escape}">
					<input type="hidden" name="imageName" value="{$imageName|escape}">
					<input type="hidden" name="imageType" value="{$imageType|escape}">
					<input type="hidden" name="MAX_FILE_SIZE" value="1000000">
					<input name="userfile1" type="file">
				</div>
			</div>
			<div class="form-group row">
				{if $hasImage eq 'y'}
				<label class="col-sm-3 col-form-label">{tr}Current Image{/tr}</label>
				<div class="col-sm-7">
					{$imageName}: <img src="{$tempimg}" alt="{tr}Current Image{/tr}">
				</div>
				{/if}
			</div>
			<div class="form-group row">
				<label class="col-sm-3 col-form-label"><label><input type="radio" name="use" value="useFixedURL" {if $use eq 'useFixedURL'}checked="checked"{/if}> {tr}Use Image from URL{/tr}</label></label>
				<div class="col-sm-7">
					<input type="text" name="fixedURLData" value="{$fixedURLData|escape}" class="form-control">
					<div class="form-text">
						{tr}(the image will be requested at the URL for each impression){/tr}
					</div>
				</div>
			</div>
			<div class="form-group row">
				<label class="col-sm-3 col-form-label"><label><input type="radio" name="use" value="useFlash" {if $use eq 'useFlash'}checked="checked"{/if}> {tr}Use Flash{/tr}</label></label>
				{if $use eq 'useFlash'}
					<div class="col-sm-7">
						{banner id="$bannerId"}
					</div>
				{/if}
			</div>
			<div class="form-group row">
				<label class="col-sm-3 col-form-label">{tr}Movie URL{/tr}</label>
				<div class="col-sm-7 mb-3">
					<input type="text" name="movieUrl" value="{$movie.movie|escape}" class="form-control">
				</div>
			</div>
			<div class="form-group row">
				<label class="col-sm-3 col-form-label">{tr}Movie Size{/tr}</label>
				<div class="col-sm-3">
					<input type="text" name="movieWidth" value="{$movie.width|escape}" class="form-control" placeholder="{tr}width in pixels{/tr}">
					<div class="form-text">
						{tr}Pixels{/tr}
					</div>
				</div>
				<div class="col-sm-3 offset-sm-1">
					<input type="text" name="movieHeight" value="{$movie.height|escape}" class="form-control" placeholder="{tr}height in pixels{/tr}">
					<div class="form-text">
						{tr}Pixels{/tr}
					</div>
				</div>
			</div>
			<div class="form-group row">
				<label class="col-sm-3 col-form-label">{tr}FlashPlugin min version{/tr}</label>
				<div class="col-sm-7 mb-3">
					<input type="text" name="movieVersion" value="{$movie.version|escape}" class="form-control">
					<div class="form-text">
						({tr}ex:{/tr}9.0.0)
					</div>
				</div>
				<div class="col-sm-7 col-sm-offset-4">
					<div class="form-text">
						Note: To be managed with tiki , your flash banner link should be: <a class="link" href="banner_click.php?id={$bannerId}&amp;url={$url}">banner_click.php?id={$bannerId}&amp;url={$url}</a>
					</div>
				</div>
			</div>
			<div class="form-group row">
				<label class="col-sm-3 col-form-label"><label><input type="radio" name="use" value="useText" {if $use eq 'useText'}checked="checked"{/if}> {tr}Use Text{/tr}</label></label>
				<div class="col-sm-7">
					<textarea class="form-control" rows="5" name="textData">{$textData|escape}</textarea>
				</div>
			</div>
		</div>
	</div>
	<input type="submit" class="btn btn-primary" name="save" value="{tr}Save the Banner{/tr}">
</form>

{if $zones}
	<div align="left" class="card">
		<div class="card-body">
			<h2>{tr}Remove zones (info entered for any banner in the zones will be lost){/tr}</h2>
			<div class="table-responsive">
				<table class="table table-striped table-hover">
					<tr>
						<th>{tr}Name{/tr}</th>
						<th></th>
					</tr>

					{section name=ix loop=$zones}
						<tr>
							<td class="text">{$zones[ix].zone|escape}</td>
							<td class="action">
								<a class="tips" title=":{tr}Remove{/tr}" href="tiki-edit_banner.php?removeZone={$zones[ix].zone|escape:url}">
									{icon name='remove'}
								</a>
							</td>
						</tr>
					{/section}
				</table>
			</div>
		</div>
	</div>
{/if}
