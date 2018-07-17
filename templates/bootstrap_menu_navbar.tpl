<ul class="{if $bs_menu_class}{$bs_menu_class}{else}navbar-nav mr-auto{/if}">
	{foreach from=$list item=item}
		{if $item.children|default:null|count}
			<li class="nav-item dropdown{if $item.selected|default:null} active{/if} {$item.class|escape}">
				<a href="#" class="nav-link dropdown-toggle" data-toggle="dropdown">
					{tr}{$item.name}{/tr}
				</a>
				<div class="dropdown-menu">
					{foreach from=$item.children item=sub}
						<a class="dropdown-item{$sub.class|escape}{if $sub.selected|default:null} active{/if}" href="{$sub.sefurl|escape}">{tr}{$sub.name}{/tr}</a>
					{/foreach}
				</div>
			</li>
		{else}
			<li class="nav-item {$item.class|escape}{if $item.selected|default:null} active{/if}"><a class="nav-link" href="{$item.sefurl|escape}">{tr}{$item.name}{/tr}</a></li>
		{/if}
	{/foreach}
</ul>
