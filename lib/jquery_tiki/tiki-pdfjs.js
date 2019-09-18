/**
 * (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
 *
 * All Rights Reserved. See copyright.txt for details and a complete list of authors.
 * Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
 * $Id$
 *
 * Handles pdf.js to load and display the PDF document in webpage.
 */
function getQueryVariable(variable) {
	var query = window.location.search.substring(1);
	var vars = query.split("&");
	for (var i=0;i<vars.length;i++) {
		var pair = vars[i].split("=");
		if (pair[0] == variable) {
			return pair[1];
		}
	}
	return '';
}

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

	if (pageNum === 1) {
		$('#prev').addClass('disabled');
	}

	if (pageNum > 1) {
		$('#prev').removeClass('disabled');
	}

	if (pageNum < pdfSinglePageViewer.pagesCount) {
		$('#next').removeClass('disabled');
	}

	if (pageNum === pdfSinglePageViewer.pagesCount) {
		$('#next').addClass('disabled');
	}

	pdfSinglePageViewer.currentPageNumber = pageNum;
	$('#page_num').html(pageNum);
}

$('#prev').on('click', function() { prevPage(); });
$('#next').on('click', function() { nextPage(); });

var pdf = {
	url: $('#source-link').val() || getQueryVariable('fileSrc'),
	cMapUrl: 'vendor/npm-asset/pdfjs-dist-viewer-min/build/minified/web/cmaps',
	cMapPacked: true
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
