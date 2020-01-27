<fieldset class="mb-3 w-100">
	<legend>{tr}Wiki attachments{/tr}</legend>
	<form action="tiki-admin.php?page=wikiatt" method="post">
		{ticket}
		<input type="text" name="find" value="{$find|escape}">
		<input type="submit" class="btn btn-primary btn-sm" name="action" value="{tr}Find{/tr}">
	</form>


	<div class="table-responsive">
		<table class="table">
			<tr>
				<th>{self_link _sort_arg='sort_mode' _sort_field='user'}{tr}User{/tr}{/self_link}</th>
				<th>{self_link _sort_arg='sort_mode' _sort_field='page'}{tr}Page{/tr}{/self_link}</th>
				<th>{self_link _sort_arg='sort_mode' _sort_field='filename'}{tr}Name{/tr}{/self_link}</th>
				<th>{self_link _sort_arg='sort_mode' _sort_field='filesize'}{tr}Size{/tr}{/self_link}</th>
				<th>{self_link _sort_arg='sort_mode' _sort_field='filetype'}{tr}Type{/tr}{/self_link}</th>
				<th>{self_link _sort_arg='sort_mode' _sort_field='path'}{tr}Storage{/tr}{/self_link}</th>
				<th>{self_link _sort_arg='sort_mode' _sort_field='created'}{tr}Created{/tr}{/self_link}</th>
				<th>{self_link _sort_arg='sort_mode' _sort_field='hits'}{tr}Hits{/tr}{/self_link}</th>
				<th>&nbsp;</th>
			</tr>

			{section name=x loop=$attachements}
				{if $attachements[x].path}
					{$current = 'file'}{$move = 'move2db'}{$confirm = "{tr}Move attachment to database?{/tr}"}
					{$tip = "{tr}Move to database{/tr}"}
				{else}
					{$current = 'db'}{$move = 'move2file'}{$confirm = "{tr}Move attachment to file system?{/tr}"}
					{$tip = "{tr}Move to file system{/tr}"}
				{/if}
				<tr class={cycle}>
					<td>{$attachements[x].user}</td>
					<td><a href="tiki-index.php?page={$attachements[x].page}">{$attachements[x].page}</a></td>
					<td>
						<a href="tiki-download_wiki_attachment.php?attId={$attachements[x].attId}">{$attachements[x].filename}</a>
					</td>
					<td>{$attachements[x].filesize|kbsize}</td>
					<td>{$attachements[x].filetype}</td>
					<td>{$current}</td>
					<td>{$attachements[x].created|tiki_short_date}</td>
					<td>{$attachements[x].hits}</td>
					<td>
						<form action="tiki-admin.php?page=wikiatt" method="post">
							{ticket}
							<input type="hidden" name="attId" value="{$attachements[x].attId}&amp;action={$move}">
							<input type="hidden" name="action" value="{$move}">
							<button  class="btn btn-link tips" title=":{$tip}" onclick="confirmSimple(event, '{$confirm}')">{icon name=move}</button>
						</form>
					</td>
				</tr>
			{sectionelse}
				{norecords _colspan=9}
			{/section}
		</table>
	</div>

	{pagination_links cant=$cant_pages step=$prefs.maxRecords offset=$offset}{/pagination_links}

	<table>
		<tr>
			<td>
				<form action="tiki-admin.php?page=wikiatt" method="post">
					{ticket}
					<input type="hidden" name="all2db" value="1">
					<input
						type="submit"
						class="btn btn-primary btn-sm"
						name="action"
						value="{tr}Change all to db{/tr}"
					>
				</form>
			</td>
			<td>
				<form action="tiki-admin.php?page=wikiatt" method="post">
					{ticket}
					<input type="hidden" name="all2file" value="1">
					<input
						type="submit"
						class="btn btn-primary btn-sm"
						name="action"
						value="{tr}Change all to file{/tr}"
					>
				</form>
			</td>
		</tr>
	</table>
</fieldset>
