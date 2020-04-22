{if $field.options_map.autoassign lt 1 or $tiki_p_admin_trackers eq 'y'}
	{if $tiki_p_group_add_member ne 'y' and $tiki_p_group_view ne 'y'}
		{remarksbox type="error" close="n" title="{tr}You do not have permission to add a member to a group.{/tr}"}{/remarksbox}
	{else}
		<select name="{$field.ins_id}" class="form-control">
			{if $field.isMandatory ne 'y'}
				<option value="">{tr}None{/tr}</option>
			{/if}
			{section name=ux loop=$field.list}
				{if !isset($field.itemChoices) or $field.itemChoices|@count eq 0 or in_array($field.list[ux], $field.itemChoices)}
					<option value="{$field.list[ux]|escape}" {if $field.value eq $field.list[ux]}selected="selected"{/if}>{$field.list[ux]}</option>
				{/if}
			{/section}
		</select>
	{/if}
{elseif not empty($field.options_map.autoassign)}
	{$field.defvalue}
	<input type="hidden" name="{$field.ins_id}" value="{$field.defvalue}">
{/if}
