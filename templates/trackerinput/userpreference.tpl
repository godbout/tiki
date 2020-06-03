{if $field.options_map.type eq 'password'}
	{if ($prefs.auth_method neq 'cas' || ($prefs.cas_skip_admin eq 'y' && $user eq 'admin')) and $prefs.change_password neq 'n'}
		<input type="password" name="{$field.ins_id}" class="form-control">
		<br><i>Leave empty if password is to remain unchanged</i>
	{/if}
{elseif $field.options_map.type eq 'language'}
	<select name="{$field.ins_id}" class="form-control">
		{section name=ix loop=$languages}
			<option value="{$languages[ix].value|escape}" {if $field.value eq $languages[ix].value}selected="selected"{/if}>
				{$languages[ix].name}
			</option>
		{/section}
		<option value=''{if !$field.value} selected="selected"{/if}>{tr}Site default{/tr}</option>
	</select>
{elseif $field.options_map.type eq 'country'}
	<select name="{$field.ins_id}" class="form-control">
		<option value="Other"{if $field.value eq "Other"} selected="selected"{/if}>
			{tr}Other{/tr}
		</option>
		{foreach from=$context.flags item=flag key=fval}{strip}
			{if $fval ne "Other"}
				<option value="{$fval|escape}"{if $field.value eq $fval} selected="selected"{/if}>
					{$flag|stringfix}
				</option>
			{/if}
		{/strip}{/foreach}
	</select>
{elseif $field.options_map.type eq 'display_timezone'}
	<select name="{$field.ins_id}" class="form-control">
		<option value=""{if empty($field.value)} selected="selected"{/if} style="font-style:italic;">
			{tr}Detect user time zone if browser allows, otherwise site default{/tr}
		</option>
		<option value="Site" style="font-style:italic;border-bottom:1px dashed #666;"{if $field.value eq 'Site'} selected="selected"{/if}>
			{tr}Site default{/tr}
		</option>
		{foreach key=tz item=tzinfo from=$context.timezones}
			{math equation="floor(x / (3600000))" x=$tzinfo.offset assign=offset}
			{math equation="(x - (y*3600000)) / 60000" y=$offset x=$tzinfo.offset assign=offset_min format="%02d"}
			<option value="{$tz|escape}"{if $field.value eq $tz} selected="selected"{/if}>
				{$tz|escape} (UTC{if $offset >= 0}+{/if}{$offset}h{if $offset_min gt 0}{$offset_min}{/if})
			</option>
		{/foreach}
	</select>
{else}
	<input type="text" name="{$field.ins_id}" value="{$field.value|escape}" class="form-control">
{/if}
