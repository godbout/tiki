{* $Id$ *}
{if $upgrade_messages|count}
	{if $upgrade_messages|count eq 1}
		{$title="{tr}Upgrade Available{/tr}"}
	{else}
		{$title="{tr}Upgrades Available{/tr}"}
	{/if}

	<strong>{$title}</strong>

	{foreach from=$upgrade_messages item=um}
		<p>{$um|escape}</p>
	{/foreach}
{/if}