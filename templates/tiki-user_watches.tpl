{* $Id$ *}

{title help="User Watches"}{tr}User Watches and preferences{/tr}{/title}
{include file='tiki-mytiki_bar.tpl'}

{if $email_ok eq 'n'}
	{remarksbox type="warning" title="{tr}Warning{/tr}"}
	{tr}You need to set your email to receive email notifications.{/tr}
		<a href="tiki-user_preferences.php" class="tips alert-link" title=":{tr}User preferences{/tr}">{icon name="next"}</a>
	{/remarksbox}
{/if}


{tabset name="user_watches"}

{if $prefs.feature_daily_report_watches eq 'y'}
	{tab name="{tr}Report Preferences{/tr}"}
		<h2>{tr}Report Preferences{/tr}</h2>
		{remarksbox type="tip" title="{tr}Tip{/tr}"}{tr}Use reports to summarise notifications about objects you are watching.{/tr}{/remarksbox}
		<form action="tiki-user_reports.php" method="post">
			{ticket}
			<input type="hidden" name="report_preferences" value="true">

			<div class="form-group row">
				<label class="col-sm-3" for="use_daily_reports">{tr}Use reports{/tr}</label>
				<div class="col-sm-9">
                    <div class="form-check">
					    <input type="checkbox" class="form-check-input" name="use_daily_reports" value="true" {if $report_preferences != false}checked{/if}>
				    </div>
                </div>
			</div>
			<div class="form-group row">
				<label class="col-sm-3" for="interval">{tr}Reporting interval{/tr}</label>
				<div class="col-sm-9">
					<select name="interval" class="form-control" >
						<option value="minute"
								{if $report_preferences.interval eq "minute"}selected{/if}>{tr}Every minute{/tr}</option>
						<option value="hourly"
								{if $report_preferences.interval eq "hourly"}selected{/if}>{tr}Hourly{/tr}</option>
						<option value="daily"
								{if $report_preferences.interval eq "daily" or !isset($report_preferences.interval)}selected{/if}>{tr}Daily{/tr}</option>
						<option value="weekly"
								{if $report_preferences.interval eq "weekly"}selected{/if}>{tr}Weekly{/tr}</option>
						<option value="monthly"
								{if $report_preferences.interval eq "monthly"}selected{/if}>{tr}Monthly{/tr}</option>
					</select>
				</div>
			</div>
			<div class="form-group row">
				<label class="col-sm-3" for="view">{tr}Report length{/tr}</label>
				<div class="col-sm-9">
					<input type="radio" name="view" value="short"{if $report_preferences.view eq "short"} checked="checked"{/if}>
					{tr}Short report{/tr}
					<br>
					<input type="radio" name="view" value="detailed"{if $report_preferences.view eq "detailed" OR $report_preferences eq false} checked="checked"{/if}>
					{tr}Detailed report{/tr}
					<br>
				</div>
			</div>
			<div class="form-group row">
				<label class="col-sm-3" for="type">{tr}Report format{/tr}</label>
				<div class="col-sm-9">
					<input type="radio" name="type" value="html"{if $report_preferences.type eq "html" OR $report_preferences eq false} checked="checked"{/if}>
					{tr}HTML{/tr}
					<br>
					<input type="radio" name="type" value="plain"{if $report_preferences.type eq "plain"} checked="checked"{/if}>
					{tr}Plain text{/tr}
					<br>
				</div>
			</div>
			<div class="form-group row">
				<label class="col-sm-3" for="always_email">{tr}Send report even if no activity{/tr}</label>
				<div class="col-sm-9">
                    <div class="form-check">
    					<input type="checkbox" class="form-check-input" name="always_email" value="1"{if $report_preferences.always_email eq 1 OR $report_preferences eq false} checked="checked"{/if}>
	    			</div>
                </div>
			</div>
			<div class="form-group text-center">
				<input type="submit" name="submit" class="btn btn-primary" title="{tr}Apply Changes{/tr}" value="{tr}Apply{/tr}">
			</div>
		</form>
	{/tab}
{/if}

{tab name="{tr}My watches{/tr}"}
	<h2>{tr}My watches{/tr}</h2>
{remarksbox type="tip" title="{tr}Tip{/tr}"}{tr}Use "watches" to monitor wiki pages or other objects.{/tr} {tr}Watch new items by clicking the {icon name='watch'} button on specific pages.{/tr}{/remarksbox}

