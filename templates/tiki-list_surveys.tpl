{* $Id$ *}
{title help="Surveys"}{tr}Surveys{/tr}{/title}

<div class="t_navbar mb-4">
	{if $tiki_p_view_survey_stats eq 'y'}
		{button href="tiki-survey_stats.php" class="btn btn-primary" _text="{tr}Survey stats{/tr}"}
	{/if}
	{if $tiki_p_admin_surveys eq 'y'}
		{button href="tiki-admin_surveys.php?cookietab=2" class="btn btn-primary" _text="{tr}Create New Survey{/tr}"}
		{button href="tiki-admin_surveys.php?cookietab=1" class="btn btn-primary" _text="{tr}Admin Surveys{/tr}"}
	{/if}
</div>
<div class="{if $js}table-responsive{/if}"> {*the table-responsive class cuts off dropdown menus *}
	<table class="table table-striped table-hover">
		<tr>
			<th>
				{self_link _sort_arg='sort_mode' _sort_field='name'}{tr}Name{/tr}{/self_link}
			</th>
			<th>{tr}Questions{/tr}</th>
			<th></th>
		</tr>

		{section name=user loop=$channels}
			{if ($tiki_p_admin eq 'y') or ($channels[user].individual eq 'n' and $tiki_p_take_survey eq 'y') or ($channels[user].individual_tiki_p_take_survey eq 'y')}
				<tr>
					<td class="text">
						{if ($tiki_p_admin_surveys eq 'y') or ($channels[user].status eq 'o' and $channels[user].taken_survey eq 'n')}
							<a class="tablename" href="{$channels[user].surveyId|sefurl:survey}">
								{$channels[user].name|escape}
							</a>
						{else}
							<a class="link" href="tiki-survey_stats_survey.php?surveyId={$channels[user].surveyId}">
								{$channels[user].name|escape}
							</a>
						{/if}
						<div class="subcomment">
							{wiki}{$channels[user].description}{/wiki}
						</div>
					</td>
					<td class="text">
						<span class="badge badge-secondary">{$channels[user].questions}</span>
					</td>
					<td class="action">
						{actions}
							{strip}
								{if ($tiki_p_admin eq 'y') or ($channels[user].individual eq 'n' and $tiki_p_admin_surveys eq 'y') or ($channels[user].individual_tiki_p_admin_surveys eq 'y')}
									<action>
										<a href="tiki-admin_surveys.php?surveyId={$channels[user].surveyId}">
											{icon name='edit' _menu_text='y' _menu_icon='y' alt="{tr}Edit{/tr}"}
										</a>
									</action>
								{/if}

								{if ($tiki_p_admin_surveys eq 'y') or ($channels[user].status eq 'o' and $channels[user].taken_survey eq 'n')}
									<action>
										<a href="{$channels[user].surveyId|sefurl:survey}">
											{icon name='post' _menu_text='y' _menu_icon='y' alt="{tr}Take survey{/tr}"}
										</a>
									</action>
								{/if}

								{if ($tiki_p_admin eq 'y') or ($channels[user].individual eq 'n' and $tiki_p_view_survey_stats eq 'y') or ($channels[user].individual_tiki_p_view_survey_stats eq 'y')}
									<action>
										<a href="tiki-survey_stats_survey.php?surveyId={$channels[user].surveyId}">
											{icon name='chart' _menu_text='y' _menu_icon='y' alt="{tr}Stats{/tr}"}
										</a>
									</action>
								{/if}
							{/strip}
						{/actions}
					</td>
				</tr>
			{/if}
			{sectionelse}
			{norecords _colspan=3}
		{/section}
	</table>
</div>

{pagination_links cant=$cant_pages step=$maxRecords offset=$offset}{/pagination_links}
