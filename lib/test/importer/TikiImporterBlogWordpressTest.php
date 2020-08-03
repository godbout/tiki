<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

use Tiki\FileGallery\File;

require_once(__DIR__ . '/tikiimporter_testcase.php');
require_once(__DIR__ . '/../../importer/tikiimporter_blog_wordpress.php');

/**
 * @group importer
 */
class TikiImporter_Blog_Wordpress_Test extends TikiImporter_TestCase
{
    protected function setUp() : void
    {
        date_default_timezone_set('UTC');
        $this->obj = new TikiImporter_Blog_Wordpress;
    }

    protected function tearDown() : void
    {
        TikiDb::get()->query('DELETE FROM tiki_pages WHERE pageName = "materia"');
        TikiDb::get()->query('DELETE FROM tiki_blog_posts WHERE postId = 10');
        unset($GLOBALS['prefs']['feature_sefurl'], $GLOBALS['base_url']);
    }

    public function testImport(): void
    {
        ob_start();

        $obj = $this->getMockBuilder('TikiImporter_Blog_Wordpress')
            ->onlyMethods(['validateInput', 'extractBlogInfo', 'parseData', 'insertData', 'setupTiki', 'extractPermalinks'])
            ->getMock();
        $obj->expects($this->once())->method('validateInput');
        $obj->expects($this->once())->method('extractBlogInfo')->willReturn([]);
        $obj->expects($this->once())->method('parseData');
        $obj->expects($this->once())->method('insertData');
        $obj->expects($this->once())->method('setupTiki');
        $obj->expects($this->once())->method('extractPermalinks');

        $_FILES['importFile']['type'] = 'text/xml';
        $obj->import(__DIR__ . '/fixtures/wordpress_sample.xml');
        unset($_FILES['importFile']);

        $this->assertInstanceOf(DOMDocument::class, $obj->dom);
        $this->assertTrue($obj->dom->hasChildNodes());

        $output = ob_get_clean();
        $this->assertEquals("Loading and validating the XML file\n\nImportation completed!\n\n<b><a href=\"tiki-importer.php\">Click here</a> to finish the import process</b>", $output);
    }

    public function testImportShouldHandleAttachments(): void
    {
        ob_start();

        $obj = $this->getMockBuilder('TikiImporter_Blog_Wordpress')
            ->onlyMethods(['validateInput', 'extractBlogInfo', 'parseData', 'insertData', 'downloadAttachments', 'setupTiki', 'extractPermalinks'])
            ->getMock();
        $obj->expects($this->once())->method('validateInput');
        $obj->expects($this->once())->method('extractBlogInfo')->willReturn([]);
        $obj->expects($this->once())->method('parseData');
        $obj->expects($this->once())->method('insertData');
        $obj->expects($this->once())->method('downloadAttachments');
        $obj->expects($this->once())->method('setupTiki');
        $obj->expects($this->once())->method('extractPermalinks');
        $_POST['importAttachments'] = 'on';

        $obj->import(__DIR__ . '/fixtures/wordpress_sample.xml');

        unset($_POST['importAttachments']);

        ob_get_clean();
    }

    public function testReplaceParagraphWithLineBreak(): void
    {
        $expectedOutput = "Hello world<br />Here is Foo<br />Talking from Bar<br />Thanks";

        $input = "<p>Hello world</p><p style='color:black'>Here is Foo<br/>Talking from Bar</p><br>Thanks";
        $output = $this->obj->replaceParagraphWithLineBreak($input);
        $this->assertEquals($expectedOutput, $output, "html paragraphs should be replaced with line break");

        $input = "Hello world<br><br/>Here is Foo<br />Talking from Bar<br><br  />Thanks";
        $output = $this->obj->replaceParagraphWithLineBreak($input);
        $this->assertEquals($expectedOutput, $output, "When there is a tag '<br>' this one is also replaced by '\n'");
    }

    public function testParseYoutubeEmbedded(): void
    {
        $expectedOutput = "{youtube movie=\"h80QuuYxbhk\" width=\"560\" height=\"315\" quality=\"high\" allowFullScreen=\"y\"}";

        $input = "<iframe width=\"560\" height=\"315\" src=\"https://www.youtube.com/embed/h80QuuYxbhk\"></iframe>";
        $output = $this->obj->parseYoutubeEmbedded($input);
        $this->assertEquals($expectedOutput, $output, "iframe tag should be replaced Youtube Plugin Tiki code");
    }

    public function testParseData(): void
    {
        ob_start();

        $obj = $this->getMockBuilder('TikiImporter_Blog_Wordpress')
            ->onlyMethods(['extractItems', 'extractTags', 'extractCategories'])
            ->getMock();
        $obj->expects($this->once())->method('extractItems')->willReturn(['posts' => [], 'pages' => []]);
        $obj->parseData();
        $this->assertCount(4, $obj->parsedData);

        $output = ob_get_clean();
        $this->assertEquals("\nExtracting data from XML file:\n", $output);
    }

