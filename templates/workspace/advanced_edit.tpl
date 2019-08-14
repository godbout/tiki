{extends 'layout_view.tpl'}

{block name="title"}
	{title}{$title|escape}{/title}
{/block}

{block name="navigation"}
	<div class="navbar">
		<a class="btn btn-primary" href="{service controller=workspace action=list_templates}" title="{tr}List{/tr}">
			{icon name="list"} {tr}Workspace Templates{/tr}
		</a>
	</div>
{/block}

{block name="content"}
	<form method="post" action="{service controller=workspace action=advanced_edit id=$id}" class="form" role="form">
		{if $is_advanced != 'y'}
			{remarksbox type=warning title="{tr}No turning back{/tr}" close="n"}
				<p>{tr}Once you switch your template to advanced mode, there is no turning back. The simple interface will no longer be available.{/tr}</p>
				<a href="{service controller=workspace action=edit_template id=$id}" class="alert-link">{tr}Return to simple interface{/tr}</a>
			{/remarksbox}
		{/if}
		<div class="form-group row">
			<label for="name" class="col-sm-2 col-form-label">
				{tr}Name{/tr}
			</label>
			<div class="col-sm-10">
				<input type="text" name="name" value="{$name|escape}" class="form-control">
			</div>
		</div>
		<div class="form-group row">
			<div class="col-sm-12">
				{textarea syntax='tiki' codemirror='true'}{$definition}{/textarea}
			</div>
		</div>
		<div class="submit text-center">
			<input type="submit" class="btn btn-primary" value="{tr}Save{/tr}">
		</div>
	</form>
{/block}
