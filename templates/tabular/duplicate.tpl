{extends "layout_view.tpl"}

{block name="title"}
	{title}{$title}{/title}
{/block}

{block name="navigation"}
	<div class="nav d-inline-flex">
		{permission name=admin_trackers}
			<a class="btn btn-link" href="{service controller=tabular action=manage}">{icon name=list} {tr}Manage{/tr}</a>
		{/permission}
	</div>
{/block}

{block name="content"}
	<form method="post" action="{service controller=tabular action=duplicate tabularId=$tabularId}">
		<div class="form-group row">
			<label class="col-form-label col-sm-3">{tr}Name{/tr}</label>
			<div class="col-sm-9">
				<input class="form-control" type="text" name="name" value="{$name}" required>
			</div>
		</div>
		<div class="form-group submit">
			<div class="col-sm-9 offset-sm-3">
				<input type="submit" class="btn btn-secondary" value="{tr}Create{/tr}">
			</div>
		</div>
	</form>
{/block}
