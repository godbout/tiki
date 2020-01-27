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
		{tr}Main new and improved features and settings in Tiki 18.{/tr}
		<a href="https://doc.tiki.org/Tiki18" target="tikihelp" class="tikihelp text-info" title="{tr}Tiki18:{/tr}
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
			<div class="adminoption form-group row">
				<label class="col-sm-3 col-form-label"><b>{tr}Control Panels{/tr}</b>:</label>
				<div class="offset-sm-1 col-sm-11">
					{icon name="admin_packages" size=2 iclass="float-left"}
					{tr}Composer Web Install (<b>Packages</b>).{/tr}
					<a href="https://doc.tiki.org/Packages">{tr}More Information{/tr}...</a><br/><br/>
				</div>
				<label class="col-sm-3 col-form-label"><b>{tr}Style guide tool{/tr}</b>:</label>
				<div class="offset-sm-1 col-sm-9">
					{icon name="admin_look" size=2 iclass="float-left"}
					{tr}Look and feel colors can be customized with a style guide tool.{/tr}
					<a href="https://doc.tiki.org/Style-Guide">{tr}More Information{/tr}...</a><br/><br/>
				</div>
			</div>
			<div class="adminoption form-group row">
			</div>
			{preference name='sitemap_enable'}
			{preference name='feature_sefurl_routes'}
			{preference name='fallbackBaseUrl'}
			{preference name='wiki_make_ordered_list_items_display_unique_numbers'}
		</fieldset>
		<fieldset class="mb-3 w-100 clearfix featurelist">
			<legend>{tr}New Wiki Plugins{/tr}</legend>
			{preference name=wikiplugin_pdfpage}
			{preference name=wikiplugin_preview}
		</fieldset>
		<fieldset class="mb-3 w-100 clearfix featurelist">
			<legend>{tr}Improved Plugins{/tr}</legend>
			{preference name=wikiplugin_img}
			{preference name=wikiplugin_list}
			{preference name=wikiplugin_listexecute}
			{preference name=wikiplugin_pdf}
			{preference name=wikiplugin_pivottable}
			{preference name=wikiplugin_trackercalendar}
			{preference name=wikiplugin_trackerlist}
		</fieldset>
		<fieldset class="mb-3 w-100 clearfix featurelist">
			<legend>{tr}PDF from URL: mPDF new settings{/tr}</legend>
			{preference name=print_pdf_from_url}
			<div class="adminoptionboxchild print_pdf_from_url_childcontainer mpdf">
				{preference name=print_pdf_mpdf_pagetitle}
				{preference name=print_pdf_mpdf_hyperlinks}
				{preference name=print_pdf_mpdf_columns}
				{preference name=print_pdf_mpdf_watermark}
				{preference name=print_pdf_mpdf_watermark_image}
				{preference name=print_pdf_mpdf_background}
				{preference name=print_pdf_mpdf_background_image}
				{preference name=print_pdf_mpdf_coverpage_text_settings}
				{preference name=print_pdf_mpdf_coverpage_image_settings}
			</div>
		</fieldset>
		<fieldset class="mb-3 w-100 clearfix featurelist">
			<legend>{tr}Other Extended Features{/tr}</legend>
			<div class="adminoption form-group row">
				<label class="col-sm-3 col-form-label"><b>{tr}Control Panels{/tr}</b>:</label>
				<div class="offset-sm-1 col-sm-11">
					{icon name="admin_rtc" size=2 iclass="float-left"}
					{tr}Real-time collaboration tools (<b>RTC</b>).{/tr}
					<a href="https://doc.tiki.org/RTC">{tr}More Information{/tr}...</a><br/><br/>
				</div>
				<label class="col-sm-3 col-form-label"><b>{tr}Console{/tr}</b>:</label>
				<div class="offset-sm-1 col-sm-11">
					{icon name="terminal" size=2 iclass="float-left"}
					{tr}New actions can be performed.{/tr}
					<a href="https://doc.tiki.org/Console">{tr}More Information{/tr}...</a><br/><br/>
				</div>
				<label class="col-sm-3 col-form-label"><b>{tr}Menus{/tr}</b>:</label>
				<div class="offset-sm-1 col-sm-11">
					{icon name="navicon" size=2 iclass="float-left"}
					{tr}Drag and drop added to menu management.{/tr}
					<a href="https://doc.tiki.org/Menu#Drag_and_drop">{tr}More Information{/tr}...</a><br/><br/>
				</div>
				<label class="col-sm-3 col-form-label"><b>{tr}Profiles{/tr}</b>:</label>
				<div class="offset-sm-1 col-sm-9">
					{icon name="admin_profiles" size=2 iclass="float-left"}
					{tr}Dry-run/Preview and Selective Rollback were added, as well as new options to allow exporting files and tracker items.{/tr}
					<a href="https://doc.tiki.org/Tiki18#Profiles">{tr}More Information{/tr}...</a><br/><br/>
				</div>
				<label class="col-sm-3 col-form-label"><b>{tr}Search{/tr}</b>:</label>
				<div class="offset-sm-1 col-sm-9">
					{icon name="admin_search" size=2 iclass="float-left"}
					{tr}Calendars and Calendar Items will now appear in search results of the unified search index.{/tr}
					<a href="https://doc.tiki.org/Tiki18#Search">{tr}More Information{/tr}...</a><br/><br/>
				</div>
			</div>
			{* {preference name=foo} *}
			{preference name=feature_trackers}
			<div class="adminoptionboxchild" id="feature_trackers_childcontainer">
				<legend>{tr}General{/tr}</legend>
				<div class="col-sm-12">
					{tr}Certain tracker fields can be converted keeping options{/tr}
					<a href="https://doc.tiki.org/Tiki18#Trackers">{tr}More Information{/tr}...</a><br/><br/>
				</div>
				<legend>{tr}New Fields{/tr}</legend>
				{preference name=trackerfield_calendaritem}<br/>
				<legend>{tr}Improved Fields{/tr}</legend>
				{preference name=trackerfield_relation}<br/>
			</div>
			{preference name=ids_enabled}
			<div class="adminoptionboxchild" id="ids_enabled_childcontainer">
				<div class="form-group adminoptionbox clearfix">
					<div class="offset-sm-4 col-sm-8">
						<a href="tiki-admin_ids.php">{tr}Admin IDS custom rules{/tr}</a>
					</div>
				</div>
				{preference name=ids_custom_rules_file}
				{preference name=ids_mode}
				{preference name=ids_threshold}
				{preference name=ids_log_to_file}
				{*{preference name=ids_log_to_database}*}
			</div>
			{preference name='h5p_enabled'}
			<div class="adminoptionboxchild" id="h5p_enabled_childcontainer">
				{preference name='h5p_filegal_id'}
				{preference name='h5p_whitelist'}
				{preference name='h5p_dev_mode'}
				{preference name='h5p_track_user'}
				{preference name='h5p_save_content_state'}
				<div class="adminoptionboxchild" id="h5p_save_content_state_childcontainer">
					{preference name='h5p_save_content_frequency'}
				</div>
				{preference name='h5p_export'}

				{remarksbox type="info" title="{tr}H5P Info{/tr}"}
				{tr}Service URL to purge unused libraries can be found here. Can be used in a cron task{/tr}<br>
					<a href="{service controller='h5p' action='cron' token=$prefs.h5p_cron_token}" class="btn btn-link">
						{service controller='h5p' action='cron' token=$prefs.h5p_cron_token}
					</a>
				{/remarksbox}
			</div>
		</fieldset>
		<fieldset class="mb-3 w-100 clearfix featurelist">
			<legend>{icon name="admin_profiles" size=2 iclass="float-left"}{tr}New Profiles{/tr}</legend>
			<ul>
				<li>{tr}Activity_Stream{/tr}
					<a href="https://profiles.tiki.org/Activity_Stream" target="tikihelp" class="tikihelp" title="{tr}Activity_Stream:{/tr}
						{tr}Click to read more{/tr}">{icon name="help" size=1}
					</a>
				</li><li>{tr}Simple_Forum{/tr}
					<a href="https://profiles.tiki.org/Simple_Forum" target="tikihelp" class="tikihelp" title="{tr}Simple_Forum:{/tr}
						{tr}Click to read more{/tr}">{icon name="help" size=1}
					</a>
				</li>
				<li>{tr}Timesheets_Tracker{/tr}
					<a href="https://profiles.tiki.org/Timesheets_Tracker" target="tikihelp" class="tikihelp" title="{tr}Timesheets_Tracker:{/tr}
						{tr}Click to read more{/tr}">{icon name="help" size=1}
					</a>
				</li>
				<li>{tr}User_Profile_Business{/tr}
					<a href="https://profiles.tiki.org/User_Profile_Business" target="tikihelp" class="tikihelp" title="{tr}User_Profile_Business:{/tr}
						{tr}Click to read more{/tr}">{icon name="help" size=1}
					</a>
				</li>
			</ul>
		</fieldset>
		<i>{tr}See the full list of changes.{/tr}</i>
		<a href="https://doc.tiki.org/Tiki18" target="tikihelp" class="tikihelp" title="{tr}Tiki18:{/tr}
			{tr}Click to read more{/tr}
		">
			{icon name="help" size=1}
		</a>
	</div>
</div>
