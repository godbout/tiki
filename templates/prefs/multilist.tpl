<div class="adminoptionbox preference clearfix form-group row {$p.tagstring|escape}{if isset($smarty.request.highlight) and $smarty.request.highlight eq $p.preference} highlight{/if}" style="text-align: left;">
	<label class="col-sm-4 col-form-label" for="{$p.id|escape}">{$p.name|escape}</label>
	<div class="col-sm-8">
		<select class="form-control" name="{$p.preference|escape}[]" id="{$p.id|escape}" multiple="multiple">
			{foreach from=$p.options key=value item=label}
				<option value="{$value|escape}"{if in_array($value, $p.value)} selected="selected"{/if} {$p.params}>{$label|escape}</option>
			{/foreach}
		</select>
		{include file="prefs/shared.tpl"}
		{if $prefs.jquery_ui_chosen neq 'y'}
			{remarksbox type="tip" title="{tr}Tip{/tr}"}{tr}Use Ctrl+Click to select multiple options{/tr}{/remarksbox}
		{/if}
	</div>
</div>
