<?php
/**
 * @package tikiwiki
 */
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

$section = 'wiki page';
require_once('tiki-setup.php');
$tikilib = TikiLib::lib('tiki');
$structlib = TikiLib::lib('struct');
$wikilib = TikiLib::lib('wiki');

$headerlib->add_js(
	"var fragments='y';
	var fragmentClass='grow';
	var fragmentHighlightColor='highlight-blue';"
);
include_once('lib/wiki-plugins/wikiplugin_slideshow.php');

$access->check_feature('feature_wiki');
$access->check_feature('feature_slideshow');

//make the other things know we are loading a slideshow
$tikilib->is_slideshow = true;
$smarty->assign('is_slideshow', 'y');

// Create the HomePage if it doesn't exist
if (! $tikilib->page_exists($prefs['wikiHomePage'])) {
	$tikilib->create_page(
		$prefs['wikiHomePage'], 0, '', date("U"), 'Tiki initialization'
	);
}

if (! isset($_SESSION["thedate"])) {
	$thedate = date("U");
} else {
	$thedate = $_SESSION["thedate"];
}

// Get the page from the request var or default it to HomePage
if (! isset($_REQUEST["page"])) {
	$_REQUEST["page"] = $wikilib->get_default_wiki_page();
}
$page = htmlspecialchars(str_replace('-',' ',$_REQUEST['page']));
$smarty->assign('page', $page);

// If the page doesn't exist then display an error
if (! ($info = $tikilib->page_exists($page))) {
	include_once('tiki-index.php');
	die;
}

if (isset($_REQUEST['theme'])) {
	$theme = $_REQUEST['theme'];
} else {
	$theme = "black";
}

// Now check permissions to access this page
$tikilib->get_perm_object($page, 'wiki page', $info);
if ($tiki_p_view != 'y') {
	$smarty->assign('errortype', 401);
	$smarty->assign(
		'msg', tra("Permission denied. You cannot view this page.")
	);

	$smarty->display("error_raw.tpl");
	die;
}

// BreadCrumbNavigation here
// Remember to reverse the array when posting the array

if (! isset($_SESSION["breadCrumb"])) {
	$_SESSION["breadCrumb"] = [];
}

if (! in_array($page, $_SESSION["breadCrumb"])) {
	if (count($_SESSION["breadCrumb"]) > $prefs['userbreadCrumb']) {
		array_shift($_SESSION["breadCrumb"]);
	}

	array_push($_SESSION["breadCrumb"], $page);
} else {
	// If the page is in the array move to the last position
	$pos = array_search($page, $_SESSION["breadCrumb"]);

	unset($_SESSION["breadCrumb"][$pos]);
	array_push($_SESSION["breadCrumb"], $page);
}

// Now increment page hits since we are visiting this page
$tikilib->add_hit($page);

// Get page data
$parserlib = TikiLib::lib('parser');
$info = $tikilib->get_page_info($page);
$pdata = $parserlib->parse_data_raw($info["data"]);

if (! isset($_REQUEST['pagenum'])) {
	$_REQUEST['pagenum'] = 1;
}

//tags need to be removed from data before data formatting
$tagsArr = [["div", "icon_edit_section", "class"], ["a", "editplugin", "class"],
			["a", "show-errors-button", "id"], ["a", "heading-link", "class"]];


$pages = $wikilib->get_number_of_pages($pdata);
$pdata = $wikilib->get_page($pdata, $_REQUEST['pagenum']);
// Put ~pp~, ~np~ and <pre> back. --rlpowell, 24 May 2004
$parserlib->replace_preparse($info["data"], $preparsed, $noparsed);
$parserlib->replace_preparse($pdata, $preparsed, $noparsed);

$pdata = formatContent($pdata, $tagsArr);

