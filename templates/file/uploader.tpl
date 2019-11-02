{extends "layout_view.tpl"}

{block name="title"}
	{if $uploadInModal}{title}{$title}{/title}{/if}
{/block}

{block name="content"}
	{if $uploadInModal}

		<form class="file-uploader" enctype="multipart/form-data" method="post" action="{service controller=file action=upload galleryId=$galleryId image_max_size_x=$image_max_size_x image_max_size_y=$image_max_size_y}" data-gallery-id="{$galleryId|escape}" data-image_max_size_x="{$image_max_size_x|escape}" data-image_max_size_y="{$image_max_size_y|escape}" data-ticket="{ticket mode=get}">
			{if $image_max_size_x || $image_max_size_y }
				{remarksbox type="note" title="{tr}Note{/tr}"}
					{tr}Images will be resized to {if $image_max_size_x} {$image_max_size_x}px in width{/if}{if $image_max_size_y} and {$image_max_size_y}px in height{/if} {/tr}
				{/remarksbox}
			{elseif not empty($admin_trackers)}
				{remarksbox type="note" title="{tr}Note{/tr}"}
					{tr}Images will not be resized, for resizing edit this tracker field and set image max width and height in "Options for files" section.{/tr}
				{/remarksbox}
			{/if}
			{ticket}
			<div class="progress invisible mb-2">
				<div class="progress-bar progress-bar-striped active" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0%;">
					<span class="sr-only"><span class="count">0</span>% Complete</span>
				</div>
			</div>
			<div class="custom-file-title form-group" style="display: none;">
				<label class="custom-file-title-label" for="inputFileTitle">Title</label> <span class="text-danger">*</span>
				<input id="inputFileTitle" class="custom-file-title-input form-control" type="text" name="title" />
				<label class="invalid-feedback feedback-required-title">{tr}This field is required before file can be uploaded.{/tr}</label>
				<label class="invalid-feedback feedback-one-at-time">{tr}Only one file can be uploaded at a time{/tr}</label>
			</div>
			<div class="input-group">
				<div class="input-group-prepend">
					<span class="input-group-text" id="inputGroupText">{if $limit !== 1}{tr}Upload Files{/tr}{else}{tr}Upload File{/tr}{/if}</span>
				</div>
				<div class="custom-file">
					<input type="file" name="file[]" {if $limit !== 1}multiple{/if} {if $typeFilter}accept="{$typeFilter|escape}"{/if}
						   class="custom-file-input" id="inputFile" aria-describedby="inputGroupText"
							onchange="$(this).next('.custom-file-label').text($(this).val().replace('C:\\fakepath\\', ''));">
					<label class="custom-file-label" for="inputFile">Choose file</label>
				</div>
			</div>
			<p class="drop-message text-center">
				{if $limit !== 1}{tr}Or drop files here from your file manager.{/tr}{else}{tr}Or drop file here from your file manager.{/tr}{/if}
			</p>
		</form>
		<form class="file-uploader-result" method="post" action="{service controller=file action=uploader galleryId=$galleryId}">
			<ul class="list-unstyled" data-adddescription="{$addDecriptionOnUpload}"></ul>

			<div class="submit">
				{ticket}
				<input type="submit" class="btn btn-secondary" value="{tr}Select{/tr}">
			</div>
		</form>

	{else}{* not $uploadInModal *}

		<div class="file-uploader inline" data-action="{service controller=file action=upload galleryId=$galleryId image_max_size_x=$image_max_size_x image_max_size_y=$image_max_size_y}" data-gallery-id="{$galleryId|escape}" data-image_max_size_x="{$image_max_size_x|escape}" data-image_max_size_y="{$image_max_size_y|escape}" data-ticket="{ticket mode=get}">
			{if $image_max_size_x || $image_max_size_y }
				{remarksbox type="note" title="{tr}Note{/tr}"}
					{tr}Images will be resized to {if $image_max_size_x} {$image_max_size_x}px in width{/if}{if $image_max_size_y} and {$image_max_size_y}px in height{/if} {/tr}
				{/remarksbox}
			{elseif not empty($admin_trackers)}
				{remarksbox type="note" title="{tr}Note{/tr}"}
					{tr}Images will not be resized, for resizing edit this tracker field and set image max width and height in "Options for files" section.{/tr}
				{/remarksbox}
			{/if}
			<div class="progress invisible mb-2">
				<div class="progress-bar progress-bar-striped active" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0%;">
					<span class="sr-only"><span class="count">0</span>% Complete</span>
				</div>
			</div>
			<div class="input-group">
				<div class="input-group-prepend">
					<span class="input-group-text" id="inputGroupText">{if $limit !== 1}{tr}Upload Files{/tr}{else}{tr}Upload File{/tr}{/if}</span>
				</div>
				<div class="custom-file">
					<input type="file" name="file[]" {if $limit !== 1}multiple{/if} {if $typeFilter}accept="{$typeFilter|escape}"{/if}
						   class="custom-file-input" id="inputFile" aria-describedby="inputGroupText"
							onchange="$(this).next('.custom-file-label').text($(this).val().replace('C:\\fakepath\\', ''));">
					<label class="custom-file-label" for="inputFile">Choose file</label>
				</div>
			</div>

			<p class="drop-message text-center">
				{if $limit !== 1}{tr}Or drop files here from your file manager.{/tr}{else}{tr}Or drop file here from your file manager.{/tr}{/if}
			</p>

			<div class="file-uploader-result" method="post" action="{service controller=file action=uploader galleryId=$galleryId}">
				<ul class="list-unstyled" data-adddescription="{$addDecriptionOnUpload}"></ul>
			</div>
		</div>

	{/if}
{/block}
{if $requireTitle == 'y'}
	{jq}
		$('.custom-file-title').show();
	{/jq}
{/if}
