<div class="adminoptionbox preference clearfix form-group row {$p.tagstring|escape}{if isset($smarty.request.highlight) and $smarty.request.highlight eq $p.preference} highlight{/if}">
	<label class="col-sm-4 col-form-label" for="{$p.id|escape}">{$p.name|escape}</label>
	<div class="col-sm-8">
		<input name="{$p.preference|escape}" id="{$p.id|escape}" value="{$p.value|escape}" class="form-control" {* size="{$p.size|default:80|escape}" *} type="password" {$p.params}>
		{$p.detail|escape}
		{include file="prefs/shared.tpl"}
	</div>
</div>
