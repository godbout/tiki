{* $Id$ *}
{extends "layout_plain.tpl"}

{block name="title"}
	{* {title}{tr}Configuration Wizard{/tr}{/title} *}
{/block}

{block name="content"}
<form action="tiki-wizard_admin.php" method="post">
	<div class="col-sm-12">
		{include file="wizard/wizard_bar_admin.tpl"}
	</div>
	<hr>
	<div id="wizardBody">
		<div class="row">
		{if !empty($wizard_toc)}
			<div class="col-sm-4">
				<div class="card">
					<div class="card-header font-weight-bold adminWizardTOCTitle">{if $useDefaultPrefs}{tr}Profiles Wizard{/tr}{elseif $useChangesWizard}{tr}Changes Wizard{/tr}{else}{tr}Configuration Wizard{/tr}{/if} - {tr}steps:{/tr}</div>
					{$wizard_toc}
				</div>
			</div>
		{/if}
			<div class="{if !empty($wizard_toc)}col-sm-8{else}col-sm-12{/if}">
			{$wizardBody}
			</div>
		</div>
	</div>
	<hr>
	{include file="wizard/wizard_bar_admin.tpl"}
</form>
{/block}
