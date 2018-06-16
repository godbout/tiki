{* $Id$ *}

{if ! $js}
	<ul class="cssmenu_horiz">
		<li>
{/if}
<a
	class="tips"
	title="{if ! empty($title)}{$title}{else}{tr}Actions{/tr}{/if}" href="#"
	{if $js}{popup fullhtml="1" center=true text=$smarty.capture.$capturedActions}{/if}
	style="padding:0; margin:0; border:0"
>
	{icon name='settings'}
</a>
{if ! $js}
			<ul class="dropdown-menu" role="menu">
				{$smarty.capture.$capturedActions}
			</ul>
		</li>
	</ul>
{/if}
