<div class="object-selector-multi">
{if $object_selector_multi.separator}
	<input
		data-separator="{$object_selector_multi.separator|escape}"
		type="text"
		{if $prefs.javascript_enabled eq 'y'}style="display: none"{/if}
		id="{$object_selector_multi.simpleid|escape}"
		{if $object_selector_multi.simpleclass}class="{$object_selector_multi.simpleclass|escape}"{/if}
		{if $object_selector_multi.simplename}name="{$object_selector_multi.simplename|escape}"{/if}
		value="{$object_selector_multi.separator|implode:$object_selector_multi.current_selection_simple|escape}"
	>
{/if}
<textarea
	id="{$object_selector_multi.id|escape}"
	{if $prefs.javascript_enabled eq 'y'}style="display: none"{/if}
	{if $object_selector_multi.name}name="{$object_selector_multi.name|escape}"{/if}
	{if $object_selector_multi.class}class="{$object_selector_multi.class|escape}"{/if}
	{if $object_selector_multi.title}data-label="{$object_selector_multi.title|escape}"{/if}
	{if $object_selector_multi.parent}data-parent="{$object_selector_multi.parent|escape}"{/if}
	{if $object_selector_multi.parentkey}data-parentkey="{$object_selector_multi.parentkey|escape}"{/if}
	{if $object_selector_multi.format}data-format="{$object_selector_multi.format|escape}"{/if}
	{if $object_selector_multi.sort}data-sort="{$object_selector_multi.sort|escape}"{/if}
	data-wildcard="{$object_selector_multi.wildcard|escape}"
	data-filters="{$object_selector_multi.filter|escape}"
	data-threshold="{$object_selector_multi.threshold|default:$prefs.tiki_object_selector_threshold|escape}"
	data-searchfield="{$object_selector_multi.searchfield|escape}"
>{"\n"|implode:$object_selector_multi.current_selection}</textarea>
	<div class="basic-selector d-none">
		<select class="form-control" multiple>
			{foreach $object_selector_multi.current_selection as $object}
				<option value="{$object|escape}" selected="selected">{$object.title|escape}</option>
			{/foreach}
		</select>
	</div>

	<div class="card d-none">
		<div class="card-header">
			<div class="input-group">
				<div class="input-group-prepend">
					<span class="input-group-text">
						{icon name=search}
					</span>
				</div>
				<input type="text" placeholder="{$object_selector_multi.placeholder|escape}..." value="" class="filter form-control" autocomplete="off">
				<div class="input-group-append">
					<input type="button" class="btn btn-info search" value="{tr}Find{/tr}">
				</div>
			</div>
		</div>
		<div class="card-body">
			<p class="too-many">{tr}Search and select what you are looking for from the options that appear.{/tr}</p>
			<div class="results">
				{foreach from=$object_selector_multi.current_selection item=object name=ix}
					<div class="form-check">
						<input id="{$object_selector_multi.id|escape}_selected_{$smarty.foreach.ix.index}" class="form-check-input" type="checkbox" value="{$object|escape}" checked>
						<label class="form-check-label" for="{$object_selector_multi.id|escape}_selected_{$smarty.foreach.ix.index}">
							{if $object|substring:0:11 eq 'trackeritem'}
								{tracker_item_status_icon item=$object|substring:12}
							{/if}
							{$object.title|escape}
						</label>
					</div>
				{/foreach}
			</div>
			<p class="no-results d-none">
				{tr}No matching results.{/tr}
			</p>
		</div>
	</div>
</div>

{jq}
$('#{{$object_selector_multi.id|escape}}')
	.object_selector_multi();
{/jq}
