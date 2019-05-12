{* $Id$ *}

<div class="col-lg-12">
	<fieldset>
		<legend><h2>{tr}Get Started{/tr}</h2></legend>

		<div class="alert alert-light lead p-3 mb-3">
			<p class="text-success">
			{icon name="check" size=1} {tr _0=$tiki_version}Congratulations! You now have a working instance of Tiki %0.{/tr}
			{tr}You may <a href="tiki-index.php">start using it right away</a>, or you may configure it to better meet your needs, using one of the configuration helpers below.{/tr}
			</p>
		</div>

		{remarksbox type="tip" title="{tr}Tip{/tr}"}
			{tr}Mouse over the icons to know more about the features and preferences that are new for you.{/tr}
			{tr}Example: {/tr}
			<a href="http://doc.tiki.org/Wizards" target="tikihelp" class="alert-link tikihelp" title="{tr}Help icon{/tr}:
				{tr}You will get more information about the features and preferences whenever this icon is available and you pass your mouse over it.{/tr}
				<br/><br/>{tr}Moreover, if you click on it, you'll be directed in a new window to the corresponding documentation page for further information on that feature or topic.{/tr}"
			>
				{icon name="help"}
			</a>
		{/remarksbox}

		<div class="media mb-5">
			<span class="float-left fa-stack fa-lg mr-1" alt="{tr}Configuration Profiles Wizard{/tr}" title="{tr}Configuration Profiles Wizard{/tr}" >
				<i class="fas fa-cubes fa-stack-2x text-warning"></i>
				<i class="fas fa-flip-horizontal fa-magic fa-stack-1x ml-4 mt-4"></i>
			</span>
			<div class="media-body mx-4">
				<legend>{tr}Configuration Profiles Wizard{/tr}</legend>
				<p>{tr}You may start by applying some of our configuration templates through the <b>Configuration Profiles Wizard</b>.{/tr}
					{tr}They are like the <b>Macros</b> from many computer languages.{/tr}
					{tr}It is best to apply them from the start and you can always preview and reverse the process.{/tr}
					<a href="http://doc.tiki.org/Profiles+Wizard" target="tikihelp" class="tikihelp text-warning" title="{tr}Configuration Profiles{/tr}:
						<p>{tr}Each of these provides a shrink-wrapped solution that meets most of the needs of a particular kind of community or site (Personal Blog space, Company Intranet, ...) or that extends basic setup with extra features configured for you.{/tr}</p>
						<p>{tr}If you are new to Tiki administration, we recommend that you start with this approach.{/tr}</p>
						<p>{tr}If the profile you selected does not quite meet your needs, you will still have the option of customizing it further with one of the approaches below.{/tr}</p>"
					>
						{icon name="help"}
					</a>
				</p>

				<input type="submit" class="btn btn-warning" name="use-default-prefs" value="{tr}Start Configuration Profiles Wizard (Macros){/tr}" />
			</div>
		</div>
		<div class="media mb-5">
			<span class="float-left fa-stack fa-lg mr-1" alt="{tr}Configuration Walkthrough{/tr}" title="Configuration Walkthrough">
				<i class="fas fa-cog fa-stack-2x text-info"></i>
				<i class="fas fa-flip-horizontal fa-magic fa-stack-1x ml-4 mt-4"></i>
			</span>
			<div class="media-body mx-4">
				<legend>{tr}Configuration Wizard{/tr}</legend>
				<p>
					{tr}Alternatively, you may use the <b>Configuration Wizard</b>.{/tr}
					{tr}This will guide you through the most common preference settings in order to customize your site.{/tr}
					{tr}You will easily be able to configure options like: languages, date and time, user login, theme, website title and logo, etc.{/tr}
					<a href="http://doc.tiki.org/Admin+Wizard" target="tikihelp" class="tikihelp text-info" title="{tr}Configuration Wizard{/tr}:
						{tr}Use this wizard if none of the <b>Configuration Profiles</b> look like a good starting point, or if you need to customize your site further{/tr}"
					>
						{icon name="help"}
					</a>
				</p>
				<input type="submit" class="btn btn-info" name="continue" value="{tr}Start Configuration Wizard{/tr}" />
			</div>
		</div>
		<div class="media mb-5">
			<span class="float-left fa-stack fa-lg mr-1" alt="{tr}Changes Wizard{/tr}" title="Changes Wizard">
				<i class="fas fa-arrow-circle-up fa-stack-2x text-success"></i>
				<i class="fas fa-flip-horizontal fa-magic fa-stack-1x ml-4 mt-4"></i>
			</span>
			<div class="media-body mx-4">
				<legend>{tr}Changes Wizard{/tr}</legend>
				<p>
					{tr}Or you may use the <b>Changes Wizard</b>{/tr}.
					{tr}This will guide you through the most common new settings and informations in order to upgrade your site.{/tr}
					<a href="http://doc.tiki.org/Upgrade+Wizard" target="tikihelp" class="tikihelp text-success" title="{tr}Changes Wizard{/tr}:
						{tr}Use this wizard if you are upgrading from previous versions of Tiki, specially if you come from the previous Long-Term Support (LTS) version.{/tr}</p>

						<p>{tr}Some of these settings are also available through the Configuration Wizard, and all of them are available through Control Panels{/tr}.
						{tr}But this wizard will let you learn about them as well as enable/disable them easily according to your needs and interests for your site{/tr}."
					>
						{icon name="help"}
					</a>
				</p>

					<input type="submit" class="btn btn-success" name="use-changes-wizard" value="{tr}Start Changes Wizard{/tr}" />
			</div>
		</div>
		<hr>
		<div class="media mb-5">
			<span class="float-left fa-stack fa-lg mr-1" alt="{tr}Control Panels{/tr}" title="Control Panels">
				<i class="fas fa-sliders-h fa-stack-2x text-primary"></i>
				<i class="fas fa-cogs fa-stack-1x ml-4 mt-4" style="margin-left: 20px"></i>
			</span>
			<div class="media-body mx-4">
				<legend>{tr}Control Panels{/tr}</legend>
				<p>
					{tr}Use the <b>Control Panels</b> to manually browse through the full list of preferences{/tr}.
					{tr}From the main administration page you'll be able to configure your Tiki, to enable features not set on by default and to change settings{/tr}.
					{tr}To Avoid Getting Overwhelmed by the impressive number of settings as a Startup Tiki Admin we set a preferences filters for Basic and Advanced features to start with.{/tr}
					<a href="https://doc.tiki.org/Admin-Home" target="tikihelp" class="tikihelp text-primary" title="{tr}Control Panels{/tr}: {tr}Explore the control panels and configure your Tiki manually.{/tr}" >{icon name="help"}</a>
				</p>

				{button href="tiki-admin.php" _class="btn-primary" _text="{tr}Go to the Control Panels{/tr}"}
			</div>
		</div>
		<div class="media mb-5">
			<span class="float-left fa-stack fa-lg mr-1" alt="{tr}Control Panels{/tr}" title="Control Panels">
				<i class="fas fa-heartbeat fa-stack-2x text-danger"></i>
				<i class="fas fa-server fa-stack-1x ml-4 mt-4"></i>
			</span>
			<div class="media-body mx-4">
		<legend>{tr}Server Fitness{/tr}</legend>
				<p>
					{tr _0=$tiki_version}You can check if your server meets the requirements for running Tiki version %0{/tr}.
					{tr}Using our home made standalone script for server environment settings diagnostics you can check that everything is ready to run your Tiki properly.{/tr}
					{tr}It is very useful for any PHP app, as it checks over 50 different things and provides contextual feedback{/tr}.
					<a href="https://doc.tiki.org/Server-Check" target="tikihelp" class="tikihelp text-danger" title="{tr}Server Fitness{/tr}: {tr}Check your server fitness.{/tr}">
						{icon name="help"}
					</a>
				</p>
				{button href="tiki-check.php" _class="btn-danger" _text="{tr}Go to the Tiki Server Compatibility Check{/tr}"}
			</div>
		</div>
	</fieldset>
</div>
