<ul class="{if $bs_menu_class}{$bs_menu_class}{else}navbar-nav mr-auto{/if}">
	{foreach from=$list item=item}
		{if $item.children|default:null|count}
			<li class="nav-item{if $item.selected|default:null} active{/if} {$item.class|escape}">
				<a href="#" class="nav-link dropdown-toggle" data-toggle="dropdown">
					{tr}{$item.name}{/tr}
				</a>
				<ul class="dropdown-menu">
					{foreach from=$item.children item=sub}
						<li class="dropdown-item{$sub.class|escape}{if $sub.selected|default:null} active{/if}"><a href="{$sub.sefurl|escape}">{tr}{$sub.name}{/tr}</a></li>
					{/foreach}
				</ul>
			</li>
		{else}
			<li class="nav-item {$item.class|escape}{if $item.selected|default:null} active{/if}"><a class="nav-link" href="{$item.sefurl|escape}">{tr}{$item.name}{/tr}</a></li>
		{/if}
	{/foreach}
</ul>
