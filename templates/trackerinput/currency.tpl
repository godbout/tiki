{*prepend*}
{if $field.options_map.prepend}
	<span class="formunit">{$field.options_map.prepend|escape}&nbsp;</span>
{/if}

<input type="number" class="numeric form-control" name="{$field.ins_id|escape}"
	{if $field.options_map.size}size="{$field.options_map.size|escape}" maxlength="{$field.options_map.size|escape}"{/if}
	value="{$field.amount|escape}" id="{$field.ins_id|escape}"
>

{if $data.currencies}
<select name="{$field.ins_id|escape}_currency" id="{$field.ins_id|escape}_currency" class="form-control">
  <option value=""></option>
  {foreach from=$data.currencies item=c}
    <option value="{$c}" {if $c eq $field.currency}selected{/if}>{$c}</option>
  {/foreach}
</select>
{/if}

{if $data.error}
  {$data.error}
{/if}

{*append*}
{if $field.options_map.append}
	<span class="formunit">&nbsp;{$field.options_map.append|escape}</span>
{/if}

