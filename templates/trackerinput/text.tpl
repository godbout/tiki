{if $field.isMultilingual ne 'y'}
	<div{if $field.options_map.prepend or $field.options_map.append} class="input-group"{/if}>
		{if $field.options_map.prepend}
			<span class="input-group-append">
				<span class="input-group-text">{$field.options_map.prepend}</span>
			</span>
		{/if}
		<input type="text" class="form-control" id="{$field.ins_id|replace:'[':'_'|replace:']':''}" name="{$field.ins_id}" {if $field.options_map.size}size="{$field.options_map.size}"{/if} {if $field.options_map.max}maxlength="{$field.options_map.max}"{/if} value="{$field.value|default:$field.defaultvalue|escape}">
		{if $field.options_map.append}
			<span class="input-group-append">
				<span class="input-group-text">{$field.options_map.append}</span>
			</span>
		{/if}
		{if $field.options_map.autocomplete eq 'y' and $prefs.feature_jquery_autocomplete eq 'y'}
			{autocomplete element="#"|cat:$field.ins_id|replace:"[":"_"|replace:"]":"" type="trackervalue"
					options="trackerId:"|cat:$field.trackerId|cat:",fieldId:"|cat:$field.fieldId}
		{/if}
	</div>
{else}
	{foreach from=$field.lingualvalue item=ling name=multi}
		<label for="{$ling.id|escape}">{$ling.lang|langname}</label>
		<div{if $field.options_map.prepend or $field.options_map.append} class="input-group"{/if}>
			{if !empty($field.options_map.prepend)}
				<span class="input-group-append">
					<span class="input-group-text">{$field.options_map.prepend}</span>
				</span>
			{/if}

			<input type="text" id="{$ling.id|escape}" name="{$field.ins_id}[{$ling.lang}]" value="{$ling.value|escape}" class="form-control"
				{if $field.options_map.size}size="{$field.options_map.size}"{/if} {if $field.options_map.max}maxlength="{$field.options_map.max}"{/if}> {*@@ missing value*}

			{if $field.options_map.append}
				<span class="input-group-append">
					<span class="input-group-text">{$field.options_map.append}</span>
				</span>
			{/if}

			{if $field.options_map.autocomplete eq 'y' and $prefs.feature_jquery_autocomplete eq 'y'}
				{autocomplete element="#`$ling.id`" type="trackervalue"
					options="trackerId:`$field.trackerId`,fieldId:`$field.fieldId`"}
			{/if}
		</div>
	{/foreach}
{/if}
