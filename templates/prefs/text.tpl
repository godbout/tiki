<div class="form-group adminoptionbox preference clearfix {$p.tagstring|escape}{if isset($smarty.request.highlight) and $smarty.request.highlight eq $p.preference} highlight{/if}">
	<label class="control-label col-sm-4" for="{$p.id|escape}">{$p.name|escape}</label>
	<div class="col-sm-8">
		{if !empty($p.units)}
			<div class="input-group">
		{/if}
		{if is_array( $p.value )}
			<input name="{$p.preference|escape}" id="{$p.id|escape}" value="{$p.value|@implode:$p.separator|escape}" class="form-control" size="{$p.size|default:40|escape}"
				type="text" {$p.params}>
		{else}
			<input name="{$p.preference|escape}" id="{$p.id|escape}" value="{$p.value|escape}" class="form-control" size="{$p.size|default:40|escape}"
				type="text" {$p.params}>
		{/if}
		{if !empty($p.units)}
			<span class="input-group-addon">{$p.units}</span></div>
		{/if}

		{include file="prefs/shared.tpl"}
	</div>
</div>
