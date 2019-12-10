{* navbar menu for admin_navbar.tpl *}
<ul class="nav navbar-nav mr-auto">
	<li class="nav-item dropdown  mr-1">
		<a href="#" class="nav-link dropdown-toggle" data-toggle="dropdown">{tr}Access{/tr}</a>
		<ul class="dropdown-menu">
			{if $tiki_p_admin eq "y" and $tiki_p_admin_users eq "y"}
				<a class="dropdown-item" href="tiki-adminusers.php">{tr}Users{/tr}</a>
			{/if}
			{if $tiki_p_admin eq "y"}
				<a class="dropdown-item" href="tiki-admingroups.php">{tr}Groups{/tr}</a>
			{/if}
			{if $tiki_p_admin eq "y"}
				<a class="dropdown-item" href="tiki-objectpermissions.php">{tr}Permissions{/tr}</a>
			{/if}
			{if $prefs.feature_banning eq "y" and $tiki_p_admin_banning eq "y"}
				<div class="dropdown-divider"></div>
				<a class="dropdown-item" href="tiki-admin_banning.php">{tr}Banning{/tr}</a>
			{/if}
		</ul>
	</li>
	<li class="nav-item dropdown mr-1">
		<a href="#" class="nav-link dropdown-toggle" data-toggle="dropdown">{tr}Content{/tr}</a>
		<ul class="dropdown-menu">
			{if $prefs.feature_articles eq "y"}
				<a class="dropdown-item" href="tiki-list_articles.php">{tr}Articles{/tr}</a>
			{/if}
			{if $prefs.feature_banners eq "y" and $tiki_p_admin_banners eq "y"}
				<a class="dropdown-item" href="tiki-list_banners.php">{tr}Banners{/tr}</a>
			{/if}
			{if $prefs.feature_blogs eq "y"}
				<a class="dropdown-item" href="tiki-list_blogs.php">{tr}Blogs{/tr}</a>
			{/if}
			{if $prefs.feature_calendar eq "y"}
				<a class="dropdown-item" href="tiki-admin_calendars.php">{tr}Calendars{/tr}</a>
			{/if}
			{if $prefs.feature_categories eq "y"}
				<a class="dropdown-item" href="tiki-admin_categories.php">{tr}Categories{/tr}</a>
			{/if}
			{if $tiki_p_admin_comments eq "y"}
				<a class="dropdown-item" href="tiki-list_comments.php">{tr}Comments{/tr}</a>
			{/if}
			{if $prefs.feature_directory eq "y" and $tiki_p_admin_directory_cats eq "y"}
				<a class="dropdown-item" href="tiki-directory_admin.php">{tr}Directories{/tr}</a>
			{/if}
			{if $tiki_p_admin_rssmodules eq "y"}
				<a class="dropdown-item" href="tiki-admin_rssmodules.php">{tr}External Feeds{/tr}</a>
			{/if}
			{if $prefs.feature_file_galleries eq "y"}
				<a class="dropdown-item" href="tiki-list_file_gallery.php">{tr}Files{/tr}</a>
			{/if}
			{if $prefs.feature_faqs eq "y" and $tiki_p_view_faqs eq "y"}
				<a class="dropdown-item" href="tiki-list_faqs.php">{tr}FAQs{/tr}</a>
			{/if}
			{if $prefs.feature_forums eq "y"}
				<a class="dropdown-item" href="tiki-admin_forums.php">{tr}Forums{/tr}</a>
			{/if}
			{if $prefs.feature_html_pages eq "y" and $tiki_p_edit_html_pages eq "y"}
				<a class="dropdown-item" href="tiki-admin_html_pages.php">{tr}HTML Pages{/tr}</a>
			{/if}
			{if $prefs.feature_newsletters eq "y" and $tiki_p_admin_newsletters eq "y"}
				<a class="dropdown-item" href="tiki-admin_newsletters.php">{tr}Newsletters{/tr}</a>
			{/if}
			{if $prefs.feature_polls eq "y" and $tiki_p_admin_polls eq "y"}
				<a class="dropdown-item" href="tiki-admin_polls.php">{tr}Polls{/tr}</a>
			{/if}
			{if $prefs.feature_quizzes eq "y" and $tiki_p_admin_quizzes eq "y"}
				<a class="dropdown-item" href="tiki-edit_quiz.php">{tr}Quizzes{/tr}</a>
			{/if}
			{if $prefs.feature_sheet eq "y" and $tiki_p_view_sheet eq "y"}
				<a class="dropdown-item" href="tiki-sheets.php">{tr}Spreadsheets{/tr}</a>
			{/if}
			{if $prefs.feature_surveys eq "y" and $tiki_p_admin_surveys eq "y"}
				<a class="dropdown-item" href="tiki-admin_surveys.php">{tr}Surveys{/tr}</a>
			{/if}
			{if $prefs.feature_freetags eq "y"}
				<a class="dropdown-item" href="tiki-browse_freetags.php">{tr}Tags{/tr}</a>
			{/if}
			{if $prefs.feature_trackers eq "y" and $tiki_p_list_trackers eq "y"}
				<a class="dropdown-item" href="tiki-list_trackers.php">{tr}Trackers{/tr}</a>
			{/if}
			{if $prefs.feature_wiki eq "y"}
				<a class="dropdown-item" href="tiki-listpages.php">{tr}Wiki Pages{/tr}</a>
			{/if}
			{if $prefs.feature_wiki eq "y" and $prefs.feature_wiki_structure eq "y" and $tiki_p_view eq "y"}
				<a class="dropdown-item" href="tiki-admin_structures.php">{tr}Wiki Structures{/tr}</a>
			{/if}
		</ul>
	</li>
	<li class="nav-item dropdown mr-1">
		<a href="#" class="nav-link dropdown-toggle mr-2" data-toggle="dropdown">{tr}System{/tr}</a>
		<ul class="dropdown-menu">
			{if $tiki_p_admin eq "y"}
				<a class="dropdown-item" href="{service controller=managestream action=list}">{tr}Activity Rules{/tr}</a>
			{/if}
			{if ($prefs.feature_wiki_templates eq "y" or $prefs.feature_cms_templates eq "y" or $prefs.feature_file_galleries_templates eq 'y') and $tiki_p_edit_content_templates eq "y"}
				<a class="dropdown-item" href="tiki-admin_content_templates.php ">{tr}Content Templates{/tr}</a>
			{/if}
			{if $prefs.feature_contribution eq "y" and $tiki_p_admin_contribution eq "y"}
				<a class="dropdown-item" href="tiki-admin_contribution.php">{tr}Contributions{/tr}</a>
			{/if}
			{if $prefs.feature_dynamic_content eq "y" and $tiki_p_admin_dynamic eq "y"}
				<a class="dropdown-item" href="tiki-list_contents.php">{tr}Dynamic Content{/tr}</a>
			{/if}
			{if $prefs.feature_hotwords eq "y"}
				<a class="dropdown-item" href="tiki-admin_hotwords.php">{tr}Hotwords{/tr}</a>
			{/if}
			{if $prefs.lang_use_db eq "y" and $tiki_p_edit_languages eq "y"}
				<a class="dropdown-item" href="tiki-edit_languages.php">{tr}Languages{/tr}</a>
			{/if}
			{if $prefs.feature_live_support eq "y" and $tiki_p_live_support_admin eq "y"}
				<a class="dropdown-item" href="tiki-live_support_admin.php">{tr}Live Support{/tr}</a>
			{/if}
			{if $prefs.feature_mailin eq "y" and $tiki_p_admin_mailin eq "y"}
				<a class="dropdown-item" href="tiki-admin_mailin.php">{tr}Mail-in{/tr}</a>
			{/if}
			{if $tiki_p_admin_notifications eq "y"}
				<a class="dropdown-item" href="tiki-admin_notifications.php">{tr}Mail Notifications{/tr}</a>
			{/if}
			{if $tiki_p_edit_menu eq "y"}
				<a class="dropdown-item" href="tiki-admin_menus.php">{tr}Menus{/tr}</a>
			{/if}
			{if $tiki_p_admin_modules eq "y"}
				<a class="dropdown-item" href="tiki-admin_modules.php">{tr}Modules{/tr}</a>
			{/if}
			{if $prefs.feature_perspective eq "y"}
				<a class="dropdown-item" href="tiki-edit_perspective.php">{tr}Perspectives{/tr}</a>
			{/if}
			{if $prefs.feature_shoutbox eq "y" and $tiki_p_admin_shoutbox eq "y"}
				<a class="dropdown-item" href="tiki-shoutbox.php">{tr}Shoutbox{/tr}</a>
			{/if}
			{if $prefs.payment_feature eq "y"}
				<a class="dropdown-item" href="tiki-admin_credits.php">{tr}User Credits{/tr}</a>
			{/if}
			{if $prefs.feature_theme_control eq "y" and $tiki_p_admin eq "y"}
				<a class="dropdown-item" href="tiki-theme_control.php">{tr}Theme Control{/tr}</a>
			{/if}
			{if $tiki_p_admin_toolbars eq "y"}
				<a class="dropdown-item" href="tiki-admin_toolbars.php">{tr}Toolbars{/tr}</a>
			{/if}
			{if $tiki_p_admin eq "y"}
				<a class="dropdown-item" href="tiki-admin_transitions.php">{tr}Transitions{/tr}</a>
			{/if}
			{if $prefs.workspace_ui eq "y" and $tiki_p_admin eq "y"}
				<a class="dropdown-item" href="tiki-ajax_services.php?controller=workspace&action=list_templates">{tr}Workspace Templates{/tr}</a>
			{/if}
			<div class="dropdown-divider"></div>
			{if $tiki_p_plugin_approve eq "y"}
				<a class="dropdown-item" href="tiki-plugins.php">{tr}Plugin Approval{/tr}</a>
			{/if}
			<div class="dropdown-divider"></div>
			<a class="dropdown-item" href="tiki-mods.php">{tr}Mods{/tr}</a>
		</ul>
	</li>
	<li class="nav-item dropdown mr-1">
		<a href="#" class="nav-link dropdown-toggle" data-toggle="dropdown">{tr}Tools{/tr}</a>
		<ul class="dropdown-menu">
			{if $prefs.feature_actionlog eq "y" and $tiki_p_view_actionlog}
				<a class="dropdown-item" href="tiki-admin_actionlog.php">{tr}Action Log{/tr}</a>
			{/if}
			{if $tiki_p_edit_cookies eq "y"}
				<a class="dropdown-item" href="tiki-admin_cookies.php">{tr}Cookies{/tr}</a>
			{/if}
			{if $prefs.feature_sefurl_routes eq "y" and $tiki_p_admin}
				<a class="dropdown-item" href="tiki-admin_routes.php">{tr}Custom Routes{/tr}</a>
			{/if}
			<a class="dropdown-item" href="tiki-admin_dsn.php">{tr}DSN/Content Authentication{/tr}</a>
			{if $prefs.feature_editcss eq "y" and $tiki_p_create_css eq "y"}
				<a class="dropdown-item" href="tiki-edit_css.php">{tr}Edit CSS{/tr}</a>
			{/if}
			{if $prefs.feature_view_tpl eq "y" and $prefs.feature_edit_templates eq "y" and $tiki_p_edit_templates eq "y"}
				<a class="dropdown-item" href="tiki-edit_templates.php">{tr}Edit TPL{/tr}</a>
			{/if}
			{if $prefs.cachepages eq "y" and $tiki_p_admin eq "y"}
				<a class="dropdown-item" href="tiki-list_cache.php">{tr}External Pages Cache{/tr}</a>
			{/if}
			<a class="dropdown-item" href="tiki-admin_external_wikis.php">{tr}External Wikis{/tr}</a>
			{if $tiki_p_admin_importer eq "y"}
				<a class="dropdown-item" href="tiki-importer.php">{tr}Importer{/tr}</a>
			{/if}
			{if $prefs.feature_integrator eq "y" and $tiki_p_admin_integrator eq "y"}
				<a class="dropdown-item" href="tiki-admin_integrator.php">{tr}Integrator{/tr}</a>
			{/if}
			<a class="dropdown-item" href="tiki-phpinfo.php">{tr}PhpInfo{/tr}</a>
			{if $prefs.feature_referer_stats eq "y" and $tiki_p_view_referer_stats eq "y"}
				<a class="dropdown-item" href="tiki-referer_stats.php">{tr}Referer Statistics{/tr}</a>
			{/if}
			{if $prefs.feature_search_stats eq "y" and $tiki_p_admin eq "y"}
				<a class="dropdown-item" href="tiki-search_stats.php">{tr}Search Statistics{/tr}</a>
			{/if}
			<a class="dropdown-item" href="tiki-admin_security.php">{tr}Security Admin{/tr}</a>
			<a class="dropdown-item" href="tiki-check.php">{tr}Server Check{/tr}</a>
			<a class="dropdown-item" href="tiki-admin_sync.php">{tr}Synchronize Dev{/tr}</a>
			{if $tiki_p_clean_cache eq "y"}
				<a class="dropdown-item" href="tiki-admin_system.php">{tr}System Cache{/tr}</a>
			{/if}
			<a class="dropdown-item" href="tiki-syslog.php">{tr}System Logs{/tr}</a>
			{if $prefs.feature_scheduler eq "y" and $tiki_p_admin}
				<a class="dropdown-item" href="tiki-admin_schedulers.php">{tr}Scheduler{/tr}</a>
			{/if}
			{if $prefs.sitemap_enable eq "y" and $tiki_p_admin}
				<a class="dropdown-item" href="tiki-admin_sitemap.php">{tr}Sitemap{/tr}</a>
			{/if}
			<div class="dropdown-divider"></div>
			<a class="dropdown-item" href="tiki-wizard_admin.php">{tr}Wizards{/tr}</a>
		</ul>
	</li>
</ul>
