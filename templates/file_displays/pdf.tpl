{* $Id$ *}
{if $source_link}
	<div class="iframe-container">
		<iframe src="{$source_link}" /></iframe>
	</div>
	<div class="mt-3">
		{if $export_pdf_link}
			<a class="btn btn-primary" href="{$export_pdf_link}">{tr}Export PDF{/tr}</a>
		{/if}
	</div>
{/if}
