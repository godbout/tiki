<nav class="navbar-expand-md {if $prefs.theme_navbar_color_variant eq 'dark'}navbar-dark bg-dark {else}navbar-light bg-light{/if} admin-navbar mb-4" role="navigation">
	<button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#admin-navbar-collapse-1" aria-controls="admin-navbar-collapse-1" aria-expanded="false" aria-label="Toggle navigation">
		<span class="navbar-toggler-icon"></span>
	</button>
{*	<div class="navbar-header"> *}

	{* </div> *}
	<div class="collapse navbar-collapse" id="admin-navbar-collapse-1">
		<form method="post" class="form form-inline my-2 my-md-0" role="form" style="min-height: 60px; width: 165px;">
			<div class="form-check">
				{ticket}
				<input type="checkbox" id="preffilter-toggle-1" class="preffilter-toggle preffilter-toggle-round form-check-input {$pref_filters.advanced.type|escape}" value="advanced"{if $pref_filters.advanced.selected} checked="checked"{/if}>
				<label for="preffilter-toggle-1"></label>
			</div>

			<ul class="nav navbar-nav filter-menu"{if not $pref_filters.advanced.selected} style="display: none;"{/if}>
				<li class="nav-item dropdown mr-0" style="padding-top: 6px;">
					<a href="#" class="nav-link dropdown-toggle pr-0" data-toggle="dropdown" title="{tr}Settings{/tr}" style="width: 48px;">
						{icon name="filter"}
					</a>
					<ul class="dropdown-menu" role="menu">
						<li class="dropdown-item">
							<span class="dropdown-title">{tr}Preference Filters{/tr}</span>
							<input type="hidden" name="pref_filters[]" value="basic">
						</li>
						{foreach from=$pref_filters key=name item=info}
							<li class="dropdown-item">
								<div class="form-check justify-content-start">
									<label>
										<input type="checkbox" class="form-check-input preffilter {$info.type|escape}" name="pref_filters[]" value="{$name|escape}"{if $info.selected} checked="checked"{/if}{if $name eq basic} disabled="disabled"{/if}>{$info.label|escape}
									</label>
								</div>
							</li>
						{/foreach}
						<li class="dropdown-item">
							<div class="text-center">
								<input type="submit" value="{tr}Set as my default{/tr}" class="btn btn-primary btn-sm">
							</div>
						</li>
						{if $prefs.connect_feature eq "y"}
							{capture name=likeicon}{icon name="thumbs-up"}{/capture}
							<div class="form-check">
								<label class="form-check-label">
									<input type="checkbox" id="connect_feedback_cbx" class="form-check-input" {if !empty($connect_feedback_showing)}checked="checked"{/if}>
									{tr}Provide Feedback{/tr}
									<a href="https://doc.tiki.org/Connect" target="tikihelp" class="tikihelp" title="{tr}Provide Feedback:{/tr}
										{tr}Once selected, some icon/s will be shown next to all features so that you can provide some on-site feedback about them{/tr}.
										<br/><br/>
										<ul>
											<li>{tr}Icon for 'Like'{/tr} {$smarty.capture.likeicon|escape}</li>
	<!--											<li>{tr}Icon for 'Fix me'{/tr} <img src=img/icons/connect_fix.png></li> -->
	<!--											<li>{tr}Icon for 'What is this for?'{/tr} <img src=img/icons/connect_wtf.png></li> -->
										</ul>
										<br>
										{tr}Your votes will be sent when you connect with mother.tiki.org (currently only by clicking the 'Connect > <strong>Send Info</strong>' button){/tr}
										<br/><br/>
										{tr}Click to read more{/tr}
									">
										{icon name="help"}
									</a>
								</label>
							</div>
							{$headerlib->add_jsfile("lib/jquery_tiki/tiki-connect.js")}
						{/if}
						{jq}
							var updateVisible = function() {
							var show = function (selector) {
							selector.show();
							selector.parents('fieldset:not(.tabcontent)').show();
							selector.closest('fieldset.tabcontent').addClass('filled');
							};
							var hide = function (selector) {
							selector.hide();
							/*selector.parents('fieldset:not(.tabcontent)').hide();*/
							};

							var filters = [];
							var prefs = $('#col1 .adminoptionbox.preference, .admbox').hide();
							prefs.parents('fieldset:not(.tabcontent)').hide();
							prefs.closest('fieldset.tabcontent').removeClass('filled');
							$('.preffilter').each(function () {
							var targets = $('.adminoptionbox.preference.' + $(this).val() + ',.admbox.' + $(this).val());
							if ($(this).is(':checked')) {
							filters.push($(this).val());
							show(targets);
							} else if ($(this).is('.negative:not(:checked)')) {
							hide(targets);
							}
							});

							show($('.adminoptionbox.preference.modified'));

							$('input[name="filters"]').val(filters.join(' '));
							$('.tabset .tabmark a').each(function () {
							var selector = 'fieldset.tabcontent.' + $(this).attr('href').substring(1);
							var content = $(this).closest('.tabset').find(selector);

							$(this).parent().toggle(content.is('.filled') || content.find('.preference').length === 0);
							});
							};

							updateVisible();
							$('.preffilter').change(updateVisible);
							$('.preffilter-toggle').change(function () {
							var checked = $(this).is(":checked");
							$("input.preffilter[value=advanced]").prop("checked", checked);
							$(".filter-menu.nav").css("display", checked ? "block" : "none");
							updateVisible();
							});
						{/jq}
						<li class="dropdown-divider"></li>
						<li class="dropdown-item">
							<a href="tiki-admin.php?prefrebuild">
								{tr}Rebuild Admin Index{/tr}
							</a>
						</li>
						<li class="dropdown-item">
							<a href="tiki-admin.php">
								{tr}Control Panels{/tr}
							</a>
						</li>
					</ul>
				</li>
			</ul>
		</form>
		{include file="admin/admin_navbar_menu.tpl"}
		<ul class="navbar-nav flex-row d-md-flex mr-2">
			<li class="nav-item">
				<form method="post" class="form-inline my-2 my-md-0 ml-auto" role="form">
					<div class="form-group row mx-0">
						<input type="hidden" name="filters">
						<div class="input-group">
							<input type="text" name="lm_criteria" value="{$lm_criteria|escape}" class="form-control form-control-sm" placeholder="{tr}Search preferences{/tr}...">
							<div class="input-group-append">
								<button type="submit" class="btn btn-info btn-sm" {if $indexNeedsRebuilding} class="tips" title="{tr}Configuration search{/tr}|{tr}Note: The search index needs rebuilding, this will take a few minutes.{/tr}"{/if}>{icon name="search"}</button>
							</div>
						</div>
					</div>
				</form>
			</li>
		</ul>
	</div>
	{if $include != "list_sections"}
		<div class="adminanchors card"><div class="card-body {if $prefs.theme_navbar_color_variant eq 'dark'}navbar-dark bg-dark {else}navbar-light bg-light{/if}"><ul class="nav navbar-nav">{include file='admin/include_anchors.tpl'}</ul></div></div>
	{/if}
