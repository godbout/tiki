{if !$pdfJsAvailable}
	{remarksbox type=error title="{tr}Missing Package{/tr}" close="n"}
		{tr}To view pdf files Tiki needs npm-asset/pdfjs-dist package.{/tr}
		{tr}Please contact the Administrator to install it.{/tr}
	{/remarksbox}
{else}
	<div>
		<nav>
			<ul class="pagination justify-content-center">
				<li id="prev" class="page-item"><a class="page-link" href="#">{tr}Previous{/tr}</a></li>
				<li class="page-item disabled"><a class="page-link" href="#"><span id="page_num"></span> {tr}of{/tr} <span id="page_count"></span></a></li>
				<li id="next" class="page-item" ><a class="page-link" href="#">{tr}Next{/tr}</a></li>
			</ul>
		</nav>
	</div>
	<div id="viewerContainer" style="border: 1px solid gray; text-align: center">
		<div id="viewer" class="pdfViewer singlePageView loadingIcon"></div>
	</div>
	{jq}
		/**
		 * Displays previous page.
		 */
		function prevPage() {
			changePage(pdfSinglePageViewer.currentPageNumber - 1);
		}

		/**
		 * Displays next page.
		 */
		function nextPage() {
			changePage(pdfSinglePageViewer.currentPageNumber + 1);
		}

		/**
		 * Changes document to a specific page
		 */
		function changePage(pageNum) {
			if (pageNum < 1) {
				return;
			}

			if (pageNum > pdfSinglePageViewer.pagesCount) {
				return;
			}

			if (pageNum == 1) {
				$('#prev').addClass('disabled');
			}

			if (pageNum > 1) {
				$('#prev').removeClass('disabled');
			}

			if (pageNum < pdfSinglePageViewer.pagesCount) {
				$('#next').removeClass('disabled');
			}

			if (pageNum == pdfSinglePageViewer.pagesCount) {
				$('#next').addClass('disabled');
			}

			pdfSinglePageViewer.currentPageNumber = pageNum;
			$('#page_num').html(pageNum);
		}

		$('#prev').on('click', function() { prevPage(); });
		$('#next').on('click', function() { nextPage(); });

		var pdf = {
			url: '{{$url}}',
			cMapUrl: 'vendor/npm-asset/pdfjs-dist/cmaps/',
			cMapPacked: true,
		};

		var container = document.getElementById('viewerContainer');

		container.addEventListener('pagesinit', function () {
			// Update document scale
			pdfSinglePageViewer.currentScaleValue = 'page-width';

			changePage(pdfSinglePageViewer.currentPageNumber);
			$('#page_count').html(pdfSinglePageViewer.pagesCount);
		});

		var pdfLinkService = new pdfjsViewer.PDFLinkService();
		var pdfSinglePageViewer = new pdfjsViewer.PDFSinglePageViewer({
			container: container,
			linkService: pdfLinkService,
		});
		pdfLinkService.setViewer(pdfSinglePageViewer);

		/**
		 * Asynchronously downloads PDF.
		 */
		pdfjsLib.getDocument(pdf).then(function(pdfDocument) {
			pdfSinglePageViewer.setDocument(pdfDocument);
			pdfLinkService.setDocument(pdfDocument, null);
		});
	{/jq}
{/if}
