{$main_admin_icons = [
	"general" => [
		'title' => tra('General'),
		'description' => tra('Global site configuration, date formats, etc.'),
		'help' => 'General Admin'
	],
	"features" => [
		'title' => tra('Features'),
		'description' => tra('Switches for major features'),
		'help' => 'Features Admin'
	],
	"login" => [
		'title' => tra('Log in'),
		'description' => tra('User registration, remember me cookie settings and authentication methods'),
		'help' => 'Login Config'
	],
	"user" => [
		'title' => tra('User Settings'),
		'description' => tra('User related preferences like info and picture, features, messages and notification, files, etc'),
		'help' => 'User Settings'
	],
	"profiles" => [
		'title' => tra('Profiles'),
		'description' => tra('Repository configuration, browse and apply profiles'),
		'help' => 'Profiles'
	],
	"look" => [
		'title' => tra('Look & Feel'),
		'description' => tra('Theme selection, layout settings and UI effect controls'),
		'help' => 'Look and Feel'
	],
	"textarea" => [
		'title' => tra('Editing and Plugins'),
		'description' => tra('Text editing settings applicable to many areas. Plugin activation and plugin alias management'),
		'help' => 'Text area'
	],
	"module" => [
		'title' => tra('Modules'),
		'description' => tra('Module appearance settings'),
		'help' => 'Module'
	],
	"performance" => [
		'title' => tra('Performance'),
		'description' => tra('Server performance settings'),
		'help' => 'Performance'
	],
	"security" => [
		'title' => tra('Security'),
		'description' => tra('Site security settings'),
		'help' => 'Security'
	],
	"wiki" => [
		'title' => tra('Wiki'),
		'description' => tra('Wiki page settings and features'),
		'help' => 'Wiki Config'
	],
	"print" => [
		'title' => tra('Print Settings'),
		'description' => tra('Settings and features for print versions and pdf generation'),
		'help' => 'Print Setting-Admin'
	],
	"packages" => [
		'title' => tra('Packages'),
		'description' => tra('External packages installation and management'),
		'help' => 'Packages'
	]
]}

<a class="js-quick-admin link-quick-admin">
	<i class="fas fa-cog icon-quick-admin"></i>
