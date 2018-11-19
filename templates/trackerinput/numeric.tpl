<div{if $field.options_map.prepend or $field.options_map.append} class="input-group"{/if}>
	{if $field.options_map.prepend}
		<span class="input-group-append">
			<span class="input-group-text">{$field.options_map.prepend}&nbsp</span>
		</span>
	{/if}

	<input type="number" class="numeric form-control" name="{$field.ins_id|escape}"
		   {if $field.options_array[1]}size="{$field.options_array[1]|escape}" maxlength="{$field.options_array[1]|escape}"{/if}
		   value="{$field.value|escape}" id="{$field.ins_id}">

	{if $field.options_map.append}
		<span class="input-group-append">
			<span class="input-group-text">{$field.options_map.append}</span>
		</span>
	{/if}
</div>
