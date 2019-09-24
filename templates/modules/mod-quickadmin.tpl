{* $Id$ *}

{tikimodule error=$module_params.error title=$tpl_module_title name="quickadmin" flip=$module_params.flip decorations=$module_params.decorations nobox=$module_params.nobox notitle=$module_params.notitle}
	{if $tiki_p_admin == "y"}
		<div id="quickadmin" class="nav justify-content-end flex-nowrap">
			{if $only_shortcuts neq 'y'}
				<div class="nav-item prefs-history-dropdown">
					{if ! $js}<ul class="cssmenu_horiz"><li>{/if}
					<a class="nav-link dropdown-toggle" data-toggle="dropdown" data-hover="dropdown" data-display="dynamic" data-flip="true" href="#" role="button">
						{icon name="history"}
					</a>
					<div class="dropdown-menu dropdown-menu-right" role="menu">
						<h6 class="dropdown-header">
							{tr}Recent Preferences{/tr}
						</h6>
						<div class="dropdown-divider"></div>
						{foreach $recent_prefs as $p}
							<a class="dropdown-item" href="tiki-admin.php?lm_criteria={$p|escape}&amp;exact">{$p|stringfix}</a>
						{foreachelse}
						<div class="dropdown-item" >{tr}None{/tr}</div>
						{/foreach}
					</div>
					{if ! $js}</li></ul>{/if}
				</div>
			{/if}
			{if $only_prefs_history neq 'y'}
				<div class="nav-item quickadmin-dropdown">
					{if ! $js}<ul class="cssmenu_horiz"><li>{/if}
					<a class="nav-link dropdown-toggle" data-toggle="dropdown" data-hover="dropdown" data-display="dynamic" href="#" role="button">
						{icon name='cogs'}
					</a>
					<div class="dropdown-menu dropdown-menu-right" role="menu">
						<h6 class="dropdown-header">
							{tr}Quick Administration{/tr}
						</h6>
						<div class="dropdown-divider"></div>
						<div class="dropdown-item mb-2 mt-2">
							<form method="post" action="tiki-admin.php" class="form-inline my-2 my-md-0 ml-auto" role="form">
								<div class="input-group">
									<input type="text" name="lm_criteria" value="{if ! empty($smarty.request.lm_criteria)}{$smarty.request.lm_criteria|escape}{/if}" class="form-control form-control-sm" placeholder="Search preferences...">
									<div class="input-group-append">
										<button type="submit" class="btn btn-primary btn-sm">
											<span class="icon icon-search fas fa-search fa-fw "></span>
										</button>
									</div>
								</div>
							</form>
						</div>
						<div class="dropdown-divider"></div>
						<a class="dropdown-item" href="tiki-wizard_admin.php?stepNr=0&amp;url=index.php">
							{icon name="wizard"} {tr}Wizards{/tr}
						</a>
						<a class="dropdown-item" href="tiki-admin.php">
								{icon name="cogs"} {tr}Control panels{/tr}
						</a>
						<a class="dropdown-item" href="tiki-admin.php?page=look">
							{icon name="image"} {tr}Themes{/tr}
						</a>
						<a class="dropdown-item"  href="tiki-adminusers.php">
							{icon name="user"} {tr}Users{/tr}
						</a>
						<a class="dropdown-item"  href="tiki-admingroups.php">
							{icon name="group"} {tr}Groups{/tr}
						</a>
						<div class="dropdown-item">
							{permission_link mode=text}
						</div>
						<a class="dropdown-item" href="tiki-admin_menus.php">
							{icon name="menu"} {tr}Menus{/tr}
						</a>
							{if $prefs.lang_use_db eq "y"}
								{if isset($smarty.session.interactive_translation_mode) && $smarty.session.interactive_translation_mode eq "on"}
										<a class="dropdown-item" href="tiki-interactive_trans.php?interactive_translation_mode=off">
											{icon name="translate"} {tr}Turn off interactive translation{/tr}
										</a>
								{else}
										<a class="dropdown-item" href="tiki-interactive_trans.php?interactive_translation_mode=on">
											{icon name="translate"} {tr}Turn on interactive translation{/tr}
										</a>
								{/if}
							{/if}
						{if $prefs.feature_comments_moderation eq "y"}
							<a class="dropdown-item" href="tiki-list_comments.php">
									{icon name="comments"} {tr}Comment moderation{/tr}
							</a>
						{/if}
							<a class="dropdown-item" href="tiki-admin_system.php?do=all">
								{icon name="trash"} {tr}Clear all caches{/tr}
							</a>
							<a class="dropdown-item" href="{bootstrap_modal controller=search action=rebuild}">
								{icon name="index"} {tr}Rebuild search index{/tr}
							</a>
							<a class="dropdown-item" href="tiki-plugins.php">
								{icon name="plugin"} {tr}Plugin approval{/tr}
							</a>
							<a class="dropdown-item" href="tiki-syslog.php">
								{icon name="log"} {tr}Logs{/tr}
							</a>
							<a class="dropdown-item" href="tiki-admin_modules.php">
								{icon name="modules"} {tr}Modules{/tr}
							</a>
						{if $prefs.feature_scheduler eq "y"}
							<a class="dropdown-item" href="tiki-admin_schedulers.php">
								{icon name="calendar"} {tr}Scheduler{/tr}
							</a>
						{/if}
						{if $prefs.feature_sefurl_routes eq "y"}
							<a class="dropdown-item" href="tiki-admin_routes.php">
								{icon name="random"} {tr}Custom Routes{/tr}
							</a>
						{/if}
						{if $prefs.feature_debug_console eq 'y'}
							<a class="dropdown-item" href="{query _type='relative' show_smarty_debug=1}">
								{icon name="bug"} {tr}Smarty debug window{/tr}
							</a>
						{/if}
					</div>
					{if ! $js}</li></ul>{/if}
				</div>
			{/if}
		</div>
	{/if}
{/tikimodule}
