{* param status_types, $item.status, $tracker.newItemStatus, form_status *}
<select name="{$form_status}">
	{foreach key=st item=stdata from=$status_types}
		<option value="{$st}"
			{if (empty($item) and $tracker.newItemStatus eq $st) or (!empty($item) and $item.status eq $st)} selected="selected"{/if}
			style="background: url('{$stdata.image}') no-repeat center left 4px;padding-left:20px;">
			{$stdata.label}
		</option>
	{/foreach}
</select>