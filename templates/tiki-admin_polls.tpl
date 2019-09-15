{* $Id$ *}
{title help="Polls" admpage="polls"}{tr}Admin Polls{/tr}{/title}

<div class="t_navbar mb-4">
	<form action="tiki-admin_polls.php" method="post">
		{ticket}
		<button type="submit" name="setlast" value="1" class="btn btn-primary">{icon name="previous"} {tr}Set last poll as current{/tr} </button>
		<button type="submit" name="closeall" value="1" class="btn btn-primary">{icon name="disable"} {tr}Close all polls but last{/tr}</button>
		<button type="submit" name="activeall" value="1" class="btn btn-primary">{icon name="broadcast-tower"} {tr}Activate all polls{/tr}</button>
	</form>
	{if $pollId neq '0'}
		{button pollId=0 cookietab=1 _class="btn btn-link" _icon_name="create" _text="{tr}Create poll{/tr}"}
	{/if}
</div>

{tabset}

	{if $pollId eq '0'}
		{assign var='title' value="{tr}Create poll{/tr}"}
	{else}
		{assign var='title' value="{tr}Edit poll{/tr}"}
	{/if}
	{tab name=$title}
		<h2>{$title}</h2>
		<form action="tiki-admin_polls.php?save=1" method="post">
			{ticket}
			<input type="hidden" name="pollId" value="{$pollId|escape}">

			<div class="form-group row">
				<label class="col-sm-3 col-form-label" for="title">{tr}Title{/tr}</label>
				<div class="col-sm-7">
					<input type="text" name="title" id="title" value="{$info.title|escape}" class="form-control">
				</div>
			</div>
			<div class="form-group row">
				<label class="col-sm-3 col-form-label" for="active">{tr}Status{/tr}</label>
				<div class="col-sm-7">
					<select name="active" id="active" class="form-control">
						<option value='a' {if $info.active eq 'a'}selected="selected"{/if}>{tr}active{/tr}</option>
						<option value='c' {if $info.active eq 'c'}selected="selected"{/if}>{tr}current{/tr}</option>
						<option value='x' {if $info.active eq 'x'}selected="selected"{/if}>{tr}closed{/tr}</option>
						<option value='t' {if $info.active eq 't'}selected="selected"{/if} style="border-top:1px solid black;">{tr}template{/tr}</option>
						<option value='o' {if $info.active eq 'o'}selected="selected"{/if}>{tr}object{/tr}</option>
					</select>
				</div>
			</div>
			<div class="form-group row">
				<label class="col-sm-3 col-form-label">{tr}Options{/tr}</label>
				<div class="col-sm-7">
					<a id="tikiPollsOptionsButton" href="javascript://toggle quick options" onclick="pollsToggleQuickOptions()" class="btn btn-primary btn-sm mb-2">{tr}Show Options{/tr}</a>
				</div>
			</div>
			<div id="tikiPollsQuickOptions" style="display: none">
				<div>
					{section name=opt loop=$options}
					<div>
						<input type="hidden" name="optionsId[]" value="{$options[opt].optionId}">
						<input class="form-control mb-2" type="text" name="options[]" value="{$options[opt].title}">
					</div>
					{/section}
					<div id="tikiPollsOptions" class="col-sm-7 col-sm-offset-3 mb-3">
						<input type="text" name="options[]" class="form-control mb-2" placeholder="{tr}New option{/tr}">
						<a href="javascript://Add Option"	onclick="pollsAddOption()" class="btn btn-primary btn-sm">{tr}Add Option{/tr}</a>
					</div>
				</div>
				<div class="col-sm-7 col-sm-offset-3">
					<span class="description form-text text-muted">{tr}Leave box empty to delete an option.{/tr}</span>
				</div>
			</div>
			<div class="form-group row">
				<label class="col-sm-3 col-form-label">{tr}Publish Date{/tr}</label>
				<div class="col-sm-7">
					{if ($prefs.feature_jscalendar) == 'y'}
						{jscalendar showtime="y" fieldname="pollPublishDate" date=$info.publishDate}
					{else}
						<div class="mb-2">
							{html_select_date time=$info.publishDate end_year="+1" field_order=$prefs.display_field_order}<br>
						</div>
						{html_select_time time=$info.publishDate display_seconds=false use_24_hours=$use_24hr_clock}
					{/if}
				</div>
			</div>
			<div class="form-group row">
				<label class="col-sm-3 col-form-label">{tr}Votes older than x days are not considered{/tr}</label>
				<div class="col-sm-7">
					<input type="text" id="voteConsiderationSpan" name="voteConsiderationSpan" size="5" value="{$info.voteConsiderationSpan|escape}" class="form-control">
					<div class="small-hint">
						<span class="description text-muted">{tr}0 for no limit{/tr}</span>
					</div>
				</div>
			</div>
			{include file='categorize.tpl' labelcol="3" inputcol="7"}
			<div class="form-group row">
				<label class="col-sm-3 col-form-label"></label>
				<div class="col-sm-8 offset-sm-1">
					<input type="submit" class="btn btn-primary btn-sm" name="add" value="{tr}Add{/tr}">
				</div>
			</div>
		</form>
	{/tab}

	{tab name="{tr}Polls{/tr}"}
		<h2>{tr}Polls{/tr}</h2>
		{if $channels or ($find ne '')}
			{include file='find.tpl'}
		{/if}
		<div class="{if $js}table-responsive{/if} poll-table"> {* table-responsive class cuts off css drop-down menus *}
			<table class="table table-striped table-hover">
				{assign var=numbercol value=8}
				<tr>
					<th>{self_link _sort_arg='sort_mode' _sort_field='pollId' title="{tr}ID{/tr}"}{tr}ID{/tr}{/self_link}</th>
					<th>{self_link _sort_arg='sort_mode' _sort_field='title' title="{tr}Title{/tr}"}{tr}Title{/tr}{/self_link}</th>
					{if $prefs.poll_list_categories eq 'y'}<th>{tr}Categories{/tr}</th>{assign var=numbercol value=$numbercol+1}{/if}
					{if $prefs.poll_list_objects eq 'y'}<th>{tr}Objects{/tr}</th>{assign var=numbercol value=$numbercol+1}{/if}
					<th>{self_link _sort_arg='sort_mode' _sort_field='active' title="{tr}Active{/tr}"}{tr}Active{/tr}{/self_link}</th>
					<th>{self_link _sort_arg='sort_mode' _sort_field='votes' title="{tr}Votes{/tr}"}{tr}Votes{/tr}{/self_link}</th>
					<th>{self_link _sort_arg='sort_mode' _sort_field='publishDate' title="{tr}Publish{/tr}"}{tr}Publish{/tr}{/self_link}</th>
					<th>{self_link _sort_arg='sort_mode' _sort_field='voteConsiderationSpan' title="{tr}Span{/tr}"}{tr}Span{/tr}{/self_link}</th>
					<th>{tr}Options{/tr}</th>
					<th></th>
				</tr>

				{section name=user loop=$channels}
					<tr>
						<td class="id">{$channels[user].pollId}</td>
						<td class="text">
							<a class="tablename" href="tiki-poll_results.php?pollId={$channels[user].pollId}">{$channels[user].title|escape}</a>
						</td>
						{if $prefs.poll_list_categories eq 'y'}
							<td class="text">
								{section name=cat loop=$channels[user].categories}
									{$channels[user].categories[cat].name}
									{if !$smarty.section.cat.last}
										<br>
									{/if}
								{/section}
							</td>
						{/if}
						{if $prefs.poll_list_objects eq 'y'}
							<td class="text">
								{section name=obj loop=$channels[user].objects}
									<a href="{$channels[user].objects[obj].href}">{$channels[user].objects[obj].name}</a>
									{if !$smarty.section.obj.last}
										<br>
									{/if}
								{/section}
							</td>
						{/if}
						<td class="text">{$channels[user].active}</td>
						<td class="integer">{$channels[user].votes}</td>
						<td class="date">{$channels[user].publishDate|tiki_short_datetime}</td>
						<td class="integer">{$channels[user].voteConsiderationSpan|escape}</td>
						<td class="integer">{$channels[user].options}</td>
						<td class="action">
							{actions}
								{strip}
									<action>
										<a href="tiki-admin_poll_options.php?pollId={$channels[user].pollId}">
											{icon name='list' _menu_text='y' _menu_icon='y' alt="{tr}Options{/tr}"}
										</a>
									</action>
									<action>
										<a class="link" href="tiki-poll_results.php?pollId={$channels[user].pollId}">
											{icon name="chart" _menu_text='y' _menu_icon='y' alt="{tr}Results{/tr}"}
										</a>
									</action>
									<action>
										{self_link pollId=$channels[user].pollId cookietab=1 _menu_text='y' _menu_icon='y' _icon_name="edit"}
											{tr}Edit{/tr}
										{/self_link}
									</action>
									<action>
										<a href="tiki-admin_polls.php?offset={$offset}&amp;sort_mode={$sort_mode}&amp;remove={$channels[user].pollId}" onclick="confirmSimple(event, '{tr}Delete poll?{/tr}', '{ticket mode=get}')">
											{icon name='remove' _menu_text='y' _menu_icon='y' alt="{tr}Remove{/tr}"}
										</a>
									</action>
								{/strip}
							{/actions}
						</td>
					</tr>
				{sectionelse}
					{norecords _colspan=$numbercol}
				{/section}
			</table>
		</div>
		{pagination_links cant=$cant_pages step=$prefs.maxRecords offset=$offset}{/pagination_links}
	{/tab}

	{tab name="{tr}Add poll to pages{/tr}"}
		<h2>{tr}Add poll to pages{/tr}</h2>
		<form action="tiki-admin_polls.php" method="post" class="form-horizontal">
			{ticket}
			<div class="form-group row">
				<label class="col-sm-3 col-form-label">{tr}Poll{/tr}</label>
				<div class="col-sm-7 mb-2">
					<select name="poll_template" class="form-control">
						{section name=ix loop=$channels}
							{if $channels[ix].active eq 't'}
								<option value="{$channels[ix].pollId|escape}"{if $smarty.section.ix.first} selected="selected"{/if}>{tr}{$channels[ix].title}{/tr}</option>
							{/if}
						{/section}
					</select>
					<span class="form-text text-muted description">{tr}Only polls with a status of "template" shown{/tr}</span>
				</div>
			</div>
			<div class="form-group row">
				<label class="col-sm-3 col-form-label">{tr}Title{/tr}</label>
				<div class="col-sm-7">
					<input type="text" name="poll_title" class="form-control">
				</div>
			</div>
			<div class="form-group row">
				<label class="col-sm-3 col-form-label">{tr}Wiki pages{/tr}</label>
				<div class="col-sm-7">
					<select name="pages[]" multiple="multiple" class="form-control">
						{section name=ix loop=$listPages}
							<option value="{$listPages[ix].pageName|escape}">{tr}{$listPages[ix].pageName|escape}{/tr}</option>
						{/section}
					</select>
					<span class="form-text text-muted description">{tr}Use Ctrl+Click to select multiple options{/tr}</span>
				</div>
			</div>
			{if $prefs.feature_wiki_usrlock eq 'y'}
				<div class="form-group row">
					<label class="col-sm-3 col-form-label">{tr}Lock the pages{/tr}</label>
					<div class="col-sm-7">
						<input type="checkbox" class="form-control" name="locked">
					</div>
				</div>
			{/if}
			<div class="form-group row">
				<label class="col-sm-3 col-form-label"></label>
				<div class="col-sm-7">
					<input type="submit" class="btn btn-primary" name="addPoll" value="{tr}Save{/tr}">
				</div>
			</div>
		</form>
	{/tab}
{/tabset}
