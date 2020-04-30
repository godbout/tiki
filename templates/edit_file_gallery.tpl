{* $Id$ *}
{if $tiki_p_create_file_galleries eq 'y' or (not empty($user) and $user eq $gal_info.user and $gal_info.type eq 'user' and $tiki_p_userfiles eq 'y')}
	{if isset($individual) and $individual eq 'y'}
		{remarksbox type="tip" title="{tr}Permissions{/tr}"}
			{tr}There are individual permissions set for this file gallery{/tr}. {permission_link mode=icon type="file gallery" permType="file galleries" id=$galleryId title=$name label="{tr}Manage Permissions{/tr}"}
		{/remarksbox}
	{/if}
	<form role="form" action="{$smarty.server.SCRIPT_NAME}?{query}" method="post">
		<input type="hidden" name="galleryId" value="{$galleryId|escape}">
		<input type="hidden" name="filegals_manager" {if isset($filegals_manager)}value="{$filegals_manager}"{/if}>
		{ticket}

		{tabset name="list_file_gallery"}
			{tab name="{tr}Properties{/tr}"}
				<h2>{tr}Properties{/tr}</h2><br>
				<div class="form-group row">
					<label for="name" class="col-sm-4 col-form-label">{tr}Name{/tr}</label>
					<div class="col-sm-8">
						{if $galleryId eq $treeRootId or $gal_info.type eq 'user'}
							<b>{tr}{$gal_info.name}{/tr}</b>
							<input type="hidden" name="name" value="{$gal_info.name|escape}" class="form-control">
						{else}
							<input type="text" id="name" name="name" value="{$gal_info.name|escape}" class="form-control">
							<span class="form-text">{tr}Required for podcasts{/tr}.</span>
						{/if}
					</div>
				</div>
				{if $prefs.feature_file_galleries_templates eq 'y'}
					<div class="form-group row">
						<label for="fgal_template" class="col-sm-4 col-form-label">{tr}Template{/tr}</label>
						<div class="col-sm-8">
							<select name="fgal_template" id="fgal_template" class="form-control">
								<option value=""{if !isset($templateId) or $templateId eq ""} selected="selected"{/if}>{tr}None{/tr}</option>
								{foreach from=$all_templates key=key item=item}
									<option value="{$item.id}"{if $gal_info.template eq $item.id} selected="selected"{/if}>{$item.label|escape}</option>
								{/foreach}
								{jq}