</a>
<div class="sliding-panel-quick-admin js-sliding-panel-quick-admin card-header">
	<div class="card-header left"></div>
	<div class="container-fluid container-sliding-panel d-flex flex-column h-100 justify-content-center">
		
		<div class="row">
			<div class="col-md-4 align-self-center col-left-sliding-panel mb-2 mb-md-0 pl-md-0">
				<div class="col-md-12 col-search mb-2 px-0">
						<form method="post" action="tiki-admin.php" class="form-inline my-2 my-md-0 ml-auto" role="form">
							<label class="col-form-control mr-md-3 text-left">Admin Features</label>
							<input type="text" name="lm_criteria" value="{$smarty.request.lm_criteria|escape}" class="form-control form-control-sm mr-2 col-10 col-md-auto" placeholder="Search preferences...">
							<button type="submit" class="btn btn-primary btn-sm">
								<span class="icon icon-search fas fa-search fa-fw "></span>
							</button>
						</form>						
				</div>
				<div class="col-md-12 px-0">
					<div class="row">
						
						{* $Id$ *}

						{tikimodule error=$module_params.error title=$tpl_module_title name="quickadmin" flip=$module_params.flip decorations=$module_params.decorations nobox=$module_params.nobox notitle=$module_params.notitle}
							{if $tiki_p_admin == "y"}
								<div id="quickadmin" class="btn-group">
									<div class="btn-group">
										{if ! $js}<ul class="cssmenu_horiz"><li>{/if}
										<a class="btn btn-link" data-toggle="dropdown" data-hover="dropdown" href="#">
											{icon name="history"} Recent Actions
										</a>
										<ul class="dropdown-menu" role="menu">
											{foreach $recent_prefs as $p}
												<li class="dropdown-item">
													<a href="tiki-admin.php?lm_criteria={$p|escape}&amp;exact">{$p|stringfix}</a>
												</li>
												{foreachelse}
												<li>{tr}None{/tr}</li>
											{/foreach}
										</ul>
										{if ! $js}</li></ul>{/if}
									</div>
									<div class="btn-group">
										{if ! $js}<ul class="cssmenu_horiz"><li>{/if}
										<a class="btn btn-link" data-toggle="dropdown" data-hover="dropdown" href="#">
											{icon name='menu-extra'} Quick Links
										</a>
										<ul class="dropdown-menu">
											<li class="dropdown-item">
												<a href="tiki-wizard_admin.php?stepNr=0&amp;url=index.php">
													{icon name="wizard"} {tr}Wizards{/tr}
												</a>
											</li>
											<li class="dropdown-item">
												<a href="tiki-admin.php">
													{icon name="cog"} {tr}Control panels{/tr}
												</a>
											</li>
											<li class="dropdown-item">
												<a href="tiki-admin.php?page=look">
													{icon name="image"} {tr}Themes{/tr}
												</a>
											</li>
											<li class="dropdown-item">
												<a href="tiki-adminusers.php">
													{icon name="user"} {tr}Users{/tr}
												</a>
											</li>
											<li class="dropdown-item">
												<a href="tiki-admingroups.php">
													{icon name="group"} {tr}Groups{/tr}
												</a>
											</li>
											<li class="dropdown-item">
												{permission_link mode=text}
											</li>
											<li class="dropdown-item">
												<a href="tiki-admin_menus.php">
													{icon name="menu"} {tr}Menus{/tr}
												</a>
											</li>
												{if $prefs.lang_use_db eq "y"}
													<li class="dropdown-item">
														{if isset($smarty.session.interactive_translation_mode) && $smarty.session.interactive_translation_mode eq "on"}
															<a href="tiki-interactive_trans.php?interactive_translation_mode=off">
																{icon name="translate"} {tr}Turn off interactive translation{/tr}
															</a>
														{else}
															<a href="tiki-interactive_trans.php?interactive_translation_mode=on">
																{icon name="translate"} {tr}Turn on interactive translation{/tr}
															</a>
														{/if}
													</li>
												{/if}
											{if $prefs.feature_comments_moderation eq "y"}
												<li class="dropdown-item">
													<a href="tiki-list_comments.php">
														{icon name="comments"} {tr}Comment moderation{/tr}
													</a>
												</li>
											{/if}
											<li class="dropdown-item">
												<a href="tiki-admin_system.php?do=all">
													{icon name="trash"} {tr}Clear all caches{/tr}
												</a>
											</li>
											<li class="dropdown-item">
												<a href="{bootstrap_modal controller=search action=rebuild}">
													{icon name="index"} {tr}Rebuild search index{/tr}
												</a>
											</li>
											<li class="dropdown-item">
												<a href="tiki-plugins.php">
													{icon name="plugin"} {tr}Plugin approval{/tr}
												</a>
											</li>
											<li class="dropdown-item">
												<a href="tiki-syslog.php">
													{icon name="log"} {tr}Logs{/tr}
												</a>
											</li>
											<li class="dropdown-item">
												<a href="tiki-admin_modules.php">
													{icon name="module"} {tr}Modules{/tr}
												</a>
											</li>
											{if $prefs.feature_scheduler eq "y"}
											<li class="dropdown-item">
												<a href="tiki-admin_schedulers.php">
													{icon name="calendar"} {tr}Scheduler{/tr}
												</a>
											</li>
											{/if}
											{if $prefs.feature_sefurl_routes eq "y"}
												<li class="dropdown-item">
													<a href="tiki-admin_routes.php">
														{icon name="random"} {tr}Custom Routes{/tr}
													</a>
												</li>
											{/if}
											{if $prefs.feature_debug_console eq 'y'}
												<li class="dropdown-item">
													<a href="{query _type='relative' show_smarty_debug=1}">
														{icon name="bug"} {tr}Smarty debug window{/tr}
													</a>
												</li>
											{/if}
										</ul>
										{if ! $js}</li></ul>{/if}
									</div>
								</div>
							{/if}
						{/tikimodule}
					</div>
				</div>
			</div>
			<div class="col-md-8 mb-2 mb-md-0 pr-md-0">
				<div class="swiper-container js-quick-admin-slider quick-admin-slider">
					<div class="swiper-wrapper">
						{foreach from=$main_admin_icons key=page item=info}

							{if $info.disabled}
								{assign var=class value="admbox advanced btn btn-primary disabled"}
							{else}
								{assign var=class value="admbox basic btn btn-primary"}
								<div class="swiper-slide">
								{* FIXME: Buttons are forced to be squares, not fluid. Labels which exceed 2 lines will be cut. *}
								<a href="tiki-admin.php?page={$page}" alt="{$info.title} {$info.description}" class="d-flex flex-column justify-content-center align-items-center btn-primary tips bottom slow {if $info.disabled}disabled-clickable{/if}" title="{$info.title|escape}{if $info.disabled} ({tr}Disabled{/tr}){/if}|{$info.description}">
									{icon name="admin_$page"}
									<span class="title">{$info.title|escape}</span>
								</a>
								</div>
							{/if}


						{/foreach}
					</div>
				</div>
				<!-- Add Arrows -->
				<div class="swiper-button-next">

				</div>

				<div class="swiper-button-prev">

				</div>
			</div>
		</div>
	</div>
	<div class="card-header right"></div>

	
</div>


{literal}
	

	<link rel="stylesheet" href="vendor_bundled/vendor/nolimits4web/swiper/dist/css/swiper.css" type="text/css">
	<script type="text/javascript" src="vendor_bundled/vendor/nolimits4web/swiper/dist/js/swiper.min.js"></script>

{/literal}