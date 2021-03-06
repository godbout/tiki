{* $Id$ *}

{remarksbox type="tip" title="{tr}Tip{/tr}"}
	{tr}Enable/disable Tiki features in {/tr}<a class="alert-link" href="tiki-admin.php?page=features">{tr}Control Panels{/tr}&nbsp;{$prefs.site_crumb_seper}&nbsp;{tr}Features{/tr}</a>{tr}, but configure them elsewhere{/tr}.
	<br/>
	{tr}See <strong>more options</strong> after you enable more <a class='alert-link' target='tikihelp' href='https://doc.tiki.org/Preference+Filters'>Preference Filters</a> above ({icon name="filter"}){/tr}.
{/remarksbox}

{if $show_system_configuration_warning}
	{remarksbox type="warning" title="{tr}Warning{/tr}"}
	{tr}Tiki detected system configuration files with <strong>.ini</strong> extension, under the root folder of Tiki. It is recommended to change it to <strong>.ini.php</strong>.<br/>Check <strong><a href="https://doc.tiki.org/System-Configuration">https://doc.tiki.org/System-Configuration</a></strong> for examples.{/tr}
	{/remarksbox}
{/if}

<div class="d-flex align-content-start flex-wrap">
	{foreach from=$admin_icons key=page item=info}
			{if $info.disabled}
				{assign var=class value="admbox advanced btn btn-primary disabled"}
			{else}
				{assign var=class value="admbox basic btn btn-primary"}
			{/if}
				{* FIXME: Buttons are forced to be squares, not fluid. Labels which exceed 2 lines will be cut. *}
				<a href="{if $info.url}{$info.url}{else}tiki-admin.php?page={$page}{/if}" data-alt="{$info.title} {$info.description}" class="{$class} tips bottom slow {if $info.disabled}disabled-clickable{/if}" title="{$info.title|escape}{if $info.disabled} ({tr}Disabled{/tr}){/if}|{$info.description}">
					{icon name="admin_$page"}
					<span class="title">{$info.title|escape}</span>
				</a>
	{/foreach}
</div>
