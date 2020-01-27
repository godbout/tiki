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
		{tr}Main new and improved features and settings in Tiki 20.{/tr}
		<a href="https://doc.tiki.org/Tiki20" target="tikihelp" class="tikihelp text-info" title="{tr}Tiki20:{/tr}
			{tr}It is a Standard Term Support (STS) version.{/tr}
			{tr}It will be supported until Tiki 21.1 is released.{/tr}
			{tr}Some internal libraries and optional external packages have been upgraded or replaced by more updated ones.{/tr}
			<br/><br/>
			{tr}Click to read more{/tr}
		">
			{icon name="help" size=1}
		</a>
		<fieldset>
			<legend>{tr}OCR Indexing{/tr}{help url="OCR+Indexing"}</legend>
			{preference name=ocr_enable}
				<div class="adminoptionboxchild" id="ocr_enable_childcontainer">
					{preference name=ocr_every_file}
				</div>
		</fieldset>
		<fieldset class="mb-3 w-100 clearfix featurelist">
			<legend>{tr}New Wiki Plugins{/tr}</legend>
			{preference name=wikiplugin_cypht}
			{preference name=wikiplugin_markdown}
		</fieldset>
		<fieldset class="mb-3 w-100 clearfix featurelist">
			<legend>{tr}Improved Plugins{/tr}</legend>
            {preference name=wikiplugin_list}
			{preference name=wikiplugin_listexecute}
			{preference name=wikiplugin_map}
            {preference name=wikiplugin_trackercalendar}
            {preference name=wikiplugin_trackerfilter}
		</fieldset>
		<fieldset class="mb-3 w-100 clearfix featurelist">
			<legend>{tr}Other New Features{/tr}</legend>
			{preference name=jquery_jqdoublescroll}
			{preference name=profile_autoapprove_wikiplugins}
			{preference name=feature_tag_users}
			{preference name=feature_notify_users_mention}
		</fieldset>
		<fieldset class="mb-3 w-100 clearfix featurelist">
			<legend>{tr}Other Extended Features{/tr}</legend>
			<div class="adminoption form-group row">
				<label class="col-sm-3 col-form-label"><b>{tr}Console{/tr}</b>:</label>
				<div class="offset-sm-1 col-sm-11">
					{tr}New actions can be performed.{/tr}
					<a href="https://doc.tiki.org/Tiki20#Console">{tr}More Information{/tr}...</a><br/><br/>
				</div>
			</div>
			{preference name=feature_webmail}
		</fieldset>
		<fieldset class="mb-3 w-100 clearfix featurelist">
			<legend>{icon name="admin_profiles" size=2 iclass="float-left"}{tr}New Profiles{/tr}</legend>
			<ul>
				<li>{tr}Groupmail_20{/tr}
					<a href="https://profiles.tiki.org/Groupmail_20" target="tikihelp" class="tikihelp" title="{tr}Groupmail_20:{/tr}
						{tr}Click to read more{/tr}">{icon name="help" size=1}
					</a>
				</li>
			</ul>
		</fieldset>
		<i>{tr}And many more improvements{/tr}.
			{tr}See the full list of changes.{/tr}</i>
		<a href="https://doc.tiki.org/Tiki20" target="tikihelp" class="tikihelp" title="{tr}Tiki20:{/tr}
			{tr}Click to read more{/tr}
		">
			{icon name="help" size=1}
		</a>
	</div>
</div>
