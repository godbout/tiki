{foreach item=unit from=$data.units|array_reverse}
	{if $field.options_map.$unit}
		<div class="input-group">
			<input type="number" class="numeric form-control" name="{$field.ins_id|escape}[{$unit}]" value="{$data.amounts.$unit|escape}" id="{$field.ins_id}_{$unit}">
			<div class="input-group-append">
    		<span class="input-group-text">{$unit}</span>
  		</div>
  	</div>
	{/if}
{/foreach}
