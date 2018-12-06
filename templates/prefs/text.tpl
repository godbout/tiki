<div class="adminoptionbox preference clearfix form-group row {$p.tagstring|escape}{if isset($smarty.request.highlight) and $smarty.request.highlight eq $p.preference} highlight{/if}">
	<label class="col-form-label col-sm-4" for="{$p.id|escape}">{$p.name|escape}</label>
	<div class="col-sm-8">
		{if not empty($p.units) or not empty($p.fgal_picker)}
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
				<div class="input-group-append">
					<span class="input-group-text">{$p.units}</span>
				</div>
			</div>
		{/if}
		{if !empty($p.fgal_picker)}
			{$fileId = {ldelim}|cat:'fileId'|cat:{rdelim}}
				<div class="input-group-append">
					<a class="btn btn-primary" title="{tr}Upload image{/tr}" href="#"
						onclick="$('#{$p.id|escape}').select(); openFgalsWindow('tiki-upload_file.php?filegals_manager={$p.id|escape}&insertion_syntax={$fileId|sefurl:display}', true);return false;">
					{icon name='image'}&nbsp;{tr}Upload image{/tr}
					</a>
				</div>
			</div>
		{/if}

		{include file="prefs/shared.tpl"}
	</div>
</div>
