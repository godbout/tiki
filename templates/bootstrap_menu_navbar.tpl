<ul class="{if $bs_menu_class}{$bs_menu_class}{else}navbar-nav mr-auto{if $prefs.jquery_smartmenus_enable eq 'y'} {*sm*}{if not empty($prefs.jquery_smartmenus_mode)} sm-{$prefs.jquery_smartmenus_mode}{/if}{/if}{/if}">
	{foreach from=$list item=item}
		{if $prefs.jquery_smartmenus_enable eq 'y'}
			{include file='bootstrap_menu_navbar_children.tpl' item=$item}
		{else}
			{if not empty($item.children)}
				<li class="nav-item dropdown{if $item.selected|default:null} active{/if} {$item.class|escape}">
					<a href="#" class="nav-link dropdown-toggle" data-toggle="dropdown">
						{tr}{$item.name}{/tr}
					</a>
					<ul class="dropdown-menu">
						{foreach from=$item.children item=sub}
							<li><a class="dropdown-item{$sub.class|escape}{if $sub.selected|default:null} active{/if}" href="{$sub.sefurl|escape}">{tr}{$sub.name}{/tr}</a></li>
						{/foreach}
					</ul>
				</li>
			{else}
				<li class="nav-item {$item.class|escape}{if $item.selected|default:null} active{/if}"><a class="nav-link" href="{$item.sefurl|escape}">{tr}{$item.name}{/tr}</a></li>
			{/if}
		{/if}
	{/foreach}
</ul>