{if $add_options|@count > 0}
	<h3>{tr}Add Watch{/tr}</h3>
	<form action="tiki-user_watches.php" method="post">
		{ticket}
		<div class="form-group row">
			<label class="col-sm-3 col-form-label" for="type_selector">{tr}Event{/tr}</label>

			<div class="col-sm-9">
				<select name="event" id="type_selector" class="form-control">
					<option>{tr}Select event type{/tr}</option>
					{foreach key=event item=type from=$add_options}
						<option value="{$event|escape}">{$type.label|escape}</option>
					{/foreach}
				</select>
			</div>
		</div>
		{if $prefs.feature_categories eq 'y'}
			<div class="form-group row" id="categ_list">
				<label class="col-sm-3 col-form-label" for="langwatch_categ">{tr}Category{/tr}</label>

				<div class="col-sm-9">
					<select class="categwatch-select form-control" name="categwatch" id="langwatch_categ">
						{foreach item=c from=$categories}
							<option value="{$c.categId|escape}">{$c.name|escape}</option>
						{/foreach}
					</select>
				</div>
			</div>
		{/if}
		{if $prefs.feature_multilingual eq 'y'}
			<div class="form-group row" id="lang_list">
				<label class="col-sm-3 col-form-label">{tr}Language{/tr}</label>

				<div class="col-sm-9">
					<select name="langwatch" class="form-control">
						{foreach item=l from=$languages}
							<option value="{$l.value|escape}">{$l.name|escape}</option>
						{/foreach}
					</select>
				</div>
			</div>
		{/if}
		<div class="form-group text-center">
			<input type="submit" class="btn btn-primary" name="add" value="{tr}Add{/tr}">
		</div>
	</form>
	{jq}
		$('#type_selector').change( function() {
		var type = $(this).val();

		$('#lang_list').hide();
		$('#categ_list').hide();

		if( type == 'wiki_page_in_lang_created' ) {
		$('#lang_list').show();
		}

		if( type == 'category_changed_in_lang' ) {
		$('#lang_list').show();
		$('#categ_list').show();
		}
		} ).trigger('change');
	{/jq}
{/if}
	<h3>{tr}Watches{/tr}</h3>
	<form class="mb-4" action="tiki-user_watches.php" method="post" id='formi'>
		<div class="form-group row">
			<label class="col-sm-3 col-form-label" for="event">{tr}Show{/tr}</label>

			<div class="col-sm-9">
				<select class="form-control" name="event"
						onchange="javascript:document.getElementById('formi').submit();">
					<option value=""{if $smarty.request.event eq ''} selected="selected"{/if}>{tr}All watched events{/tr}</option>
					{foreach from=$events key=name item=description}
						<option value="{$name|escape}"{if $name eq $smarty.request.event} selected="selected"{/if}>
							{if $name eq 'blog_post'}
								{tr}A user submits a blog post{/tr}
							{elseif $name eq 'forum_post_thread'}
								{tr}A user posts a forum thread{/tr}
							{elseif $name eq 'forum_post_topic'}
								{tr}A user posts a forum topic{/tr}
							{elseif $name eq 'wiki_page_changed'}
								{if $prefs.wiki_watch_comments eq 'y'}
									{tr}A user edited or commented on a wiki page{/tr}
								{else}
									{tr}A user edited a wiki page{/tr}
								{/if}
							{else}
								{$description}
							{/if}
						</option>
					{/foreach}
				</select>
			</div>
		</div>
	</form>
	<form action="tiki-user_watches.php" method="post">
		{ticket}
		<div class="table-responsive">
			<table class="table table-striped table-hover">
				<tr>
					{if $watches}
						<th id="checkbox">
							{select_all checkbox_names='checked[]'}
						</th>
					{/if}
					<th>{tr}Event{/tr}</th>
					<th>{tr}Object{/tr}</th>
				</tr>

				{foreach item=w from=$watches}
					<tr>
						{if $watches}
							<td class="checkbox-cell">
								<div class="form-check">
									<input type="checkbox" name="checked[]" value="{$w.watchId}">
								</div>
							</td>
						{/if}
						<td class="text">
							{if $w.event eq 'blog_post'}
								{tr}A user submits a blog post{/tr}
							{elseif $w.event eq 'forum_post_thread'}
								{tr}A user posts a forum thread{/tr}
							{elseif $w.event eq 'forum_post_topic'}
								{tr}A user posts a forum topic{/tr}
							{elseif $w.event eq 'wiki_page_changed'}
								{if $prefs.wiki_watch_comments eq 'y'}
									{tr}A user edited or commented on a wiki page{/tr}
								{else}
									{tr}A user edited a wiki page{/tr}
								{/if}
							{elseif isset($w.label)}
								{$w.label}
							{/if}
							({$w.event})
						</td>
						<td class="text"><a class="link" href="{$w.url}">{tr}{$w.type}:{/tr} {$w.title|escape}</a></td>
					</tr>
					{foreachelse}
					{norecords _colspan=2}
				{/foreach}
			</table>
		</div>
		{if $watches}
			<div class="form-group text-center">
				{tr}Perform action with checked:{/tr}
				<input type="submit" class="btn btn-danger btn-sm" name="delete" value="{tr}Delete{/tr}">
			</div>
		{/if}
	</form>
{/tab}

