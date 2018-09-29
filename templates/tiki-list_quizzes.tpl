{* $Id$ *}
{title help="Quiz"}{tr}Quizzes{/tr}{/title}

<div class="t_navbar mb-4">
	{if $tiki_p_admin_quizzes eq 'y'}
			{button href="tiki-edit_quiz.php" class="btn btn-link" _type="link" _icon_name="cog" _text="{tr}Admin Quizzes{/tr}"}
	{/if}
	{if $tiki_p_view_quiz_stats eq 'y'}
		{button href="tiki-quiz_stats.php" class="btn btn-link" _type="link" _icon_name="bar-chart" _text="{tr}Quiz Stats{/tr}"}
	{/if}
</div>

{if $channels or ($find ne '')}
	{include file='find.tpl'}
{/if}
<div class="{if $js}table-responsive{/if}"> {*the table-responsive class cuts off dropdown menus *}
	<table class="table table-striped table-hover">
		<tr>
			{assign var=numbercol value=1}
			<th>
				<a href="tiki-list_quizzes.php?offset={$offset}&amp;sort_mode={if $sort_mode eq 'name_desc'}name_asc{else}name_desc{/if}">{tr}Quiz{/tr}</a>
			</th>
			{assign var=numbercol value=$numbercol+1}
			<th>
				<a href="tiki-list_quizzes.php?offset={$offset}&amp;sort_mode={if $sort_mode eq 'timeLimit_desc'}timeLimit_asc{else}timeLimit_desc{/if}">{tr}Time Limit{/tr}</a>
			</th>
			{assign var=numbercol value=$numbercol+1}
			<th>
				{tr}Questions{/tr}
			</th>
			{if ($tiki_p_admin eq 'y' or $tiki_p_admin_quizzes eq 'y' or $tiki_p_view_quiz_stats eq 'y')}
				{assign var=numbercol value=$numbercol+1}
				<th></th>
			{/if}
		</tr>

		{section name=user loop=$channels}
			{if ($tiki_p_admin eq 'y') or ($channels[user].individual eq 'n' and $tiki_p_take_quiz eq 'y') or ($channels[user].individual_tiki_p_take_quiz eq 'y')}
				<tr>
					<td class="text">
						<a class="tablename" href="tiki-take_quiz.php?quizId={$channels[user].quizId}">{$channels[user].name|escape}</a>
						<span class="form-text">
							{$channels[user].description|escape|nl2br}
						</span>
					</td>
					<td class="integer">
						{$channels[user].timeLimited} {if $channels[user].timeLimited eq 'y'}({$channels[user].timeLimit} mins){/if}
					</td>
					<td class="integer">
						<span class="badge badge-secondary">{$channels[user].questions}</span>
					</td>
					{if ($tiki_p_admin eq 'y' or $tiki_p_admin_quizzes eq 'y' or $tiki_p_view_quiz_stats eq 'y')}
						<td class="action">
							{actions}
								{strip}
									{if ($tiki_p_admin eq 'y') or ($channels[user].individual eq 'n' and $tiki_p_admin_quizzes eq 'y') or ($channels[user].individual_tiki_p_admin_quizzes eq 'y')}
										<action>
											<a href="tiki-edit_quiz.php?quizId={$channels[user].quizId}">
												{icon name='edit' _menu_text='y' _menu_icon='y' alt="{tr}Edit{/tr}"}
											</a>
										</action>
									{/if}
									{if ($tiki_p_admin eq 'y') or ($channels[user].individual eq 'n' and $tiki_p_view_quiz_stats eq 'y') or ($channels[user].individual_tiki_p_view_quiz_stats eq 'y')}
										<action>
											<a href="tiki-quiz_stats_quiz.php?quizId={$channels[user].quizId}">
												{icon name='chart' _menu_text='y' _menu_icon='y' alt="{tr}Stats{/tr}"}
											</a>
										</action>
									{/if}
								{/strip}
							{/actions}
						</td>
					{/if}
				</tr>
			{/if}
		{sectionelse}
			{norecords _colspan=$numbercol}
		{/section}
	</table>
</div>

{pagination_links cant=$cant_pages step=$prefs.maxRecords offset=$offset}{/pagination_links}
