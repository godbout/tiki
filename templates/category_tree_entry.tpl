<div class="form-check" {if !empty($category_data.description)} title="{$category_data.name|escape} | {$category_data.description|escape}"{/if}>
	<label for="categ-{$category_data.categId|escape}" class="form-check-label">
		{if $category_data.canchange}
			<input class="form-check-input" id="categ-{$category_data.categId|escape}" type="checkbox" name="cat_categories[]" value="{$category_data.categId|escape}"
				{if $category_data.incat eq 'y'}checked="checked"{/if}>
			<input id="categ-{$category_data.categId|escape}_hidden" type="hidden" name="cat_managed[]" value="{$category_data.categId|escape}">
		{else}
			<input class="form-check-input" id="categ-{$category_data.categId|escape}" type="checkbox" disabled="disabled"
				{if $category_data.incat eq 'y'}checked="checked"{/if}>
		{/if}
		{$category_data.name|escape}
	</label>
</div>