    public function testExtractPermalinks(): void
    {
        $this->obj->dom = new DOMDocument;
        $this->obj->dom->load(__DIR__ . '/fixtures/wordpress_sample.xml');
        $this->obj->blogInfo['link'] = 'http://example.com';

        $expectedResult = [
            107 => [
                'oldLinks' => [
                    'http://example.com/2007/03/11/materia-sobre-a-viagem-de-bicicleta-entre-as-chapadas/',
                    '/2007/03/11/materia-sobre-a-viagem-de-bicicleta-entre-as-chapadas/',
                    'http://example.com/?p=107',
                    '/?p=107',
                ],
            ],
            36 => [
                'oldLinks' => [
                    'http://example.com/2008/01/20/circuito-grande-torres-del-paine/',
                    '/2008/01/20/circuito-grande-torres-del-paine/',
                    'http://example.com/?p=36',
                    '/?p=36',
                ],
            ],
            73 => [
                'oldLinks' => [
                    'http://example.com/2008/02/23/lo-mas-importante-son-los-veinte/',
                    '/2008/02/23/lo-mas-importante-son-los-veinte/',
                    'http://example.com/?p=73',
                    '/?p=73',
                ],
            ],
            10 => [
                'oldLinks' => [
                    'http://example.com/2009/05/04/como-impedir-que-o-editor-do-wordpress-tinymce-remova-quebras-de-linha/',
                    '/2009/05/04/como-impedir-que-o-editor-do-wordpress-tinymce-remova-quebras-de-linha/',
                ],
            ],
        ];

        $this->assertEquals($expectedResult, $this->obj->extractPermalinks());
    }

    public function testIdentifyInternalLinks(): void
    {
        $this->obj->permalinks = [
            107 => [
                'oldLinks' => [
                    'http://example.com/2007/03/11/materia-sobre-a-viagem-de-bicicleta-entre-as-chapadas/',
                    'http://example.com/?p=107',
                ],
            ],
            36 => [
                'oldLinks' => [
                    'http://example.com/2008/01/20/circuito-grande-torres-del-paine/',
                    'http://example.com/?p=36',
                ],
            ],
        ];

        $item['wp_id'] = 10;
        $item['content'] = 'Continuação do post sobre o uso de bicicletas na Europa. <a href="http://example.com/2007/03/11/materia-sobre-a-viagem-de-bicicleta-entre-as-chapadas/">Teste</a> E continua o texto por aqui.';
        $this->assertTrue($this->obj->identifyInternalLinks($item));

        $item['wp_id'] = 11;
        $item['content'] = 'Continuação do post sobre o uso de bicicletas na Europa. <a href="http://example.com/2007/03/11/outra-materia/">Teste</a> E continua o texto por aqui.';
        $this->assertFalse($this->obj->identifyInternalLinks($item));
    }

    public function testExtractItems(): void
    {
        $obj = $this->getMockBuilder('TikiImporter_Blog_Wordpress')
            ->onlyMethods(['extractInfo'])
            ->getMock();
        $obj->dom = new DOMDocument;
        $obj->dom->load(__DIR__ . '/fixtures/wordpress_sample.xml');
        $obj->expects($this->exactly(4))->method('extractInfo')->willReturn([]);

        $expectedResult = [
            'posts' => [[], [], []],
            'pages' => [[]],
        ];

        $this->assertEquals($expectedResult, $obj->extractItems());
    }

    public function testExtractTags(): void
    {
        $expectedResult = ['alta montanha', 'barcelona', 'bicicleta', 'bicicletada', 'buenos aires', 'caminhada', 'canadá', 'carga',
            'cerro plata', 'chapada diamantina', 'chapada dos veadeiras', 'chile', 'cicloativismo', 'cicloturismo', 'cidade', 'cidades',
            'comida', 'conhecimento livre', 'creative commons', 'davi marski', 'debate', 'die-in', 'digikam', 'dmsc', 'dmsc2010',
            'el chaltén', 'eleições', 'escalada', 'europa', 'exiv2', 'filme', 'fotos', 'gelo', 'gettext', 'ghost bike', 'gsoc', 'hacklab',
            'januária', 'linux', 'livros', 'londres', 'mapas', 'mediawiki', 'mendoza', 'montanhismo', 'montreal', 'mudanças', 'null tag name', 'osorno',
            'parser', 'partidos políticos', 'patagônia', 'pear', 'php', 'phpbb', 'phpdocumentor', 'phpt', 'phpunit', 'plugin',
            'quinta livre', 'restaurantes vegetarianos', 'san pedro de atacama', 'santiago', 'são paulo', 'software livre', 'Text_Wiki',
            'tikifest', 'tikiwiki', 'tinymce', 'torres del paine', 'transporte', 'trekking', 'tv', 'ubuntu', 'unit tests', 'usp',
            'vegetarianismo', 'vídeo', 'vulcão', 'vulcão maipo', 'wiki', 'wordpress', 'youtube'];

        $this->obj->dom = new DOMDocument;
        $this->obj->dom->load(__DIR__ . '/fixtures/wordpress_sample.xml');

        $this->assertEquals($expectedResult, $this->obj->extractTags());
    }

