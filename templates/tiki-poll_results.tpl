{* $Id$ *}

{title help="polls" admpage="polls"}{tr}Poll Results{/tr}{/title}

<div class="t_navbar mb-4">
	{button href="tiki-old_polls.php" class="btn btn-primary" _text="{tr}All Polls{/tr}"}
	{button href="tiki-poll_results.php" class="btn btn-primary" _text="{tr}Top-Voted Polls{/tr}"}
	{if $tiki_p_admin_polls eq 'y'}
		{if empty($pollId)}{button href="tiki-admin_polls.php" _text="{tr}Admin Polls{/tr}"}{else}{button href="tiki-admin_polls.php?pollId=$pollId&cookietab=1" _text="{tr}Edit Poll{/tr}"}{/if}
	{/if}
</div>

<form method="post" class="mb-4">
	{if !empty($sort_mode)}<input type="hidden" name="sort_mode" value="{$sort_mode|escape}">{/if}
	{if !empty($pollId)}<input type="hidden" name="pollId" value="{$pollId|escape}">{/if}
	{if !empty($list)}<input type="hidden" name="list" value="{$list|escape}">{/if}
	{if !empty($offset)}<input type="hidden" name="list" value="{$offset|escape}">{/if}
	{if empty($pollId) and !isset($list_votes)}
		<div class="form-group row">
			<label class="col-form-label col-sm-4">
				{if empty($what)}{tr}Find the poll{/tr}{else}{tr}{$what}{/tr}{/if}
			</label>
			<div class="col-sm-6">
				<input type="text" name="find" class="form-control" value="{$find|escape}">
			</div>
		</div>
		<div class="form-group row">
			<label class="col-form-label col-sm-4">
				{tr}Number of top voted polls to show{/tr}
			</label>
			<div class="col-sm-3">
				<input type="text" name="maxRecords" class="form-control" value="{$maxRecords|escape}" size="3">
			</div>
		</div>
		<br>
	{/if}
	<div class="form-group row">
		<div class="col-sm-12">
			<div class="form-check">
				<input class="form-check-input" type="radio" name="which_date" value="between"{if $which_date eq 'between'} checked="checked"{/if}>
				<label class="form-check-label" for="which_date">{tr}Show results in this range:{/tr}</label>
			</div>
		</div>
	</div>
	<div class="form-group row">
		<div class="col-sm-5 offset-sm-1">
			<label>
				{tr}Start:{/tr} {html_select_date prefix="from_" time="$vote_from_date" start_year="$start_year"}
			</label>
		</div>
		<div class="col-sm-5">
			<label>
				{tr}End:{/tr} {html_select_date prefix="to_" time="$vote_to_date" start_year="$start_year"}
			</label>
		</div>
	</div>
	{if empty($pollId) or $poll_info.voteConsiderationSpan > 0}
	<div class="form-group row">
		<div class="col-sm-12">
			<div class="form-check">
				<input class="form-check-input" type="radio" name="which_date_all" value="all"{if $which_date eq 'all'} checked="checked"{/if}>
				<label class="form-check-label" for="which_date_all">{tr}Show all results with no time span consideration{/tr}</label>
			</div>
		</div>
	</div>
	<div class="form-group row">
		<div class="col-sm-12">
			<div class="form-check">
				<input class="form-check-input" type="radio" name="which_date_consideration" value="consideration"{if $which_date eq 'consideration' or $which_date eq ''} checked="checked"{/if}>
				<label class="form-check-label" for="which_date_consideration">{tr}Show all results with time span consideration{/tr}</label>
			</div>
		</div>
	</div>
	{else}
	<div class="form-group row">
		<div class="col-sm-12">
			<div class="form-check">
				<input class="form-check-input" type="radio" name="which_date_all_dates" value="all"{if $which_date eq 'all' or $which_date eq ''} checked="checked"{/if}>
				<label class="form-check-label" for="which_date_all_dates">{tr}Show all results{/tr}</label>
			</div>
		</div>
	</div>
	{/if}
	<br>
	<input type="submit" class="btn btn-primary btn-sm" name="search" value="{tr}Find results{/tr}">
</form>

