{* $Id$ *}
{if $blog_post_context eq 'view_blog' and $allow_comments eq 'y' and $prefs.feature_blogposts_comments eq 'y' && $tiki_p_read_comments eq 'y'}
	<div class="status">
		<a class="link linkcomments" href="{$post_info.postId|sefurl:blogpost}#comments">
			{if $post_info.comments == 0 && $tiki_p_post_comments eq 'y'}
				{icon name="comment"} {tr}Leave a comment{/tr}
			{else}
				{if $post_info.comments == 1}
                    {icon name="comment"} {tr _0=$post_info.comments}%0 comment{/tr}
				{else}
                    {icon name="comments"} {tr _0=$post_info.comments}%0 comments{/tr}
				{/if}
			{/if}
		</a>
	</div>
{/if}
