{* $Id$ *}
{if $prefs.connect_feature eq "y"}
<div class="form-row provide-feedback">
	<div class="col">
		{if !isset($provideFeedback) or $provideFeedback neq 'y'}
		<div class="form-check">
			{capture name=likeicon}{icon name="thumbs-up"}{/capture}
			<label>
				<input type="checkbox" class="form-check-input" id="connect_feedback_cbx" {if !empty($connect_feedback_showing)}checked="checked"{/if}>
				{tr}Provide Feedback{/tr}
				<a href="http://doc.tiki.org/Connect" target="tikihelp" class="tikihelp" title="{tr}Provide Feedback{/tr}:
					{tr}Once selected, some icon/s will be shown next to all features so that you can provide some on-site feedback about them.{/tr}
					<br/><br/>
					<ul>
						<li>{tr}Icon for 'Like'{/tr} {$smarty.capture.likeicon|escape}</li>
<!--					<li>{tr}Icon for 'Fix me'{/tr} <img src=img/icons/connect_fix.png></li> -->
<!--					<li>{tr}Icon for 'What is this for?'{/tr} <img src=img/icons/connect_wtf.png></li> -->
					</ul>
					<br/>
					{tr}Your votes will be sent when you connect with mother.tiki.org (currently only by clicking the 'Connect > <strong>Send Info</strong>' button).{/tr}
					<br/><br/>
					{tr}Click to read more{/tr}
				">
					{icon name='help'}
				</a> </label>
			{$headerlib->add_jsfile("lib/jquery_tiki/tiki-connect.js")}

			{assign var="provideFeedback" value="y" scope="root"}
		</div>
		{/if}
	</div>
</div>
{/if}
<div class="form-group form-row{if $prefs.feature_bidi eq 'y'} text-left{else} text-right{/if}">
	<div class="col">
		<input type="hidden" name="url" value="{$homepageUrl}">
		<input type="hidden" name="wizard_step" value="{$wizard_step}">
		{if isset($useDefaultPrefs)}
			<input type="hidden" name="use-default-prefs" value="{$useDefaultPrefs}">
		{/if}
		{if isset($useChangesWizard)}
			<input type="hidden" name="use-changes-wizard" value="{$useChangesWizard}">
		{/if}
		{if !isset($showOnLoginDisplayed) or $showOnLoginDisplayed neq 'y'}
		<div class="form-check">
			<input type="checkbox" class="form-check-input" id="showOnLogin" name="showOnLogin" {if isset($showOnLogin) AND $showOnLogin eq true}checked="checked"{/if} />
			<label class="form-check-label" for="showOnLogin">{tr}Show on admin log-in{/tr}</label>
			{assign var="showOnLoginDisplayed" value="y" scope="root"}
		</div>
		{/if}
		<div class="btn-group">
		{if !isset($firstWizardPage)}
			<input type="submit" class="btn btn-outline-dark" name="back" value="{tr}Back{/tr}" />
		{/if}
			<input type="submit" class="btn btn-dark" name="{if isset($firstWizardPage)}use-default-prefs{else}continue{/if}" value="{if isset($lastWizardPage)}{tr}Finish{/tr}{elseif isset($firstWizardPage)}{tr}Start the Wizardry{/tr}{else}{if $isEditable eq true}{tr}Save and Continue{/tr}{else}{tr}Next{/tr}{/if}{/if}"/>
		</div>
			<input type="submit" class="btn btn-outline-danger btn-sm" name="close" value="{tr}Close{/tr}"/>
	</div>
</div>
<div class="row">
	<div class="col-sm-12 text-center">
		{if !isset($showWizardPageTitle) or $showWizardPageTitle neq 'y'}
			<h1 class="adminWizardPageTitle">{$pageTitle}</h1>
			{assign var="showWizardPageTitle" value="y" scope="root"}
		{/if}
	</div>
</div>
