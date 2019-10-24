<div class="object-selector">
<input
	type="text"
	id="{$object_selector.simpleid|escape}"
	{if $prefs.javascript_enabled eq 'y'}style="display: none"{/if}
	{if $object_selector.simpleclass}class="{$object_selector.simpleclass|escape}"{/if}
	{if $object_selector.simplename}name="{$object_selector.simplename|escape}"{/if}
	{if $object_selector.simplevalue}value="{$object_selector.current_selection.id|escape}"{/if}
>
<input
	type="text"
	id="{$object_selector.id|escape}"
	{if $prefs.javascript_enabled eq 'y'}style="display: none"{/if}
	{if $object_selector.name}name="{$object_selector.name|escape}"{/if}
	{if $object_selector.class}class="{$object_selector.class|escape}"{/if}
	{if $object_selector.current_selection}
		value="{$object_selector.current_selection|escape}"
		data-label="{$object_selector.current_selection.title|escape}"
	{/if}
	{if $object_selector.parent}data-parent="{$object_selector.parent|escape}"{/if}
	{if $object_selector.parentkey}data-parentkey="{$object_selector.parentkey|escape}"{/if}
	{if $object_selector.format}data-format="{$object_selector.format|escape}"{/if}
	{if $object_selector.format}data-format="{$object_selector.format|escape}"{/if}
	{if $object_selector.sort}data-sort="{$object_selector.sort|escape}"{/if}
	data-filters="{$object_selector.filter|escape}"
	data-threshold="{$object_selector.threshold|default:$prefs.tiki_object_selector_threshold|escape}"
	data-searchfield="{$object_selector.searchfield|escape}"
>
	<div class="basic-selector d-none mb-3">
		<select class="form-control">
			<option value="" class="protected">&mdash;</option>
			{if $object_selector.current_selection}
				<option value="{$object_selector.current_selection|escape}" selected="selected">{$object_selector.current_selection.title|escape}</option>
			{/if}
		</select>
	</div>

	<div class="card d-none">
		<div class="card-header">
			<div class="input-group">
				<div class="input-group-prepend">
					<div class="input-group-text">
						{icon name="search"}
					</div>
				</div>
				<input type="text" placeholder="{$object_selector.placeholder|escape}..." value="" class="filter form-control" autocomplete="off">
				<div class="input-group-append">
					<input type="button" class="btn btn-info search" value="{tr}Find{/tr}">
				</div>
			</div>
		</div>
		<div class="card-body">
			<div class="results">
				<p class="too-many">{tr}Search and select what you are looking for from the options that appear.{/tr}</p>
				<div class="form-check">
					<input name="{$object_selector.id|escape}_sel" class="form-check-input protected" type="radio" value="{$object|escape}" {if ! $object_selector.current_selection} checked="checked" {/if} value="" id="{$object_selector.id|escape}_sel_empty">
					<label class="form-check-label" for="{$object_selector.id|escape}_sel_empty">&mdash;</label>
				</div>
				{if $object_selector.current_selection}
					<div class="form-check">
						<input type="radio" checked="checked" value="{$object_selector.current_selection|escape}" name="{$object_selector.id|escape}_sel" name="{$object_selector.id|escape}_sel_selected">
						<label class="form-check-label" for="{$object_selector.id|escape}_sel_selected">{$object_selector.current_selection.title|escape}</label>
					</div>
				{/if}
			</div>
			<p class="no-results d-none">
				{tr}No matching results.{/tr}
			</p>
		</div>
	</div>
</div>

{jq}
$('#{{$object_selector.id|escape}}')
	.object_selector();
{/jq}
