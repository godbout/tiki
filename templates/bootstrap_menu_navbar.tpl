<ul class="{if $bs_menu_class}{$bs_menu_class}{else}navbar-nav mr-auto{if $prefs.jquery_smartmenus_enable eq 'y'} {*sm*}{if not empty($prefs.jquery_smartmenus_mode)} sm-{$prefs.jquery_smartmenus_mode}{/if}{/if}{/if}">
	{foreach from=$list item=item}
		{include file='bootstrap_menu_navbar_children.tpl' item=$item}
	{/foreach}
</ul>