{tab name="{tr}Notification Preferences{/tr}"}
	<h2>{tr}Notification Preferences{/tr}</h2>
{remarksbox type="tip" title="{tr}Tip{/tr}"}{tr}Use this form to control notifications about objects you are watching.{/tr}{/remarksbox}
	<form action="tiki-user_notifications.php" method="post">
		{ticket}
		<input type="hidden" name="notification_preferences" value="true">

		<h4>{tr}Send notification when I am the editor{/tr}</h4>
		{if $prefs.feature_wiki eq 'y'}
			<div class="form-group row">
				<label class="col-sm-3" for="user_wiki_watch_editor">{tr}Wiki{/tr}</label>
				<div class="col-sm-9">
                    <div class="form-check">
    					<input class="form-check-input" type="checkbox" name="user_wiki_watch_editor" value="true" {if $user_wiki_watch_editor eq 'y'}checked{/if}>
				    </div>
                </div>
			</div>
		{/if}
		{if $prefs.feature_articles eq 'y'}
			<div class="form-group row">
				<label class="col-sm-3" for="user_article_watch_editor">{tr}Article{/tr}</label>
				<div class="col-sm-9">
                  <div class="form-check">
                      <input class="form-check-input" type="checkbox" name="user_article_watch_editor" value="true" {if $user_article_watch_editor eq 'y'}checked{/if}>
                  </div>
                </div>
			</div>
		{/if}
		{if $prefs.feature_blogs eq 'y'}
			<div class="form-group row">
				<label class="col-sm-3" for="user_blog_watch_editor">{tr}Blog{/tr}</label>
				<div class="col-sm-9">
                    <div class="form-check">
    					<input class="form-check-input" type="checkbox" name="user_blog_watch_editor" value="true" {if $user_blog_watch_editor eq 'y'}checked{/if}>
				    </div>
                </div>
			</div>
		{/if}
		{if $prefs.feature_trackers eq 'y'}
			<div class="form-group row">
				<label class="col-sm-3" for="user_tracker_watch_editor">{tr}Tracker{/tr}</label>
				<div class="col-sm-9">
                    <div class="form-check">
					    <input class="form-check-input" type="checkbox" name="user_tracker_watch_editor" value="true" {if $user_tracker_watch_editor eq 'y'}checked{/if}>
				    </div>
                </div>
			</div>
		{/if}
		{if $prefs.feature_calendar eq 'y'}
			<div class="form-group row">
				<label class="col-sm-3" for="user_calendar_watch_editor">{tr}Calendar{/tr}</label>
				<div class="col-sm-9">
                    <div class="form-check">
					    <input class="form-check-input" type="checkbox" name="user_calendar_watch_editor" value="true" {if $user_calendar_watch_editor eq 'y'}checked{/if}>
				    </div>
                </div>
			</div>
		{/if}
		<div class="form-group row">
			<label class="col-sm-3" for="user_comment_watch_editor">{tr}Comment{/tr}</label>
			<div class="col-sm-9">
                <div class="form-check">
				    <input class="form-check-input" type="checkbox" name="user_comment_watch_editor" value="true" {if $user_comment_watch_editor eq 'y'}checked{/if}>
			    </div>
            </div>
		</div>
		{if $prefs.feature_categories eq 'y'}
			<div class="form-group row">
				<label class="col-sm-3" for="user_category_watch_editor">{tr}Category{/tr}</label>
				<div class="col-sm-9">
                    <div class="form-check">
    					<input class="form-check-input" type="checkbox" name="user_category_watch_editor" value="true" {if $user_category_watch_editor eq 'y'}checked{/if}>
	    			</div>
                </div>
			</div>
		{/if}
		<div class="form-group row">
			<label class="col-sm-3" for="user_plugin_approval_watch_editor">{tr}Plugin approval{/tr}</label>
			<div class="col-sm-9">
                <div class="form-check">
    				<input class="form-check-input" type="checkbox" name="user_plugin_approval_watch_editor" value="true" {if $user_plugin_approval_watch_editor eq 'y'}checked{/if}>
	    		</div>
            </div>
		</div>
		<div class="form-group text-center">
			<input type="submit" class="btn btn-primary" name="submit" title="{tr}Apply Changes{/tr}" value="{tr}Apply{/tr}">
		</div>
	</form>
{/tab}

{/tabset}
