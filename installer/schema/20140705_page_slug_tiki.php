<?php

function post_20140705_page_slug_tiki($installer)
{
	$pages = $installer->table('tiki_pages');
	$names = $pages->fetchColumn('pageName', []);
	foreach ($names as $name) {
		try {
			$pages->update(['pageSlug' => urlencode($name)], [
				'pageName' => $name,
			]);
		} catch (Exception $e) {
			// Presumably, Duplicate entry found. Too bad $e->getCode() returns 0
			// We need to find a pageSlug name which is not already taken
			// Shorten so we can add numbers afterwards in the form blabla…bla_42 from _0 to _999
			$prefixPageSlug = substr(urlencode($name), 0, 156);
			// Strip urlencoding %xx which might have been truncated at the end
			if (strrpos($prefixPageSlug, '%') && strrpos($prefixPageSlug, '%') >= 154) {
				$prefixPageSlug = substr($prefixPageSlug, 0, strrpos($prefixPageSlug, '%'));
			}
			for ($i = 0; $i <= 999; $i++) {
				$pageSlug = $prefixPageSlug . "_$i";
				$duplicates = $pages->fetchColumn('pageName', ['pageSlug' => $pageSlug ]);
				if (count($duplicates) == 0) {
					$pages->update(['pageSlug' => $pageSlug], ['pageName' => $name]);
					break;
				}
			}
		}
	}
}
