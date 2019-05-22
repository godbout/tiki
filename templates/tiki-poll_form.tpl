<div class="card">
	<div class="card-header">
		<h2 class="card-title">{tr}Poll:{/tr}</h2>
	</div>
	<div class="card-body">
		{if $menu_info['active'] == 'x'}
			{remarksbox type="info" title="{tr}Sorry{/tr}"}
				{tr}This poll is closed.{/tr}
			{/remarksbox}

		{else}


				{include file='tiki-poll.tpl'}
				<div><a href="tiki-old_polls.php" class="link">{tr}Other Polls{/tr}</a></div>
		{/if}
	</div>
</div>


