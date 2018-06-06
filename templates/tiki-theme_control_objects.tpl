{* $Id$ *}
{title help="Theme Control"}{tr}Theme Control{/tr}: {tr}Objects{/tr}{/title}
<div class="t_navbar btn-group">
	{button href="tiki-theme_control.php" class="btn btn-primary" _text="{tr}Control by Categories{/tr}"}
	{button href="tiki-theme_control_sections.php" class="btn btn-primary" _text="{tr}Control by Sections{/tr}"}
</div>

<h2>{tr}Assign themes to objects{/tr}</h2>
<form id='objform' action="tiki-theme_control_objects.php" method="post" class="form-inline" role="form">
		<label for="type" class="mr-2">{tr}Type{/tr}</label>
		<select name="type" onchange="javascript:document.getElementById('objform').submit();" class="form-control mr-2 mb-2">
			{section name=ix loop=$objectypes}
				<option value="{$objectypes[ix]|escape}" {if $type eq $objectypes[ix]}selected="selected"{/if}>{$objectypes[ix]}</option>
			{/section}
		</select>
		<!--<input type="submit" class="btn btn-primary btn-sm" name="settype" value="{tr}Set{/tr}">-->
		<label for="objdata" class="mr-2 mb-2">{tr}Object{/tr}</label>
		<select name="objdata" class="form-control mr-2 mb-2">
			{section name=ix loop=$objects}
				<option value="{$objects[ix].objId|escape}|{$objects[ix].objName}" {if $a_object eq $objects[ix].objId|cat:'|'|cat:$objects[ix].objName}selected="selected"{/if}>{$objects[ix].objName}</option>
			{/section}
		</select>
		<label for="theme" class="mr-2 mb-2">{tr}Theme{/tr}</label>
		<select name="theme" class="form-control form-control mr-2 mb-2">
			{foreach from=$themes key=theme item=theme_name}
				<option value="{$theme|escape}">{$theme_name}</option>
			{/foreach}
		</select>
	<input type="submit" class="btn btn-primary mb-2" name="assign" value="{tr}Assign{/tr}">
</form>

<h2>{tr}Assigned objects{/tr}</h2>

{include file='find.tpl'}

<form action="tiki-theme_control_objects.php" method="post" class="form" role="form">
	<input type="hidden" name="type" value="{$type|escape}">
	<div class="table-responsive themeobj-table">
		<table class="table">
			<tr>
				<th><button type="submit" class="btn btn-warning btn-sm" name="delete" title="{tr}Delete selected{/tr}">{icon name="delete"}</button></th>
				<th>
					<a href="tiki-theme_control_objects.php?offset={$offset}&amp;sort_mode={if $sort_mode eq 'type_desc'}type_asc{else}type_desc{/if}">
						{tr}Type{/tr}
					</a>
				</th>
				<th>
					<a href="tiki-theme_control_objects.php?offset={$offset}&amp;sort_mode={if $sort_mode eq 'name_desc'}name_asc{else}name_desc{/if}">
						{tr}Name{/tr}
					</a>
				</th>
				<th>
					<a href="tiki-theme_control_objects.php?offset={$offset}&amp;sort_mode={if $sort_mode eq 'theme_desc'}theme_asc{else}theme_desc{/if}">
						{tr}Theme{/tr}</a>
				</th>
			</tr>

			{section name=user loop=$channels}
				<tr>
					<td class="checkbox-cell">
						<div class="form-check">
							<input type="checkbox" class="form-check-input" name="obj[{$channels[user].objId}]">
						</div>
					</td>
					<td class="text">{$channels[user].type}</td>
					<td class="text">{$channels[user].name}</td>
					<td class="text">{$channels[user].theme}</td>
				</tr>
			{/section}
		</table>
	</div>
</form>

{pagination_links cant=$cant_pages step=$prefs.maxRecords offset=$offset}{/pagination_links}
