<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

function prefs_print_list()
{
    return [
        'print_pdf_from_url' => [
            'name' => tra('PDF from URL'),
            'description' => tra('Using external tools, generate PDF documents from URLs.'),
            'type' => 'list',
            'options' => [
                'none' => tra('Disabled'),
                'webkit' => tra('WebKit (wkhtmltopdf)'),
                'weasyprint' => tra('WeasyPrint'),
                'webservice' => tra('Webservice'),
                'mpdf' => tra('mPDF'),
            ],
            'default' => 'none',
            'help' => 'PDF',
        ],
        'print_pdf_webservice_url' => [
            'name' => tra('Webservice URL'),
            'description' => tra('URL to a service that takes a URL as the query string and returns a PDF document'),
            'type' => 'text',
            'size' => 50,
            'dependencies' => ['auth_token_access'],
            'default' => '',
        ],
        'print_pdf_webkit_path' => [
            'name' => tra('WebKit path'),
            'description' => tra('Full path to the wkhtmltopdf executable to generate the PDF document with'),
            'type' => 'text',
            'size' => 50,
            'help' => 'wkhtmltopdf',
            'dependencies' => ['auth_token_access'],
            'default' => '',
        ],
        'print_pdf_weasyprint_path' => [
            'name' => tra('WeasyPrint path'),
            'description' => tra('Full path to the weasyprint executable to generate the PDF document with'),
            'type' => 'text',
            'size' => 50,
            'help' => 'weasyprint',
            'dependencies' => ['auth_token_access'],
            'default' => '',
        ],
        'print_pdf_mpdf_printfriendly' => [
            'name' => tra('Print Friendly PDF'),
            'description' => tra('Useful for dark themes, enabling this option will change the theme background color to white and the color of text to black. If not activated, theme colors will be retained in the pdf file.'),
            'type' => 'flag',
            'default' => 'y'

        ],
        'print_pdf_mpdf_orientation' => [
            'name' => tra('PDF Orientation'),
            'description' => tra('Landscape or portrait'),
            'tags' => ['advanced'],
            'type' => 'list',
            'options' => [
                'P' => tra('Portrait'),
                'L' => tra('Landscape'),
            ],
            'default' => 'P',
            'packages_required' => ['mpdf/mpdf' => 'Mpdf\\Mpdf'],
        ],
        'print_pdf_mpdf_size' => [
            'name' => tra('PDF page size'),
            'description' => tra('ISO Standard sizes: A0, A1, A2, A3, A4, A5 or North American paper sizes: Letter, Legal, Tabloid/Ledger (for ledger, select landscape orientation)'),
            'tags' => ['advanced'],
            'type' => 'list',
            'options' => [
                'Letter' => tra('Letter'),
                'Legal' => tra('Legal'),
                'Tabloid' => tra('Tabloid/Ledger'),
                'A0' => tra('A0'),
                'A1' => tra('A1'),
                'A2' => tra('A2'),
                'A3' => tra('A3'),
                'A4' => tra('A4'),
                'A5' => tra('A5'),
                'A6' => tra('A6'),
            ],
            'default' => 'A4',
        ],

        'print_pdf_mpdf_toc' => [
            'name' => tra('Table of contents'),
            'description' => tra('Generate auto table of contents with PDF'),
            'type' => 'flag',
            'default' => 'n'

        ],
        'print_pdf_mpdf_toclinks' => [
            'name' => tra('Link TOC with content'),
            'description' => tra('Link table of contents headings with content in the PDF'),
            'type' => 'flag',
            'default' => 'n'

        ],
        'print_pdf_mpdf_tocheading' => [
            'name' => tra('TOC heading'),
            'description' => tra('Heading to be displayed above the table of contents'),
            'type' => 'text',
            'default' => 'Table of Contents'

        ],
        'print_pdf_mpdf_toclevels' => [
            'name' => tra('PDF TOC levels'),
            'description' => tra('These will be automatically created from the content; the default will be heading 1, heading 2 and heading 3 - for example, H1|H2|H3.'),
            'tags' => ['advanced'],
            'type' => 'text',
            'default' => 'H1|H2|H3',
        ],
        'print_pdf_mpdf_pagetitle' => [
            'name' => tra('Show Page title'),
            'description' => tra('Print wiki page title with pdf'),
            'type' => 'list',
            'options' => [
                '' => tra('Default'),
                'y' => tra('Yes'),
                'n' => tra('No'),

            ],
            'default' => '',
        ],
        'print_pdf_mpdf_header' => [
            'name' => tra('PDF header text'),
            'description' => tra('Possible values, Plain text, HTML, Wiki syntax, {PAGENO},{PAGETITLE},{DATE j-m-Y}, Page {PAGENO} of {NB}, {include page="wiki_page_name"}'),
            'tags' => ['basic'],
            'type' => 'textarea',
            'size' => 5,
            'default' => '',
            'shorthint' => tr('HTML / Wiki Syntax / ') . tr('Left text') . ' | ' . tr('Center Text') . ' | ' . tr('Right Text')
        ],
        'print_pdf_mpdf_footer' => [
            'name' => tra('PDF footer text'),
            'description' => tra('HTML, Wiki Syntax, plain text, {PAGENO}, {DATE j-m-Y}, Page {PAGENO} of {NB}.'),
            'tags' => ['basic'],
            'type' => 'textarea',
            'size' => 5,
            'default' => '',
            'shorthint' => tr('HTML / Wiki Syntax / ') . tr('Left text') . ' | ' . tr('Center Text') . ' | ' . tr('Right Text')
        ],
        'print_pdf_mpdf_margin_left' => [
            'name' => tra('Left margin'),
            'description' => tra('Numeric value.For example 10'),
            'units' => tra('pixels'),
            'tags' => ['advanced'],
            'type' => 'text',
            'default' => '10',
            'size' => '2',
            'filter' => 'digits',

        ],
        'print_pdf_mpdf_margin_right' => [
            'name' => tra('Right margin'),
            'description' => tra('Numeric value, no need to add px. For example 10'),
            'tags' => ['advanced'],
            'units' => tra('pixels'),
            'type' => 'text',
            'default' => '10',
            'size' => '2',
            'filter' => 'digits',

        ],
        'print_pdf_mpdf_margin_top' => [
            'name' => tra('Top margin'),
            'description' => tra('Numeric value, no need to add px. For example 10'),
            'tags' => ['advanced'],
            'units' => tra('pixels'),
            'type' => 'text',
            'default' => '10',
            'size' => '2',
            'filter' => 'digits',

        ],
        'print_pdf_mpdf_margin_bottom' => [
            'name' => tra('Bottom margin'),
            'description' => tra('Numeric value, no need to add px. For example 10'),
            'tags' => ['advanced'],
            'units' => tra('pixels'),
            'type' => 'text',
            'default' => '10',
            'size' => '2',
            'filter' => 'digits',

        ],
        'print_pdf_mpdf_margin_header' => [
            'name' => tra('Header margin from top of document'),
            'description' => tra('Only applicable if the header is set. Specify a numeric value, and there is no need to add "px". For example, 10.'),
            'tags' => ['advanced'],
            'units' => tra('pixels'),
            'type' => 'text',
            'default' => '5',
            'size' => '2',
            'filter' => 'digits',
            'shorthint' => tra('Warning: Header can overlap text if top margin is not set properly')
        ],
        'print_pdf_mpdf_margin_footer' => [
            'name' => tra('Footer margin from bottom of document'),
            'description' => tra('Only applicable if the footer is set. Specify a numeric value, and there is no need to add "px". For example, 10.'),
            'tags' => ['advanced'],
            'units' => tra('pixels'),
            'type' => 'text',
            'default' => '5',
            'size' => '2',
            'filter' => 'digits',
            'shorthint' => tra('Warning: Footer can overlap text if bottom margin is not set properly')
        ],
        'print_pdf_mpdf_hyperlinks' => [
            'name' => tra('Hyperlink behaviour in PDF'),
            'tags' => ['advanced'],
            'type' => 'list',
            'default' => '',
            'options' => [
                '' => tra('Default'),
                'off' => tra('Off (Links will be removed)'),
                'footnote' => tra('Add as footnote (Links will be listed at end of document')
            ]
        ],
        'print_pdf_mpdf_autobookmarks' => [
            'name' => tra('PDF Bookmarks'),
            'description' => tra('Automatically generate bookmarks'),
            'tags' => ['advanced'],
            'type' => 'text',
            'default' => 'H1|H2|H3',
            'shorthint' => tra('H1-H6, separated by |.For example: H1|H2|H3')
        ],
        'print_pdf_mpdf_columns' => [
            'name' => tra('Number of columns'),
            'tags' => ['advanced'],
            'type' => 'list',
            'default' => '',
            'options' => [
                '' => tra('Default - 1 Column'),
                '2' => tra('2 Columns'),
                '3' => tra('3 Columns'),
                '4' => tra('4 Columns')
            ]
        ],
        'print_pdf_mpdf_password' => [
            'name' => tra('PDF password for viewing'),
            'description' => tra('Password-protect the generated PDF.'),
            'tags' => ['advanced'],
            'type' => 'password',
            'autocomplete' => 'off',
            'default' => '',
        ],
        'print_pdf_mpdf_watermark' => [
            'name' => tra('Watermark text'),
            'description' => tra('PDF watermark text, if any'),
            'tags' => ['advanced'],
            'type' => 'text',
            'default' => '',
        ],
        'print_pdf_mpdf_watermark_image' => [
            'name' => tra('Watermark Image URL'),
            'description' => tra('The watermark image will appear under the text. Enter the complete image URL.'),
            'tags' => ['advanced'],
            'type' => 'text',
            'default' => '',
        ],
        'print_pdf_mpdf_background' => [
            'name' => tra('PDF page background color'),
            'description' => tra('Enter a valid CSS color code.'),
            'tags' => ['advanced'],
            'type' => 'text',
            'default' => '',
        ],
        'print_pdf_mpdf_background_image' => [
            'name' => tra('PDF page background image'),
            'description' => tra('Enter the full URL.'),
            'tags' => ['advanced'],
            'type' => 'text',
            'default' => '',
        ],
        'print_pdf_mpdf_watermark' => [
            'name' => tra('Watermark text'),
            'description' => tra('PDF watermark text, if any'),
            'tags' => ['advanced'],
            'type' => 'text',
            'default' => '',
        ],
        'print_pdf_mpdf_coverpage_text_settings' => [
            'name' => tra('CoverPage text settings'),
            'description' => tra('Heading|Subheading|Alignment|Background color|Text color|Page border|Border color. Enter settings separated by |. Leave blank to use default settings.'),
            'tags' => ['advanced'],
            'type' => 'text',
            'default' => '',
        ],
        'print_pdf_mpdf_coverpage_image_settings' => [
            'name' => tra('Coverpage image URL'),
            'description' => tra('Enter the full URL.'),
            'type' => 'text',
            'tags' => ['advanced'],
            'default' => '',
        ],
        'print_wiki_authors' => [
            'name' => tra('Print wiki authors'),
            'description' => tra('Include wiki page authors and date in print versions of wiki pages.'),
            'type' => 'flag',
            'dependencies' => [
                'feature_wiki',
            ],
            'default' => 'n',
        ],
        'print_original_url_wiki' => [
            'name' => tra('Print original wiki URL'),
            'description' => tra('Include original wiki page URL in print versions of wiki pages.'),
            'type' => 'flag',
            'dependencies' => [
                'feature_wiki',
            ],
            'default' => 'y',
        ],
        'print_original_url_tracker' => [
            'name' => tra('Print original tracker item URL'),
            'description' => tra('Include original wiki page URL in print versions of wiki pages.'),
            'type' => 'flag',
            'dependencies' => [
                'feature_trackers',
            ],
            'default' => 'y',
        ],
        'print_original_url_forum' => [
            'name' => tra('Print original forum post URL'),
            'description' => tra('Include original forum post URL in print version of forum posts.'),
            'type' => 'flag',
            'dependencies' => [
                'feature_forums',
            ],
            'default' => 'y',
        ],
    ];
}
