{extends 'layout_view.tpl'}

{block name="title"}
	{title}{$title|escape}{/title}
{/block}
{block name="subtitle"}
	<small>
		{if $info.menuId}
			{tr}Id{/tr}:{$info.menuId|escape}
		{/if}
		{if $symbol}
			<a class="btn btn-link btn-sm tips" title="{tr}Symbol Information{/tr}|{tr}Symbol{/tr}: <strong>{$symbol.object}</strong><br>{tr}Profile Name{/tr}: <strong>{$symbol.profile}</strong><br>{tr}Profile Source{/tr}: <strong>{$symbol.domain}</strong>">
				{icon name="information"}
			</a>
		{/if}	
	</small>
{/block}
{block name="content"}
	<form action="{service controller=menu action=edit}" method="post" role="form" class="form">
		<div class="form-group row mx-2">
			<label for="menus_name" class="col-form-label">
				{tr}Name{/tr}
			</label>
			<input type="text" name="name" id="menus_name" value="{$info.name|escape}" class="form-control" required="required">
		</div>
		<div class="form-group row mx-2">
			<label for="menus_desc" class="col-form-label">
				{tr}Description{/tr}
			</label>
			<textarea name="description" id="menus_desc" class="form-control">{$info.description|escape}</textarea>
		</div>
		<div class="form-group row mx-2">
			<label for="menus_type" class="col-form-label">
				{tr}Type{/tr}
			</label>
			<select name="type" id="menus_type" class="form-control">
				<option value="d" {if $info.type eq 'd'}selected="selected"{/if}>{tr}dynamic collapsed{/tr} (d)</option>
				<option value="e" {if $info.type eq 'e'}selected="selected"{/if}>{tr}dynamic extended{/tr} (e)</option>
				<option value="f" {if $info.type eq 'f'}selected="selected"{/if}>{tr}fixed{/tr} (f)</option>
			</select>
		</div>
		<div class="form-group row mx-2">
			<label for="menus_parse" class="w-100">
				<input type="checkbox" name="parse" id="menus_parse"{if $info.parse eq 'y'} checked="checked"{/if} value="1">
				{tr}Wiki Parse{/tr}
			</label>
			<div class="form-text">{tr}Labels of all options will be wiki parsed.{/tr}</div>
		</div>
		{if $prefs.feature_menusfolderstyle eq 'y'}
			<div class="form-group row mx-2">
				<label for="icon" class="col-form-label">
					{tr}Folder Icon{/tr}
				</label>
				<input type="text" id="icon" name="icon" value="{$info.icon|escape}" class="form-control">
				<div class="form-text">{tr}Path and filename of closed folder icon{/tr}</div>
			</div>
		{/if}
		{if $prefs.menus_items_icons eq 'y'}
			<div class="form-group row mx-2">
				<label for="use_items_icons">
					<input type="checkbox" id="use_items_icons" name="use_items_icons" {if $info.use_items_icons eq 'y'} checked="checked"{/if} value="1">
					{tr}Configure icons for menu entries{/tr}
				</label>
			</div>
		{/if}
		<div class="submit">
			{if $prefs.menus_items_icons neq 'y'}
				<input type="hidden" name="use_items_icons" value="{$info.use_items_icons}">
			{/if}
			{ticket mode=confirm}
			<input type="hidden" name="menuId" value="{$info.menuId|escape}">
			<input type="submit" class="btn btn-primary" name="save" value="{tr}Save{/tr}">
		</div>
	</form>
{/block}
