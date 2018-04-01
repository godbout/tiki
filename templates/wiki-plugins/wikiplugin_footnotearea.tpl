<div class="footnotearea">
	{foreach $footnotes as $number => $footnote}
		<div {if $footnote['class']}class="{$footnote['class']}" {/if}id="footnote{$footnote.globalId}">
			<span>
				<a href="#ref_footnote{$footnote.globalId}">{$number+1|numStyle:"decimal"}</a>
			</span>
			{$footnote['data']}
		</div>
	{/foreach}
</div>