$('#fgal_template').change( function() {
var otherTabs = $('ul.nav-tabs li:not(.active)');
var otherParams = $('#description').parents('div.form-group').nextAll('div.form-group');

if ($(this).val() != '') {
	// Select template, hide parameters
	otherTabs.hide();
	otherParams.hide();
} else {
	// No template, show parameters
	otherTabs.show();
	otherParams.show();
}
}).change();
								{/jq}
							</select>
						</div>
					</div>
				{/if}
				<div class="form-group row">
					<label for="fgal_type" class="col-sm-4 col-form-label">{tr}Type{/tr}</label>
					<div class="col-sm-8">
						{if $galleryId eq $treeRootId or $gal_info.type eq 'user'}
							{if $gal_info.type eq 'system'}
								{tr}System{/tr}
							{elseif $gal_info.type eq 'user'}
								{tr}User{/tr}
							{else}
								{tr _0=$gal_info.type}Other (%0){/tr}
							{/if}
							<input type="hidden" name="fgal_type" value="{$gal_info.type}">
						{else}
							<select name="fgal_type" id="fgal_type" class="form-control">
								<option value="default" {if $gal_info.type eq 'default'}selected="selected"{/if}>{tr}Any file{/tr}</option>
								<option value="podcast" {if $gal_info.type eq 'podcast'}selected="selected"{/if}>{tr}Podcast (audio){/tr}</option>
								<option value="vidcast" {if $gal_info.type eq 'vidcast'}selected="selected"{/if}>{tr}Podcast (video){/tr}</option>
							</select>
						{/if}
					</div>
				</div>
				<div class="form-group row">
					<label for="description" class="col-sm-4 col-form-label">{tr}Description{/tr}</label>
					<div class="col-sm-8">
						<textarea rows="3" id="description" name="description" class="form-control">{$gal_info.description|escape}</textarea>
						<span class="form-text">{tr}Required for podcasts{/tr}.</span>
					</div>
				</div>
				<div class="form-group row">
					<label for="visible" class="col-sm-4">{tr}Gallery is visible to non-admin users{/tr}</label>
					<div class="col-sm-8">
						<div class="form-check">
							<input type="checkbox" class="form-check-input" id="visible" name="visible" {if $gal_info.visible eq 'y'}checked="checked"{/if}>
						</div>
					</div>
				</div>
				<div class="form-group row">
					<label for="public" class="col-sm-4">{tr}Gallery is unlocked{/tr}</label>
					<div class="col-sm-8">
						<div class="form-check">
							<input type="checkbox" class="form-check-input" id="public" name="public" {if isset($gal_info.public) and $gal_info.public eq 'y'}checked="checked"{/if}>
							<span>{tr}Users with upload permission can add files to the gallery (not just the gallery owner){/tr}</span>
						</div>
					</div>
				</div>
				{if $tiki_p_admin_file_galleries eq 'y' or $gal_info.type neq 'user'}
					<div class="form-group row">
						<label for="backlinkPerms" class="col-sm-4">{tr}Respect permissions for backlinks to view a file{/tr}</label>
						<div class="col-sm-8">
							<div class="form-check">
								<input type="checkbox" class="form-check-input" id="backlinkPerms" name="backlinkPerms" {if $gal_info.backlinkPerms eq 'y'}checked="checked"{/if}>
							</div>
						</div>
					</div>
					<div class="form-group row">
						<label for="lockable" class="col-sm-4">{tr}Files can be locked at download{/tr}.</label>
						<div class="col-sm-8">
							<div class="form-check">
								<input type="checkbox" class="form-check-input" id="lockable" name="lockable" {if $gal_info.lockable eq 'y'}checked="checked"{/if}>
							</div>
						</div>
					</div>
					<div class="form-group row">
						<label for="archives" class="col-sm-4 col-form-label">{tr}Maximum number of archives for each file{/tr}</label>
						<div class="col-sm-8">
							<div class="form-control-plaintext">
								<input type="text" id="archives" name="archives" value="{$gal_info.archives|escape}" class="form-control">
								<span class="form-text">{tr}Use{/tr}: 0={tr}unlimited{/tr}, -1={tr}none{/tr}.</span>

							</div>
						</div>
					</div>
					{if $galleryId neq $treeRootId}
						<div class="form-group row">
							<label for="parentId" class="col-sm-4 col-form-label">{tr}Parent gallery{/tr}</label>
							<div class="col-sm-8">
								<select name="parentId" id="parentId" class="form-control">
									<option value="{$treeRootId}"{if $parentId eq $treeRootId} selected="selected"{/if}>{tr}none{/tr}</option>
									{foreach from=$all_galleries key=key item=item}
										{if $galleryId neq $item.id}
											<option value="{$item.id}"{if $parentId eq $item.id} selected="selected"{/if}>{$item.label|escape}</option>
										{/if}
									{/foreach}
								</select>
							</div>
						</div>
					{else}
						<input type="hidden" name="parentId" value="{$parentId|escape}">
					{/if}
				{/if}
				{if $tiki_p_admin eq 'y' or $tiki_p_admin_file_galleries eq 'y'}
					<div class="form-group row">
						<label for="user" class="col-sm-4 col-form-label">{tr}Owner of the gallery{/tr}</label>
						<div class="col-sm-8">
							{user_selector user=$creator id='user'}
						</div>
					</div>

					{if $prefs.fgal_quota_per_fgal eq 'y'}
						<div class="form-group row">
							<label for="quota" class="col-sm-4 col-form-label">{tr}Quota{/tr}</label>
							<div class="col-sm-8">
								<div class="input-group col-sm-4">
									<input type="text" class="form-control" id="quota" name="quota" value="{$gal_info.quota}" size="5">
									<span class="input-group-append"> {tr}Mb{/tr}</span>
								</div>
								<span class="form-text">{tr}0 for unlimited{/tr}</span>
								{if $gal_info.usedSize}<br>{tr}Used:{/tr} {$gal_info.usedSize|kbsize}{/if}
								{if !empty($gal_info.quota)}
									{capture name='use'}
										{math equation="round((100*x)/(1024*1024*y))" x=$gal_info.usedSize y=$gal_info.quota}
									{/capture}
									{quotabar length='100' value=$smarty.capture.use}
								{/if}
								{if !empty($gal_info.maxQuota)}<br>{tr}Max:{/tr} {$gal_info.maxQuota} {tr}Mb{/tr}{/if}
								{if !empty($gal_info.minQuota)}<br>{tr}Min:{/tr} {$gal_info.minQuota|string_format:"%.2f"} {tr}Mb{/tr}{/if}
							</div>
						</div>
					{/if}

					{if $prefs.feature_groupalert eq 'y'}
						<div class="form-group row">
							<label for="groupforAlert" class="col-sm-4 col-form-label">{tr}Group of users alerted when file gallery is modified{/tr}</label>
							<div class="col-sm-8">
								<select id="groupforAlert" name="groupforAlert" class="form-control">
									<option value=""></option>
									{foreach key=k item=i from=$groupforAlertList}
										<option value="{$k}" {$i}>{$k}</option>
									{/foreach}
								</select>
							</div>
						</div>
						<div class="form-group row">
							<label for="showeachuser" class="col-sm-4">{tr}Allows each user to be selected for small groups{/tr}</label>
							<div class="col-sm-8">
								<div class="form-check">
									<input type="checkbox" class="form-check-input" name="showeachuser" id="showeachuser" {if $showeachuser eq 'y'}checked="checked"{/if}>
								</div>
							</div>
						</div>
					{/if}
				{/if}
				<div class="form-group row">
					<label for="image_max_size_x" class="col-sm-4 col-form-label">{tr}Maximum width of images in gallery{/tr}</label>
					<div class="col-sm-8">
						<div class="form-control-plaintext">
							<div class="input-group col-sm-4">
								<input type="text" name="image_max_size_x" id="image_max_size_x" value="{$gal_info.image_max_size_x|escape}" class="form-control text-right">
								<span>&nbsp;px</span>
							</div>
							<span class="form-text">{tr}If an image is wider than this, it will be resized.{/tr} {tr}Attention: In this case, the original image will be lost.{/tr} (0={tr}unlimited{/tr})</span>
						</div>
					</div>
				</div>
				<div class="form-group row">
					<label for="image_max_size_y" class="col-sm-4 col-form-label">{tr}Maximum height of images in gallery{/tr}</label>
					<div class="col-sm-8">
						<div class="form-control-plaintext">
							<div class="input-group col-sm-4">
								<input type="text" name="image_max_size_y" id="image_max_size_y" value="{$gal_info.image_max_size_y|escape}" class="form-control text-right">
								<span>&nbsp;px</span>
							</div>
							<span class="form-text">{tr}If an image is higher than this, it will be resized.{/tr} {tr}Attention: In this case, the original image will be lost.{/tr} (0={tr}unlimited{/tr})</span>
						</div>
					</div>
				</div>
				<div class="form-group row">
					<label for="wiki_syntax" class="col-sm-4 col-form-label">{tr}Wiki markup to enter when image selected from "file gallery manager"{/tr}</label>
					<div class="col-sm-8">
						<div class="form-control-plaintext">
							<input size="80" type="text" name="wiki_syntax" id="wiki_syntax" value="{$gal_info.wiki_syntax|escape}" class="form-control">
							<span class="form-text">{tr}The default is {/tr}"{literal}{img fileId="%fileId%" thumb="box"}{/literal}")</span>
							<span class="form-text">{tr}Field names will be replaced when enclosed in % chars. e.g. %fileId%, %name%, %filename%, %description%{/tr}</span>
							<span class="form-text">{tr}Attributes will be replaced when enclosed in % chars. e.g. %tiki.content.url% for remote file URLs{/tr}</span>
						</div>
					</div>
				</div>
            {if $prefs.ocr_enable eq 'y'}
                {if $selectedLanguages || $languages}
					<div class="form-group row">
						<label for="ocr_lang" class="col-md-4 col-form-label">{tr}Override Default OCR Languages{/tr}</label>
						<div class="col-md-8">
							<select multiple id="ocr_lang" class="form-control" name="ocr_lang[]">
                                {foreach $selectedLanguages as $code => $language}
									<option value="{$code|escape}" selected="selected">{$language|truncate:60|escape}</option>
                                {/foreach}

                                {foreach $languages as $code => $language}
									<option value="{$code}">{$language|truncate:60}</option>
                                {/foreach}
							</select>
						</div>
					</div>
                {/if}
            {/if}

				{include file='categorize.tpl' labelcol='4' inputcol='8'}

			{/tab}

