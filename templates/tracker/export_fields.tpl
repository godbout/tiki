{extends 'layout_view.tpl'}

{block name="title"}
	{title}{$title|escape}{/title}
{/block}

{block name="content"}
<form method="post" action="">
	<div class="form-group row mx-0">
		<label for="export_fields" class="col-form-label">{tr}Fields Export{/tr}</label>
		<textarea rows="20" name="export" id="export_fields" class="form-control">{$export|escape}</textarea>
	</div>
	<div class="description">
		{tr}Copy the definition text above and paste into the Import Fields box on a tracker's fields page.{/tr}
	</div>
</form>
{/block}
