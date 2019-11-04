{* $Id$ *}
<div id="tiki-center">
	<br>
	<div class="card">
		<div class="card-header">
			{tr}Information{/tr}
		</div>
		<div class="card-body">
		<div class="alert alert-info">
			{if is_array($msg)}
				{foreach from=$msg item=line}
					{$line|escape}<br>
				{/foreach}
			{else}
				{$msg|escape}
			{/if}
		</div>

		<p>
			{if $show_history_back_link eq 'y'}
				<a href="javascript:history.back()" class="linkmenu">{tr}Go back{/tr}</a><br><br>
			{/if}
			&nbsp;<a href="{$prefs.tikiIndex}" class="linkmenu">{tr}Return to home page{/tr}</a>
		</p>
		</div>
	</div>
</div>
