{* $Id$ *}

<div class="media">
	<div class="mr-4">
		<span class="fa-stack fa-lg" style="width: 100px;" title="Configuration Wizard">
			<i class="fa fa-gear fa-stack-2x"></i>
			<i class="fa fa-rotate-270 fa-magic fa-stack-2x ml-5"></i>
		</span></div>
	<div class="media-body">
		{icon name="file-text-o" size=3 iclass="pull-right"}
		<h4 class="mt-0 mb-4">{tr}Automatic table of contents for a wiki page{/tr}</h4>
		<fieldset>
			<legend>{tr}Auto TOC options{/tr}</legend>
			{preference name=wiki_inline_auto_toc}
			{preference name=wiki_toc_pos}
			{preference name=wiki_toc_offset}
			<br>
			<em>{tr}See also{/tr} <a href="http://doc.tiki.org/tiki-index.php?page=Auto+TOC" target="_blank">{tr}Auto TOC{/tr} @ doc.tiki.org</a></em>
		</fieldset>
	</div>
</div>
