{extends "layout_view.tpl"}

{block name="title"}
	{title}{$title}{/title}
{/block}

{block name="content"}
	{if $prefs.unified_last_rebuild}
		<div class="alert alert-warning">
			<p>{tr _0=$prefs.unified_last_rebuild|tiki_long_datetime}Your index was last fully rebuilt on %0.{/tr}</p>
		</div>
	{/if}

	{if !empty($search_engine)}
		<div class="alert alert-info">
			<p>{tr}Unified search engine:{/tr} <b>{$search_engine}</b>{if !empty($search_version)}, {tr}version{/tr} <b>{$search_version}</b>{/if}{if $search_index}, index <b>{$search_index}</b>{/if}</p>
			{if $fallback_search_set && !empty($fallback_search_engine)}
				<p>{tr}Unified search engine fallback:{/tr} <b>{$fallback_search_engine}</b>{if !empty($fallback_search_version)}, {tr}version{/tr} <b>{$fallback_search_version}</b>{/if}{if $fallback_search_index}, index <b>{$fallback_search_index}</b>{/if}</p>
			{/if}
		</div>
	{/if}

	{if !empty($stat)}
		{remarksbox type='feedback' title="{tr}Indexed{/tr}"}
			<ul>
				{foreach from=$stat key=what item=nb}
					<li>{$what|escape}: {$nb|escape}</li>
				{/foreach}
			</ul>
		{/remarksbox}
		{if $fallback_search_set}
			{if $fallback_search_indexed}
				{remarksbox type='feedback' title="{tr}Fallback search engine{/tr}"}
					<p>{tr}Fallback search index was rebuild.{/tr}</p>
				{/remarksbox}
			{else}
				{remarksbox type='error' title="{tr}Fallback search engine{/tr}"}
					<p>{tr}Fallback search index was not rebuilt.{/tr}</p>
				{/remarksbox}
			{/if}
		{/if}
		{remarksbox type='feedback' title="{tr}Execution Statistics{/tr}"}
			<ul>
				<li>{tr}Execution time:{/tr} {$execution_time}</li>
				<li>{tr}Current Memory usage:{/tr} {$memory_usage}</li>
				<li>{tr}Memory peak usage before indexing:{/tr} {$memory_peak_usage_before}</li>
				<li>{tr}Memory peak usage after indexing:{/tr} {$memory_peak_usage_after}</li>
				<li>{tr}Number of queries:{/tr} {$num_queries}</li>
			</ul>
		{/remarksbox}
	{else}
		<form method="post" class="no-ajax" action="{service controller=search action=rebuild}" onsubmit="$(this).parent().tikiModal('{tr}Rebuilding index...{/tr}')">
			<div class="form-group row mx-2">
				<div class="form-check">
					<label class="form-check-label">
						<input type="checkbox" class="form-check-input" name="loggit" value="1">
						{tr}Enable logging{/tr}
					</label>
					<div class="form-text">{tr _0=$log_file_browser}Log file is saved as %0{/tr}</div>
				</div>
			</div>
			<div class="form-group submit">
				<input type="submit" class="btn btn-primary" value="{tr}Rebuild{/tr}">
				{if $queue_count > 0}
					<a class="btn btn-primary" href="{service controller=search action=process_queue}">{tr}Process Queue{/tr} <span class="badge badge-secondary">{$queue_count|escape}</span></a>
				{/if}
			</div>
		</form>

		{* If the indexing succeeded, there are clearly no problems, free up some screen space *}
		{remarksbox type=tip title="{tr}Indexing Problems?{/tr}"}
			<p>{tr}If the indexing does not complete, check the log file to see where it ended.{/tr}</p>
			<p style="overflow-wrap: break-word">{tr}Last line of log file (web):{/tr} <strong>{$lastLogItemWeb|escape}</strong></p>
			<p style="overflow-wrap: break-word">{tr}Last line of log file (console):{/tr} <strong>{$lastLogItemConsole|escape}</strong></p>

			<p>Common failures include:</p>
			<ul>
				<li><strong>{tr}Not enough memory.{/tr}</strong> Larger sites require more memory to re-index.</li>
				<li><strong>{tr}Time limit too short.{/tr}</strong> It may be required to run the rebuild through the command line.</li>
				<li><strong>{tr}High resource usage.{/tr}</strong> Some plugins in your pages may cause excessive load. Blacklisting some plugins during indexing can help.</li>
			</ul>
		{/remarksbox}

		{remarksbox type=tip title="{tr}Command Line Utilities{/tr}"}
			<kbd>php console.php{if not empty($tikidomain)} --site={$tikidomain|replace:'/':''}{/if} index:optimize</kbd><br>
			<kbd>php console.php{if not empty($tikidomain)} --site={$tikidomain|replace:'/':''}{/if} index:rebuild</kbd><br>
			<kbd>php console.php{if not empty($tikidomain)} --site={$tikidomain|replace:'/':''}{/if} index:rebuild --log</kbd><br>
			<p>{tr _0=$log_file_console}Log file is saved as %0{/tr}</p>
		{/remarksbox}
	{/if}
{/block}
