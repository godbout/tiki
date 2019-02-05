{extends "layout_view.tpl"}

{block name="title"}
	{title}{$title}{/title}
{/block}

{block name="content"}
	{if $id}
		<div class="alert alert-success">
			<strong>{tr}Your article has been added!{/tr}</strong>
			{object_link type=article id=$id}
		</div>
	{/if}
	<form method="post" action="{service controller=article action=create_from_url}">
		<div class="form-group row">
			<label for="url" class="col-form-label">{tr}URL{/tr}</label>
			<input type="url" name="url" class="form-control">
		</div>

		{if $topics}
		<div class="form-group row">
			<label for="topicId" class="col-form-label">{tr}Topic{/tr}</label>
			<select name="topicId" class="form-control">
				{foreach $topics as $topicId => $name}
					<option value="{$topicId|escape}">{$name|escape}</option>
				{/foreach}
			</select>
		</div>
		{/if}

		<div class="form-group row">
			<label for="type" class="col-form-label">{tr}Article Type{/tr}</label>
			<select name="type" class="form-control">
				{foreach $types as $name}
					<option value="{$name|escape}">{$name|escape}</option>
				{/foreach}
			</select>
		</div>

		<div class="submit">
			<input class="btn btn-primary" type="submit" value="{tr}Create Article{/tr}">
		</div>
	</form>
{/block}

