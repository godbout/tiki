{title help="Cache"}{tr}External Pages Cache{/tr}{/title}

{remarksbox type="tip" title="{tr}Tip{/tr}"}
	{tr}The cache is used by:{/tr} <a href="tiki-admin.php?page=textarea" class="alert-link">{tr}Cache external pages{/tr}</a>
{/remarksbox}

{include file='find.tpl'}

<div class="{if $js}table-responsive{/if}"> {* table-responsive class cuts off css drop-down menus *}
	<table class="table">
		<tr>
			<th>
				<a href="tiki-list_cache.php?offset={$offset}&amp;sort_mode={if $sort_mode eq 'url_desc'}url_asc{else}url_desc{/if}">{tr}URL{/tr}</a>
			</th>
			<th>
				<a href="tiki-list_cache.php?offset={$offset}&amp;sort_mode={if $sort_mode eq 'refresh_desc'}refresh_asc{else}refresh_desc{/if}">{tr}Last updated{/tr}</a>
			</th>
			<th></th>
		</tr>
		{section name=changes loop=$listpages}
			<tr>
				<td class="text">
					<a class="link" href="{$listpages[changes].url}">{$listpages[changes].url}</a>
				</td>
				<td class="date">
					{$listpages[changes].refresh|tiki_short_datetime}
				</td>
				<td class="action">
					{actions}
						{strip}
							<action>
								<a target="_blank" href="tiki-view_cache.php?cacheId={$listpages[changes].cacheId}">
									{icon name="view" _menu_text='y' _menu_icon='y' alt="{tr}View{/tr}"}
								</a>
							</action>
							<action>
								<a href="tiki-list_cache.php?offset={$offset}&amp;sort_mode={$sort_mode}&amp;refresh={$listpages[changes].cacheId}"">
									{icon name="refresh" _menu_text='y' _menu_icon='y' alt="{tr}Refresh{/tr}"}
								</a>
							</action>
							<action>
								<a href="tiki-list_cache.php?offset={$offset}&amp;sort_mode={$sort_mode}&amp;remove={$listpages[changes].cacheId}">
									{icon name="remove" _menu_text='y' _menu_icon='y' alt="{tr}Remove{/tr}"}
								</a>
							</action>
						{/strip}
					{/actions}
				</td>
			</tr>
		{sectionelse}
			{norecords _colspan=3}
		{/section}
	</table>
</div>

{pagination_links cant=$cant_pages step=$prefs.maxRecords offset=$offset}{/pagination_links}