    public function testExtractCategories(): void
    {
        $expectedResult = [
            ['parent' => '', 'name' => 'bicicleta', 'description' => 'Qualquer descrição'],
            ['parent' => 'bicicleta', 'name' => 'cicloativismo', 'description' => ''],
            ['parent' => 'bicicleta', 'name' => 'cicloturismo', 'description' => ''],
            ['parent' => '', 'name' => 'hacklab', 'description' => ''],
            ['parent' => '', 'name' => 'montanhismo', 'description' => ''],
            ['parent' => '', 'name' => 'Sem categoria', 'description' => ''],
            ['parent' => '', 'name' => 'software livre', 'description' => ''],
            ['parent' => '', 'name' => 'Uncategorized', 'description' => ''],
            ['parent' => '', 'name' => 'vegetarianismo', 'description' => ''],
            ['parent' => '', 'name' => 'viagens', 'description' => ''],
            ['parent' => 'viagens', 'name' => 'argentina', 'description' => 'Another description'],
            ['parent' => 'viagens', 'name' => 'canadá', 'description' => ''],
            ['parent' => 'viagens', 'name' => 'chile', 'description' => ''],
            ['parent' => 'viagens', 'name' => 'europa', 'description' => ''],
        ];

        $this->obj->dom = new DOMDocument;
        $this->obj->dom->load(__DIR__ . '/fixtures/wordpress_sample.xml');

        $this->assertEquals($expectedResult, $this->obj->extractCategories());
    }

    public function testExtractInfoPost(): void
    {
        ob_start();

        $obj = $this->getMockBuilder('TikiImporter_Blog_Wordpress')
            ->onlyMethods(['extractComment', 'parseContent', 'identifyInternalLinks'])
            ->getMock();
        $obj->expects($this->exactly(3))->method('extractComment')->willReturn(true);
        $obj->method('parseContent')->willReturn('Test');
        $obj->expects($this->once())->method('identifyInternalLinks')->willReturn(true);

        $obj->permalinks = ['not empty'];

        $expectedResult = [
            'categories' => [
                0 => 'argentina',
                1 => 'montanhismo',
            ],
            'tags' => [
                0 => 'alta montanha',
                1 => 'cerro plata',
                2 => 'mendoza',
                3 => 'montanhismo',
            ],
            'comments' => [
                0 => true,
                1 => true,
                2 => true,
            ],
            'name' => 'Lo más importante son los veinte',
            'author' => 'rodrigo',
            'content' => 'Test',
            'excerpt' => '',
            'wp_id' => 73,
            'created' => '1203784780',
            'type' => 'post',
            'hasInternalLinks' => true,
        ];

        $obj->dom = new DOMDocument;
        $obj->dom->load(__DIR__ . '/fixtures/wordpress_post.xml');
        $data = $obj->extractInfo($obj->dom->getElementsByTagName('item')->item(0));

        $this->assertEquals($expectedResult, $data);

        ob_get_clean();
    }

    public function testExtractInfoPage(): void
    {
        ob_start();

        $obj = $this->getMockBuilder('TikiImporter_Blog_Wordpress')
            ->onlyMethods(['extractComment', 'parseContent', 'identifyInternalLinks'])
            ->getMock();
        $obj->expects($this->exactly(0))->method('extractComment')->willReturn(true);
        $obj->method('parseContent')->willReturn('Test');
        $obj->expects($this->once())->method('identifyInternalLinks')->willReturn(true);

        $obj->permalinks = ['not empty'];

        $expectedResult = [
            'categories' => [
                0 => 'cicloturismo',
                1 => 'viagens',
            ],
            'tags' => [
                0 => 'chapada diamantina',
                1 => 'cicloturismo',
                2 => 'januária',
                3 => 'tv',
                4 => 'youtube',
            ],
            'comments' => [],
            'name' => 'Matéria sobre a viagem de bicicleta entre as chapadas',
            'author' => 'rodrigo',
            'content' => 'Test',
            'excerpt' => '',
            'wp_id' => 107,
            'created' => 1173636811,
            'type' => 'page',
            'hasInternalLinks' => true,
            'revisions' => [
                [
                    'data' => 'Test',
                    'lastModif' => 1173636811,
                    'comment' => '',
                    'user' => 'rodrigo',
                    'ip' => '',
                    'is_html' => true,
                ]
            ],
        ];

        $obj->dom = new DOMDocument;
        $obj->dom->load(__DIR__ . '/fixtures/wordpress_page.xml');
        $data = $obj->extractInfo($obj->dom->getElementsByTagName('item')->item(0));

        $this->assertEquals($expectedResult, $data);

        ob_get_clean();
    }

    public function testExtractCommentShouldReturnFalseForSpamOrTrashOrPingback(): void
    {
        $this->obj->dom = new DOMDocument;
        $this->obj->dom->load(__DIR__ . '/fixtures/wordpress_comment_spam.xml');

        // spam
        $this->assertFalse($this->obj->extractComment($this->obj->dom->getElementsByTagName('comment')->item(0)));

        // trash
        $this->assertFalse($this->obj->extractComment($this->obj->dom->getElementsByTagName('comment')->item(1)));

        // pingback
        $this->assertFalse($this->obj->extractComment($this->obj->dom->getElementsByTagName('comment')->item(2)));
    }