</nav>

{if $lm_searchresults}
	<div class="card card-primary" id="pref_searchresults">
		<div class="card-header">
			<h3 class="card-title">{tr}Preference Search Results{/tr}<button type="button" id="pref_searchresults-close" class="close" aria-hidden="true">&times;</button></h3>
		</div>
		<form method="post" href="tiki-admin.php" class="table" role="form">
			<div class="pref_search_results card-body">
				{foreach from=$lm_searchresults item=prefName}
					{preference name=$prefName get_pages='y' visible='always'}
				{/foreach}
			</div>
			<div class="card-footer text-center">
				<input class="btn btn-primary" type="submit" title="{tr}Apply Changes{/tr}" value="{tr}Apply{/tr}">
			</div>
			<input type="hidden" name="lm_criteria" value="{$lm_criteria|escape}">
			{ticket}
		</form>
	</div>
	{jq}
		$( "#pref_searchresults-close" ).click(function() {
			$( "#pref_searchresults" ).hide();
		});
	{/jq}
{elseif $lm_criteria}
	{remarksbox type="note" title="{tr}No results{/tr}" icon="magnifier"}
		{tr}No preferences were found for your search query.{/tr}<br>
		{tr _0='<a class="alert-link" href="tiki-admin.php?prefrebuild">' _1='</a>'}Not what you expected? Try %0rebuilding%1 the preferences search index.{/tr}
	{/remarksbox}
{/if}
