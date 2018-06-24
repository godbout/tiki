{title help="polls" admpage="polls"}{tr}Polls{/tr}{/title}

{include file='find.tpl'}
<div class="{if $js}table-responsive{/if}"> {*the table-responsive class cuts off dropdown menus *}
<table class="table table-striped table-hover">
<tr>
<th>{self_link _sort_arg='sort_mode' _sort_field='title' title="{tr}Title{/tr}"}{tr}Title{/tr}{/self_link}</th>
<th>{self_link _sort_arg='sort_mode' _sort_field='publishDate' title="{tr}Published{/tr}"}{tr}Published{/tr}{/self_link}</th>
<th>{self_link _sort_arg='sort_mode' _sort_field='votes' title="{tr}Votes{/tr}"}{tr}Votes{/tr}{/self_link}</th>
<th></th>
</tr>

{section name=changes loop=$listpages}
<tr>
<td class="text">{$listpages[changes].title|escape}</td>
<td class="date">{$listpages[changes].publishDate|tiki_short_datetime}</td>
<td class="text">{$listpages[changes].votes}</td>
<td class="action">
	{actions}
		{strip}
			<action>
				<a href="tiki-poll_results.php?pollId={$listpages[changes].pollId}">
					{icon name='chart' _menu_text='y' _menu_icon='y' alt="{tr}Results{/tr}"}
				</a>
			</action>
			{if $tiki_p_vote_poll ne 'n'}
				<action>
					<a href="tiki-poll_form.php?pollId={$listpages[changes].pollId}">
						{icon name='ok' _menu_text='y' _menu_icon='y' alt="{tr}Vote{/tr}"}
					</a>
				</action>
			{/if}
		{/strip}
	{/actions}
</td>
</tr>
{sectionelse}
	{norecords _colspan=4}
{/section}
</table>
</div>
{pagination_links cant=$cant_pages step=$prefs.maxRecords offset=$offset}{/pagination_links}
