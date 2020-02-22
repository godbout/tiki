{* $Id$ *}

<div class="media">
		<div class="mr-4">
			<span class="float-left fa-stack fa-lg margin-right-18em" alt="{tr}Changes Wizard{/tr}" title="Changes Wizard">
			<i class="fas fa-arrow-circle-up fa-stack-2x"></i>
			<i class="fas fa-flip-horizontal fa-magic fa-stack-1x ml-4 mt-4"></i>
			</span>
		</div>
	<br/><br/><br/>
	<div class="media-body">
		{tr}Main new and improved features and settings in Tiki 21.{/tr}
		<a href="https://doc.tiki.org/Tiki21" target="tikihelp" class="tikihelp text-info" title="{tr}Tiki21 (LTS):{/tr}
			{tr}This is an LTS version.{/tr}
			{tr}As it is a Long-Term Support (LTS) version, it will be supported for 5 years.{/tr}
			{tr}Many libraries have been upgraded.{/tr}
			<br/><br/>
			{tr}Click to read more{/tr}
		">
			{icon name="help" size=1}
		</a>
		<fieldset class="mb-3 w-100 clearfix featurelist">
			<legend>{tr}New Features{/tr}</legend>
			{preference name='object_maintainers_enable'}
			<div class="adminoptionboxchild" id="object_maintainers_enable_childcontainer">
				{preference name='object_maintainers_default_update_frequency'}
			</div>
			{preference name='pwa_feature'}
			{preference name=vuejs_enable}
			<div class="adminoptionboxchild" id="vuejs_enable_childcontainer">
				{preference name=vuejs_always_load}
				{preference name=vuejs_build_mode}
				{preference name=tracker_field_rules}
			</div>
			{preference name='twoFactorAuth'}
		</fieldset>
		<fieldset class="mb-3 w-100 clearfix featurelist">
			<legend>{tr}New Wiki Plugins{/tr}</legend>
			{preference name=wikiplugin_preference}
		</fieldset>
		<fieldset class="mb-3 w-100 clearfix featurelist">
			<legend>{tr}Improved Plugins{/tr}</legend>
            {preference name=wikiplugin_list}
		</fieldset>
		<fieldset class="mb-3 w-100 clearfix featurelist">
			<legend>{tr}Other Extended Features{/tr}</legend>
			<div class="adminoption form-group row">
				<label class="col-sm-3 col-form-label"><b>{tr}Console{/tr}</b>:</label>
				<div class="offset-sm-1 col-sm-11">
					{tr}New actions can be performed.{/tr}
					<a href="https://doc.tiki.org/Tiki21#Console">{tr}More Information{/tr}...</a><br/><br/>
				</div>
			</div>
			<div class="adminoption form-group row">
				<label class="col-sm-3 col-form-label"><b>{tr}Contacts{/tr}</b>:</label>
				<div class="offset-sm-1 col-sm-11">
					{tr}CardDAV support has been added.{/tr}
					<a href="https://doc.tiki.org/Tiki21#Contacts_-_CardDAV_support">{tr}More Information{/tr}...</a><br/><br/>
				</div>
				<label class="col-sm-3 col-form-label"><b>{tr}Calendars{/tr}</b>:</label>
				<div class="offset-sm-1 col-sm-11">
					{tr}CalDAV support has been added.{/tr}
					<a href="https://doc.tiki.org/Tiki21#Calendar_-_CalDAV_support">{tr}More Information{/tr}...</a><br/><br/>
				</div>
			</div>
			<div class="adminoption form-group row">
				<label class="col-sm-3 col-form-label"><b>{tr}Risky Preferences{/tr}</b>:</label>
				<div class="offset-sm-1 col-sm-11">
					{tr}Some of Tiki's preferences are quite powerful (and thus dangerous) and should be used only by experts.{/tr}
					{tr}These risky preferences are disabled and hidden by default, since Tiki 21.{/tr}
					{tr}Only the system administrator can make them visible through Tiki's system configuration file.{/tr}
					<a href="https://doc.tiki.org/Risky-Preferences">{tr}More Information{/tr}...</a><br/><br/>
				</div>
			</div>
			<div class="adminoption form-group row">
				<label class="col-sm-3 col-form-label"><b>{tr}Roles{/tr}</b>:</label>
				<div class="offset-sm-1 col-sm-11">
					{tr}Roles make Groups Management and Permissions setting easier.{/tr}
					{tr}Think of Roles as a permissions template which you can apply to Categories.{/tr}
					<a href="https://doc.tiki.org/Roles">{tr}More Information{/tr}...</a><br/><br/>
				</div>
				<label class="col-sm-3 col-form-label"><b>{tr}Templated groups{/tr}</b>:</label>
				<div class="offset-sm-1 col-sm-11">
					{tr}Templated groups are groups that follow the same pattern as a template.{/tr}
					{tr}They have similar types of members, permissions, group's assets, etc.{/tr}
					<a href="https://doc.tiki.org/Templated-Groups">{tr}More Information{/tr}...</a><br/><br/>
				</div>
			</div>
			{preference name=feature_webmail}
		</fieldset>
		<i>{tr}And many more improvements{/tr}.
			{tr}See the full list of changes.{/tr}</i>
		<a href="https://doc.tiki.org/Tiki21" target="tikihelp" class="tikihelp" title="{tr}Tiki21:{/tr}
			{tr}Click to read more{/tr}
		">
			{icon name="help" size=1}
		</a>
	</div>
</div>
