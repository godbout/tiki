{* $Id$ *}

{include file="admin/admin_navbar.tpl"}

{if $prefs.sender_email eq ''}
	{remarksbox type=warning title="{tr}Warning{/tr}" close="y"}
		{tr _0="tiki-admin.php?page=general&highlight=sender_email"}Your sender email is not set. You can set it <a href="%0" class="alert-link">in the general admin panel.</a>{/tr}
	{/remarksbox}
{/if}
<div class="page-header">
	{title help="$helpUrl"}{$admintitle}{/title}
	<span class="form-text">{$description}</span>
</div>

<div id="pageheader">
	{* bother to display this only when breadcrumbs are on *}
	{*
	{if $prefs.feature_breadcrumbs eq 'y'}
		{breadcrumbs type="trail" loc="page" crumbs=$crumbs}
		{breadcrumbs type="pagetitle" loc="page" crumbs=$crumbs}
	{/if}
	*}
	{if $db_requires_update}
		{remarksbox type="error" title="{tr}Database Version Problem{/tr}"}
			{tr}Your database requires an update to match the current Tiki version. Please proceed to <a class="alert-link" href="tiki-install.php">the installer</a>. Using Tiki with an incorrect database version usually provokes errors.{/tr}
			{tr}If you have shell (SSH) access, you can also use the following, on the command line, from the root of your Tiki installation:{/tr}
			<kbd>php console.php{if not empty($tikidomain)} --site={$tikidomain|replace:'/':''}{/if} database:update</kbd>
		{/remarksbox}
	{/if}

	{if $vendor_autoload_ignored or $vendor_autoload_disabled}
		{remarksbox type="error" title="{tr}Vendor folder issues{/tr}"}
			{tr}Your vendor folder contains multiple packages that were normally bundled with Tiki. Since version 17 those libraries were migrated from the folder <strong>vendor</strong> to the folder <strong>vendor_bundled</strong>.{/tr}<br />
			{if $vendor_autoload_ignored}
				{tr}To avoid issues your <strong>vendor/autoload.php</strong> was not loaded.{/tr}<br />
				{tr}We recommend that you remove/clean the <strong>vendor/</strong> folder content unless you really want to load these libraries, that are not bundled with tiki, and in such case add a file called <strong>vendor/do_not_clean.txt</strong> to force the load of these libraries.{/tr}
			{elseif $vendor_autoload_disabled}
				{tr}To avoid issues your <strong>vendor/autoload.php</strong> was renamed to <strong>vendor/autoload-disabled.php</strong>.{/tr}<br />
				{tr}For more information check <strong>vendor/autoload-disabled-README.txt</strong> file.{/tr}
			{/if}
		{/remarksbox}
	{/if}

	{if $installer_not_locked}
		{remarksbox type="error" title="{tr}Installer not locked{/tr}"}
			{tr} The installer allows a user to change or destroy the site's database through the browser so it is very important to keep it locked. {/tr}
			{tr}<br />You can re-run the installer (tiki-install.php), skip to the last step and select <strong>LOCK THE INSTALLER</strong>. Alternatively, you can simply <strong>add a lock file</strong> (file without any extension) in your db/ folder.{/tr}
			{tr}You can also use the following, on the command line, from the root of your Tiki installation:{/tr}
			<kbd>php console.php installer:lock</kbd>
		{/remarksbox}
	{/if}

	{if $search_index_outdated}
		{remarksbox type="error" title="{tr}Search Index outdated{/tr}"}
		{tr}The search index might be outdated. It is recommended to rebuild the search index.{/tr}
		{/remarksbox}
	{/if}
	{if $fgal_web_accessible}
		{remarksbox type="warning" title="{tr}File gallery directory web accessable{/tr}"}
		{tr}This is a potential security risk.{/tr} {tr}You may deny access to this directory with server access rules, move your gallery directory into a space outside of your web root, or transfer file gallery storage into the database.{/tr}
		{/remarksbox}
	{/if}

	{*{tr}{$description}{/tr}*}
</div>

{if $upgrade_messages|count}
	{if $upgrade_messages|count eq 1}
		{$title="{tr}Upgrade Available{/tr}"}
	{else}
		{$title="{tr}Upgrades Available{/tr}"}
	{/if}
	{remarksbox type="note" title=$title icon="announce"}
		{foreach from=$upgrade_messages item=um}
			<p>{$um|escape}</p>
		{/foreach}
	{/remarksbox}
{/if}

{if $template_not_found eq 'y'}
	{remarksbox type="error" title="{tr}Error{/tr}"}
		{tr _0="page" _1={$include|escape}}The <strong>%0</strong> parameter has an invalid value: <strong>%1</strong>.{/tr}
	{/remarksbox}
{else}
	{include file="admin/include_$include.tpl"}
{/if}
