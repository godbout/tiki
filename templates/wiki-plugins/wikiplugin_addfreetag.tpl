<form action="{$smarty.server.SCRIPT_NAME}?{query}" method="post" class="form-inline">
	<div class="input-group">
		<input type="text" class="form-control" name="{$wp_addfreetag|escape}">
		<div class="input-group-append">
			<input type="submit" class="btn btn-primary btn-sm" value="{tr}Add Tag{/tr}">
		</div>
	</div>
</form>
