{* param status_types, $item.status, $tracker.newItemStatus, form_status *}
<select name="{$form_status}">
	{foreach key=st item=stdata from=$status_types}
		<option value="{$st}" class="tracker-status-{$st}"
			{if (empty($item) and $tracker.newItemStatus eq $st) or (!empty($item) and $item.status eq $st)} selected="selected"{/if}>
			{$stdata.label}
		</option>
	{/foreach}
</select>