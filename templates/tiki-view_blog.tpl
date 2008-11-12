<div class="navbar">
	{button href="tiki-list_blogs.php" _text="{tr}List Blogs{/tr}"}
</div>

{if strlen($heading) > 0}
  {eval var=$heading}
{else}
  {include file="blog-heading.tpl"}
{/if}

{if $use_find eq 'y'}
  <div class="blogtools">
    <table>
      <tr>
        <td>
          <form action="tiki-view_blog.php" method="get">
            <input type="hidden" name="sort_mode" value="{$sort_mode|escape}" />
            <input type="hidden" name="blogId" value="{$blogId|escape}" />
            {tr}Find:{/tr} 
            <input type="text" name="find" value="{$find|regex_replace:"/\"/":"'"}" /> 
            <input type="submit" name="search" value="{tr}Find{/tr}" />
          </form>
        </td>
          
        <td>
        <!--
          {tr}Sort posts by:{/tr}
          <a class="bloglink" href="tiki-view_blog.php?blogId={$blogId}&amp;offset={$offset}&amp;sort_mode={if $sort_mode eq 'created_desc'}created_asc{else}created_desc{/if}">{tr}Date{/tr}</a>
        -->	
        </td>
      </tr>
    </table>
  </div>
{/if}

{section name=ix loop=$listpages}
  <a name="postId{$listpages[ix].postId}"></a>
  <div class="blogpost">
    <div class="posthead">
      {if $use_title eq 'y'}
        <h3>{$listpages[ix].title}</h3>
      {else}
        <h3>{$listpages[ix].created|tiki_short_datetime}</h3>
      {/if}
        
      <table>
        <tr>
          <td align="left">
            <span class="posthead">
              {if $use_title eq 'y'}
                <small> 
                  {tr}Posted by{/tr} {$listpages[ix].user|userlink}  
                  {if $show_avatar eq 'y'}
                    {$listpages[ix].avatar}
                  {/if} 
                  {tr}on{/tr} {$listpages[ix].created|tiki_short_datetime}
                </small>
              {else}
                <small> 
                  {tr}Posted by{/tr} {$listpages[ix].user} 
                  {if $show_avatar eq 'y'}
                    {$listpages[ix].avatar}
                  {/if}
                </small>
              {/if}
            </span>
          </td>
            
          <td align="right">
            {if ($ownsblog eq 'y') or ($user and $listpages[ix].user eq $user) or $tiki_p_blog_admin eq 'y'}
              <a class="blogt" href="tiki-blog_post.php?blogId={$listpages[ix].blogId}&amp;postId={$listpages[ix].postId}">{icon _id='page_edit'}</a> 
              &nbsp;
              <a class="blogt" href="tiki-view_blog.php?blogId={$blogId}&amp;remove={$listpages[ix].postId}">{icon _id='cross' alt='{tr}Remove{/tr}'}</a>
            {/if}

            {if $user and $prefs.feature_notepad eq 'y' and $tiki_p_notepad eq 'y'}
              <a title="{tr}Save to notepad{/tr}" href="tiki-view_blog.php?blogId={$blogId}&amp;savenotepad={$listpages[ix].postId}">{icon _id='disk'
							alt='{tr}Save to notepad{/tr}'}</a>
            {/if}
          </td>
        </tr>
      </table>
    </div> <!-- posthead -->

    {if $prefs.feature_freetags eq 'y' and $tiki_p_view_freetags eq 'y'}
      {if $listpages[ix].freetags.data|@count >0}
        <div class="freetaglist">
          {foreach from=$listpages[ix].freetags.data item=taginfo}
            <a class="freetag" href="tiki-browse_freetags.php?tag={$taginfo.tag}">{$taginfo.tag}</a> 
          {/foreach}
        </div>
      {/if}
    {/if}

    <div class="postbody">
      {$listpages[ix].parsed_data}
      {if $listpages[ix].pages > 1}
        <a class="link" href="{$listpages[ix].postId|sefurl:blogpost}">
          {tr}read more{/tr} ({$listpages[ix].pages} {tr}pages{/tr})
        </a>
      {/if}

      {if $prefs.blogues_feature_copyrights  eq 'y' and $prefs.wikiLicensePage}
        {if $prefs.wikiLicensePage == $page}
          {if $tiki_p_edit_copyrights eq 'y'}
            <p class="editdate">{tr}To edit the copyright notices{/tr} 
              <a href="copyrights.php?page={$copyrightpage}">{tr}Click Here{/tr}</a>.
            </p>
          {/if}
        {else}
          <p class="editdate">{tr}The content on this page is licensed under the terms of the{/tr} 
            <a href="tiki-index.php?page={$prefs.wikiLicensePage}&amp;copyrightpage={$page|escape:"url"}">
              {$prefs.wikiLicensePage}
            </a>.
          </p>
        {/if}
      {/if}

      <hr style="clear:both"/>

      <table>
        <tr>
          <td>
            <small>
              <a class="link" href="{$listpages[ix].postId|sefurl:blogpost}">{tr}Permalink{/tr}</a>
              {if $allow_comments eq 'y' and $prefs.feature_blogposts_comments eq 'y'}
                <a class="link" href="tiki-view_blog_post.php?find={$find}&amp;blogId={$blogId}&amp;offset={$offset}&amp;sort_mode={$sort_mode}&amp;postId={$listpages[ix].postId}&amp;show_comments=1">
								{$listpages[ix].comments}
								{if $listpages[ix].comments == 1}
									{tr}comment{/tr}
								{else}
									{tr}comments{/tr}
									</a>
								{/if}
              {/if}
            </small>
          </td>

          <td style='text-align:right'>
            <a href='tiki-print_blog_post.php?postId={$listpages[ix].postId}'>{icon _id='printer' alt='{tr}Print{/tr}'}</a>
            <a href='tiki-send_blog_post.php?postId={$listpages[ix].postId}'>{icon _id='email' alt='{tr}Email This Post{/tr}'}</a>
          </td>
        </tr>
      </table>
    </div> <!-- postbody -->
  </div> <!--blogpost -->
{/section}

{pagination_links cant=$cant step=$maxRecords offset=$offset}{/pagination_links}

{if $prefs.feature_blog_comments == 'y'
  && (($tiki_p_read_comments  == 'y'
  && $comments_cant != 0)
  ||  $tiki_p_post_comments  == 'y'
  ||  $tiki_p_edit_comments  == 'y')}

  <div id="page-bar">
		{if $comments_cant gt 0}
			{assign var=thisbuttonclass value='highlight'}
		{else}
			{assign var=thisbuttonclass value=''}
		{/if}
	  {if $comments_cant == 0 or ($tiki_p_read_comments == 'n' and $tiki_p_post_comments == 'y')}
			{assign var=thistext value="{tr}Add Comment{/tr}"}
		{elseif $comments_cant == 1}
			{assign var=thistext value="{tr}1 comment{/tr}"}
		{else}
			{assign var=thistext value="$comments_cant&nbsp;{tr}Comments{/tr}"}
		{/if}
		{button href="#comments" _flip_id="comzone" _class=$thisbuttonclass _text=$thistext}
  </div>

  {include file=comments.tpl}
{/if}