<!-- display properties -->
			{tab name="{tr}Display Settings{/tr}"}
				<h2>{tr}Display Settings{/tr}</h2><br>
				<div class="form-group row">
					<label class="col-sm-4 col-form-label" for="fgal_default_view">
						{tr}Default View{/tr}
					</label>
					<div class="col-sm-8">
						<select id="fgal_default_view" name="fgal_default_view" class="form-control">
							<option value="list"{if $gal_info.default_view eq 'list'} selected="selected"{/if}>
								{tr}List{/tr}
							</option>
							<option value="browse"{if $gal_info.default_view eq 'browse'} selected="selected"{/if}>
								{tr}Browse{/tr}
							</option>
							<option value="page"{if $gal_info.default_view eq 'page'} selected="selected"{/if}>
								{tr}Page{/tr}
							</option>
							{if $prefs.fgal_elfinder_feature eq 'y'}
								<option value="finder"{if $gal_info.default_view eq 'finder'} selected="selected"{/if}>
									{tr}Finder View{/tr}
								</option>
							{/if}
						</select>
					</div>
				</div>
				<div class="form-group row">
					<label for="sortorder" class="col-sm-4 col-form-label">{tr}Default sort order{/tr}</label>
					<div class="col-sm-8">
						<select name="sortorder" id="sortorder" class="form-control">
							{foreach from=$options_sortorder key=key item=item}
								<option value="{$item|escape}" {if $sortorder == $item} selected="selected"{/if}>{$key}</option>
							{/foreach}
						</select>
						<span class="form-text">
							<label  for="fgal_sortdirection1">
								<input type="radio" id="fgal_sortdirection1" name="sortdirection" value="desc" {if $sortdirection == 'desc'}checked="checked"{/if} />
								&nbsp;{tr}Descending{/tr}&nbsp;
							</label>
							<label for="fgal_sortdirection2">
								<input type="radio" id="fgal_sortdirection2" name="sortdirection" value="asc" {if $sortdirection == 'asc'}checked="checked"{/if} />
								&nbsp;{tr}Ascending{/tr}&nbsp;
							</label>
						</span>
					</div>
				</div>
				<hr>
				<div class="">
					<label for="" class="col-form-label">{tr}Select which items to display when listing galleries{/tr}</label>
					{include file='fgal_listing_conf.tpl'}
				</div>
				<hr>
				<div class="form-group row">
					<label for="max_desc" class="col-sm-4 col-form-label">{tr}Max description display size{/tr}</label>
					<div class="col-sm-8">
						<input type="text" id="max_desc" name="max_desc" value="{$max_desc|escape}" class="form-control">
					</div>
				</div>
				<div class="form-group row">
					<label for="maxRows" class="col-sm-4 col-form-label">{tr}Max rows per page{/tr}</label>
					<div class="col-sm-8">
						<input type="text" id="maxRows" name="maxRows" value="{$maxRows|escape}" class="form-control">
					</div>
				</div>
			{/tab}
		{/tabset}
		<div class="form-group row">
			<label for="viewitem" class="col-sm-4">
				{tr}View inserted gallery after save{/tr}
			</label>
			<div class="col-sm-8">
				<div class="form-check">
					<input type="checkbox" class="form-check-input" name="viewitem" id="viewitem" checked="checked">
				</div>
			</div>
		</div>

		<div class="form-group row">
			<div class="col-md-8 offset-md-4">
				<input type="submit" class="btn btn-primary" value="{tr}Save{/tr}" name="edit">
			</div>
		</div>
	</form>
{/if}
