{if not empty($item.children)}
	<li class="nav-item dropdown{if $item.selected|default:null} active{/if} {$item.class|escape}">
		<a href="#" class="{if $sub|default:false}dropdown-item{else}nav-link{/if} dropdown-toggle" data-toggle="dropdown">
			{tr}{$item.name}{/tr}
		</a>
		<ul class="dropdown-menu">
			{foreach from=$item.children item=sub}
				{include file='bootstrap_menu_navbar_children.tpl' item=$sub sub=true}
			{/foreach}
		</ul>
	</li>
{else}
	<li class="nav-item {$item.class|escape}{if $item.selected|default:null} active{/if}"><a class="{if $sub|default:false}dropdown-item{else}nav-link{/if}" href="{$item.sefurl|escape}">{tr}{$item.name}{/tr}</a></li>
{/if}