    public function testExtractCommentShouldReturnCommentArray(): void
    {
        $expectedResult = [
            'author' => 'rodrigo',
            'author_email' => 'test@test.com',
            'author_url' => '',
            'author_ip' => '127.0.0.1',
            'created' => 1250059024,
            'data' => '<a href="#comment-33" rel="nofollow">@otavio </a>
Olá Otavio, o Torres del Paine é um parte grande e bem movimentado. Se você for no verão vai encontrar gente sempre, principalmente no W. O circuito grande é um pouco menos movimentado mas ainda sim você encontra pessoas todos os dias. As trilhas estão minimamente sinalizadas. Lembro que levei comigo a carta topográfica do parque e uma bussóla mas não cheguei a utilizá-los.

Se você for fazer apenas caminhadas não terá problemas com os equipamentos que encontra no Brasil. Botas duplas só se estiver pensando em caminhar pelo Campo de Hielo Sur ou alguma outra coisa do tipo uma caminhada de vários dias por glaciares, escalar o Cerro Torre. É importante você ter uma camada impermeável (bota, calça e anorak). Eu fui com uma bota Trilogia e gostei bastante. A calça e o anorak (da Conquista e Trilhas e Rumos, respectivamente) seguraram o tranco. O problema deles é que não respiram direito, em pouco tempo de caminhada eu começo a fever dentro deles de calor, mas paciência. Equipamentos de goretex no Brasil são muito caros e não são necessários para alguma coisa como o Torres del Paine.

Sobre fazer sozinho ou não o W depende muito de você. Depende de quanta de experiência tem. Para uma pessoa que tenha um bom conhecimento de trilhas no Brasil não vejo necessidade alguma de guia, mas isso é uma escolha individual.

Estou a disposição para te ajudar com mais informações. Abraços, Rodrigo.',
            'approved' => 1,
            'type' => '',
        ];

        $this->obj->dom = new DOMDocument;
        $this->obj->dom->load(__DIR__ . '/fixtures/wordpress_comment.xml');

        $comment = $this->obj->extractComment($this->obj->dom->getElementsByTagName('comment')->item(0));

        $this->assertEquals($expectedResult, $comment);
    }

    public function testExtractBlogCreatedDate(): void
    {
        $this->obj->dom = new DOMDocument;
        $this->obj->dom->load(__DIR__ . '/fixtures/wordpress_sample.xml');

        $this->assertEquals(1173636811, $this->obj->extractBlogCreatedDate());
    }

    public function testExtractBlogInfo(): void
    {
        $expectedResult = [
            'title' => 'example.com',
            'link' => 'http://example.com',
            'desc' => 'Software livre, cicloativismo, montanhismo e quem sabe permacultura',
            'lastModif' => 1284989827,
            'created' => 1173636811,
        ];

        $this->obj->dom = new DOMDocument;
        $this->obj->dom->load(__DIR__ . '/fixtures/wordpress_sample.xml');
        $this->obj->extractBlogInfo();

        $this->assertEquals($expectedResult, $this->obj->blogInfo);
    }

    public function testExtractAttachmentsInfo(): void
    {
        $this->obj->dom = new DOMDocument;
        $this->obj->dom->load(__DIR__ . '/fixtures/wordpress_attachments.xml');

        $expectedResult = [
            [
                'name' => 'Parte da tela de administração do TinyMCE Advanced',
                'link' => 'http://example.com/files/tadv2.jpg',
                'created' => '1241461850',
                'author' => 'rodrigo',
                'fileName' => 'tadv2.jpg',
                'sizes' => [
                    'thumbnail' => [
                        'name' => 'tadv2-150x150.jpg',
                        'width' => 150,
                        'height' => 150,
                    ],
                    'medium' => [
                        'name' => 'tadv2-300x171.jpg',
                        'width' => 300,
                        'height' => 171,
                    ],
                ],
            ],
            [
                'name' => 'Hostelaria Las Torres',
                'link' => 'http://example.com/files/1881232-hostelaria-las-torres-0.jpg',
                'created' => '1242095082',
                'author' => 'rodrigo',
                'fileName' => '1881232-hostelaria-las-torres-0.jpg',
                'sizes' => [
                    'thumbnail' => [
                        'name' => '1881232-hostelaria-las-torres-0-150x150.jpg',
                        'width' => 150,
                        'height' => 150,
                    ],
                    'medium' => [
                        'name' => '1881232-hostelaria-las-torres-0-300x225.jpg',
                        'width' => 300,
                        'height' => 225,
                    ],
                ],
            ],
            [
                'name' => 'Caminhando no gelo no Vale do Silêncio',
                'link' => 'http://example.com/files/1881259-caminhando-no-gelo-no-vale-do-sil-ncio-0.jpg',
                'created' => '1242095085',
                'author' => 'rodrigo',
                'fileName' => '1881259-caminhando-no-gelo-no-vale-do-sil-ncio-0.jpg',
                'sizes' => [
                    'thumbnail' => [
                        'name' => '1881259-caminhando-no-gelo-no-vale-do-sil-ncio-0-150x150.jpg',
                        'width' => 150,
                        'height' => 150,
                    ],
                    'medium' => [
                        'name' => '1881259-caminhando-no-gelo-no-vale-do-sil-ncio-0-225x300.jpg',
                        'width' => 225,
                        'height' => 300,
                    ],
                ],
            ],
        ];

        $attachments = $this->obj->extractAttachmentsInfo();

        $this->assertEquals($expectedResult, $attachments);
    }

