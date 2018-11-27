<div>
	<nav>
		<ul class="pagination justify-content-center">
			<li id="prev" class="page-item"><a class="page-link" href="#">{tr}Previous{/tr}</a></li>
			<li class="page-item disabled"><a class="page-link" href="#"><span id="page_num"></span> {tr}of{/tr} <span id="page_count"></span></a></li>
			<li id="next" class="page-item"><a class="page-link" href="#">{tr}Next{/tr}</a></li>
		</ul>
	</nav>
</div>
<input type="hidden" id="source-link" value="{$source_link}">
<div id="viewerContainer" style="border: 1px solid gray; text-align: center">
	<div id="viewer" class="pdfViewer singlePageView loadingIcon"></div>
</div>
<div class="mt-3">
	<a class="btn btn-primary" href="{$download_link}">{tr}Download original{/tr}</a>
	{if $export_pdf_link}
	<a class="btn btn-primary" href="{$export_pdf_link}">{tr}Export PDF{/tr}</a>
	{/if}
</div>
