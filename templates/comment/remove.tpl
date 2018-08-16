{extends 'layout_view.tpl'}

{block name="title"}
	{title}{$title|escape}{/title}
{/block}

{block name="content"}
	{if $status neq 'DONE'}
		<form method="post" action="{service controller=comment action=remove}">
			<div class="card bg-warning">
				<div class="card-header">
					{tr}Are you sure you want to delete this comment?{/tr}
				</div>
				<div class="card-body">
					<input type="hidden" name="threadId" value="{$threadId|escape}"/>
					<input type="hidden" name="confirm" value="1"/>
					<input type="submit" class="btn btn-warning btn-sm" value="{tr}Delete{/tr}"/>
					{object_link type=$objectType id=$objectId title="{tr}Cancel{/tr}"}
				</div>
			</div>
		</form>
	{/if}
{/block}
