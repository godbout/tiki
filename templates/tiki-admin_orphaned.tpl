{title}{tr}Orphaned field names{/tr}{/title}
{remarksbox type="note" title="{tr}Note:{/tr}"}
	{tr}Use this tool to search for orphaned tracker permanent names. You can search wiki pages, wiki plugins and tracker field preferences. Useful when you change permanent names and want to see what other places you need to update.{/tr}
{/remarksbox}
<form action="tiki-admin_orphaned.php" method="post" class="form-horizontal" role="form">
	{ticket}
	<div class="form-group row">
		<label for="search_wiki_pages" class="col-sm-3 form-check-label">{tr}Include wiki pages{/tr}</label>
		<div class="col-sm-9">
			<div class="form-check">
				<input type="checkbox" id="search_wiki_pages" class="form-check-input" name="search[]" value="wiki_pages" {if $wiki_pages_checked} checked {/if}>
			</div>
		</div>
	</div>
	<div class="form-group row">
		<label for="search_tracker_fields" class="col-sm-3 form-check-label">{tr}Include tracker fields{/tr}</label>
		<div class="col-sm-9">
			<div class="form-check">
				<input type="checkbox" id="search_tracker_fields" class="form-check-input" name="search[]" value="tracker_fields" {if $tracker_fields_checked} checked {/if}>
			</div>
		</div>
	</div>
	<div class="form-group text-center">
		<input type="submit" class="btn btn-primary" name="submit" value="{tr}Search{/tr}">
	</div>
</form>

{if $results}
<div class="table-responsive">
	<table class="table">
		<tr>
			<th>{tr}Source{/tr}</th>
			<th>{tr}Missing Permanent Name{/tr}</th>
		</tr>
		{foreach from=$results item=row}
		<tr>
			<td>
				{if $row.page}
					<a href="{$row.page|sefurl}">Page: {$row.page}</a>
				{else}
					<a href="{service controller='tracker' action='edit_field' trackerId=$row.trackerId fieldId=$row.fieldId}" class="click-modal">Field: {$row.fieldId} {$row.fieldName}</a><br>
					<a href="tiki-admin_tracker_fields.php?trackerId={$row.trackerId}">Tracker: {$row.trackerName}</a>
				{/if}
			</td>
			<td>
				{$row.permanentName}
			</td>
		</tr>
		{/foreach}
	</table>
</div>
{elseif $searched}
<h4>{tr}No orphaned names found!{/tr}</h4>
{/if}
