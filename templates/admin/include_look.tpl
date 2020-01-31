{* $Id$ *}
<form action="tiki-admin.php?page=look" id="look" name="look" class="labelColumns admin" method="post">
	{ticket}
	<div class="t_navbar mb-4 clearfix">
		{if $prefs.feature_theme_control eq y}
			{button _text="{tr}Theme Control{/tr}" href="tiki-theme_control.php" _class="btn-sm btn-link tikihelp" _icon_name="file-image-o"}
		{/if}
		{if $prefs.feature_editcss eq 'y' and $tiki_p_create_css eq 'y'}
			{button _text="{tr}Edit CSS{/tr}" _class="btn-sm" href="tiki-edit_css.php"}
		{/if}
		{include file='admin/include_apply_top.tpl'}
	</div>
	{tabset name="admin_look"}
		{tab name="{tr}Theme{/tr}"}
			<br>
			<legend>{tr}Main theme{/tr}</legend>
			<div class="row">
				<div class="col-md-8 adminoptionbox">
					{preference name=theme}
					<div class="adminoptionbox theme_childcontainer custom_url">
						{preference name=theme_custom_url}
					</div>
					{preference name=theme_option}
					{preference name=theme_option_includes_main}
					{preference name=theme_navbar_color_variant}
					{preference name=theme_navbar_fixed_topbar_offset}
				</div>
				<div class="col-md-4">
					<div class="card">
						<div class="card-body text-center">
						{if $thumbfile}
							<img src="{$thumbfile}" class="img-fluid" alt="{tr}Theme Screenshot{/tr}" id="theme_thumb">
						{else}
							<span>{icon name="image"}</span>
						{/if}
						</div>
					</div>
				</div>
			</div>
			{preference name=change_theme}
			<div class="adminoptionboxchild" id="change_theme_childcontainer">
				{preference name=available_themes}
			</div>

			{* Fixed width is the first decision to make, so it needs to be visibly on the first tab. But its logical place is in the "layout" tab.
					So we we put it twice. But its not possible to "just" put it twice, hence following hack.
					If this hack is required in more places, we can add an opton to "preference" plugin
			*}
			<div class="adminoptionbox preference clearfix basic feature_fixed_width all" style="">
				<div class="adminoption form-group row">
					<label class="col-sm-4">
						Fixed width
					</label>
					<div class="col-sm-8">
						<div class="form-check">
							<input id="dummy_pref-25" class="form-check-input" type="checkbox" name="dummy_feature_fixed_width" {if $prefs.feature_fixed_width eq 'y'} checked="checked"{/if} >
							<a class="tikihelp text-info" title="Fixed width:Restrict the width of the site content area, in contrast to a liquid (full-width) layout." >
								<span class="icon icon-information fas fa-info-circle "></span>
							</a>
						</div>
					</div>
				</div>
			</div>
			{jq}
				$('input[name=dummy_feature_fixed_width]').click(function(){
					/* get value of dummy_feature_fixed_width */
					var dummy = $(this).is(":checked");
					/* Synchronize with dummy checkbox and trigger display of child option */
					$('input[name=feature_fixed_width]').prop('checked', dummy).change();
				});

				$('input[name=feature_fixed_width]').click(function(){
					/* get value of feature_fixed_width */
					var real = $(this).is(":checked");
					/* Synchronize with dummy checkbox display */
					$('input[name=dummy_feature_fixed_width]').prop('checked', real);
				});
			{/jq}
			{preference name=useGroupTheme}
			<hr>

			<legend>{tr}Admin theme{/tr}</legend>
			<div class="adminoptionbox">
				{preference name=theme_admin}
				{preference name=theme_option_admin}
			</div>
			<hr>

			<legend>{tr}Other{/tr}</legend>
			{preference name=theme_iconset}
			{if $prefs.javascript_enabled eq 'n' or $prefs.feature_jquery eq 'n'}
				{* TODO I don't see where this is used in in admin/include_look.php *}
				<input type="submit" class="btn btn-primary btn-sm" name="changestyle" value="{tr}Go{/tr}">
			{/if}
			<div class="adminoptionbox">
				{if $prefs.feature_jquery_ui eq 'y'}
					{preference name=feature_jquery_ui_theme}
				{/if}
			</div>
			{preference name=feature_theme_control}
			<div class="adminoptionboxchild" id="feature_theme_control_childcontainer">
				{preference name=feature_theme_control_savesession}
				{preference name=feature_theme_control_parentcategory}
				{preference name=feature_theme_control_autocategorize}
			</div>
			<hr>
			<legend>{tr}Theme Preferences{/tr}</legend>
			<div class="adminoptionboxchild">
				{foreach key=theme item=preferences from=$themePrefs}
					<fieldset>
						<legend>{tr}{{$theme|escape|ucfirst}}{/tr}</legend>
						{foreach $preferences as $pref}
							{preference name="{$pref|escape}"}
						{/foreach}
					</fieldset>
				{foreachelse}
					{tr}No theme preferences found.{/tr}
				{/foreach}
			</div>
			<hr>
		{/tab}
		{tab name="{tr}Layout{/tr}"}
			<br>
			<legend>{tr}General layout{/tr}</legend>
			{preference name=feature_fixed_width}
			<div class="adminoptionboxchild" id="feature_fixed_width_childcontainer">
				{preference name=layout_fixed_width}
			</div>
			{preference name=site_layout}
			{preference name=site_layout_per_object}

			<legend>{tr}Admin pages layout{/tr} (<small>{tr}Admin theme must be selected first{/tr}</small>)</legend>
			{preference name=site_layout_admin}
			
			<!--legend>{tr}Fixed vs full width layout{/tr}</legend-->
			<hr>
			
			<legend>{tr}Logo and Title{/tr}</legend>
			{preference name=feature_sitelogo}
			<div class="adminoptionboxchild" id="feature_sitelogo_childcontainer">
				<fieldset>
					<legend>{tr}Logo{/tr}</legend>
					{preference name=sitelogo_src}
					{preference name=sitelogo_icon}
					{preference name=sitelogo_bgcolor}
					{preference name=sitelogo_title}
					{preference name=sitelogo_alt}
				</fieldset>
				<fieldset>
					<legend>{tr}Title{/tr}</legend>
					{preference name=sitetitle}
					{preference name=sitesubtitle}
				</fieldset>
			</div>
			<hr>

			<div class="adminoptionbox">
				<fieldset>
					<legend>{tr}Module zone visibility{/tr}</legend>
					{preference name=module_zones_top}
					{preference name=module_zones_topbar}
					{preference name=module_zones_pagetop}
					{preference name=feature_left_column}
					{preference name=feature_right_column}
					{preference name=module_zones_pagebottom}
					{preference name=module_zones_bottom}
					<br>
					{preference name=module_file}
					{preference name=module_zone_available_extra}
				</fieldset>
			</div>
			<hr>

			<div class="adminoptionbox">
				<fieldset>
					<legend>{tr}Site report bar{/tr}</legend>
					{preference name=feature_site_report}
					{preference name=feature_site_report_email}
				</fieldset>
			</div>
			<hr>
		{/tab}
		{if $prefs.site_layout eq 'classic'}
			{tab name="{tr}Shadow layer{/tr}"}
				<br>
				<legend>{tr}Shadow layer{/tr}</legend>
				{preference name=feature_layoutshadows}
				<div class="adminoptionboxchild" id="feature_layoutshadows_childcontainer">
					{preference name=main_shadow_start}
					{preference name=main_shadow_end}
					{preference name=header_shadow_start}
					{preference name=header_shadow_end}
					{preference name=middle_shadow_start}
					{preference name=middle_shadow_end}
					{preference name=center_shadow_start}
					{preference name=center_shadow_end}
					{preference name=footer_shadow_start}
					{preference name=footer_shadow_end}
					{preference name=box_shadow_start}
					{preference name=box_shadow_end}
				</div>
				<hr>
			{/tab}
		{/if}
		{tab name="{tr}Pagination{/tr}"}
			<br>
			<legend>{tr}Pagination{/tr}</legend>
			{preference name=nextprev_pagination}
			{preference name=direct_pagination}
			<div class="adminoptionboxchild" id="direct_pagination_childcontainer">
				{preference name=direct_pagination_max_middle_links}
				{preference name=direct_pagination_max_ending_links}
			</div>
			{preference name=pagination_firstlast}
			{preference name=pagination_fastmove_links}
			{preference name=pagination_hide_if_one_page}
			{preference name=pagination_icons}

			<legend>{tr}Limits{/tr}</legend>
			{preference name=user_selector_threshold}
			{preference name=maxRecords}
			{preference name=tiki_object_selector_threshold}
			{preference name=tiki_object_selector_searchfield}
		{preference name=comments_per_page}
			<hr>
		{/tab}
		{tab name="{tr}UI Effects{/tr}"}
			<br>
			<div class="adminoptionbox">
				<fieldset class="mb-3 w-100">
					<legend>{tr}Standard UI effects{/tr}</legend>
					{preference name=jquery_effect}
					{preference name=jquery_effect_speed}
					{preference name=jquery_effect_direction}
				</fieldset>
			</div>
			<div class="adminoptionbox">
				<fieldset class="mb-3 w-100">
					<legend>{tr}Tab UI effects{/tr}</legend>
					{preference name=jquery_effect_tabs}
					{preference name=jquery_effect_tabs_speed}
					{preference name=jquery_effect_tabs_direction}
				</fieldset>
			</div>
			<hr>

			<fieldset>
				<legend>{tr}Other{/tr}</legend>
				<div class="admin featurelist">
					{preference name=feature_shadowbox}
					<div class="adminoptionboxchild" id="feature_shadowbox_childcontainer">
						{preference name=jquery_colorbox_theme}
					</div>
					{preference name=feature_jscalendar}
					{preference name=wiki_heading_links}
					{preference name=feature_equal_height_rows_js}
					{preference name=feature_conditional_formatting}
					{preference name=jquery_ui_modals_draggable}
					{preference name=jquery_ui_modals_resizable}
				</div>
			</fieldset>
			<hr>
		{/tab}
		{tab name="{tr}Customization{/tr}"}
			<br>
			<fieldset>
				<legend>{tr}Custom codes{/tr}</legend>
				{preference name="header_custom_css" syntax="css"}
				{preference name="header_custom_less" syntax="css"}
				{preference name=feature_custom_html_head_content syntax="htmlmixed"}
				{preference name=feature_endbody_code syntax="tiki"}
				{preference name=site_google_analytics_account}
				{preference name="header_custom_js" syntax="javascript"}
				{preference name="layout_add_body_group_class"}
				{preference name=categories_add_class_to_body_tag}
			</fieldset>
			<hr>

			<fieldset>
				<legend>{tr}Editing{/tr}</legend>
				{preference name=theme_customizer}
				{preference name=feature_editcss}
				{preference name=feature_view_tpl}
				{if $prefs.feature_view_tpl eq 'y'}
					<div class="adminoptionboxchild">
						{button href="tiki-edit_templates.php" _text="{tr}View Templates{/tr}"}
					</div>
				{/if}
				{preference name=feature_edit_templates}
				{if $prefs.feature_edit_templates eq 'y'}
					<div class="adminoptionboxchild">
						{button href="tiki-edit_templates.php" _text="{tr}Edit Templates{/tr}"}
					</div>
				{/if}
			</fieldset>
			<hr>
		{/tab}
		{tab name="{tr}Miscellaneous{/tr}"}
			<br>
			<fieldset class="adminoptionbox">
				<legend>{tr}Tabs{/tr}</legend>
				{preference name=feature_tabs}
				<div class="adminoptionboxchild" id="feature_tabs_childcontainer">
					{preference name=layout_tabs_optional}
				</div>
			</fieldset>
			<hr>
			
			<fieldset class="adminoptionbox">
				<legend>{tr}Favicons{/tr}</legend>
				{preference name=site_favicon_enable}
			</fieldset>
			<hr>
			
			<fieldset class="adminoptionbox">
				<legend>{tr}Responsive images{/tr}</legend>
				{preference name=image_responsive_class}
			</fieldset>
			<hr>
			
			<div class="adminoptionbox">
				<fieldset class="mb-3 w-100">
					<legend>{tr}Context menus{/tr} (<small>{tr}currently used in file galleries only{/tr}</small>)</legend>
					{preference name=use_context_menu_icon}
					{preference name=use_context_menu_text}
				</fieldset>
			</div>
			<hr>
			
			<fieldset>
				<legend>{tr}Separators{/tr}</legend>
				{preference name=site_crumb_seper}
				<div class="adminoptionboxchild clearfix">
					<span class="col-md-8 offset-md-4 form-text">{tr}Examples:{/tr} &nbsp; » &nbsp; / &nbsp; &gt; &nbsp; : &nbsp; -> &nbsp; →</span>
				</div>
				{preference name=site_nav_seper}
				<div class="adminoptionboxchild clearfix">
					<span class="col-md-8 offset-md-4 form-text">{tr}Examples:{/tr} &nbsp; | &nbsp; / &nbsp; ¦ &nbsp; :</span>
				</div>
			</fieldset>
			<hr>

			<legend>{tr}Smarty templates (TPL files){/tr}</legend>
			{preference name=log_tpl}
			{preference name=smarty_compilation}
			{preference name=smarty_cache_perms}
			{preference name=categories_used_in_tpl}
			{preference name=feature_html_head_base_tag}
			<hr>
		{/tab}
	{/tabset}
	{include file='admin/include_apply_bottom.tpl'}
</form>
