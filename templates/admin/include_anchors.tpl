{*$Id$*}
{foreach from=$admin_icons key=page item=info}
	{if ! $info.disabled}
		<li><a href="{if $info.url}{$info.url}{else}tiki-admin.php?page={$page}{/if}" data-alt="{$info.title} {$info.description}" class="tips bottom slow icon nav-link" title="{$info.title}|{$info.description}">
			{icon name="admin_$page"}
		</a></li>
	{/if}
{/foreach}