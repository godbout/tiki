{extends 'layout_view.tpl'}

{block name="title"}
	{title}{$title|escape}{/title}
{/block}

{block name="content"}
	<form class="content-form" enctype="multipart/form-data" action="{service controller='h5p' action='edit'}" method="post" accept-charset="UTF-8">
		<div>
			<div class="form-item form-type-textfield form-item-title">
				<label for="edit-title">Title
					<span class="form-required" title="This field is required.">*</span></label>
				<input type="text" id="edit-title" name="title" value="{$title}" size="60" maxlength="128" class="form-control required">
			</div>

			<div>
				<br><br><br>
				Rest of form here...
				<br><br><br>
			</div>
			<div class="form-actions form-wrapper" id="edit-actions">
				<input type="submit" id="edit-submit" name="op" value="Save" class="btn btn-primary">
				<input type="submit" id="edit-delete" name="op" value="Delete" class="btn btn-default confrim">
			</div>
		</div>
	</form>
{/block}
