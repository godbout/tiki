{* Note that when there in only one item it needs to be unformatted as it is often used inline in pretty trackers *}
{if $data.num > 1}
<ul class="arrowLinks">
	{foreach from=$data.items key=id item=label}
		<li class="d-flex justify-content-between">
			<span class="d-flex justify-content-start">
			{if $data.links}
				{object_link type=trackeritem id=$id title=$label}
			{elseif $data.raw}
				{$label}
			{else}
				{$label|escape}
			{/if}
			</span>
			<span class="d-flex justify-content-end">
			{if $field.options_map.editItem || $field.options_map.deleteItem}
				{if $field.options_map.editItem && $data.itemPermissions[$id].can_modify}
					<a href="{bootstrap_modal controller=tracker action=update_item trackerId=$field.options_map.trackerId itemId=$id}">{icon name="edit" ititle='{tr}Edit item{/tr}'}</a>
				{/if}
				{if $field.options_map.deleteItem && $data.itemPermissions[$id].can_remove}
					<a class="text-danger" href="{bootstrap_modal controller=tracker action=remove_item trackerId=$field.options_map.trackerId itemId=$id}">{icon name="remove" ititle='{tr}Delete item{/tr}'}</a>
				{/if}
			{/if}
			</span>
		</li>
	{/foreach}
	{if $data.addItemText}
		{$forcedParam[$data.otherFieldPermName]=$data.parentItemId}
		<li class="d-flex justify-content-start">
			<a class="float-left" href="{bootstrap_modal controller=tracker action=insert_item trackerId=$field.options_map.trackerId forced=$forcedParam}">{icon name='create' _menu_text='y' _menu_icon='y' ititle="{$data.addItemText}" alt="{$data.addItemText}"}</a>
		</li>
   	{/if}
</ul>
{elseif $data.num eq 1}
{strip}
	{foreach from=$data.items key=id item=label}
		{if $data.links}
			{object_link type=trackeritem id=$id title=$label}
		{elseif $data.raw}
			{$label}
		{else}
			{$label|escape}
		{/if}
	{/foreach}
	{if $field.options_map.editItem && $data.itemPermissions[$id].can_modify}
		&nbsp;&nbsp;<a href="{bootstrap_modal controller=tracker action=update_item trackerId=$field.options_map.trackerId itemId=$id}">{icon name="edit" ititle='{tr}Edit item{/tr}'}</a>
	{/if}
	{if $field.options_map.deleteItem && $data.itemPermissions[$id].can_remove}
		&nbsp;&nbsp;<a class="text-danger" href="{bootstrap_modal controller=tracker action=remove_item trackerId=$field.options_map.trackerId itemId=$id}">{icon name="remove" ititle='{tr}Delete item{/tr}'}</a>
	{/if}
	{if $data.addItemText}
        {$forcedParam[$data.otherFieldPermName]=$data.parentItemId}
		&nbsp;&nbsp;<a href="{bootstrap_modal controller=tracker action=insert_item trackerId=$field.options_map.trackerId forced=$forcedParam}">{icon name='create' _menu_icon='y' ititle="{$data.addItemText}" alt="{$data.addItemText}"}</a>
	{/if}
{/strip}
{/if}
