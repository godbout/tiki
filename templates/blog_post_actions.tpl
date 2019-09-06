{* $Id$ *}
<div class="actions blogpostactions float-right btn-group">
	{if ! $js}<ul class="cssmenu_horiz"><li>{/if}
	<a class="btn btn-info btn-sm dropdown-toggle" data-toggle="dropdown" data-hover="dropdown" href="#"title="{tr}Blog post actions{/tr}">
		{icon name="menu-extra"}
	</a>
	<div class="dropdown-menu">
		<h6 class="dropdown-header">
			{tr}Blog post actions{/tr}
		</h6>
		<div class="dropdown-divider"></div>
			<a class="dropdown-item" href="tiki-print_blog_post.php?postId={$post_info.postId}">
				{icon name="print" _menu_text='y' _menu_icon='y' alt="{tr}Print{/tr}"}
			</a>
		{if $blog_post_context ne 'print'}
			{if ($ownsblog eq 'y') or ($user and $post_info.user eq $user) or $tiki_p_blog_admin eq 'y'}
					<a class="dropdown-item" href="tiki-blog_post.php?blogId={$post_info.blogId}&amp;postId={$post_info.postId}">
						{icon name="edit"} {tr}Edit{/tr}
					</a>
					<a class="dropdown-item" href="tiki-view_blog.php?blogId={$post_info.blogId}&amp;remove={$post_info.postId}" onclick="confirmSimple(event, '{tr}Delete this item?{/tr}', '{ticket mode=get}')">
						{icon name="remove"} {tr}Remove{/tr}
					</a>
			{/if}
			{if $tiki_p_admin eq 'y' || $tiki_p_assign_perm_blog eq 'y'}
				<span class="dropdown-item">{permission_link mode=text type="blog" permType="blogs" id=$post_info.blogId}</span>
			{/if}
			{if $user and $prefs.feature_notepad eq 'y' and $tiki_p_notepad eq 'y'}
					{if $blog_post_context eq 'view_blog'}
						<a class="dropdown-item" href="tiki-view_blog.php?blogId={$post_info.blogId}&amp;savenotepad={$post_info.postId}" onclick="confirmSimple(event, '{tr}Save to notepad?{/tr}', '{ticket mode=get}')">
							{icon name="notepad"} {tr}Save to notepad{/tr}
						</a>
					{else}
						<a class="dropdown-item" href="tiki-view_blog_post.php?postId={$smarty.request.postId}&amp;savenotepad=1" onclick="confirmSimple(event, '{tr}Save to notepad?{/tr}', '{ticket mode=get}')">
							{icon name="notepad"} {tr}Save to notepad{/tr}
						</a>
					{/if}
			{/if}
		{/if}
		{if $prefs.feature_blog_sharethis eq "y"}
				{literal}
				<script type="text/javascript">
					//Create your sharelet with desired properties and set button element to false
					var object{/literal}{$postId}{literal} = SHARETHIS.addEntry({}, {button:false});
					//Output your customized button
					document.write('<a class="dropdown-item" id="share{/literal}{$postId}{literal}" href="#">{/literal}{icon name="sharethis"} {tr}ShareThis{/tr}{literal}</a>');
					//Tie customized button to ShareThis button functionality.
					var element{/literal}{$postId}{literal} = document.getElementById("share{/literal}{$postId}{literal}");
					object{/literal}{$postId}{literal}.attachButton(element{/literal}{$postId}{literal});
				</script>
				{/literal}
		{/if}
	</div>
	{if ! $js}</li></ul>{/if}
</div>