{section name=x loop=$poll_info_arr}
	<h2><a href="tiki-poll_results.php?pollId={$poll_info_arr[x].pollId}{if !empty($list_votes)}&amp;list=y{/if}">{$poll_info_arr[x].title|escape}</a></h2>
	{if !empty($msg)}
		{remarksbox type="info"}
			{$msg}
		{/remarksbox}
	{/if}
	{if $poll_info_arr[x].from or $poll_info_arr[x].to}
		<div class="description form-text">
		{if $poll_info_arr[x].from}{$poll_info_arr[x].from|tiki_short_date}{else}{$poll_info_arr[x].publishDate|tiki_short_date}{/if}
		- {if $poll_info_arr[x].to}{$poll_info_arr[x].to|tiki_short_date}{else}{tr}Today{/tr}{/if}
		</div>
	{/if}
	{if $tiki_p_view_poll_voters eq 'y' && $poll_info_arr[x].votes > 0}
		<div class="t_navbar">
			{assign var=thispoll_info_arr value=$poll_info_arr[x].pollId}
			{button href="?list=y&amp;pollId=$thispoll_info_arr" class="btn btn-info" _text="{tr}Show detailed results of this poll{/tr}" _auto_args="$auto_args"}
		</div>
	{/if}

{*----------------------------------- Results *}
{include file='tiki-poll_results_bar.tpl' poll_info=$poll_info_arr[x] showtitle=n}

{/section}

{*---------------------------List Votes *}
{if isset($list_votes)}
	<h2>{tr}List Votes{/tr}</h2>
	<div align="center">
		<table class="text-center">
			<tr>
				<td class="text-center">{tr}Find{/tr}</td>
				<td class="text-center">
					<form method="get" action="tiki-poll_results.php">
						<input type="text" name="find" value="{$find|escape}">
						<input type="submit" class="btn btn-primary btn-sm" value="{tr}Find{/tr}" name="search">
						<input type="hidden" name="sort_mode" value="{$sort_mode|escape}">
						<input type="hidden" name="pollId" value="{$pollId|escape}">
						<input type="hidden" name="list" value="y">
						{if $vote_from_date}<input type="hidden" name="vote_from_date" value="{$vote_from_date|escape}">{/if}
						{if $vote_to_date}<input type="hidden" name="vote_to_date" value="{$vote_to_date|escape}">{/if}
						{if $which_date}<input type="hidden" name="which_date" value="{$which_date|escape}">{/if}
						{if $maxRecords}<input type="hidden" name="maxRecords" value="{$maxRecords|escape}">{/if}
					</form>
				</td>
			</tr>
		</table>
	</div>
	<div class="table-responsive">
		<table class="table table-striped table-hover">
			<tr>
				<th>{self_link _sort_arg='sort_mode' _sort_field='user'}{tr}User{/tr}{/self_link}</th>
				<th>{self_link _sort_arg='sort_mode' _sort_field='ip'}{tr}IP{/tr}{/self_link}</th>
				{if $tiki_p_view_poll_choices eq 'y'}<th>{self_link _sort_arg='sort_mode' _sort_field='title'}{tr}Option{/tr}{/self_link}</th>{/if}
				<th>{self_link _sort_arg='sort_mode' _sort_field='time'}{tr}Date{/tr}{/self_link}</th>
				{if $tiki_p_admin eq 'y'}<th></th>{/if}
			</tr>

			{section name=ix loop=$list_votes}
				<tr>
					<td class="username">{$list_votes[ix].user|userlink}</td>
					<td class="text">{$list_votes[ix].ip|escape}</td>
					{if $tiki_p_view_poll_choices eq 'y'}<td class="text">{$list_votes[ix].title|escape}</td>{/if}
					<td class="date">{$list_votes[ix].time|tiki_short_date}</td>
					{if $tiki_p_admin eq 'y'}
						<td class="action">
							{self_link deletevote=1 user=$list_votes[ix].user ip=$list_votes[ix].ip optionId=$list_votes[ix].optionId _icon_name='remove' _class='tips' _title=":{tr}Remove{/tr}"}
							{/self_link}
						</td>
					{/if}
				</tr>
			{sectionelse}
				{norecords _colspan=4}
			{/section}
		</table>
	</div>
	{pagination_links cant=$cant_pages step=$prefs.maxRecords offset=$offset}{/pagination_links}
{/if}

{*---------------------- comments *}
{if $prefs.feature_poll_comments == 'y' && !empty($pollId)
	&& ($tiki_p_read_comments == 'y'
	|| $tiki_p_post_comments == 'y'
	|| $tiki_p_edit_comments == 'y')}
	<div id="page-bar" class="btn-group">
		<span class="button btn-primary"><a id="comment-toggle" href="{service controller=comment action=list type=poll objectId=$pollId}#comment-container">{tr}Comments{/tr}</a></span>
		{jq}
			$('#comment-toggle').comment_toggle();
		{/jq}
	</div>
	<div id="comment-container"></div>
{/if}
