{* $Id$ *}
{if !$viewItemPretty.override}
	{title help="trackers"}{$tracker_item_main_value}{/title}
{/if}


{if ! isset($print_page) || $print_page ne 'y'}

	{* --------- navigation ------ *}
	<div class="t_navbar mb-4">
		<div class="float-sm-right btn-group">
			{if ! $js}<ul class="cssmenu_horiz"><li>{/if}
			<a class="btn btn-link" data-toggle="dropdown" data-hover="dropdown" href="#">
				{icon name='menu-extra'}
			</a>
			<ul class="dropdown-menu dropdown-menu-right">
				<li class="dropdown-title">
					{tr}Tracker item actions{/tr}
				</li>
				<li class="dropdown-divider"></li>
				<li class="dropdown-item">
					{if $viewItemPretty.override}
						{self_link print='y' vi_tpl={$viewItemPretty.value}}
							{icon name="print"} {tr}Print{/tr}
						{/self_link}
					{else}
						{self_link print='y'}
							{icon name="print"} {tr}Print{/tr}
						{/self_link}
					{/if}
				</li>

                {if $pdf_export eq 'y'}
					<li class="dropdown-item">
						<a href="{$smarty.server.SCRIPT_NAME}?{query pdf='y'}">
							{icon name="pdf"} {tr}PDF{/tr}
						</a>
					</li>
                {/if}

				{if $item_info.logs.cant|default:null}
					<li class="dropdown-item">
						<a href="tiki-tracker_view_history.php?itemId={$itemId}">
							{icon name="history"} {tr}History{/tr}
						</a>
					</li>
				{/if}
				{if $canRemove}
					<li class="dropdown-item">
						{self_link remove=$itemId}
							{icon name="delete"} {tr}Delete{/tr}
						{/self_link}
					</li>
				{/if}
				{if $prefs.monitor_enabled eq 'y'}
					<li class="dropdown-item">
						{monitor_link type=trackeritem object=$itemId linktext="{tr}Notification{/tr}" class="link" title=""}
					</li>
				{/if}
				{if $prefs.feature_user_watches eq 'y' and $tiki_p_watch_trackers eq 'y'}
					<li class="dropdown-item">
						{if $user_watching_tracker ne 'y'}
							<a href="tiki-view_tracker_item.php?trackerId={$trackerId}&amp;itemId={$itemId}&amp;watch=add">
								{icon name="watch"} {tr}Monitor{/tr}
							</a>
						{else}
							<a href="tiki-view_tracker_item.php?trackerId={$trackerId}&amp;itemId={$itemId}&amp;watch=stop">
								{icon name="stop-watching"} {tr}Stop monitoring{/tr}
							</a>
						{/if}
					</li>
					{if $prefs.feature_group_watches eq 'y' and ( $tiki_p_admin_users eq 'y' or $tiki_p_admin eq 'y' )}
						<li class="dropdown-item">
							<a href="tiki-object_watches.php?objectId={$itemId|escape:"url"}&amp;watch_event=tracker_item_modified&amp;objectType=tracker+{$trackerId}&amp;objectName={$tracker_info.name|escape:"url"}&amp;objectHref={'tiki-view_tracker_item.php?trackerId='|cat:$trackerId|cat:'&itemId='|cat:$itemId|escape:"url"}">
								{icon name="watch-group"} {tr}Group monitor{/tr}
							</a>
						</li>
					{/if}
				{/if}
				{if $prefs.sefurl_short_url eq 'y'}
					<li class="dropdown-item">
						<a id="short_url_link" href="#" onclick="(function() { $(document.activeElement).attr('href', 'tiki-short_url.php?url=' + encodeURIComponent(window.location.href) + '&title=' + encodeURIComponent(document.title)); })();">
							{icon name="link"} {tr}Get a short URL{/tr}
							{assign var="hasPageAction" value="1"}
						</a>
					</li>
				{/if}
				{if $tiki_p_admin_trackers eq "y"}
					<li class="dropdown-item">
						{permission_link mode=text type=trackeritem id=$itemId permType=trackers parentId=$trackerId}
					</li>
				{/if}
                {if $prefs.user_favorites eq 'y' and isset($itemId)}
					<li class="dropdown-item">
						{favorite button_classes="favorite-icon" label="{tr}Favorite{/tr}"  type="trackeritem" object=$itemId }
					</li>
                {/if}
			</ul>
			{if ! $js}</li></ul>{/if}
		</div>
		{if $canModify && $prefs.tracker_legacy_insert neq 'y'}
			{if not empty($smarty.request.from) and $prefs.pwa_feature ne 'y'}{$from = $smarty.request.from}{else}{$from=''}{/if}
			<a class="btn btn-primary" href="{bootstrap_modal controller=tracker action=update_item trackerId=$trackerId itemId=$itemId redirect=$from size='modal-lg'}">{icon name="edit"} {tr}Edit{/tr}</a>
		{/if}

		{* only include actions bar if no custom view template is assigned *}
		{if !$viewItemPretty.override}
			{include file="tracker_actions.tpl"}
		{/if}

		{* show button back only if tpl has been set with vi_tpl or ei_tpl *}
		{if $viewItemPretty.override and !empty($referer)}
			<a class="btn btn-primary" href="{$referer}" title="{tr}Back{/tr}">{icon name="arrow-circle-left"} {tr}Back{/tr}</a>
		{/if}
	</div>

	{if $user and $prefs.feature_user_watches eq 'y' and $category_watched eq 'y'}
	<div class="categbar">
		{tr}Watched by categories:{/tr}
		{section name=i loop=$watching_categories}
			<a href="tiki-browse_categories.php?parentId={$watching_categories[i].categId}">{$watching_categories[i].name|escape}</a>&nbsp;
		{/section}
	</div>
	{/if}

	{* ------- return/next/previous tab --- *}
	{if $canView}
		{pagination_links cant=$cant|default:null offset=$offset reloff=$smarty.request.reloff|default:null itemname="{tr}Item{/tr}"}
			{* Do not specify an itemId in URL used for pagination, because it will use the specified itemId instead of moving to another item *}
			{$smarty.server.php_self|default:null}?{query itemId=NULL trackerId=$trackerId}
		{/pagination_links}
	{/if}

	{include file='tracker_error.tpl'}
{else}
	<style>
	.tab-content .tab-pane {
    	display:block;
	}</style>
{/if}
{* print_page *}



