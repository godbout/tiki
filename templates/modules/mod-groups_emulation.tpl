{strip}
{tikimodule error=$module_params.error title=$tpl_module_title name="groups_emulation" flip=$module_params.flip decorations=$module_params.decorations nobox=$module_params.nobox notitle=$module_params.notitle}

	{if isset($allGroups) && $showallgroups eq 'y'}
		<fieldset>
			<div id='mge-all-legend'><strong>{tr}All Groups{/tr}</strong></div>
			<ul id='mge-all' >
			{foreach from=$allGroups key=groupname item=inclusion name=ix}
				<li>{$groupname|escape}</li>
			{/foreach}
			</ul >
		</fieldset>
	{/if}

	{if $showyourgroups eq 'y'}
		<fieldset>
			<div id='mge-mine-legend'><strong>{tr}Your Groups{/tr}</strong></div>
			<ul id='mge-mine' >
			{foreach from=$userGroups key=groupname item=inclusion name=ix}
				{if $inclusion eq 'included'}
					<li><i>{$groupname|escape}</i></li>
				{else}
					<li>{$groupname|escape}</li>
				{/if}
			{/foreach}
			</ul >
		</fieldset>
	{/if}

	{if $groups_are_emulated eq 'y'}
		<fieldset>
			<div id='mge-emulated-legend' ><strong>{tr}Emulated Groups{/tr}</strong></div>
			<ul id='mge-emulated' >
			{section name=ix loop=$groups_emulated}
				<li>{$groups_emulated[ix]}</li>
			{/section}
			</ul>
			<form method="get" action="tiki-emulate_groups_switch.php" target="_self">
				<div style="text-align: center"><button type="submit" class="btn btn-primary btn-sm" name="emulategroups" value="resetgroups">{tr}Reset{/tr}</button></div>
			</form>
		</fieldset>
	{/if}

	<form method="get" action="tiki-emulate_groups_switch.php" target="_self">
		<fieldset>
			<div><strong>{tr}Switch to Groups{/tr}</strong></div>
			<select name="switchgroups[]" size="{$module_rows}" multiple="multiple" class="form-control table">
				{foreach from=$chooseGroups key=groupname item=inclusion name=ix}
					<option value="{$groupname|escape}" >{$groupname|escape}</option>
				{/foreach}
			</select>
			<div class="text-center"><button type="submit" class="btn btn-primary" name="emulategroups" value="setgroups" >{tr}Simulate{/tr}</button></div>
		</fieldset>
	</form>

{/tikimodule}
{/strip}
