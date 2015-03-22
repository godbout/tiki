{title help="Shoutbox"}{tr}Admin Shoutbox Words{/tr}{/title}

<h2>{tr}Add Banned Word{/tr}</h2>

<div class="t_navbar">
	{button href="tiki-shoutbox.php" _icon_name="comments" _text="{tr}Shoutbox{/tr}"}
</div>

<form method="post" action="tiki-admin_shoutbox_words.php">
	<table class="formcolor">
		<tr>
			<td>{tr}Word{/tr}</td>
			<td><input type="text" name="word"></td>
		</tr>
		<tr>
			<td>&nbsp;</td>
			<td><input type="submit" class="btn btn-default btn-sm" name="add" value="{tr}Add{/tr}"></td>
		</tr>
	</table>
</form>

{include file='find.tpl'}

<div class="table-responsive">
<table class="table normal table-striped table-hover">
	<tr>
		<th>
			<a href="tiki-admin_shoutbox_words.php?offset={$offset}&amp;sort_mode={if $sort_mode eq 'word_desc'}word_asc{else}word_desc{/if}">{tr}Word{/tr}</a>
		</th>
		<th></th>
	</tr>

	{section name=user loop=$words}
		<tr>
			<td class="text">{$words[user].word|escape}</td>
			<td class="action">
				<a class="tips" href="tiki-admin_shoutbox_words.php?offset={$offset}&amp;sort_mode={$sort_mode}&amp;remove={$words[user].word|escape:"url"}" onclick="return confirmTheLink(this,"{tr}Are you sure you want to delete this word?{/tr}")" title=":{tr}Delete{/tr}">
					{icon name='remove'}
				</a>
			</td>
		</tr>
	{sectionelse}
		{norecords _colspan=2}
	{/section}
</table>
</div>

{pagination_links cant=$cant_pages step=$prefs.maxRecords offset=$offset}{/pagination_links}