if (isset($_REQUEST['pdf'])) {
	$access->check_feature("feature_slideshow_pdfexport");
	set_time_limit(777);

	$_POST["html"] = urldecode($_POST["html"]);

	if (isset($_POST["html"])) {
		$generator = new PdfGenerator(PdfGenerator::MPDF);
		if (! empty($generator->getError())) {
			Feedback::error(
				tr(
					'Exporting slideshow as PDF requires a working installation of mPDF.'
				)
				. "<br \>"
				. tr('Export to PDF error: %0', $generator->getError())
			);
			$access = Tikilib::lib('access');
			$access->redirect(
				str_replace(
					'tiki-slideshow.php?', 'tiki-index.php?',
					$_SERVER['HTTP_REFERER']
				)
			);
		}

		$params = [
			'orientation' => isset($_REQUEST['landscape']) ? 'L' : 'P',
		];
		$filename = TikiLib::lib('tiki')
			->remove_non_word_characters_and_accents($_REQUEST['page']);
		if ($_REQUEST['pdfSettings']) {
			$_POST['html'] = '<' . $_REQUEST['pdfSettings'] . ' />'
				. $_POST['html'];
		}
		//checking if to export slideshow
		if ($_REQUEST['printslides']) {
			$customCSS
				= "<style type='text/css'>img{max-height:300px;width:auto;} body{font-size:1em} h1{font-size:1.5em}  section{height:300px;border:1px solid #000;margin-bottom:1%;padding:1%;}</style> ";
			$pdata = $customCSS . $pdata;
		} else {
			//getting css
			$customCSS .= file_get_contents(
				'vendor_bundled/vendor/components/revealjs/css/reveal.scss'
			);
			$customCSS .= file_get_contents(
				'vendor_bundled/vendor/components/revealjs/css/theme/' . $theme
				. '.css'
			);
			$customCSS .= '.reveal section{width:70%;text-align:center;padding-top:50px;margin:auto;text-align:center} .reveal h1{font-size:2em} .reveal{font-size:1.3em;line-height:1.5em}';
			$pdata = '<div class="reveal">' . $pdata . '</div>';

			$pdata = str_replace(
				"</section><section", "</section><pagebreak /><section",
				'<style>' . $customCSS . '</style>' . $pdata
			);
		}

		$pdf = $generator->getPdf(
			$filename, $params,
			preg_replace('/%u([a-fA-F0-9]{4})/', '&#x\\1;', $pdata)
		);
		$length = strlen($pdf);
		header('Cache-Control: private, must-revalidate');
		header('Pragma: private');
		header('Content-disposition: inline; filename="' . $filename . '.pdf"');
		header("Content-Type: application/pdf");
		header("Content-Transfer-Encoding: binary");
		header('Content-Length: ' . $length);
		echo $pdf;
		exit(0);
	}
	die;
}
$smarty->assign('pages', $pages);
$smarty->assign_by_ref('parsed', $pdata);
$smarty->assign_by_ref('lastModif', $info["lastModif"]);

if (empty($info["user"])) {
	$info["user"] = 'anonymous';
}

$smarty->assign_by_ref('lastUser', $info["user"]);

include_once('tiki-section_options.php');


$headerlib->add_jsfile(
	'vendor_bundled/vendor/components/revealjs/js/reveal.js'
);
$headerlib->add_cssfile(
	'vendor_bundled/vendor/components/revealjs/css/reveal.css'
);
$headerlib->add_cssfile(
	'vendor_bundled/vendor/components/revealjs/css/theme/' . $theme . '.css'
);
$headerlib->add_css(
	'.reveal span{font-family: "FontAwesome";font-style: normal;} .reveal .controls{z-index:103;}#ss-settings-holder{position:fixed;bottom:10px;left:0px;width:10%;height:30px;text-align:left;padding-left:15px;cursor:pointer;z-index:102;line-height:1.5rem}#ss-options{position:fixed;bottom:0px;left:-2000px;width:100%;background-color:rgba(00,00,00,0.8);font-size:1.1rem;line-height:2.2rem;color:#fff;z-index:101;} #ss-options a{color:#999} #ss-options a:hover{color:#fff} #page-bar,.icon_edit_section,.editplugin, #show-errors-button, .wikitext, .icon_edit_section, #toc,.heading-link {display:none} .fade:not(.show) { opacity: 1;} .reveal section img {border:0px}'
);

