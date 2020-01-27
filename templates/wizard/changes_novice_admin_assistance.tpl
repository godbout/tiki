{* $Id$ *}
<div class="media">
	<div class="mr-4">
	<span class="float-left fa-stack fa-lg margin-right-18em" alt="{tr}Changes Wizard{/tr}" title="Changes Wizard">
		<i class="fas fa-arrow-circle-up fa-stack-2x"></i>
		<i class="fas fa-flip-horizontal fa-magic fa-stack-1x ml-4 mt-4"></i>
	</span>
	</div>
	<br/><br/>
	<div class="media-body">
		{icon name="user-plus" size=3 iclass="float-sm-right"}
		{tr}Improvements that can help novice admins to set up their tiki sites more easily and improve their usability{/tr}.
		<fieldset>
			<legend>{tr}Basic Information about Wizards{/tr}</legend>
			<p>
				{tr}Starting in Tiki12, some wizards were added to Tiki in order to help in the initial setup based on configuration templates like "Macros" (<b>Profiles Wizard</b>), as well as further site configuration (<b>Configuration Wizard</b>), fine tunning the new features and preferences when upgrading (<b>Changes Wizard</b>), and to help you as site admin to collect more information from your users if you need it (<b>Users Wizard</b>){/tr}.
				<a href="http://doc.tiki.org/Wizards" target="tikihelp" class="tikihelp" title="{tr}Wizards:{/tr}
					{tr}Wizards oriented to help the site admin (Profiles, Configuration and Changes Wizards) come always enabled{/tr}.
					<br/><br/>
					{tr}The User Wizard comes disabled by default, and you have the option to enable it and configure it for your site{/tr}.
				">
					{icon name="help" size=1}
				</a>
			</p>
		</fieldset>
		<fieldset class="mb-3 w-100 clearfix featurelist">
			{icon name="magic" size=3 iclass="float-sm-right"}
			<legend> {tr}Wizards settings{/tr} </legend>
			{preference name=feature_wizard_user}
			{preference name=userTracker}
			<div class="adminoptionboxchild" id="userTracker_childcontainer">
				{preference name=feature_userWizardDifferentUsersFieldIds}
				<div class="adminoptionboxchild" id="feature_userWizardDifferentUsersFieldIds_childcontainer">
					{preference name=feature_userWizardUsersFieldIds}
				</div>
			</div>
			{preference name=wizard_admin_hide_on_login}
		</fieldset>
		<fieldset class="mb-3 w-100 clearfix featurelist">
			{icon name="envelope-o" size=3 iclass="float-sm-right"}
			<legend> {tr}Email{/tr} </legend>
			{preference name=email_footer}
			{preference name=messu_truncate_internal_message}
		</fieldset>
	</div>
</div>