    public function testDownloadAttachmentsShouldDisplayMessageIfNoAttachments(): void
    {
        ob_start();

        $file = $this->getMockBuilder(File::class)
            ->onlyMethods(['replace', ])
            ->getMock();
        $file->expects($this->exactly(0))->method('replace')->willReturn(1);

        $this->obj->dom = new DOMDocument;
        $this->obj->downloadAttachments();

        $output = ob_get_clean();
        $this->assertEquals("\n\nNo attachments found to import!\n", $output);
    }

    public function testCreateFileGallery(): void
    {
        $last_id = TikiDb::get()->getOne('SELECT max(galleryId) FROM tiki_file_galleries');

        $this->obj->blogInfo['title'] = 'Test';
        $filegalId = $this->obj->createFileGallery();

        $this->assertEquals($last_id + 1, $filegalId);
    }

    public function testDownloadAttachment(): void
    {
        ob_start();

        $last_id = TikiDb::get()->getOne('SELECT max(fileId) FROM tiki_files');
        $adapter = new Laminas\Http\Client\Adapter\Test();

        $adapter->setResponse(
            "HTTP/1.1 200 OK" . "\r\n" .
            "Content-type: image/jpg" . "\r\n" .
            "Content-length: 1034" . "\r\n" .
            "\r\n" .
            'empty content'
        );

        $client = new Laminas\Http\Client();
        $client->setAdapter($adapter);

        $obj = $this->getMockBuilder('TikiImporter_Blog_Wordpress')
            ->onlyMethods(['getHttpClient', 'createFileGallery'])
            ->getMock();
        $obj->expects($this->once())->method('getHttpClient')->willReturn($client);
        $obj->expects($this->once())->method('createFileGallery')->willReturn(1);
        $obj->dom = new DOMDocument;
        $obj->dom->load(__DIR__ . '/fixtures/wordpress_attachments.xml');

        $obj->downloadAttachments();

        $expectedResult = [
            [
                'fileId' => $last_id + 1,
                'oldUrl' => 'http://example.com/files/tadv2.jpg',
                'sizes' => [
                    'thumbnail' => [
                        'name' => 'tadv2-150x150.jpg',
                        'width' => 150,
                        'height' => 150,
                    ],
                    'medium' => [
                        'name' => 'tadv2-300x171.jpg',
                        'width' => 300,
                        'height' => 171,
                    ],
                ],
            ],
            [
                'fileId' => $last_id + 2,
                'oldUrl' => 'http://example.com/files/1881232-hostelaria-las-torres-0.jpg',
                'sizes' => [
                    'thumbnail' => [
                        'name' => '1881232-hostelaria-las-torres-0-150x150.jpg',
                        'width' => 150,
                        'height' => 150,
                    ],
                    'medium' => [
                        'name' => '1881232-hostelaria-las-torres-0-300x225.jpg',
                        'width' => 300,
                        'height' => 225,
                    ],
                ],
            ],
            [
                'fileId' => $last_id + 3,
                'oldUrl' => 'http://example.com/files/1881259-caminhando-no-gelo-no-vale-do-sil-ncio-0.jpg',
                'sizes' => [
                    'thumbnail' => [
                        'name' => '1881259-caminhando-no-gelo-no-vale-do-sil-ncio-0-150x150.jpg',
                        'width' => 150,
                        'height' => 150,
                    ],
                    'medium' => [
                        'name' => '1881259-caminhando-no-gelo-no-vale-do-sil-ncio-0-225x300.jpg',
                        'width' => 225,
                        'height' => 300,
                    ],
                ],
            ],
        ];

        $this->assertEquals($expectedResult, $obj->newFiles);

        $output = ob_get_clean();
        $this->assertEquals("\n\nImporting attachments:\nAttachment tadv2.jpg successfully imported!\nAttachment 1881232-hostelaria-las-torres-0.jpg successfully imported!\nAttachment 1881259-caminhando-no-gelo-no-vale-do-sil-ncio-0.jpg successfully imported!\n3 attachments imported and 0 errors.\n", $output);
    }

