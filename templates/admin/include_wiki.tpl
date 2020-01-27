{* $Id$ *}

{remarksbox type="tip" title="{tr}Tip{/tr}"}
	{tr}Use the 'Quick Edit' module to easily create or edit wiki pages.{/tr} <a class="btn btn-link alert-link" href="tiki-admin_modules.php">{icon name="module"} {tr}Modules{/tr}</a>
{/remarksbox}
<form action="tiki-admin.php?page=wiki" method="post" class="admin">
	{ticket}
	<div class="heading input_submit_container text-right">
	</div>
	<div class="t_navbar mb-4 clearfix">
		{button _icon_name='admin_wiki' _text="{tr}Pages{/tr}" _type="link" _class='btn btn-link' _script='tiki-listpages.php' _title="{tr}List wiki pages{/tr}"}
		{if $prefs.feature_wiki_structure eq "y" and $tiki_p_view eq "y"}
			{button _icon_name='structure' _text="{tr}Structures{/tr}" _type="link" _class='btn btn-link' _script='tiki-admin_structures.php' _title="{tr}List structures{/tr}"}
		{/if}
		{include file='admin/include_apply_top.tpl'}
	</div>
	{tabset name="admin_wiki"}
		{tab name="{tr}General Preferences{/tr}"}
			<br>
			<div class="adminoptionbox clearfix">
				<fieldset class="mb-3 w-100">
					<legend>{tr}Activate the feature{/tr}</legend>
					{preference name=feature_wiki visible="always"}
					{preference name=wikiHomePage}
					{preference name=wiki_url_scheme}
				</fieldset>
			</div>
			<div class="adminoptionbox clearfix">
			<fieldset class="mb-3 w-100">
				<legend>{tr}Page name{/tr}</legend>
				{preference name=wiki_badchar_prevent}
				{preference name=wiki_page_regex}
				{preference name=wiki_pagename_strip}
			</fieldset>
			</div>
			<div class="adminoptionbox clearfix">
				<fieldset class="mb-3 w-100">
					<legend>{tr}Page display{/tr}</legend>
					{preference name=feature_page_title}
					{preference name=wiki_page_name_above}
					{preference name=wiki_page_name_inside}
					{preference name=wiki_page_hide_title}
					{preference name=wiki_heading_links}
					{preference name=feature_wiki_description}
					{preference name=feature_wiki_pageid}
					{preference name=wiki_show_version}
					{preference name=wiki_authors_style}
					{preference name=wiki_authors_style_by_page}
					{preference name=wiki_encourage_contribution}
					{preference name=feature_wiki_show_hide_before}
					{preference name=wiki_actions_bar}
					{preference name=wiki_page_navigation_bar}
					{preference name=wiki_topline_position}
					{preference name=page_bar_position}
				</fieldset>
			</div>
			<div class="adminoptionbox clearfix">
				<fieldset class="mb-3 w-100">
					<legend>{tr}Editing{/tr}</legend>
					{preference name=feature_wiki_undo}
					{preference name=wiki_edit_minor}
					{preference name=wiki_edit_plugin}
					{preference name=wiki_edit_section}
					<div class="adminoptionboxchild" id="wiki_edit_section_childcontainer">
						{preference name=wiki_edit_icons_toggle}
						{preference name=wiki_edit_section_level}
					</div>
					{preference name=feature_wiki_allowhtml}
					{preference name=wiki_mandatory_edit_summary}
					{preference name=feature_wiki_footnotes}
					{preference name=wiki_freetags_edit_position}
					{preference name=wiki_timeout_warning}
					{preference name=feature_warn_on_edit}
					{preference name=warn_on_edit_time}
					<div class="adminoptionboxchild" id="feature_wiki_allowhtml_childcontainer">
						{preference name=wysiwyg_wiki_parsed}
					</div>
					{preference name=feature_wysiwyg}
					{preference name=feature_wiki_mandatory_category}
					{preference name=feature_actionlog_bytes}
					{preference name=feature_wiki_templates}
					<div class="adminoptionboxchild" id="feature_wiki_templates_childcontainer">
						{preference name=lock_content_templates}
					</div>
				</fieldset>
			</div>
			<div class="adminoptionbox clearfix">
				<fieldset class="mb-3 w-100">
					<legend>{tr}Automatic table of contents{/tr}</legend>
					{preference name=wiki_auto_toc}
					<div class="adminoptionbox clearfix" id="wiki_auto_toc_childcontainer">
						{preference name=wiki_toc_default}
						{preference name=wiki_inline_auto_toc}
						{preference name=wiki_toc_pos}
						{preference name=wiki_toc_offset}
					</div>
				</fieldset>
			</div>
			<div class="adminoptionbox clearfix">
				<fieldset class="mb-3 w-100">
					<legend>{tr}Plugins{/tr}</legend>
					{preference name=wikiplugin_attach}
					{preference name=wikiplugin_author}
					{preference name=wikiplugin_backlinks}
					{preference name=wikiplugin_include}
					<div class="adminoptionboxchild" id="wikiplugin_include_childcontainer">
					  {preference name=wiki_plugin_include_link_original}
					</div>
					{preference name=wikiplugin_listpages}
					{preference name=wikiplugin_randominclude}
					{preference name=wikiplugin_showpages}
					{preference name=wikiplugin_slideshow}
					{preference name=wikiplugin_titlesearch}
					{preference name=wikiplugin_transclude}
					{preference name=wikiplugin_wantedpages}
					{preference name=wikiplugin_footnote}
					<div class="adminoptionboxchild" id="wikiplugin_footnote_childcontainer">
						{preference name=wikiplugin_footnotearea}
						{preference name=footnote_popovers}
					</div>
				</fieldset>
			</div>
		{/tab}
		{tab name="{tr}Features{/tr}"}
			<br>
			<div class="adminoptionbox clearfix">
				<fieldset class="mb-3 w-100">
					<legend>{tr}General features{/tr}</legend>
					{preference name=wiki_uses_slides}
					{preference name=feature_backlinks}
							<div class="adminoptionboxchild" id="feature_backlinks_childcontainer">
								{preference name=wiki_backlinks_name_len}
								{preference name=wiki_backlinks_show_article}
								{preference name=wiki_backlinks_show_post}
								{preference name=wiki_backlinks_show_calendar_event}
								{preference name=wiki_backlinks_show_comment}
								{preference name=wiki_backlinks_show_forum_post}
								{preference name=wiki_backlinks_show_trackeritem}
								{preference name=wiki_backlinks_show_tracker}
								{preference name=wiki_backlinks_show_trackerfield}
							</div>
						{preference name=feature_wiki_discuss}
							<div class="adminoptionboxchild" id="feature_wiki_discuss_childcontainer">
								{preference name=wiki_forum_id}
								{preference name=wiki_discuss_visibility}
							</div>
						{preference name=feature_wiki_export}
							<div class="adminoptionboxchild col-md-8 offset-sm-4" id="feature_wiki_export_childcontainer">
								{button href="tiki-export_wiki_pages.php" _text="{tr}Export Wiki Pages{/tr}"}
							</div>
						{preference name=geo_locate_wiki}
						{preference name=feature_history}
							<div class="adminoptionboxchild" id="feature_history_childcontainer">
								{preference name=maxVersions}
								{preference name=keep_versions}
								{preference name=feature_page_contribution}
								{preference name=feature_wiki_history_ip}
								{preference name=default_wiki_diff_style}
								{preference name=feature_wiki_history_full}
							</div>
						{preference name=feature_wiki_import_html}
						{preference name=feature_wiki_import_page}
						{preference name=wiki_keywords}
						{preference name=wiki_creator_admin}
						{preference name=feature_wiki_mindmap}
						{preference name=feature_wiki_rankings}
						{preference name=feature_wiki_ratings}
						{preference name=feature_sandbox}
						{preference name=wiki_date_field}
						{preference name=feature_wiki_use_date}
						<div class="adminoptionboxchild" id="feature_wiki_use_date_childcontainer">
							{preference name=feature_wiki_use_date_links}
						</div>
						{preference name=feature_wiki_userpage}
						<div class="adminoptionboxchild" id="feature_wiki_userpage_childcontainer">
							{preference name=feature_wiki_userpage_prefix}
						</div>
						{preference name=feature_wiki_usrlock}
						{preference name=feature_source}
						{preference name=wiki_feature_copyrights}
						{preference name=wiki_pagination}
							<div class="adminoptionboxchild" id="wiki_pagination_childcontainer">
								{preference name=wiki_page_separator}
							</div>
						{preference name=feature_references}
							<div class="adminoptionboxchild" id="feature_references_childcontainer">
								{preference name=feature_library_references}
								{preference name=feature_references_popover}
								{preference name=feature_references_style}
							</div>
						{preference name=wiki_simple_ratings}
						<div class="adminoptionboxchild" id="wiki_simple_ratings_childcontainer">
							{preference name=wiki_simple_ratings_options}
						</div>
				</fieldset>
			</div>
			<div class="adminoptionbox clearfix">
				<fieldset class="mb-3 w-100">
					<legend>{tr}Redirect and Similar{/tr}</legend>
						{preference name=feature_wiki_1like_redirection}
						{preference name=wiki_prefixalias_tokens}
						{preference name=feature_semantic}
						{preference name=feature_likePages}
						<div class="adminoptionboxchild" id="wiki_likepages_samelang_only">
							{preference name=wiki_likepages_samelang_only}
						</div>
						{preference name=feature_wiki_pagealias}
						{preference name=wiki_pagealias_tokens}
				</fieldset>
			</div>
			<div class="adminoptionbox clearfix">
				<fieldset class="mb-3 w-100">
					<legend>{tr}Wikiwords Features{/tr}</legend>
					{preference name=feature_wikiwords}
					{preference name=feature_wikiwords_usedash}
					{preference name=feature_wiki_plurals}
				</fieldset>
			</div>
			<div class="adminoptionbox clearfix">
				<fieldset class="mb-3 w-100">
					<legend>{tr}Attachments{/tr}</legend>
					{preference name=feature_wiki_attachments}
					<div class="adminoptionboxchild" id="feature_wiki_attachments_childcontainer">
						{preference name=w_displayed_default}
						{preference name=w_use_db}
						<div class="adminoptionboxchild w_use_db_childcontainer n">
							{preference name=w_use_dir}
						</div>
						{if !empty($prefs.w_use_dir)}
							{tr}If you change storage, it is better to move all the files for easy backup...{/tr}
							{button href="tiki-admin.php?page=wikiatt&all2db=1" _text="{tr}Change all to db{/tr}" _onclick="confirmSimple(event, '{tr}Move all attachments to database?{/tr}', '{ticket mode=get}')"}
							{button href="tiki-admin.php?page=wikiatt&all2file=1" _text="{tr}Change all to file{/tr}" _onclick="confirmSimple(event, '{tr}Move all attachments to file system?{/tr}', '{ticket mode=get}')"}
						{/if}
					</div>
					{preference name=feature_wiki_pictures}
					<div class="adminoptionboxchild" id="feature_wiki_pictures_childcontainer">
						{preference name=feature_filegals_manager}
						<div class="offset-sm-4 col-sm-8">
							{button href="tiki-admin.php?page=wiki&amp;rmvunusedpic=1" _text="{tr}Remove unused pictures{/tr}" _onclick="confirmSimple(event, '{tr}Remove unused pictures?{/tr}', '{ticket mode=get}')"}
							{button href="tiki-admin.php?page=wiki&amp;moveWikiUp=1" _text="{tr}Move images from wiki_up to the home file gallery{/tr}" _onclick="confirmSimple(event, '{tr}Move images to home gallery?{/tr}', '{ticket mode=get}')"}
							<span class="form-text">
								{tr}If you use these buttons please make sure to have a backup of the database and the directory wiki_up{/tr}
							</span>
						</div>
					</div>
				</fieldset>
			</div>
			<div class="adminoptionbox clearfix">
				<fieldset class="mb-3 w-100">
					<legend>{tr}Comments{/tr}</legend>
					{preference name=feature_wiki_comments}
					<div class="adminoptionboxchild" id="feature_wiki_comments_childcontainer">
						{preference name=wiki_comments_allow_per_page}
						{preference name=wiki_comments_displayed_default}
						{preference name=wiki_comments_default_ordering}
						{preference name=wiki_comments_form_displayed_default}
						{preference name=wiki_watch_comments}
						{preference name=wiki_comments_per_page}
					</div>
				</fieldset>
			</div>
			<div class="adminoptionbox clearfix">
				<fieldset class="mb-3 w-100">
					<legend>{tr}Structures{/tr}{help url="Structures"}</legend>
					{preference name=feature_wiki_structure}
					<div class="adminoptionboxchild" id="feature_wiki_structure_childcontainer">
						{preference name=wiki_structure_bar_position}
						{preference name=feature_wiki_open_as_structure}
						{preference name=feature_wiki_make_structure}
						{preference name=feature_wiki_categorize_structure}
						{preference name=feature_wiki_structure_drilldownmenu}
						{preference name=lock_wiki_structures}
						{preference name=feature_create_webhelp}
						{preference name=page_n_times_in_a_structure}
						{preference name=feature_listorphanStructure}
						{preference name=feature_wiki_no_inherit_perms_structure}
						{preference name=wikiplugin_toc}
					</div>
				</fieldset>
			</div>
			<div class="adminoptionbox clearfix">
				<fieldset class="mb-3 w-100">
					<legend>{tr}Wiki watch{/tr}{help url="Watch"}</legend>
					{preference name=wiki_watch_author}
					{preference name=wiki_watch_editor}
					{preference name=wiki_watch_comments}
					{preference name=wiki_watch_minor}
				</fieldset>
			</div>
			<div class="adminoptionbox clearfix">
				<fieldset class="mb-3 w-100">
					<legend>{tr}Namespaces{/tr}{help url="Namespaces"}</legend>
					{preference name=namespace_enabled}
					<div class="adminoptionboxchild" id="namespace_enabled_childcontainer">
						<div class="offset-sm-4 colsm-8">
							{tr}The namespace separator should not{/tr}
							<ul>
								<li>{tr}contain any of the characters not allowed in wiki page names, typically{/tr} /?#[]@$&amp;+;=&lt;&gt;</li>
								<li>{tr}conflict with wiki syntax tagging{/tr}</li>
							</ul>
						</div>
						{preference name=namespace_separator}
						{preference name=namespace_indicator_in_structure}
						{preference name=namespace_indicator_in_page_title}
						<div class="offset-sm-4 colsm-8">
							<p><strong>{tr}Settings that may be affected by the namespace separator{/tr}</strong></p>
							{tr}To use :: as a separator, you should also use ::: as the wiki center tag syntax{/tr}.<br/>
							{tr}Note: a conversion of :: to ::: for existing pages must be done manually.{/tr}<br/>
						</div>
						{preference name=feature_use_three_colon_centertag}
						<div class="offset-sm-4 colsm-8">
							{tr}If the page name display stripper conflicts with the namespace separator, the namespace is used and the page name display is not stripped.{/tr}
						</div>
						{preference name=wiki_pagename_strip}
						{preference name=namespace_force_links}
					</div>
				</fieldset>
			</div>
		{/tab}
		{tab name="{tr}Flagged Revision{/tr}"}
			<br>
			<div class="adminoptionbox clearfix">
				<fieldset class="mb-3 w-100">
					{preference name=flaggedrev_approval}
					<div id="flaggedrev_approval_childcontainer">
						{preference name=flaggedrev_approval_categories}
					</div>
				</fieldset>
			</div>
		{/tab}
		{tab name="{tr}Page Listings{/tr}"}
			<br>
			<div class="adminoptionbox clearfix">
				<fieldset class="mb-3 w-100">
				<legend>{tr}Options{/tr}</legend>
					{preference name=feature_lastChanges}
					{preference name=feature_listPages}
					{preference name=feature_listorphanPages}
					{preference name=feature_listorphanStructure}
					{preference name=gmap_page_list}
				</fieldset>
			</div>
			<div class="adminoptionbox clearfix">
				<fieldset class="mb-3 w-100">
				<legend>{tr}Configuration{/tr}</legend>
					{preference name=wiki_list_backlinks}
					{preference name=wiki_list_categories}
					{preference name=wiki_list_categories_path}
					{preference name=wiki_list_comment}
					{preference name=wiki_list_creator}
					{preference name=wiki_list_description}
					<div class="adminoptionboxchild" id="wiki_list_description_childcontainer">
						{preference name=wiki_list_description_len}
					</div>
					{preference name=wiki_list_hits}
					{preference name=wiki_list_language}
					{preference name=wiki_list_lastmodif}
					{preference name=wiki_list_user}
					{preference name=wiki_list_links}
					{preference name=wiki_list_name}
					{preference name=wiki_list_id}
					<div class="adminoptionbox clearfix">
						{tr}Select which items to display when listing pages:{/tr}
					</div>
					{preference name=wiki_list_sortorder}
					<div class="adminoptionboxchild">
						{preference name=wiki_list_sortdirection}
					</div>
					<div class="adminoptionboxchild" id="wiki_list_name_childcontainer">
						{preference name=wiki_list_name_len}
					</div>
					<div class="adminoptionboxchild" id="wiki_list_comment_childcontainer">
						{preference name=wiki_list_comment_len}
					</div>
					{preference name=wiki_list_size}
					{preference name=wiki_list_rating}
					{preference name=wiki_list_status}
					{preference name=wiki_list_lastver}
					{preference name=wiki_list_versions}
				</fieldset>
			</div>
		{/tab}
		{tab name="{tr}Tools{/tr}"}
			<br>
			<div class="adminoptionbox clearfix">
				<fieldset class="mb-3 w-100">
					<legend>{tr}Database dumps and restores{/tr}</legend>
					Create database archives of wiki pages for restoring at a later date.
					<div class="form-group row">
						<label for="tagname" class="col-form-label col-sm-4">{tr}Name for dump{/tr}</label>
						<div class="col-sm-4">
							<input maxlength="20" size="20" type="text" name="newtagname" id="newtagname" class="form-control">
						</div>
						<div class="col-sm-4">
							<input type="submit" class="btn btn-primary" name="createtag" value="{tr}Create Database Dump{/tr}">
						</div>
					</div>
					<div class="form-group row">
						<label for="databasetag" class="col-form-label col-sm-4">{tr}Wiki database{/tr}</label>
						<div class="col-sm-4">
							<select name="tagname" class="form-control" {if $tags|@count eq '0'} disabled="disabled"{/if}>
								{section name=sel loop=$tags}
									<option value="{$tags[sel]|escape}">{$tags[sel]}</option>
									{sectionelse}
									<option value=''>{tr}None{/tr}</option>
								{/section}
							</select>
						</div>
						<div class="col-sm-4">
							<input type="submit" class="btn btn-primary" name="restoretag" value="{tr}Restore{/tr}"{if $tags|@count eq '0'} disabled="disabled"{/if}>
							<input type="submit" class="btn btn-danger" name="removetag" value="{tr}Remove{/tr}"{if $tags|@count eq '0'} disabled="disabled"{/if}>
						</div>
					</div>
				</fieldset>
			</div>
			<div class="adminoptionbox clearfix">
				<fieldset class="mb-3 w-100">
					<legend>{tr}Dump files{/tr}</legend>
					{tr}Dump files archive wiki pages for various usages such as off-line browsing or distribution on optical disks.{/tr}
					{remarksbox type="warning" title="{tr}Warning{/tr}"}
					{tr}The HTML files generated may refer to files not included in the dump.{/tr} {tr}Dumps do not include files attached to wiki pages.{/tr}
					{if $isDump}<li>{tr}Dumping will overwrite the preexisting dump.{/tr}</li>{/if}
					{/remarksbox}
					<input type="submit" class="btn btn-primary btn-sm" name="createdump" value="{tr}Create Dump File{/tr}">
					<input type="submit" class="btn btn-primary btn-sm" name="downloaddump" value="{tr}Download Dump File{/tr}" {if !$isDump} disabled="disabled"{/if}>
					<input type="submit" class="btn btn-primary btn-sm" name="removedump" data-target="_blank" value="{tr}Remove Dump File{/tr}" {if !$isDump} disabled="disabled"{/if}>
			</fieldset>
			</div>
			<div class="adminoptionbox clearfix">
				<fieldset class="mb-3 w-100">
					<legend>{tr}Mass replace and page permissions report{/tr}</legend>
					<div class="adminoptionbox clearfix">
						{button href="tiki-search_replace.php" _text="{tr}Mass search and replace{/tr}" _icon_name="exchange-alt" _type="primary"}
						{button href="tiki-report_direct_object_perms.php" _text="{tr}Report wiki pages with direct object permissions{/tr}" _icon_name="paperclip" _type="primary"}
					</div>
				</fieldset>
			</div>
			<div class="adminoptionbox clearfix">
				<fieldset class="mb-3 w-100">
					<legend>{tr}Redirect and Similar{/tr}</legend>
					{preference name=feature_wiki_1like_redirection}
					{preference name=wiki_prefixalias_tokens}
					{preference name=feature_semantic}
					{preference name=feature_likePages}
					<div class="adminoptionboxchild" id="wiki_likepages_samelang_only">
						{preference name=wiki_likepages_samelang_only}
					</div>
					{preference name=feature_wiki_pagealias}
					{preference name=wiki_pagealias_tokens}
				</fieldset>
			</div>
		{/tab}
	{/tabset}
	{include file='admin/include_apply_bottom.tpl'}
</form>
