{if !empty($extra['fields'])}
	{foreach from=$extra['fields'] item=field}
		<div class="form-group row">
			{if isset($field['label'])}
				<label class="col-form-label">{$field['label']}</label>
			{/if}
			<div>
				<{$field['field']}
					class="form-control {if isset($field['class'])}{$field['class']}{/if}"
					type="{$field['type']}"
					size="{if isset($field['size'])}{$field['size']}{else}40{/if}"
					name="{$field['name']}"
					{if isset($field['placeholder'])}placeholder="{$field['placeholder']}"{/if}
					value=""
				/>
			</div>
		</div>
	{/foreach}
{/if}