{tabset name='tabs_view_tracker_item' skipsingle=1 toggle=n}

	{* when printing, no js is called to select the tab thus no class "active" assigned (would show nothing). print=y sets this class on printing *}
	{tab name="{tr}View{/tr}" print=y}
		{* --- tab with view ------------------------------------------------------------------------- *}
		{* In most cases one will not want this header when viewing an item *}
		{* <h3>{$tracker_info.name|escape}</h3> *}
		{if $tracker_is_multilingual}
			<div class="translations">
				<a href="{service controller=translation action=manage type=trackeritem source=$itemId}">{tr}Translations{/tr}</a>
			</div>
			{jq}
				$('.translations a').click(function () {
					var link = this;
					$(this).serviceDialog({
						title: $(link).text(),
						data: {
							controller: 'translation',
							action: 'manage',
							type: 'trackeritem',
							source: "{{$itemId|escape}}"
						}
					});
					return false;
				});
			{/jq}
		{/if}

		{* show item *}
		{trackerfields mode=view trackerId=$trackerId itemId=$itemId fields=$fields itemId=$itemId viewItemPretty=$viewItemPretty.value}

		{* -------------------------------------------------- section with comments --- *}
		{if $tracker_info.useComments eq 'y' and ($tiki_p_tracker_view_comments ne 'n' or $tiki_p_comment_tracker_items ne 'n' or $canViewCommentsAsItemOwner) and $prefs.tracker_show_comments_below eq 'y'}
			<a id="Comments"></a>
			<div id="comment-container-below" class="well well-sm" data-target="{service controller=comment action=list type=trackeritem objectId=$itemId}"></div>
			{jq}
				var id = '#comment-container-below';
				$(id).comment_load($(id).data('target'));
				$(document).ajaxComplete(function(){
					$(id).tiki_popover();
					$(id).applyColorbox();
				});
			{/jq}

		{/if}

	{/tab}

	{* -------------------------------------------------- tab with comments --- *}
	{if $tracker_info.useComments eq 'y' and ($tiki_p_tracker_view_comments ne 'n' or $tiki_p_comment_tracker_items ne 'n' or $canViewCommentsAsItemOwner) and $prefs.tracker_show_comments_below ne 'y'}

		{tab name="{tr}Comments{/tr} (`$comCount`)" print=n}
			<div id="comment-container" data-target="{service controller=comment action=list type=trackeritem objectId=$itemId}"></div>
			{jq}
				var id = '#comment-container';
				$(id).comment_load($(id).data('target'));
				$(document).ajaxComplete(function(){$(id).tiki_popover();});
			{/jq}

		{/tab}
	{/if}

	{* ---------------------------------------- tab with attachments --- *}
	{if $tracker_info.useAttachments eq 'y' and $tiki_p_tracker_view_attachments eq 'y'}
		{tab name="{tr}Attachments{/tr} (`$attCount`)" print=n}
			{include file='attachments_tracker.tpl'}
		{/tab}
	{/if}

	{* --------------------------------------------------------------- tab with edit --- *}
	{if (! isset($print_page) || $print_page ne 'y') && $canModify && $prefs.tracker_legacy_insert eq 'y'}
		{tab name=$editTitle}
			<h2>{tr}Edit Item{/tr}</h2>

			<div class="nohighlight"><table style="width: 100%;"><tr><td> {* Added this table tag as a nasty kludge because (after Bootstrappification) the page is breaking without it. *}
				{include file="tracker_validator.tpl"}

				{if $tiki_p_admin_trackers eq 'y' and !empty($trackers)}
					<form role="form">
						<input type="hidden" name="itemId" value="{$itemId}">
						<select name="moveto">
							{foreach from=$trackers item=tracker}
								{if $tracker.trackerId ne $trackerId}
									<option value="{$tracker.trackerId}">{$tracker.name|escape}</option>
								{/if}
							{/foreach}
						</select>
						<input type="submit" class="btn btn-primary btn-sm" name="go" value="{tr}Move to another tracker{/tr}">
					</form>
				{/if}

				<form enctype="multipart/form-data" action="{$formAction}" method="post" id="editItemForm">
					{if $special}
						<input type="hidden" name="view" value=" {$special}">
					{else}
						<input type="hidden" name="trackerId" value="{$trackerId|escape}">
						<input type="hidden" name="itemId" value="{$itemId|escape}">
					{/if}
					{if $from}
						<input type="hidden" name="from" value="{$from}">
					{/if}
					{if $cant}
						<input type="hidden" name="cant" value="{$cant}">
					{/if}
					<input type="hidden" name="conflictoverride" value="{$conflictoverride}">

					<div class="previewTrackerItem"></div>

					{remarksbox type="warning" title="{tr}Warning{/tr}"}<em class='mandatory_note'>{tr}Fields marked with an * are mandatory.{/tr}</em>{/remarksbox}

					<div class="form-group mx-0">
								{if count($fields) >= 5}
									<input type="submit" class="btn btn-primary btn-sm" name="save" value="{tr}Save{/tr}" onclick="needToConfirm=false">
									{* --------------------------- to return to tracker list after saving --------- *}
									{if $canView}
										<input type="submit" class="btn btn-primary btn-sm" name="save_return" value="{tr}Save Returning to Item List{/tr}" onclick="needToConfirm=false">

										{if not empty($saveAndComment) and $saveAndComment neq 'n'}
											<input type="submit" class="btn btn-primary btn-sm" name="save_and_comment" value="{tr}Save and Comment{/tr}">
										{/if}

										{if $canRemove}
											<a class="btn btn-danger btn-sm" href="tiki-view_tracker.php?trackerId={$trackerId}&amp;remove={$itemId}" title="{tr}Delete{/tr}">{icon name='delete' alt="{tr}Delete{/tr}"}</a>
										{/if}
									{/if}
								{/if}
					</div>
																	{* ------------------- *}
						{if $tracker_info.showStatus eq 'y' or ($tracker_info.showStatusAdminOnly eq 'y' and $tiki_p_admin_trackers eq 'y')}
							<div class="form-group row">
								<label class="col-form-label col-sm-3">{tr}Status{/tr}</label>
								<div class="col-sm-9">
									{include file='tracker_status_input.tpl' item=$item_info form_status=edstatus}
								</div>
							</div>
						{/if}

						{* no template defined in the tracker *}
						{if empty($editItemPretty.value)}

							{foreach from=$ins_fields key=ix item=cur_field}
								<div class="form-group row">
									<label class="col-sm-3 {if $cur_field.type eq 'h'}h{$cur_field.options_map.level}{else}col-form-label{/if}">
										{$cur_field.name|tra|escape}
										{if $cur_field.isMandatory eq 'y'}
											<strong class='mandatory_star text-danger tips' title=":{tr}This field is mandatory{/tr}">*</strong>
										{/if}
									</label>
									<div class="col-sm-9">
										{trackerinput field=$cur_field item=$item_info inTable=formcolor showDescription=y}
									</div>
								</div>
							{/foreach}

						{else}
						{* we have a preset template: it could be a wiki:myPage or a tpl:MyTpl.tpl *}
						{* Note: tracker plugin usally consumes a pagename or a tpl filename without a prefix of tpl: or wiki: as the tracker definition does *}
							<div class="form-group row">

								{if $editItemPretty.type eq 'wiki'}
									{wikiplugin _name=tracker trackerId=$trackerId itemId=$itemId view=page wiki=$editItemPretty.value formtag='n'}{/wikiplugin}
								{else}
									{wikiplugin _name=tracker trackerId=$trackerId itemId=$itemId view=page tpl=$editItemPretty.value formtag='n'}{/wikiplugin}
								{/if}

							</div>
						{/if}

						{if $groupforalert ne ''}

							<div class="form-group row">
								<div class="col-sm-3">{tr}Choose users to alert{/tr}</div>
								<div class="col-sm-9">
									{section name=idx loop=$listusertoalert}
										{if $showeachuser eq ''}
											<input type="hidden" name="listtoalert[]" value="{$listusertoalert[idx].user}">
										{else}
											<input type="checkbox" name="listtoalert[]" value="{$listusertoalert[idx].user}"> {$listusertoalert[idx].user}
										{/if}
									{/section}
								</div>

							</div>
						{/if}


						{* -------------------- antibot code -------------------- *}
						{if $prefs.feature_antibot eq 'y' && $user eq ''}
							{include file='antibot.tpl'}
						{/if}
						<div class="form-group mx-0">
								<input type="submit" class="btn btn-primary btn-sm" name="save" value="{tr}Save{/tr}" onclick="needToConfirm=false">
								{* --------------------------- to return to tracker list after saving --------- *}
								{if $canView}
									<input type="submit" class="btn btn-primary btn-sm" name="save_return" value="{tr}Save Returning to Item List{/tr}" onclick="needToConfirm=false">
								{/if}
								{if not empty($saveAndComment) and $saveAndComment neq 'n'}
									<input type="submit" class="btn btn-primary btn-sm" name="save_and_comment" value="{tr}Save and Comment{/tr}">
								{/if}

								{if $canRemove}
									<a class="link tips text-danger" href="tiki-view_tracker.php?trackerId={$trackerId}&amp;remove={$itemId}" title=":{tr}Delete{/tr}">
										{icon name='remove'}
									</a>
								{/if}
								{if $item_info.logs.cant}
									<a class="link tips" href="tiki-tracker_view_history.php?itemId={$itemId}" title=":{tr}History{/tr}">
										{icon name='history'}
									</a>
								{/if}
								{if $tiki_p_admin_trackers eq 'y' && empty($trackers)}
									<a class="link tips" href="tiki-view_tracker_item.php?itemId={$itemId}&moveto" title=":{tr}Move to another tracker{/tr}">
										{icon name='next'}
									</a>
								{/if}

						</div>


					{* ------------------- *}
				</form>

				{foreach from=$ins_fields item=cur_field}
					{if $cur_field.type eq 'x'}
						{capture name=trkaction}
							<form action="{$cur_field.options_array[2]}" {if $cur_field.options_array[1] eq 'post'}method="post"{else}method="get"{/if}>
								{section name=tl loop=$cur_field.options_array start=3}
									{assign var=valvar value=$cur_field.options_array[tl]|regex_replace:"/^[^:]*:/":""|escape}
									{if $info.$valvar eq ''}
										{assign var=valvar value=$cur_field.options_array[tl]|regex_replace:"/^[^\=]*\=/":""|escape}
										<input type="hidden" name="{$cur_field.options_array[tl]|regex_replace:"/\=.*$/":""|escape}" value="{$valvar|escape}">
									{else}
										<input type="hidden" name="{$cur_field.options_array[tl]|regex_replace:"/:.*$/":""|escape}" value="{$info.$valvar|escape}">
									{/if}
								{/section}
								<div class="form-group row">
										<div class="col-sm-6">
											{$cur_field.name|tra}
										</div>
										<div class="col-sm-6">
											<input type="submit" class="btn btn-primary btn-sm" name="trck_act" value="{$cur_field.options_array[0]|escape}" >
										</div>
								</div>
							</form>
						{/capture}
						{assign var=trkact value=$trkact|cat:$smarty.capture.trkaction}
					{/if}
				{/foreach}
				{if $trkact}
					<h2>{tr}Special Operations{/tr}</h2>
					{$trkact}
				{/if}
			</td></tr></table></div><!--nohighlight-->{*important comment to delimit the zone not to highlight in a search result*}
		{/tab}
	{/if}

{/tabset}

<br><br>

{if isset($print_page) and $print_page eq 'y' and $prefs.print_original_url_tracker eq 'y'}
	{tr}The original document is available at{/tr} <a href="{$base_url|escape}{$itemId|sefurl:trackeritem}">{$base_url|escape}{$itemId|sefurl:trackeritem}</a>
{/if}
