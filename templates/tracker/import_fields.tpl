{extends "layout_view.tpl"}

{block name="title"}{$title}{/block}

{block name="content"}
	<form class="form simple import-fields" action="{service controller=tracker action=import_fields}" method="post" role="form">
		<div class="form-group row mx-0">
			<label class="col-form-label">
				{tr}Raw Fields{/tr}
			</label>
			<textarea class="form-control" name="raw" rows="30"></textarea>
		</div>
		<div class="form-check">
			<label>
				<input type="checkbox" class="form-check-input" name="preserve_ids" value="1">
				{tr}Preserve Field IDs{/tr}
			</label>
			<label>
				<input type="checkbox" class="form-check-input" name="last_position" checked="checked" value="1">
				{tr}Imported fields at the bottom of the list{/tr}
			</label>
		</div>
		<div class="form-group submit">
			<input type="hidden" name="trackerId" value="{$trackerId|escape}">
			<input type="submit" class="btn btn-primary" value="{tr}Import{/tr}">
		</div>
	</form>
{/block}
