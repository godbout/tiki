{extends "layout_view.tpl"}

{block name="title"}
	{title}{$title}{/title}
{/block}

{block name="navigation"}
	<div class="navbar d-inline-flex">
		{permission name=admin_trackers}
			<a class="btn btn-link" href="{service controller=tabular action=create}">{icon name=create} {tr}New{/tr}</a>
			<a class="btn btn-link" href="{service controller=tabular action=manage}">{icon name=list} {tr}Manage{/tr}</a>
		{/permission}
	</div>
{/block}

{block name="content"}
	{if $completed}
		{remarksbox type=confirm title="{tr}Import Completed{/tr}"}
			{tr}Your import was completed succesfully.{/tr}
		{/remarksbox}
	{else}
		<form class="no-ajax" method="post" action="{service controller=tabular action=import_csv tabularId=$tabularId}" enctype="multipart/form-data">
			<div class="input-group mb-3">
				<div class="input-group-prepend">
					<span class="input-group-text" id="inputGroupText">{tr}CSV File{/tr}</span>
				</div>
				<div class="custom-file">
					<input type="file" name="file" accept="text/csv" class="custom-file-input" id="inputFile" aria-describedby="inputGroupText"
						onchange="$(this).next('.custom-file-label').text($(this).val().replace('C:\\fakepath\\', ''));">
					<label class="custom-file-label" for="inputFile">Choose file</label>
				</div>
			</div>
			<div class="submit">
				<input class="btn btn-primary" type="submit" value="{tr}Import{/tr}">
			</div>
		</form>
	{/if}
{/block}
