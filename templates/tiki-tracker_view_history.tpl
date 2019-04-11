{title help="trackers"}{tr}Tracker Item History{/tr}{/title}
<div class="t_navbar mb-4">
	{button _keepall='y' href="tiki-view_tracker_item.php" itemId=$item_info.itemId _class="btn btn-primary" _text="{tr}View Tracker Item{/tr}"}
</div>

{if $logging eq 0}
	{remarksbox title="{tr}Not logging{/tr}" type="warning"}
		{tr}Tracker changes are not being logged: Go to <a href="tiki-admin_actionlog.php?action_log_type=trackeritem&cookietab=2" class="alert-link">Action log admin</a> to enable{/tr}
	{/remarksbox}
{/if}

<div class="clearfix">
	<form method="post" class="form">
		<div class="row align-items-end">
			<div class="form-group col-sm">
				<label class="col-form-label">{tr}Version{/tr}
					<input type="text" name="version" value="{if !empty($filter.version)}{$filter.version|escape}{/if}" class="form-control">
				</label>
			</div>
			<div class="form-group col-sm">
				<label class="col-form-label">{tr}Field ID{/tr}
					<input type="text" name="fieldId" value="{if !empty($fieldId)}{$fieldId|escape}{/if}" class="form-control">
				</label>
			</div>
			<div class="form-group col-sm">
				<label class="col-form-label">
					{tr}Diff Style{/tr}
					<select name="diff_style" id="tracker_diff_style" class="form-control">
						<option value="" {if empty($diff_style)}selected="selected"{/if}>{tr}Original{/tr}</option>
						<option value="sidediff" {if $diff_style == "sidediff"}selected="selected"{/if}>
							{tr}Side-by-side diff{/tr}
						</option>
						<option value="inlinediff" {if $diff_style == "inlinediff"}selected="selected"{/if}>
							{tr}Inline diff{/tr}
						</option>
						<option value="unidiff" {if $diff_style == "unidiff"}selected="selected"{/if}>
							{tr}Unified diff{/tr}
						</option>
					</select>
				</label>
			</div>
			<div class="form-group col-sm mb-4">
				<input type="submit" class="btn btn-primary" name="Filter" value="{tr}Filter{/tr}">
			</div>
		</div>
	</form>
</div>
<br/>

<div class="table-responsive">
	<table class="table">
		<tr>
			<th class="text-nowrap">{tr}Version{/tr}</th>
			<th class="text-nowrap">{tr}Date{/tr}</th>
			<th class="text-nowrap">{tr}User{/tr}</th>
			<th class="text-nowrap">{tr}Field ID{/tr}</th>
			<th class="text-nowrap">{tr}Field{/tr}</th>
			{if empty($diff_style)}
				<th class="text-nowrap">{tr}Old{/tr}</th>
				<th class="text-nowrap">{tr}New{/tr}</th>
			{else}
				<th class="text-nowrap" colspan="2">{tr}Difference{/tr}</th>
			{/if}
		</tr>

		{$last_version = 0}
		{foreach from=$history item=hist}
			{if $hist.value neq $hist.new}
				{assign var='fieldId' value=$hist.fieldId}
				{assign var='field_value' value=$field_option[$fieldId]}
				<tr>
					{if $last_version neq $hist.version}
						<td class="id"><strong>{$hist.version|escape}</strong></td>
						<td class="date"><strong>{if not empty($hist.lastModif)}{$hist.lastModif|tiki_short_datetime}{/if}</strong></td>
						<td class="username"><strong>{$hist.user|username}</strong></td>
						{$last_version = $hist.version}
					{else}
						<td class="id">&nbsp;</td>
						<td class="date">&nbsp;</td>
						<td class="username">&nbsp;</td>
					{/if}
					<td class="text">
						{if $fieldId ne -1}{$fieldId}{/if}
					</td>
					<td class="text">
						{if $fieldId eq -1}_{tr}Status{/tr}_{else}{$field_option[$fieldId].name}{/if}
					</td>
					{if empty($diff_style)}
						{if $field_value.fieldId}
							<td class="text">{$field_value.value=$hist.value}{trackeroutput field=$field_value list_mode=csv item=$item_info history=y process=y}</td>
							<td class="text">{$field_value.value=$hist.new}{trackeroutput field=$field_value list_mode=csv item=$item_info history=y process=y}</td>
						{else}
							<td class="text">{$hist.value|escape}</td>
							<td class="text">{$hist.new|escape}</td>
						{/if}
					{else}
						<td colspan="2" class="tracker-diff {$diff_style}">
							{$field_value.value=$hist.new}
							{trackeroutput field=$field_value list_mode='y' history=y item=$item_info process=y oldValue=$hist.value diff_style=$diff_style}
						</td>
					{/if}
				</tr>
			{/if}
		{/foreach}
	</table>
</div>

{pagination_links cant=$cant offset=$offset step=$prefs.maxRecords}
{/pagination_links}