    public function testDownloadAttachmentShouldNotCallInsertFileWhenZendHttpClientFails(): void
    {
        ob_start();

        $file = $this->getMockBuilder(File::class)
            ->onlyMethods(['replace'])
            ->getMock();
        $file->expects($this->exactly(0))->method('replace');

        $adapter = new Laminas\Http\Client\Adapter\Test();
        $adapter->setNextRequestWillFail(true);

        $client = new Laminas\Http\Client();
        $client->setAdapter($adapter);

        $obj = $this->getMockBuilder('TikiImporter_Blog_Wordpress')
            ->onlyMethods(['getHttpClient', 'createFileGallery'])
            ->getMock();
        $obj->expects($this->once())->method('createFileGallery')->willReturn(1);
        $obj->expects($this->once())->method('getHttpClient')->willReturn($client);
        $obj->dom = new DOMDocument;
        $obj->dom->load(__DIR__ . '/fixtures/wordpress_attachments.xml');

        $obj->downloadAttachments();

        $this->assertEquals([], $obj->newFiles);

        ob_get_clean();
    }

    public function testDownloadAttachmentShouldNotCallInsertFileWhen404(): void
    {
        ob_start();

        $file = $this->getMockBuilder(File::class)
            ->onlyMethods(['replace'])
            ->getMock();
        $file->expects($this->exactly(0))->method('replace');
        $adapter = new Laminas\Http\Client\Adapter\Test();

        $adapter->setResponse(
            "HTTP/1.1 404 NOT FOUND" . "\r\n" .
            "Content-type: image/jpg" . "\r\n" .
            "Content-length: 1034" . "\r\n" .
            "\r\n" .
            'empty content'
        );

        $client = new Laminas\Http\Client();
        $client->setAdapter($adapter);

        $obj = $this->getMockBuilder('TikiImporter_Blog_Wordpress')
            ->onlyMethods(['getHttpClient', 'createFileGallery'])
            ->getMock();
        $obj->expects($this->once())->method('createFileGallery')->willReturn(1);
        $obj->expects($this->once())->method('getHttpClient')->willReturn($client);
        $obj->dom = new DOMDocument;
        $obj->dom->load(__DIR__ . '/fixtures/wordpress_attachments.xml');

        $obj->downloadAttachments();

        $this->assertEquals([], $obj->newFiles);

        $output = ob_get_clean();
        $this->assertEquals("\n\nImporting attachments:\nUnable to download attachment tadv2.jpg. Error message was: 404 NOT FOUND\nUnable to download attachment 1881232-hostelaria-las-torres-0.jpg. Error message was: 404 NOT FOUND\nUnable to download attachment 1881259-caminhando-no-gelo-no-vale-do-sil-ncio-0.jpg. Error message was: 404 NOT FOUND\n0 attachments imported and 3 errors.\n", $output);
    }

    public function testParseContentAttachmentsUrl(): void
    {
        $this->obj->newFiles = [
            [
                'fileId' => 2,
                'oldUrl' => 'http://example.com/files/1881259-caminhando-no-gelo-no-vale-do-sil-ncio-0.jpg',
                'sizes' => [
                    'thumbnail' => [
                        'name' => '1881259-caminhando-no-gelo-no-vale-do-sil-ncio-0-150x150.jpg',
                        'width' => 150,
                        'height' => 150,
                    ],
                    'medium' => [
                        'name' => '1881259-caminhando-no-gelo-no-vale-do-sil-ncio-0-225x300.jpg',
                        'width' => 225,
                        'height' => 300,
                    ],
                ],
            ],
            [
                'fileId' => 1,
                'oldUrl' => 'http://example.com/files/1881232-hostelaria-las-torres-0.jpg',
                'sizes' => [
                    'thumbnail' => [
                        'name' => '1881232-hostelaria-las-torres-0-150x150.jpg',
                        'width' => 150,
                        'height' => 150,
                    ],
                    'medium' => [
                        'name' => '1881232-hostelaria-las-torres-0-300x225.jpg',
                        'width' => 300,
                        'height' => 225,
                    ],
                ],
            ],
        ];

        $content = file_get_contents(__DIR__ . '/fixtures/wordpress_post_content.txt');

        $expectedResult = file_get_contents(__DIR__ . '/fixtures/wordpress_post_content_parsed.txt');

        $this->assertEquals($expectedResult, $this->obj->parseContentAttachmentsUrl($content));
    }

    public function testParseContentAttachmentsUrlShouldReturnSameContentIfNewFilesIsEmpty(): void
    {
        $content = '';
        $this->obj->newFiles = [];
        $this->assertEquals($content, $this->obj->parseContentAttachmentsUrl($content));
    }

    public function testValidateInput(): void
    {
        $this->obj->dom = new DOMDocument;
        $this->obj->dom->load(__DIR__ . '/fixtures/wordpress_sample.xml');
        $this->assertTrue($this->obj->validateInput());
    }

    public function testValidateInputShouldRaiseExceptionIfInvalidFile(): void
    {
        $this->expectException('DOMException');

        $this->obj->dom = new DOMDocument;
        $this->obj->dom->load(__DIR__ . '/fixtures/wordpress_invalid.xml');
        $this->obj->validateInput();
    }

