{* $Id$ *}<!DOCTYPE html>
<html lang="{if !empty($pageLang)}{$pageLang}{else}{$prefs.language}{/if}"{if !empty($page_id)} id="page_{$page_id}"{/if}>
	<head>
{include file='header.tpl'}
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
	</head>
	<body{html_body_attributes class="navbar-padding"}>
{$cookie_consent_html}

{include file="layout_fullscreen_check.tpl"}

{if $prefs.feature_ajax eq 'y'}
	{include file='tiki-ajax_header.tpl'}
{/if}
<div class="middle_outer" id="middle_outer">
{if $smarty.session.fullscreen ne 'y'}
	<div class="fixed-topbar"></div>
{/if}
	<div class="container{if $smarty.session.fullscreen eq 'y'}-fluid{/if} clearfix middle" id="middle">
{if $smarty.session.fullscreen ne 'y'}
		<div class="topbar_wrapper">
			<div class="topbar" id="topbar">
				{modulelist zone=topbar class='row topbar_modules d-flex justify-content-between'}
			</div>
		</div>
{/if}

		{*<div class="container{if $smarty.session.fullscreen eq 'y'}-fluid{/if}">*}

			<div class="row row-middle" id="row-middle">
				{if (zone_is_empty('left') or $prefs.feature_left_column eq 'n') and (zone_is_empty('right') or $prefs.feature_right_column eq 'n')}
					<div class="col col1 col-md-12" id="col1">
						{if $prefs.module_zones_pagetop eq 'fixed' or ($prefs.module_zones_pagetop ne 'n' && ! zone_is_empty('pagetop'))}
							{modulelist zone=pagetop}
						{/if}
						{feedback}
						{block name=quicknav}{/block}
						{block name=title}{/block}
						{block name=navigation}{/block}
						{block name=content}{/block}
						{if $prefs.module_zones_pagebottom eq 'fixed' or ($prefs.module_zones_pagebottom ne 'n' && ! zone_is_empty('pagebottom'))}
							{modulelist zone=pagebottom}
						{/if}
					</div>
				{elseif zone_is_empty('left') or $prefs.feature_left_column eq 'n'}
					{if $prefs.feature_right_column eq 'user'}
						<div class="col-md-12 text-right side-col-toggle">
							{$icon_name = (not empty($smarty.cookies.hide_zone_right)) ? 'toggle-left' : 'toggle-right'}
							{icon name=$icon_name class='toggle_zone right' href='#' title='{tr}Toggle right modules{/tr}'}
						</div>
					{/if}
					<div class="col col1 col-md-12 col-lg-9" id="col1">
						{if $prefs.module_zones_pagetop eq 'fixed' or ($prefs.module_zones_pagetop ne 'n' && ! zone_is_empty('pagetop'))}
							{modulelist zone=pagetop}
						{/if}
						{feedback}
						{block name=quicknav}{/block}
						{block name=title}{/block}
						{block name=navigation}{/block}
						{block name=content}{/block}
						{if $prefs.module_zones_pagebottom eq 'fixed' or ($prefs.module_zones_pagebottom ne 'n' && ! zone_is_empty('pagebottom'))}
							{modulelist zone=pagebottom}
						{/if}
					</div>
					<div class="col col3 col-md-12 col-lg-3" id="col3">
						{modulelist zone=right}
					</div>
				{elseif zone_is_empty('right') or $prefs.feature_right_column eq 'n'}
					{if $prefs.feature_left_column eq 'user'}
						<div class="col-md-12 text-left side-col-toggle">
							{$icon_name = (not empty($smarty.cookies.hide_zone_left)) ? 'toggle-right' : 'toggle-left'}
							{icon name=$icon_name class='toggle_zone left' href='#' title='{tr}Toggle left modules{/tr}'}
						</div>
					{/if}
					<div class="col col1 col-md-12 col-lg-9 order-md-1 order-lg-2" id="col1">
						{if $prefs.module_zones_pagetop eq 'fixed' or ($prefs.module_zones_pagetop ne 'n' && ! zone_is_empty('pagetop'))}
							{modulelist zone=pagetop}
						{/if}
						{feedback}
						{block name=quicknav}{/block}
						{block name=title}{/block}
						{block name=navigation}{/block}
						{block name=content}{/block}
						{if $prefs.module_zones_pagebottom eq 'fixed' or ($prefs.module_zones_pagebottom ne 'n' && ! zone_is_empty('pagebottom'))}
							{modulelist zone=pagebottom}
						{/if}
					</div>
					<div class="col col2 col-md-12 col-lg-3 order-sm-2 order-md-2 order-lg-1" id="col2">
						{modulelist zone=left}
					</div>
				{else}
					{if $prefs.feature_left_column eq 'user'}
						<div class="col-md-6 text-left side-col-toggle">
							{$icon_name = (not empty($smarty.cookies.hide_zone_left)) ? 'toggle-right' : 'toggle-left'}
							{icon name=$icon_name class='toggle_zone left' href='#' title='{tr}Toggle left modules{/tr}'}
						</div>
					{/if}
					{if $prefs.feature_right_column eq 'user'}
						<div class="col-md-6 text-right side-col-toggle{if $prefs.feature_left_column neq 'user'} col-md-offset-6{/if}">
							{$icon_name = (not empty($smarty.cookies.hide_zone_right)) ? 'toggle-left' : 'toggle-right'}
							{icon name=$icon_name class='toggle_zone right' href='#' title='{tr}Toggle right modules{/tr}'}
						</div>
					{/if}
					<div class="col col1 col-sm-12 col-lg-8 order-xs-1 order-lg-2" id="col1">
						{if $prefs.module_zones_pagetop eq 'fixed' or ($prefs.module_zones_pagetop ne 'n' && ! zone_is_empty('pagetop'))}
							{modulelist zone=pagetop}
						{/if}
						{feedback}
						{block name=quicknav}{/block}
						{block name=title}{/block}
						{block name=navigation}{/block}
						{block name=content}{/block}
						{if $prefs.module_zones_pagebottom eq 'fixed' or ($prefs.module_zones_pagebottom ne 'n' && ! zone_is_empty('pagebottom'))}
							{modulelist zone=pagebottom}
						{/if}
					</div>
					<div class="col col2 col-sm-6 col-lg-2 order-md-2 order-lg-1" id="col2">
						{modulelist zone=left}
					</div>
					<div class="col col3 col-sm-6 col-lg-2 order-md-3" id="col3">
						{modulelist zone=right}
					</div>
				{/if}
			</div> {* row *}
		{*</div>*} {* container *}
	</div>

	{if !isset($smarty.session.fullscreen) || $smarty.session.fullscreen ne 'y'}
		<footer class="footer main-footer" id="footer">
			<div class="container{if $smarty.session.fullscreen eq 'y'}-fluid{/if}">
				<div class="footer_liner">
					{modulelist zone=bottom class='row mx-0'} <!-- div.modules -->
				</div>
			</div>
		</footer>

		<nav class="navbar navbar-expand-md navbar-light {*navbar=dark*}{*navbar-primary*} bg-light {*bg-dark*} fixed-top" {*style="background: mycustomcolorcode;"*}>
			<div class="container{if $smarty.session.fullscreen eq 'y'}-fluid{/if} d-flex justify-content-between">
				<a class="navbar-brand" href="./">{if $prefs.sitelogo_icon}<img src="{$prefs.sitelogo_icon}">{/if} {$prefs.sitetitle|escape}</a>
				<button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarCollapse" aria-controls="navbarCollapse" aria-expanded="false" aria-label="Toggle navigation">
					<span class="navbar-toggler-icon"></span>
				</button>
				<div class="collapse navbar-collapse" id="navbarCollapse">
					{modulelist zone="topbar" id="topbar_modules_social" class="d-flex flex-fill justify-content-between"}
					<div class="flex">
					<ul class="navbar-nav">
						{if $user}
							<li class="nav-item">
								<a class="nav-link" href="{if $prefs.feature_sefurl eq 'y'}logout{else}tiki-logout.php{/if}">{tr}Log out{/tr}</a>
							</li>
						{else}
							<li class="dropdown">
								<a class="nav-link" href="#" class="dropdown-toggle" data-toggle="dropdown">{tr}Log in{/tr}</a>
								<div class="dropdown-menu dropdown-login card-body">
									<div class="dropdown-item">

		{module
			module=login_box
			mode="module"
			show_register=""
			show_forgot=""
			error=""
			flip=""
			decorations="n"
			nobox="y"
			notitle="y"
		}

									</div>
								</div>
							</li>
		{if $prefs.allowRegister eq 'y'}
							<li class="nav-item">
								<a class="nav-link" href="{if $prefs.feature_sefurl eq 'y'}register{else}tiki-register.php{/if}">{tr}Register{/tr}</a>
							</li>
		{/if}
	{/if}
						</ul>
					</div>
				</div> {* navbar-collapse-social *}
			</div> {* container *}

		</nav>
{/if}

{include file='footer.tpl'}
	</body>
	<script type="text/javascript">
		$(document).ready(function () {
			$('.tooltips').tooltip({
				'container': 'body'
			});
		});
	</script>
</html>
{if $prefs.feature_debug_console eq 'y' and not empty($smarty.request.show_smarty_debug)}
	{debug}
{/if}
