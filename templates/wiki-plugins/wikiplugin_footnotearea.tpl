<div class="footnotearea">
	{foreach $footnotes as $number => $footnote}
		<div {if $footnote['class']}class="{$footnote['class']}" {/if}id="footnote{$number}">
			<span>
				<a href="#ref_footnote{$number}">{$number|numStyle:"decimal"}</a>
			</span>
			{$footnote['data']}
		</div>
	{/foreach}
</div>