    public function testValidateInputShouldRaiseExceptionForMediawikiFile(): void
    {
        $this->expectException('DOMException');

        $this->obj->dom = new DOMDocument;
        $this->obj->dom->load(__DIR__ . '/fixtures/mediawiki_sample.xml');
        $this->obj->validateInput();
    }

    public function testMatchWordpressShortcodes(): void
    {
        $content = "[my-shortcode] [my-shortcode/] [my-shortcode foo='bar' bar='foo'] [my-shortcode foo='bar'/]
			[my-shortcode2]content[/my-shortcode2] [my-shortcode2 foo='bar' bar='foo']content[/my-shortcode2]
			[my-shortcode2 foo='bar' bar='foo']\n\ncontent\n\n[/my-shortcode2] [youtube width=\"625\" height=\"517\"]http://www.youtube.com/watch?v=4UCOWCfUkKU[/youtube]";

        $expectedResult = [
            ['[youtube width="625" height="517"]http://www.youtube.com/watch?v=4UCOWCfUkKU[/youtube]', 'youtube', ' width="625" height="517"', 'http://www.youtube.com/watch?v=4UCOWCfUkKU'],
            ["[my-shortcode2 foo='bar' bar='foo']\n\ncontent\n\n[/my-shortcode2]", 'my-shortcode2', " foo='bar' bar='foo'", "\n\ncontent\n\n"],
            ["[my-shortcode2 foo='bar' bar='foo']content[/my-shortcode2]", 'my-shortcode2', " foo='bar' bar='foo'", 'content'],
            ['[my-shortcode2]content[/my-shortcode2]', 'my-shortcode2', '', 'content'],
            ["[my-shortcode foo='bar' bar='foo']", 'my-shortcode', " foo='bar' bar='foo'"],
            ["[my-shortcode foo='bar'/]", 'my-shortcode', " foo='bar'"],
            ['[my-shortcode/]', 'my-shortcode', ''],
            ['[my-shortcode]', 'my-shortcode', ''],
        ];

        $this->assertEquals($expectedResult, $this->obj->matchWordpressShortcodes($content));
    }

    public function testParseWordpressShortcodes(): void
    {
        $content = file_get_contents(__DIR__ . '/fixtures/wordpress_post_content_shortcodes.txt');
        $expectedResult = file_get_contents(__DIR__ . '/fixtures/wordpress_post_content_shortcodes_parsed.txt');
        $this->assertEquals($expectedResult, $this->obj->parseWordpressShortcodes($content));
    }

    public function testInsertItem_shouldCallStoreNewLink(): void
    {
        $obj = $this->getMockBuilder('TikiImporter_Blog_Wordpress')
            ->onlyMethods(['storeNewLink', 'insertPost'])
            ->getMock();
        $obj->expects($this->once())->method('storeNewLink');
        $obj->expects($this->once())->method('insertPost')->willReturnOnConsecutiveCalls(false);

        $item = ['type' => 'post', 'name' => 'Any name'];

        $obj->insertItem($item);
    }

    public function testStoreNewLinkWithSefUrlEnabled(): void
    {
        global $prefs, $base_url;
        $prefs['feature_sefurl'] = 'y';
        $base_url = 'http://localhost/tiki';

        $this->obj->permalinks = [
            107 => [
                'oldLinks' => [
                    'http://example.com/materia/',
                    'http://example.com/?p=107',
                ],
            ],
            36 => [
                'oldLinks' => [
                    'http://example.com/2008/01/20/circuito-grande-torres-del-paine/',
                    'http://example.com/?p=36',
                ],
            ],
        ];

        $expectedResult = $this->obj->permalinks;
        $expectedResult[107]['newLink'] = 'http://localhost/tiki/materia';
        $expectedResult[36]['newLink'] = 'http://localhost/tiki/blogpost10';

        $this->obj->storeNewLink('materia', ['wp_id' => 107, 'type' => 'page']);
        $this->obj->storeNewLink(10, ['wp_id' => 36, 'type' => 'post']);

        $this->assertEquals($expectedResult, $this->obj->permalinks);
    }

    public function testStoreNewLinkWithSefUrlDisabled(): void
    {
        global $prefs, $base_url;
        $prefs['feature_sefurl'] = 'n';
        $base_url = 'http://localhost/tiki';

        $this->obj->permalinks = [
            107 => [
                'oldLinks' => [
                    'http://example.com/materia/',
                    'http://example.com/?p=107',
                ],
            ],
            36 => [
                'oldLinks' => [
                    'http://example.com/2008/01/20/circuito-grande-torres-del-paine/',
                    'http://example.com/?p=36',
                ],
            ],
        ];

        $expectedResult = $this->obj->permalinks;
        $expectedResult[107]['newLink'] = 'http://localhost/tiki/tiki-index.php?page=materia';
        $expectedResult[36]['newLink'] = 'http://localhost/tiki/tiki-view_blog_post.php?postId=10';

        $this->obj->storeNewLink('materia', ['wp_id' => 107, 'type' => 'page']);
        $this->obj->storeNewLink(10, ['wp_id' => 36, 'type' => 'post']);

        $this->assertEquals($expectedResult, $this->obj->permalinks);
    }

    public function testInsertData_shouldSetObjIdOnItemsArray(): void
    {
        ob_start();
        $_POST['replaceInternalLinks'] = 'on';

        $obj = $this->getMockBuilder('TikiImporter_Blog_Wordpress')
            ->onlyMethods(['insertItem', 'createBlog', 'replaceInternalLinks'])
            ->getMock();
        $obj->expects($this->once())->method('createBlog');
        $obj->expects($this->exactly(2))->method('insertItem')->willReturnOnConsecutiveCalls(2, 'Page name');

        $obj->permalinks = ['not empty'];

        $obj->parsedData = [
            'pages' => [
                ['type' => 'page', 'name' => 'Page name'],
            ],
            'posts' => [
                ['type' => 'post', 'name' => 'Post title'],
            ],
            'tags' => [],
            'categories' => [],
        ];

        $expectedResult = [
            ['type' => 'post', 'name' => 'Post title', 'objId' => 2],
            ['type' => 'page', 'name' => 'Page name', 'objId' => 'Page name'],
        ];

        $obj->expects($this->once())->method('replaceInternalLinks')->with($expectedResult);

        $obj->insertData();

        unset($_POST['replaceInternalLinks']);

        ob_get_clean();
    }

    public function testInsertData_shouldNotCallReplaceInternalLinks(): void
    {
        ob_start();
        $obj = $this->getMockBuilder('TikiImporter_Blog_Wordpress')
            ->onlyMethods(['insertItem', 'createBlog', 'replaceInternalLinks'])
            ->getMock();
        $obj->expects($this->once())->method('createBlog');
        $obj->expects($this->exactly(2))->method('insertItem')->willReturnOnConsecutiveCalls(2, 'Page name');
        $obj->expects($this->exactly(0))->method('replaceInternalLinks');

        $obj->parsedData = [
            'pages' => [
                ['type' => 'page', 'name' => 'Page name'],
            ],
            'posts' => [
                ['type' => 'post', 'name' => 'Post title'],
            ],
            'tags' => [],
            'categories' => [],
        ];

        $obj->insertData();

        ob_get_clean();
    }

    public function testReplaceInternalLinks(): void
    {
        $this->obj->permalinks = [
            36 => [
                'oldLinks' => [
                    'http://example.com/2008/01/20/circuito-grande-torres-del-paine/',
                    'http://example.com/?p=36',
                ],
                'newLink' => 'http://localhost/tiki/tiki-view_blog_post.php?postId=10',
            ],
        ];

        $items = [
            ['type' => 'page', 'name' => 'materia', 'hasInternalLinks' => true, 'objId' => 'materia'],
            ['type' => 'post', 'name' => 'Any name', 'hasInternalLinks' => true, 'objId' => 10],
            ['type' => 'post', 'name' => 'Any name', 'hasInternalLinks' => false, 'objId' => 11],
        ];

        $content = file_get_contents(__DIR__ . '/fixtures/wordpress_post_content_internal_links.txt');

        TikiDb::get()->query('INSERT INTO tiki_pages (pageName, data) VALUES (?, ?)', ['materia', $content]);
        TikiDb::get()->query('INSERT INTO tiki_blog_posts (postId, data) VALUES (?, ?)', [10, $content]);

        $this->obj->replaceInternalLinks($items);

        $newPageContent = TikiDb::get()->getOne('SELECT data FROM tiki_pages WHERE pageName = "materia"');
        $newPostContent = TikiDb::get()->getOne('SELECT data FROM tiki_blog_posts WHERE postId = 10');

        $this->assertStringEqualsFile(
            __DIR__
            . '/fixtures/wordpress_post_content_internal_links_replaced.txt',
            $newPageContent
        );
        $this->assertStringEqualsFile(
            __DIR__
            . '/fixtures/wordpress_post_content_internal_links_replaced.txt',
            $newPostContent
        );
    }

    public function testGetHtaccessRules(): void
    {
        $this->obj->permalinks = [
            [
                'oldLinks' => [
                    'http://example.com/2008/01/20/circuito-grande-torres-del-paine/',
                    '/2008/01/20/circuito-grande-torres-del-paine/',
                    'http://example.com/?p=36',
                    '/?p=36',
                ],
                'newLink' => 'http://localhost/tiki/tiki-view_blog_post.php?postId=10',
            ],
            [
                'oldLinks' => [
                    'http://example.com/contato/',
                    '/contato-tiki/',
                ],
                'newLink' => 'http://localhost/tiki70/tiki-index.php?page=contato tiki',
            ],
        ];

        $expectedResult = "Redirect 301 /2008/01/20/circuito-grande-torres-del-paine/ http://localhost/tiki/tiki-view_blog_post.php?postId=10\n"
            . "Redirect 301 /?p=36 http://localhost/tiki/tiki-view_blog_post.php?postId=10\n"
            . "Redirect 301 /contato-tiki/ http://localhost/tiki70/tiki-index.php?page=contato+tiki\n";

        $this->assertEquals($expectedResult, $this->obj->getHtaccessRules());
    }
}
