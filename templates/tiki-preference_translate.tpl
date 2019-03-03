{* $Id$ *}
{title url="tiki-preference_translate.php?pref={if isset($pref)}{$pref}{/if}"}{tr}Preference translation{/tr}{if isset($pref)}: {$pref}{/if}{/title}

<form method="post" action="tiki-preference_translate.php" class="form">
	{ticket}
	<input type="hidden" name="pref" value="{$pref|escape}">

	<div class="table-responsive">
		<table class="table" id="tagtranslationtable">
			<thead>
				<tr>
					<th class="text-center">Language</th>
					<th class="text-center">Value</th>
				</tr>
			</thead>
			<tbody>
				{foreach item=val key=lang from=$translated_val}
					<tr>
					{if $lang neq ''}
						<td><strong>{$lang}{if $lang eq $default_language} / default{/if}:</strong></td>
						<td>
							<div>
								<input type="text" name="new_val[{$lang}]" value="{$val}" class="form-control">
							</div>
						</td>
					{/if}
					</tr>
				{/foreach}
				<tr>
					<td colspan="2">
						<div class="text-center">
							<input type="submit" class="btn btn-primary" name="save" value="{tr}Save{/tr}">
						</div>
					</td>
				</tr>
			</tbody>
		</table>
	</div>
	<div class="form-group row">
		<label for="additional_languages" class="control-lable">
			{tr}Displayed languages:{/tr}
		</label>
		<div class="input-group">
			<select multiple="multiple" name="additional_languages[]" class="form-control">
				{foreach item=lang from=$fullLanguageList}
					<option value="{$lang.value}"{if in_array($lang.value, $languageList)} selected="selected"{/if}>{$lang.name}</option>
				{/foreach}
			</select>
		</div>
		<div class="input-group">
			<span class="input-group-append" style="padding-top: 10px;">
				<input type="submit" class="btn btn-primary" value="{tr}Select{/tr}">
			</span>
		</div>
	</div>
</form>

