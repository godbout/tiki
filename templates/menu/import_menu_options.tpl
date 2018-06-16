{extends 'layout_view.tpl'}

{block name="title"}
	{title}{$title|escape}{/title}
{/block}

{block name="content"}
	{remarksbox type="tip" title="{tr}Tips{/tr}"}
		{tr}Menu{/tr}: {$menuInfo.name|escape} ({tr}Id{/tr}: {$menuInfo.menuId|escape})	
		{if $menuSymbol}
			<span class="form-text">
				{tr}Symbol{/tr}:{$menuSymbol.object} ({tr}Profile Name{/tr}:{$menuSymbol.profile}, {tr}Profile Source{/tr}:{$menuSymbol.domain})
			</span>
		{/if}
		<p>
		{tr}To add new options to the menu set the optionId field to 0. To remove an option set the remove field to 'y'.{/tr}
		{tr}Duplicate options will be ignored.{/tr}
	{/remarksbox}
	<form action="{service controller=menu action=import_menu_options menuId=$menuId}" method="post" enctype="multipart/form-data" role="form" class="no-ajax form-inline">
		<div class="form-group">
			<label for="csvfile" class="mr-2">
				{tr}File{/tr} 
			</label>
			<input name="csvfile" type="file" required="required" class="form-control">
			<div class="submit">
				{ticket mode=confirm}
				<input type="submit" class="btn btn-primary" name="import" value="{tr}Import{/tr}">
			</div>
		</div>
	</form>
{/block}
