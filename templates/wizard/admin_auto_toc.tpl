{* $Id$ *}

<div class="media">
	<div class="mr-4">
		<span class="fa-stack fa-lg" style="width: 100px;" title="Configuration Wizard">
			<i class="fas fa-cog fa-stack-2x"></i>
			<i class="fas fa-flip-horizontal fa-magic fa-stack-1x ml-4 mt-4"></i>
		</span></div>
	<div class="media-body">
		{icon name="file-text-o" size=3 iclass="float-sm-right"}
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
