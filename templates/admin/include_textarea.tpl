{* $Id$ *}
{remarksbox type="tip" title="{tr}Tip{/tr}"}
	{tr}Text area (that apply throughout many features){/tr}
{/remarksbox}

<form action="tiki-admin.php?page=textarea" method="post" class="admin">
	{ticket}

	<div class="row">
		<div class="form-group col-lg-12 clearfix">
			{include file='admin/include_apply_top.tpl'}
		</div>
	</div>

	{tabset name="admin_textarea"}
		{tab name="{tr}General Settings{/tr}"}
			<br>
			<fieldset>
				<legend>{tr}Features{/tr}{help url="Text+Area"}</legend>
				{preference name=feature_fullscreen}
				{preference name=feature_filegals_manager}
				{preference name=feature_dynamic_content}
				{preference name=feature_wiki_replace}
				{preference name=feature_syntax_highlighter}
				<div class="adminoptionboxchild" id="feature_syntax_highlighter_childcontainer">
					{preference name=feature_syntax_highlighter_theme}
				</div>
				{preference name=feature_wysiwyg}
				{preference name=ajax_autosave}
			</fieldset>

			<fieldset>
				<legend>{tr}Wiki syntax{/tr}{help url="Wiki-syntax"}</legend>
				{preference name=feature_smileys}
				{preference name=feature_wiki_paragraph_formatting}
				<div class="adminoptionboxchild" id="feature_wiki_paragraph_formatting_childcontainer">
					{preference name=feature_wiki_paragraph_formatting_add_br}
				</div>
				{preference name=section_comments_parse}
				{preference name=feature_wiki_monosp}
				{preference name=feature_wiki_tables}
				{preference name=feature_wiki_argvariable}
				{preference name=wiki_dynvar_style}
				{preference name=wiki_dynvar_multilingual}
				{preference name=wiki_make_ordered_list_items_display_unique_numbers}
				{preference name=feature_absolute_to_relative_links}
			</fieldset>

			<fieldset>
				<legend>{tr}Typography{/tr}</legend>
				{preference name=feature_typo_enable}
				<div class="adminoptionboxchild" id="feature_typo_enable_childcontainer">
					{preference name=feature_typo_quotes}
					{preference name=feature_typo_approximative_quotes}
					{preference name=feature_typo_dashes_and_ellipses}
					{preference name=feature_typo_nobreak_spaces}
				</div>
			</fieldset>

			<fieldset class="mb-4 featurelist">
				<legend>{tr}Plugins{/tr}</legend>
				{preference name=wikiplugin_showreference}
				{preference name=wikiplugin_addreference}
				{preference name=wikiplugin_alink}
				{preference name=wikiplugin_aname}
				{preference name=wikiplugin_box}
				{preference name=wikiplugin_button}
				{preference name=wikiplugin_center}
				{preference name=wikiplugin_code}
				{preference name=wikiplugin_countdown}
				{preference name=wikiplugin_div}
				{preference name=wikiplugin_dl}
				{preference name=wikiplugin_fade}
				{preference name=wikiplugin_fancylist}
				{preference name=wikiplugin_fancytable}
				{preference name=wikiplugin_font}
				{preference name=wikiplugin_footnote}
				{preference name=wikiplugin_footnotearea}
				{preference name=wikiplugin_gauge}
				{preference name=wikiplugin_html}
				{preference name=wikiplugin_iframe}
				{preference name=wikiplugin_include}
				{preference name=wikiplugin_mono}
				{preference name=wikiplugin_mouseover}
				{preference name=wikiplugin_mwtable}
				{preference name=wikiplugin_now}
				{preference name=wikiplugin_quote}
				{preference name=wikiplugin_remarksbox}
				{preference name=wikiplugin_scroll}
				{preference name=wikiplugin_slider}
				{preference name=wikiplugin_sort}
				{preference name=wikiplugin_split}
				{preference name=wikiplugin_sup}
				{preference name=wikiplugin_sub}
				{preference name=wikiplugin_tabs}
				{preference name=wikiplugin_tag}
				{preference name=wikiplugin_toc}
				{preference name=wikiplugin_versions}
				{preference name=wikiplugin_showpref}
				{preference name=wikiplugin_casperjs}
			</fieldset>

			<fieldset>
				<legend>{tr}Miscellaneous{/tr}</legend>
				{preference name=feature_purifier}
				{preference name=feature_autolinks}
				{preference name=feature_hotwords}
				<div class="adminoptionboxchild" id="feature_hotwords_childcontainer">
					{preference name=feature_hotwords_nw}
					{preference name=feature_hotwords_sep}
				</div>
				{preference name=feature_use_quoteplugin}
				{preference name=feature_use_three_colon_centertag}
				{preference name=feature_simplebox_delim}
				{preference name=mail_template_custom_text}
			</fieldset>

			<fieldset>
				<legend>{tr}Default size{/tr}</legend>
				{preference name=default_rows_textarea_wiki}
				{preference name=default_rows_textarea_comment}
				{preference name=default_rows_textarea_forum}
				{preference name=default_rows_textarea_forumthread}
			</fieldset>

			<fieldset>
				<legend>{tr}External links and images{/tr}</legend>
				{preference name=cachepages}
				{preference name=cacheimages}
				{preference name=feature_wiki_ext_icon}
				{preference name=feature_wiki_ext_rel_nofollow}
				{preference name=popupLinks}
				{remarksbox type='tip' title="{tr}Tip{/tr}"}
					{tr}External links will be identified with:{/tr} {icon name="link-external"}
				{/remarksbox}
				{preference name=allowImageLazyLoad}
			</fieldset>
		{/tab}

		{tab name="{tr}Plugins{/tr}"}
			<br>
			{remarksbox type="note" title="{tr}About plugins{/tr}"}{tr}Tiki plugins add functionality to wiki pages, articles, blogs, and so on. You can enable and disable them below.{/tr}
			{tr}You can approve plugin use at <a href="tiki-plugins.php" class="alert-link">tiki-plugins.php</a>.{/tr}
			{tr}The edit-plugin icon is an easy way for users to edit the parameters of each plugin in wiki pages. It can be disabled for individual plugins below.{/tr}
			{/remarksbox}
			{if !isset($disabled)}
				{button href="?page=textarea&disabled=y" _text="{tr}Check disabled plugins used in wiki pages{/tr}"}
				<br><br>
			{else}
				{remarksbox type=errors title="{tr}Disabled used plugins{/tr}"}
					{if empty($disabled)}
						{tr}None{/tr}
					{else}
						<ul>
						{foreach from="{$disabled}" item=plugin}
							<li>{$plugin|lower|escape}</li>
						{/foreach}
						</ul>
					{/if}
				{/remarksbox}
			{/if}

			<fieldset class="mb-5">
				<legend>{tr}Plugin preferences{/tr}</legend>
				{preference name=profile_autoapprove_wikiplugins}
				{preference name=wikipluginprefs_pending_notification}
				{preference name=image_responsive_class}
				{preference name=wikiplugin_maximum_passes}
			</fieldset>

			<fieldset class="mb-5">
				<legend>{tr}Edit plugin icons{/tr}</legend>
				{preference name=wiki_edit_plugin}
				<div class="adminoptionboxchild" id="wiki_edit_plugin_childcontainer">
					{preference name=wiki_edit_icons_toggle}
				</div>
				{preference name=wikiplugin_list_gui}
				{preference name=wikiplugin_list_convert_trackerlist}
			</fieldset>

			<fieldset class="mb-5" id="plugins">
				<legend>{tr}Plugins{/tr}</legend>
				<fieldset class="mb-5 donthide">
					{preference name='unified_search_textarea_admin'}
					{if $prefs.unified_search_textarea_admin eq 'y'}
						<label for="pluginfilter" class="col-sm-4 col-form-label">{tr}Filter:{/tr}</label>
						<div class="col-sm-8">
							<input type="text" id="pluginfilter" class="form-control">
						</div>
					{else}
						{listfilter selectors='#plugins > fieldset' exclude=".donthide"}
					{/if}
				</fieldset>
				<div id="pluginlist">
				{if $prefs.unified_search_textarea_admin eq 'y'}
					{remarksbox type='tip' title='{tr}Plugin List{/tr}'}
						{tr}Use the filter input above to find plugins, or enter return to see the whole list{/tr}
						<a href="{bootstrap_modal controller=search action=help}" class="alert-link">{tr}Search Help{/tr} {icon name='help'}</a>
					{/remarksbox}
				{/if}
				</div>
				{if $prefs.unified_search_textarea_admin eq 'y'}<noscript>{/if}
					{foreach from=$plugins key=plugin item=info}
						<fieldset class="mb-5">
							<legend>
								{if $info.iconname}{icon name=$info.iconname}{else}{icon name='plugin'}{/if} {$info.name|escape}
							</legend>
							<div class="adminoptionbox">
								<strong>{$plugin|escape}</strong>: {$info.description|default:''|escape}
								{help url="Plugin$plugin"}
							</div>
							{assign var=pref value="wikiplugin_$plugin"}
							{if in_array( $pref, $info.prefs)}
								{assign var=pref value="wikiplugin_$plugin"}
								{assign var=pref_inline value="wikiplugininline_$plugin"}
								{preference name=$pref label="{tr}Enable{/tr}"}
								{preference name=$pref_inline label="{tr}Disable edit plugin icon (make plugin inline){/tr}"}
							{/if}
						</fieldset>
					{/foreach}
					{if $prefs.unified_search_textarea_admin eq 'y'}</noscript>{/if}
			</fieldset>
		{/tab}

		{tab name='{tr}Plugin Aliases{/tr}' key='plugin_alias'}
			<br>
			{remarksbox type="note" title="{tr}About plugin aliases{/tr}"}
				{tr}Tiki plugin aliases allow you to define your own custom configurations of existing plugins.{/tr}<br>
				{tr}Find out more here:{/tr}{help url="Plugin+Alias"}
			{/remarksbox}
			{if $prefs.javascript_enabled neq 'y'}
				{remarksbox type="tip" title="{tr}Tip{/tr}"}
					{tr}This page is designed to work with JavaScript{/tr}
				{/remarksbox}
			{/if}

			{tabset name='plugin_alias'}
				{tab name='{tr}Available alias{/tr}'}
					<fieldset id="pluginalias_available">
						<legend>
							<strong>{tr}Available alias{/tr}</strong>
						</legend>
						<div class="input_submit_container">
							<table class="table table-striped">
								<tr>
									<th></th>
									<th>{tr}Plugin Alias{/tr}</th>
									<th>{tr}Edit{/tr}</th>
									<th>{tr}Delete{/tr}</th>
								</tr>
								{foreach $plugins_alias as $name}
									<tr>
										<td>
											<input type="checkbox" name="enabled[]" value="{$name|escape}" {if $prefs['wikiplugin_'|cat:$name] eq 'y'}checked="checked"{/if}>
										</td>
										<td>
											<a href="tiki-admin.php?page=textarea&amp;plugin_alias={$name|escape}">{$name|escape}</a>
										</td>
										<td>
											{icon name='pencil' href='tiki-admin.php?page=textarea&plugin_alias='|cat:$name|escape}
										</td>
										<td>
											{* TODO add confirmation *}
											<button type="submit" name="alias_delete" value="{$name|escape}" class="btn btn-link text-danger" style="cursor: pointer">
												{icon name='delete'}
											</button>
										</td>
									</tr>
								{foreachelse}
									{norecords _colspan=4}
								{/foreach}
							</table>
							<div class="submit">
								{if  not empty($smarty.request.plugin_alias)}
									{button name='add' id='pluginalias_add' _text='Create' _class='btn btn-info' _script='tiki-admin.php?page=textarea&new_alias'}
								{/if}
								{if isset($smarty.request.new_alias)}
									{jq}$("a[href='#contentplugin_alias-2']").tab("show");{/jq}
								{/if}
								<input type="submit" class="btn btn-primary" name="enable" value="{tr}Enable Checked Plugins{/tr}">
							</div>
						</div>
					</fieldset>
					{jq}$('#pluginalias_available legend').trigger('click');{/jq}
				{/tab}
				{if not empty($smarty.request.plugin_alias)}
					{$tabname='Edit Alias'}
					{jq}$("a[href='#contentplugin_alias-2']").tab("show");{/jq}
				{else}
					{$tabname='Create Alias'}
					{if not isset($smarty.request.new_alias)}
						{jq}$("a[href='#contentplugin_alias-1']").tab("show");{/jq}
					{/if}
				{/if}
				{tab name=$tabname}
					<fieldset id="pluginalias_general">
						<legend>
							{tr}General information{/tr} 
						</legend>

						<div class="form-group row">
							<label class="col-form-label col-sm-4" for="plugin_alias">
								{tr}Plugin name{/tr}
							</label>
							<div class="col-sm-8">
								{if not empty($smarty.request.plugin_alias)}
									<input type="hidden" class="form-control" name="plugin_alias" id="plugin_alias" value="{$plugin_admin.plugin_name|escape}">
									<strong>{$plugin_admin.plugin_name|escape}</strong>
								{else}
									<input type="text" class="form-control" name="plugin_alias" id="plugin_alias">
								{/if}
							</div>
						</div><br>
						<div class="form-group row">
							<label class="col-form-label col-sm-4" for="implementation">
								{tr}Base plugin{/tr}
							</label>
							<div class="col-sm-8">
								<select class="form-control" name="implementation" id="implementation">
									<option></option>
									{foreach $plugins_real as $base}
										<option value="{$base|escape}" {if isset($plugin_admin.implementation) and $plugin_admin.implementation eq $base}selected="selected"{/if}>
											{$base|escape}
										</option>
									{/foreach}
								</select>
							</div>
						</div><br>
						<div class="form-group row">
							<label class="col-form-label col-sm-4" for="plugin_name">
								{tr}Name{/tr}
							</label>
							<div class="col-sm-8">
								<input class="form-control" type="text" name="name" id="plugin_name" value="{$plugin_admin.description.name|default:''|escape}">
							</div>
						</div><br>
						<div class="form-group row">
							<label class="col-form-label col-sm-4" for="plugin_description">
								{tr}Description{/tr}
							</label>
							<div class="col-sm-8">
								<input class="form-control" type="text" name="description" id="plugin_description" value="{$plugin_admin.description.description|default:''|escape}">
							</div>
						</div><br>
						<div class="form-group row">
							<label class="col-form-label col-sm-4" for="plugin_body">
								{tr}Body label{/tr}
							</label>
							<div class="col-sm-8">
								<input class="form-control" type="text" name="body" id="plugin_body" value="{$plugin_admin.description.body|default:''|escape}">
							</div>
						</div><br>
						<div class="form-group row">
							<label class="col-form-label col-sm-4" for="plugin_deps">
								{tr}Dependencies{/tr}
							</label>
							<div class="col-sm-8">
								<input class="form-control" type="text" name="prefs" id="plugin_deps" value="{if !empty($plugin_admin.description.prefs)}{','|implode:$plugin_admin.description.prefs}{/if}">
							</div>
						</div><br>
						<div class="form-group row">
							<label class="col-form-label col-sm-4" for="filter">
								{tr}Filter{/tr}
							</label>
							<div class="col-sm-8">
								<input class="form-control" type="text" id="filter" name="filter" value="{$plugin_admin.description.filter|default:'xss'|escape}">
							</div>
						</div><br>
						<div class="form-group row">
							<label class="col-form-label col-sm-4" for="validate">
								{tr}Validation{/tr}
							</label>
							<div class="col-sm-8">
								<select class="form-control" name="validate" id="validate">
									{foreach ['none','all','body','arguments'] as $val}
										<option value="{$val|escape}" {if !empty($plugin_admin.description.validate) and $plugin_admin.description.validate eq $val}selected="selected"{/if}>
											{$val|escape}
										</option>
									{/foreach}
								</select>
							</div>
						</div><br>
						<div class="form-group row">
							<label class="col-sm-4" for="inline">{tr}Inline (no plugin edit UI){/tr}</label>
							<div class="col-sm-8">
								<div class="form-check">
									<input class="form-check-input" type="checkbox" id="inline" name="inline" value="1" {if !empty($plugin_admin.description.inline)}checked="checked"{/if}>
								</div>
							</div>
						</div><br>
					</fieldset><br>

					<fieldset id="pluginalias_simple_args">
						<legend>
							{tr}Simple plugin arguments{/tr} {icon name="add" class='add-param text-success tips' title='|{tr}Add parameter{/tr}'}
						</legend>
						<div class="form-group row">
							<label class="col-form-label col-sm-6">
								{tr}Argument{/tr}
							</label>
							<label class="col-form-label col-sm-6">
								{tr}Default{/tr}
							</label>
						</div>
						{if !empty($plugin_admin.params)}
							{foreach $plugin_admin.params as $token => $value}
								{if not $value|is_array}
									<div class="form-group row param">
										<div class="col-sm-6">
											<input class="form-control sparam-name" type="text" name="sparams[{$token|escape}][token]" value="{$token|escape}">
										</div>
										<div class="col-sm-5">
											<input class="form-control sparam-default" type="text" name="sparams[{$token|escape}][default]" value="{$value|escape}">
										</div>
										<div class="col-sm-1">
											{icon name='delete' class='text-danger delete-param tips btn btn-link' title='|{tr}Delete this parameter{/tr}'}
										</div>
									</div>
								{elseif $token eq '__NEW__'}
									<div class="form-group row d-none param">
										<div class="col-sm-6">
											<input class="form-control sparam-name" type="text" name="sparams[__NEW__][token]" value="" placeholder="{tr}Name{/tr}">
										</div>
										<div class="col-sm-5">
											<input class="form-control sparam-default" type="text" name="sparams[__NEW__][default]" value="" placeholder="{tr}Default Value{/tr}">
										</div>
										<div class="col-sm-1">
											{icon name='delete' class='text-danger delete-param tips btn btn-link' title='|{tr}Delete this parameter{/tr}'}
										</div>
									</div>
								{/if}
							{/foreach}
						{/if}
					</fieldset>

					<fieldset id="pluginalias_doc">
						<legend>
							{tr}Plugin parameter documentation{/tr} {icon name="add" class='add-param text-success tips' title='|{tr}Add parameter documentation{/tr}'}
						</legend>

						{if !empty($plugin_admin.description.params)}
							{foreach $plugin_admin.description.params as $token => $detail}
								<div class="clearfix param{if $token eq '__NEW__'} d-none{/if}">
									<div class="form-group row">
										<label class="col-form-label col-sm-4" for="input[{$token|escape}][token]">
											{tr}Parameter{/tr}
										</label>
										<div class="col-sm-7">
											<input class="form-control" type="text" name="input[{$token|escape}][token]" id="input[{$token|escape}][token]" value="{if $token neq '__NEW__'}{$token|escape}{/if}">
										</div>
										<div class="col-sm-1">
											{icon name='delete' class='text-danger delete-param tips btn btn-link' title="|{tr}Delete this parameter's documentation{/tr}"}
										</div>
									</div>
									<div class="form-group row">
										<label class="col-form-label col-sm-4" for="input[{$token|escape}][name]">
											{tr}Name{/tr}
										</label>
										<div class="col-sm-8">
											<input class="form-control" type="text" name="input[{$token|escape}][name]" id="input[{$token|escape}][name]" value="{$detail.name|escape}">
										</div>
									</div>
									<div class="form-group row">
										<label class="col-form-label col-sm-4" for="input[{$token|escape}][description]">
											{tr}Description{/tr}
										</label>
										<div class="col-sm-8">
											<input class="form-control" type="text" name="input[{$token|escape}][description]" id="input[{$token|escape}][description]" value="{$detail.description|escape}">
										</div>
									</div>
									<div class="form-group row">
										<div class=" col-sm-2 offset-sm-4">
											<div class="form-check form-check-inline">
												<input class="form-check-input" type="checkbox" name="input[{$token|escape}][required]" id="input[{$token|escape}][required]" value="y"{if $detail.required} checked="checked"{/if}>
												<label class="col-form-label" for="input[{$token|escape}][required]">
													{tr}Required{/tr}
												</label>
											</div>
										</div>
										<div class=" col-sm-2">
											<div class="form-check form-check-inline col-sm-2">
												<input class="form-check-input" type="checkbox" name="input[{$token|escape}][safe]" id="input[{$token|escape}][safe]" value="y"{if $detail.safe} checked="checked"{/if}>
												<label class="col-form-label" for="input[{$token|escape}][safe]">
													{tr}Safe{/tr}
												</label>
											</div>
										</div>
										<label class="col-form-label col-sm-1" for="input[{$token|escape}][filter]">
											{tr}Filter{/tr}
										</label>
										<div class="col-sm-3">
											<input class="form-control" type="text" name="input[{$token|escape}][filter]" id="input[{$token|escape}][filter]" value="{$detail.filter|default:xss|escape}">
										</div>
									</div>
									<hr>
								</div>
							{/foreach}
						{/if}
					</fieldset><br>

					<div id="pluginalias_body">
						<fieldset>
							<legend>
								{tr}Plugin body{/tr} 
							</legend>

							<div class="form-group row">
								<label class="col-sm-4" for="ignorebody">
									{tr}Ignore user input{/tr}
								</label>
								<div class="col-sm-8">
									<div class="form-check">
										<input class="form-check-input" type="checkbox" name="ignorebody" id="ignorebody" value="y"{if !empty($plugin_admin.body.input) and $plugin_admin.body.input eq 'ignore'} checked="checked"{/if}/>
									</div>
								</div>
							</div>
							<div class=" form-group row">
								<label class="col-form-label col-sm-4" for="defaultbody">{tr}Default content{/tr}</label>
								<div class="col-sm-8">
									<textarea class="form-control" cols="60" rows="12" id="defaultbody" name="defaultbody">{$plugin_admin.body.default|default:''|escape}</textarea>
								</div>
							</div>
							<fieldset>
								<legend>
									{tr}Body Parameters{/tr} {icon name="add" class='add-param text-success tips' title='|{tr}Add body parameter{/tr}'}
								</legend>
								{foreach $plugin_admin.body.params as $token => $detail}
									<div class="clearfix param{if $token eq '__NEW__'} d-none{/if}">
										<div class="form-group row">
											<label class="col-form-label col-sm-6" for="bodyparam[{$token|escape}][token]">
												{tr}Parameter{/tr}
											</label>
											<div class="col-sm-5">
												<input class="form-control" type="text" name="bodyparam[{$token|escape}][token]" id="bodyparam[{$token|escape}][token]" value="{if $token neq '__NEW__'}{$token|escape}{/if}">
											</div>
											<div class="col-sm-1">
												{icon name='delete' class='text-danger delete-param tips btn btn-link' title="|{tr}Delete this body parameter{/tr}"}
											</div>
										</div>
										<div class="form-group row">
											<label class="col-form-label col-sm-6" for="bodyparam[{$token|escape}][encoding]">
												{tr}Encoding{/tr}
											</label>
											<div class="col-sm-6">
												<select class="form-control" name="bodyparam[{$token|escape}][encoding]" id="bodyparam[{$token|escape}][encoding]">
													{foreach ['none','html','url'] as $val}
														<option value="{$val|escape}" {if $detail.encoding eq $val}selected="selected"{/if}>
															{$val|escape}
														</option>
													{/foreach}
												</select>
											</div>
										</div>
										<div class="form-group row">
											<label class="col-form-label col-sm-6" for="bodyparam[{$token|escape}][input]">
												{tr}Argument source (if different){/tr}
											</label>
											<div class="col-sm-6">
												<input class="form-control" type="text" name="bodyparam[{$token|escape}][input]" id="bodyparam[{$token|escape}][input]" value="{$detail.input|escape}">
											</div>
										</div>
										<div class="form-group row">
											<label class="col-form-label col-sm-6" for="bodyparam[{$token|escape}][default]">
												{tr}Default value{/tr}
											</label>
											<div class="col-sm-6">
												<input class="form-control" type="text" name="bodyparam[{$token|escape}][default]" id="bodyparam[{$token|escape}][default]" value="{$detail.default|escape}">
											</div>
										</div>
										<hr>
									</div>
								{/foreach}
							</fieldset>
						</fieldset>
					</div><br><br>

					<fieldset id="pluginalias_composed_args">
						<legend>
							{tr}Composed plugin arguments{/tr} {icon name="add" class='add-param text-success tips' title='|{tr}Add composed parameter{/tr}'}
						</legend>

						{foreach $plugin_admin.params as $token => $detail}
							{if $detail|is_array}
								{if not isset($composed_args)}{$composed_args=true}{/if}
								<div class="clearfix param{if $token eq '__NEW__'} d-none{/if}">
									<div class="form-group row">
										<label class="col-form-label col-sm-4" for="cparams[{$token|escape}][token]">
											{tr}Parameter{/tr}
										</label>
										<div class="col-sm-7">
											<input class="form-control" type="text" name="cparams[{$token|escape}][token]" id="cparams[{$token|escape}][token]" value="{if $token neq '__NEW__'}{$token|escape}{/if}">
										</div>
										<div class="col-sm-1">
											{icon name='delete' class='text-danger delete-param tips btn btn-link' title="|{tr}Delete this composed argument{/tr}"}
										</div>
									</div>
									<div class="form-group row">
										<label class="col-form-label col-sm-4" for="cparams[{$token|escape}][pattern]">
											{tr}Pattern{/tr}
										</label>
										<div class="col-sm-8">
											<input class="form-control" type="text" name="cparams[{$token|escape}][pattern]" id="cparams[{$token|escape}][pattern]" value="{$detail.pattern|escape}">
										</div>
									</div>
									<fieldset class="ml-5">
										<legend class="h4">
											{tr}Composed parameters{/tr} {icon name="add" class='add-param text-success tips' title='|{tr}Add composed parameter{/tr}'}
										</legend>
										{foreach $detail.params as $t => $d}
											<div class="clearfix param{if $t eq '__NEW__'} d-none{/if}">
												<div class="form-group row">
													<label class="col-form-label col-sm-6" for="cparams[{$token|escape}][params][{$t|escape}][token]">
														{tr}Parameter{/tr}
													</label>
													<div class="col-sm-5">
														<input class="form-control" type="text" name="cparams[{$token|escape}][params][{$t|escape}][token]" id="cparams[{$token|escape}][params][{$t|escape}][token]" value="{if $t neq '__NEW__'}{$t|escape}{/if}">
													</div>
													<div class="col-sm-1">
														{icon name='delete' class='text-danger delete-param tips btn btn-link' title="|{tr}Delete this composed parameter{/tr}"}
													</div>
												</div>
												<div class="form-group row">
													<label class="col-form-label col-sm-6" for="cparams[{$token|escape}][pattern]">
														{tr}Encoding{/tr}
													</label>
													<div class="col-sm-6">
														<select class="form-control" name="cparams[{$token|escape}][params][{$t|escape}][encoding]" id="cparams[{$token|escape}][pattern]">
															{foreach ['none','html','url'] as $val}
																<option value="{$val|escape}" {if $d.encoding eq $val}selected="selected"{/if}>{$val|escape}</option>
															{/foreach}
														</select>
													</div>
												</div>
												<div class="form-group row">
													<label class="col-form-label col-sm-6" for="cparams[{$token|escape}][params][{$t|escape}][input]">
														{tr}Argument source (if different):{/tr}
													</label>
													<div class="col-sm-6">
														<input class="form-control" type="text" name="cparams[{$token|escape}][params][{$t|escape}][input]" id="cparams[{$token|escape}][params][{$t|escape}][input]" value="{$d.input|escape}"/>
													</div>
												</div>
												<div class="form-group row">
													<label class="col-form-label col-sm-6" for="cparams[{$token|escape}][params][{$t|escape}][input]">
														{tr}Default value{/tr}
													</label>
													<div class="col-sm-6">
														<input class="form-control" type="text" name="cparams[{$token|escape}][params][{$t|escape}][default]" id="cparams[{$token|escape}][params][{$t|escape}][input]" value="{$d.default|escape}"/>
													</div>
												</div>
												<hr>
											</div>
										{/foreach}
									</fieldset>
								</div>
							{/if}
						{/foreach}
					</fieldset>
				{/tab}
			{/tabset}
		{/tab}
	{/tabset}
	{include file='admin/include_apply_bottom.tpl'}
</form>
