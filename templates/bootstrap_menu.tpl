<ul class="{if $bs_menu_class}{$bs_menu_class}{else}nav{/if} flex-column">
	{foreach from=$list item=item}
		{if !empty($item.children)}
			<li class="nav-item {$item.class|escape|default:null}{if !empty($item.selected)} active{/if}">
				<a href="#menu_option{$item.optionId|escape}" class="collapse-toggle nav-link" data-toggle="collapse" aria-expanded="false">
					{tr}{$item.name}{/tr}&nbsp;<small>{icon name="caret-down"}</small>
				</a>
				<ul id="menu_option{$item.optionId|escape}" class="nav flex-column collapse">
					{foreach from=$item.children item=sub}
						<li class="nav-item {$sub.class|escape|default:null}{if !empty($sub.selected)} active{/if}">
							<a class="nav-link" href="{$sub.sefurl|escape}"><small>{tr}{$sub.name}{/tr}</small></a>
						</li>
					{/foreach}
				</ul>
			</li>
		{else}
			<li class="nav-item {$item.class|escape|default:null}{if !empty($item.selected)} active{/if}">
				<a class="nav-link" href="{$item.sefurl|escape}">{tr}{$item.name}{/tr}</a>
			</li>
		{/if}
	{/foreach}
</ul>
