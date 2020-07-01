{* $Id$ *}

{if $prefs.feature_search_stats eq 'y'}
	{remarksbox type="tip" title="{tr}Tip{/tr}"}
		{tr}Search statistics{/tr} {tr}can be seen on page{/tr} <a class='alert-link' target='tikihelp' href='tiki-search_stats.php'>{tr}Search statistics{/tr}</a> {tr}in Admin menu{/tr}
	{/remarksbox}
{/if}

{if $prefs.feature_file_galleries eq 'y'}
	{remarksbox type="tip" title="{tr}Tip{/tr}"}
    	{tr}To index content from files within the File Galleries see the Search Indexing tab:{/tr} <a class='alert-link' target='tikihelp' href='tiki-admin.php?page=fgal'>{tr}File Gallery admin panel{/tr}</a>
	{/remarksbox}
{/if}


<form action="tiki-admin.php?page=search" method="post" class="admin">
	{ticket}

	<div class="row">
		<div class="form-group col-lg-12 clearfix">
			{if $prefs.feature_search eq 'y'}
				<a role="link" href="tiki-searchindex.php" class="btn btn-link">{icon name="search"} {tr}Search{/tr}</a>
				<a role="link" href="{bootstrap_modal controller=search action=rebuild}" class="btn btn-primary">{icon name="cog"} {tr}Rebuild Index{/tr}</a>
			{/if}
			{include file='admin/include_apply_top.tpl'}
		</div>
	</div>
	{tabset name=admin_search}
		{tab name="{tr}General Settings{/tr}"}
			<br>

			<fieldset>
				<legend>
					{tr}Search{/tr}{help url="Search-General-Settings"}
				</legend>
				{remarksbox type=tip title="{tr}About the Unified Index{/tr}"}
				{tr}The Unified Index provides many underlying features for Tiki, including object selectors for translations amongst other things.{/tr}
				{tr}Disabling this will cause some parts of Tiki to be unavailable.{/tr}<br>
					<a href="http://doc.tiki.org/Unified+Index" class="alert-link">{tr}Find out more about it here.{/tr}</a>
				{/remarksbox}

				{preference name=feature_search visible="always"}
				<div class="adminoptionboxchild" id="feature_search_childcontainer">

					{preference name=feature_search_stats}
					{preference name=user_in_search_result}
					{preference name="unified_incremental_update"}

					{preference name="allocate_memory_unified_rebuild"}
					{preference name="allocate_time_unified_rebuild"}

					{preference name="unified_engine"}

					{if ! empty($engine_info)}

						<div class="adminoptionbox preference advanced">
							<ul>
								{foreach from=$engine_info key=property item=value}
									<li><strong>{$property|escape}:</strong> {$value|escape}</li>
								{/foreach}
							</ul>
						</div>
					{/if}

					<div class="adminoptionbox preference advanced">{* pretend this remarks box is an advanced pref so it only shows when advanced irefs are enabled *}
						{remarksbox type=tip title="{tr}About Unified search engines{/tr}"}
							<b>{tr}MySQL full-text search{/tr}: </b><br>
							{tr}Advantages{/tr}: {tr}Fast performance. Works out of the box with Tiki and even on most basic server setups{/tr}.<br>
							{tr}Disadvantages{/tr}: {tr}Many common words (such as "first", "second", and "third" are not searchable unless MySQL configuration is modified). Only the first 65,535 characters (about 8000 words) of long pieces of content are searchable{/tr}(See this <a class='alert-link' href='http://dev.mysql.com/doc/refman/5.7/en/fulltext-stopwords.html'>{tr}link{/tr}</a> {tr} for full list) {/tr}<br>
							<b>{tr}Elasticsearch{/tr}: </b><br>
							{tr}Advantages{/tr}: {tr}Most advanced, fast and scalable search engine. Enables some very advanced/new features of Tiki{/tr}.<br>
							{tr}Disadvantages{/tr}: {tr}Needs to be separately installed from Tiki and requires more configuration{/tr} (See this <a class='alert-link' href='http://doc.tiki.org/Elasticsearch'>{tr}link{/tr}</a> {tr}for more information) {/tr}<br>
						{/remarksbox}
					</div>

					<div class="adminoptionboxchild unified_engine_childcontainer elastic">
						{preference name="unified_elastic_url"}
						{preference name="unified_elastic_index_prefix"}
						{preference name="unified_elastic_index_current"}
						{preference name="unified_elastic_field_limit"}
						{preference name="unified_relation_object_indexing"}
						{remarksbox type=tip title="{tr}About Use MySQL Full-Text Search as fallback{/tr}"}
							{tr}Elasticsearch is a tiki external service. You should set at least a daily full index rebuild to keep the MySQL index updated in case of Elastic unavailability{/tr}.
						{/remarksbox}
						{preference name="unified_elastic_mysql_search_fallback"}
					</div>

					<div class="adminoptionboxchild unified_engine_childcontainer mysql">
						{preference name="unified_mysql_short_field_names"}
						{preference name="unified_mysql_restore_indexes"}
					</div>

					{preference name="unified_search_default_operator"}
					{preference name=unified_excluded_categories}
					{preference name=unified_excluded_plugins}

					{preference name=unified_exclude_all_plugins}
					<div class="adminoptionboxchild" id="unified_exclude_all_plugins_childcontainer">
						{preference name=unified_included_plugins}
					</div>

					{preference name=unified_exclude_nonsearchable_fields}
					{preference name=unified_forum_deepindexing}

					{preference name=unified_tokenize_version_numbers}
					<div class="adminoptionboxchild unified_engine_childcontainer elastic">
						<p class="description offset-sm-4">{tr}Elastic search only{/tr}</p>
						{preference name="unified_elastic_camel_case"}
						{preference name="unified_elastic_possessive_stemmer"}
					</div>

					{preference name=unified_field_weight}
					{preference name=unified_default_content}

					{preference name=unified_user_cache}
					{preference name=unified_cache_formatted_result}
					{preference name=unified_cached_formatters}
					{preference name=unified_list_cache_default_on}
					{preference name=unified_list_cache_default_expiry}

					{preference name=unified_trackerfield_keys}
					{preference name=unified_trackeritem_category_names}
					{preference name=unified_add_to_categ_search}
					{preference name=unified_trim_sorted_search}

					{preference name=search_error_missing_field}

					{preference name=unified_stopwords}

					<div class="adminoptionbox preference advanced">{* pretend this remarks box is an advanced pref so it only shows when advanced irefs are enabled *}
						{remarksbox type=tip title="{tr}Experiment with LIST plugin syntax{/tr}"}
							<a href="tiki-pluginlist_experiment.php" class="alert-link">{tr}After you have found the correct contents, you may copy-paste them in a LIST plugin.{/tr}</a>
						{/remarksbox}
					</div>
                    {preference name=search_index_outdated}
				</div>
			</fieldset>

        {if $prefs.feature_file_galleries eq 'y'}
			<fieldset>
				<legend>{tr}File galleries searches{/tr}{help url="Search-within-files" desc='You will need to rebuild the search index to see these changes'}</legend>
                {preference name=fgal_enable_auto_indexing}
                {preference name=fgal_enable_email_indexing}
                {preference name=fgal_asynchronous_indexing}
				<div class="adminoptionboxchild">
					<fieldset>
						<legend>{tr}Handlers{/tr}{help url="Search-within-files" desc='If you want the content of the files which are in the File Gallery to be accessible by a search, and if you have a script that extracts the file content into a text, you can associate the script to the Mime type and the files content will be indexed.'}</legend>
						<div class="adminoptionbox">
							<div class="adminoptionlabel">{tr}Add custom handlers to make your files &quot;searchable&quot; content{/tr}.
								<ul>
									<li>
                                        {tr}Use <strong>%1</strong> as the internal file name. For example, use <strong>strings %1</strong> to convert the document to text, using the Unix <strong>strings</strong> command{/tr}.
									</li>
									<li>
                                        {tr}To delete a handler, leave the <strong>System Command</strong> field blank{/tr}.
									</li>
								</ul>
							</div>
						</div>

                        {if !empty($missingHandlers)}
                            {tr}Tiki is pre-configured to handle many common types. If any of those are listed here, it is because the command line tool is unavailable.{/tr}
                            {remarksbox type=warning title="{tr}Missing Handlers{/tr}"}
                            {foreach from=$missingHandlers item=mime}
                                {$mime|escape}
								<br>
                            {/foreach}
                            {/remarksbox}
                            {if $vnd_ms_files_exist}
								<div class="adminoptionbox">
                                    {remarksbox type=info title="{tr}Mime Types{/tr}"}
										<p>
                                            {tr}Previous versions of Tiki may have assigned alternative mime-types to Microsoft Office files, such as "application/vnd.ms-word" and these need to be changed to be "application/msword" for the default file indexing to function properly.{/tr}
										</p>
										<input
												type="submit"
												class="btn btn-primary btn-sm"
												name="filegalfixvndmsfiles"
												value="{tr}Fix vnd.ms-* mime type files{/tr}"
										/>
                                    {/remarksbox}
								</div>
                            {/if}
                        {/if}

						<div class="adminoptionbox">
							<div class="adminoptionlabel">
								<div class="table-responsive">
									<table class="table">
										<thead>
										<tr>
											<th>{tr}MIME Type{/tr}</th>
											<th>{tr}System Command{/tr}</th>
										</tr>
										</thead>
										<tbody>
                                        {foreach key=mime item=cmd from=$fgal_handlers}
											<tr>
												<td>{$mime}</td>
												<td>
													<input name="mimes[{$mime}]" class="form-control" type="text" value="{$cmd|escape:html}" />
												</td>
											</tr>
                                        {/foreach}
										<tr>
											<td class="odd">
												<input name="newMime" type="text" class="form-control" />
											</td>
											<td class="odd">
												<input name="newCmd" type="text" class="form-control" />
											</td>
										</tr>
										</tbody>
									</table>
								</div>
							</div>
						</div>
					</fieldset>

					<div class="adminoptionbox">
						<div class="adminoptionlabel">
							<div align="center">
								<input
										type="submit"
										class="btn btn-primary btn-sm"
										name="filegalredosearch"
										value="{tr}Reindex all files for search{/tr}"
								>
							</div>
						</div>
					</div>
				</div>
			</fieldset>
        {/if}

			<fieldset>
				<legend>
					{tr}Legacy search{/tr} {help url="Search"}
				</legend>
				{preference name=feature_search_fulltext}
				<div class="adminoptionboxchild" id="feature_search_fulltext_childcontainer">
					{preference name=feature_referer_highlight}

					{preference name=feature_search_show_forbidden_obj}
					{preference name=feature_search_show_forbidden_cat}
				</div>
			</fieldset>

			<fieldset>
				<legend>{tr}Features{/tr}</legend>
				{preference name=search_autocomplete}
				{preference name=search_file_thumbnail_preview}
			</fieldset>

			<fieldset>
				<legend>{tr}Forum searches{/tr}</legend>
				{preference name=feature_forums_name_search}
				{preference name=feature_forums_search}
				{preference name=feature_forum_content_search}
				<div class="adminoptionboxchild" id="feature_forum_content_search_childcontainer">
					{preference name=feature_forum_local_tiki_search}
					{preference name=feature_forum_local_search}
				</div>
			</fieldset>

		{/tab}

		{tab name="{tr}Search Results{/tr}"}
			<br>
			{preference name=search_use_facets}
			{preference name=search_facet_default_amount}
			{preference name=search_excluded_facets}
			{preference name=category_custom_facets}
			{preference name=search_date_facets}
			{preference name=search_date_facets_interval}
			{preference name=search_date_facets_ranges}

			<fieldset>
				<legend>{tr}Items to display in search results{/tr}</legend>
				{preference name=search_default_interface_language}
				{preference name=search_default_where}
				{preference name=search_show_category_filter}
				{preference name=search_show_tag_filter}
				{preference name=feature_search_show_object_filter}
				{preference name=search_show_sort_order}
				{preference name=feature_search_show_search_box}
			</fieldset>
			<fieldset>
				<legend>{tr}Information to display for each result{/tr}</legend>
				{preference name=feature_search_show_visit_count}
				{preference name=feature_search_show_pertinence}
				{preference name=feature_search_show_object_type}
				{preference name=feature_search_show_last_modification}
				{preference name=search_parsed_snippet}
				{preference name=unified_highlight_results}
			</fieldset>
		{/tab}

		{tab name="{tr}Stored Search{/tr}"}
			<br>
			{preference name=storedsearch_enabled}
		{/tab}

		{tab name="{tr}Federated Search{/tr}"}
			<br>
			{preference name=federated_enabled}
			{preference name=federated_elastic_url}

			<legend>{tr}Configuration{/tr}</legend>
			<ul>
				<li><a href="tiki-admin_external_wikis.php">{tr}External Wiki{/tr}</a></li>
				<li><a href="{bootstrap_modal controller=search_manifold action=check}">{tr}ManifoldCF Configuration Checker{/tr}</a></li>
			</ul>
		{/tab}

		{tab name="{tr}Tools{/tr}"}
			<br>
			{include file='admin/include_search_report_string_in_db.tpl'}
			<h2 class="panel-title">{tr}Experiment with LIST plugin syntax{/tr}{help url="LIST+-+Troubleshooting+The+List+Plugin#Using_the_Experiment_with_Plugin_LIST_page" desc="{tr}Help link{/tr}"}</h2>
			<a href="tiki-pluginlist_experiment.php">{tr}After you have found the correct contents, you may copy-paste them in a LIST plugin.{/tr}</a>
			<hr>
		{/tab}

	{/tabset}
	{include file='admin/include_apply_bottom.tpl'}
</form>
