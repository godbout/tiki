{* $Id$ *}

{title}{tr}XML Zip Import{/tr}{/title}

<div class="t_navbar">
	<a role="link" href="tiki-admin_structures.php" class="btn btn-link" title="{tr}Structures{/tr}">
		{icon name="structure"} {tr}Structures{/tr}
	</a>
</div>
<form class="form-horizontal" enctype='multipart/form-data' method="post">
	<div class="form-group row">
		<div class="col-sm-12">
			<input class="form-control" type="file" name="zip">
		</div>
	</div>
	<div class="form-group row">
		<div class="col-sm-12">{tr}Or{/tr}</div>
	</div>
	<div class="form-group row">
		<label class="col-form-label col-sm-4" for="local">{tr}Name of the zip file on the server{/tr}</label>
		<div class="col-sm-8">
			<input class="form-control" type="text" name="local" id="local">
		</div>
	</div>
	<input type="submit" class="btn btn-primary btn-sm" name="import" value="{tr}Import{/tr}">
</form>