$headerlib->add_jq_onready(
	'$("<link/>", {rel: "stylesheet",type: "text/css",href: "", id:"themeCSS"}).appendTo("head");
	$("body").append("<style type=\"text/css\">.reveal h1 {font-size: 2.8em;} .reveal  {font-size: 1.4em;}.reveal .slides section .fragment.grow.visible {transform: scale(1.06);}.reveal table {overflow: hidden;} </style>");
	$("#page-bar").remove();
	$(".icon_edit_section").remove();
	$(".editplugin").remove();
	$("#show-errors-button").remove();
	$(".wikitext").remove();
	$(".icon_edit_section").remove();
	$("#toc").remove();
	$(".heading-link").remove();
	Reveal.initialize();
	if(fragments=="y") {
		$( "li" ).addClass( "fragment "+fragmentClass+" "+fragmentHighlightColor );
	}

	$("#ss-settings").click(function () {
		var position = $("#ss-options").position();
		if(position.left==0){
			$("#ss-options").animate({left: \'-2000px\'});
		}
		else {
			$("#ss-options").animate({left: \'0px\'});}
		});
		Reveal.addEventListener( \'slidechanged\', function( event ) {
			var position = $("#ss-options").position();
			if(position.left==0){
				$("#ss-options").animate({left: \'-2000px\'});
			}
		});
		$( "#showtheme" ).change(function() {
			var selectedCSS=$("#showtheme" ).val();
			$("#themeCSS").attr("href","vendor_bundled/vendor/components/revealjs/css/theme/"+selectedCSS+".css");
		});
		$( "#showtransition" ).change(function() {
			var selectedTransition=$("#showtransition" ).val();
			Reveal.configure({ transition: selectedTransition });
		});
		$("body").delegate("#exportPDF","click", function () {
			if($("#showtheme" ).val()!="") {
				var pdfURL= $( "#exportPDF" ).attr("href")+"&theme="+$("#showtheme" ).val();
				$( "#exportPDF" ).attr("href",pdfURL);
			}
		});
		'
);

ask_ticket('index-raw');

// Display the Index Template
$smarty->assign('mid', 'tiki-show_page_raw.tpl');

// use tiki_full to include include CSS and JavaScript
$smarty->display("tiki_full.tpl");


//new function for data cleaning and foramtting

function formatContent($content, $tagArr)
{

	$doc = new DOMDocument();
	$doc->loadHTML(
		mb_convert_encoding('<html>' . $content . '</html>', 'HTML-ENTITIES', 'UTF-8'),
		LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
	);
	$xpath = new DOMXpath($doc);
	$expression = '(//sslide//h1|//sslide//h2|//sslide//h3)';
	$elements = $xpath->query($expression);

	foreach ($elements as $index => $element) {
		dom_rename_element($element, 'sheading');
	}

	foreach ($tagArr as $tag) {
		$list = $xpath->query(
			'//' . $tag[0] . '[contains(concat(\' \', normalize-space(@'
			. $tag[2] . '), \' \'), "' . $tag[1] . '")]'
		);
		for ($i = 0; $i < $list->length; $i++) {
			$p = $list->item($i);
			if ($tag[3]
				== 1
			) { //the parameter checks if content of tag has to be preserved
				$attributes = $p->attributes;
				while ($attributes->length) {
					//preserving href

					if ($attributes->item(0)->name == "href") {
						$hrefValue = $attributes->item(0)->value;
					}
					$p->removeAttribute($attributes->item(0)->name);
				}
				if ($hrefValue) {
					$p->setAttribute("href", $hrefValue);
				}
			} else {
				$p->parentNode->removeChild($p);
			}
		}
	}


	$content = str_replace(['<html>', '</html>'], '', $doc->saveHTML());


	$headingsTags = preg_split('/<h[123]/', $content);
	$firstSlide = 0;
	foreach ($headingsTags as $slide) {
		if ($firstSlide == 0) {
			//checking if first slide has pluginSlideShowSlide instance, then concat with main text, otherwise ignore
			$sectionCheck = strpos($slide, '</section><section');
			if ($sectionCheck == true) {
				$slidePlugin = explode("</section>", $slide);
				$slideContent .= $slidePlugin[1] . '</section>';
			}
			$firstSlide = 1;
		} else {
			$slideContent .= '<section><h1' . str_replace(
					array('</h2>', '</h3>'), '</h1>', $slide
				) . '</section>';
		}

	}
	//images alignment left or right
	//replacment for slideshowslide

	return html_entity_decode(str_replace(
		array('<sslide', 'sheading'), array('</section><section', 'h1'),
		$slideContent
	));
}

function dom_rename_element(DOMElement $node, $name)
{
	$renamed = $node->ownerDocument->createElement($name);

	foreach ($node->attributes as $attribute) {
		$renamed->setAttribute($attribute->nodeName, $attribute->nodeValue);
	}

	while ($node->firstChild) {
		$renamed->appendChild($node->firstChild);
	}

	return $node->parentNode->replaceChild($renamed, $node);
}