<form class="form-horizontal" action="tiki-admin.php?page=directory" method="post">
	{include file='access/include_ticket.tpl'}
	<div class="row">
		<div class="form-group col-lg-12 clearfix">
			<a role="link" class="btn btn-link tips" href="tiki-directory_admin.php" title=":{tr}Directories listing{/tr}">
				{icon name="list"} {tr}Directory{/tr}
			</a>
			<div class="pull-right">
				<input type="submit" class="btn btn-primary btn-sm tips" title=":{tr}Apply changes{/tr}" value="{tr}Apply{/tr}">
			</div>
		</div>
	</div>


	<fieldset>
		<legend>{tr}Activate the feature{/tr}</legend>
		{preference name=feature_directory visible="always"}
	</fieldset>

	<fieldset>
		<legend>{tr}Directory{/tr}</legend>
		{preference name=directory_columns}
		{preference name=directory_links_per_page}
		{preference name=directory_validate_urls}
		{preference name=directory_cool_sites}
		{preference name=directory_country_flag}
		{preference name=directory_open_links}
	</fieldset>
	<br>{* I cheated. *}
	<div class="row">
		<div class="form-group col-lg-12 clearfix">
			<div class="text-center">
				<input type="submit" class="btn btn-primary btn-sm tips" title=":{tr}Apply changes{/tr}" value="{tr}Apply{/tr}">
			</div>
		</div>
	</div>
</form>
