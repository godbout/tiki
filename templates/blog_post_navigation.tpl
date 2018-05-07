{if $blog_post_context eq 'view_blog_post'}
	<div class="row pager">
		<div class="col-6 previous">
			{if $post_info.adjacent.prev}
				{self_link _script=$post_info.adjacent.prev.postId|sefurl:blogpost _title="{tr}Previous post{/tr}" _noauto='y'}{icon name="arrow-left"} {$post_info.adjacent.prev.title|truncate}{/self_link}
			{/if}
		</div>
		<div class="col-6 text-right next">
			{if $post_info.adjacent.next}
				{self_link _script=$post_info.adjacent.next.postId|sefurl:blogpost _title="{tr}Next post{/tr}" _noauto='y'}{$post_info.adjacent.next.title|truncate} {icon name="arrow-right"} {/self_link}
			{/if}
		</div>
	</div>
{/if}
