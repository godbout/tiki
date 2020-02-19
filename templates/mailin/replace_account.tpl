{extends "layout_view.tpl"}

{block name="title"}
	{title}{$title}{/title}
{/block}

{block name="content"}
	<form action="{service controller=mailin action=replace_account}" method="post">
		{ticket mode=confirm}
		<input type="hidden" name="accountId" value="{$accountId|escape}">
		<div class="form-group row">
			<div class="offset-md-3 col-md-9">
				<div class="form-check">
					<label>
						<input type="checkbox" class="form-check-input" name="active" value="1" {if $info.active eq 'y'}checked{/if}>
						{tr}Active{/tr}
					</label>
				</div>
			</div>
		</div>
		<div class="form-group row">
			<label for="account" class="col-form-label col-md-3">{tr}Account name{/tr}</label>
			<div class="col-md-9">
				<input type="text" name="account" value="{$info.account|escape}" class="form-control">
				<p class="form-text">Used as the sender email address for any errors</p>
			</div>
		</div>
		<div class="form-group row">
			<label for="type" class="col-form-label col-md-3">{tr}Type{/tr}</label>
			<div class="col-md-9">
				<select name="type" class="form-control">
					{foreach $mailinTypes as $intype => $detail}
						<option value="{$intype|escape}" {if $intype eq $info.type}selected{/if} {if ! $detail.enabled}disabled{/if}>{$detail.name|escape}</option>
					{/foreach}
				</select>
				<div class="form-text">
					<p>{tr}Wiki (multiple action) allows to prefix the subject with GET:, PREPEND: or APPEND:{/tr}</p>
					<p>{tr}Reply handler requires notifications to be enabled and the reply email pattern to be configured.{/tr}</p>
				</div>
			</div>
		</div>
		<div class="form-group row">
			<label for="host" class="col-form-label col-md-3">{tr}POP server{/tr} / {tr}Port{/tr}</label>
			<div class="col-md-3">
				<select name="protocol" class="form-control">
					<option value="pop" {if $info.protocol eq 'pop'}selected{/if}>{tr}POP{/tr}</option>
					<option value="imap" {if $info.protocol eq 'imap'}selected{/if}>{tr}IMAP{/tr}</option>
				</select>
			</div>
			<div class="col-md-4">
				<input type="text" name="host" value="{$info.host|escape}" class="form-control" placeholder="{tr}Hostname{/tr}">
			</div>
			<div class="col-md-2">
				<input type="text" name="port" value="{$info.port|escape}" class="form-control" placeholder="{tr}Port{/tr}">
			</div>
		</div>
		<div class="form-group row">
			<label for="username" class="col-form-label col-md-3">{tr}Username{/tr}</label>
			<div class="col-md-6">
				<input type="text" name="username" value="{$info.username|escape}" class="form-control">
			</div>
		</div>
		<div class="form-group row">
			<label for="pass" class="col-form-label col-md-3">{tr}Password{/tr}</label>
			<div class="col-md-4">
				<input type="password" name="pass" value="{$info.password|escape}" class="form-control" autocomplete="new-password">
			</div>
		</div>
		{if $prefs.feature_articles eq 'y'}
			<div class="form-group row">
				<label for="article_topicId" class="col-form-label col-md-3">{tr}Article Topic{/tr}</label>
				<div class="col-md-9">
					<select name="article_topicId" class="form-control">
						{foreach $topics as $topicId=>$topic}
							<option value="{$topicId|escape}" {if $info.article_topicId eq $topicId}selected="selected"{/if}>{$topic.name|escape}</option>
						{/foreach}
						<option value="" {if $info.article_topicId eq 0}selected="selected"{/if}>{tr}None{/tr}</option>
					</select>
					{if $tiki_p_admin_cms eq 'y'}
						<div class="form-text">
							<a href="tiki-admin_topics.php" class="link">{tr}Admin Topics{/tr}</a>
						</div>
					{/if}
				</div>
			</div>
			<div class="form-group row">
				<label for="article_type" class="col-form-label col-md-3">{tr}Article Type{/tr}</label>
				<div class="col-md-9">
					<select name="article_type" class="form-control">
						<option value="">{tr}None{/tr}</option>
						{foreach $types as $type}
							<option value="{$type.type|escape}" {if $info.article_type eq $type.type}selected="selected"{/if}>{$type.type|escape}</option>
						{/foreach}
					</select>
					{if $tiki_p_admin_cms eq 'y'}
						<div class="form-text">
							<a href="tiki-admin_types.php" class="link">{tr}Admin Types{/tr}</a>
						</div>
					{/if}
				</div>
			</div>
		{/if}
		{if $prefs.feature_trackers eq 'y'}
			<div class="form-group row">
				<label for="galleryId" class="col-form-label col-md-3">{tr}Tracker{/tr}</label>
				<div class="col-md-9">
					<select name="trackerId" class="form-control">
						<option value="">{tr}None{/tr}</option>
					</select>
					<div class="form-text">
						<a href="tiki-list_trackers.php" class="link">{tr}View trackers{/tr}</a>
					</div>
				</div>
			</div>
		{/if}
		{if $prefs.feature_file_galleries eq 'y'}
			<div class="form-group row">
				<label for="galleryId" class="col-form-label col-md-3">{tr}File Gallery{/tr}</label>
				<div class="col-md-9">
					<select name="galleryId" class="form-control">
						<option value="">{tr}None{/tr}</option>
						<option value="{$prefs.fgal_root_id}" {if $info.galleryId eq $prefs.fgal_root_id}selected="selected"{/if}>{tr}Root{/tr}</option>
						{foreach $galleries.data as $galInfo}
							<option value="{$galInfo.id|escape}" {if $info.galleryId eq $galInfo.id}selected="selected"{/if}>{$galInfo.name|escape}</option>
						{/foreach}
					</select>
					<div class="form-text">
						<a href="tiki-list_file_gallery.php" class="link">{tr}View file galleries{/tr}</a>
					</div>
				</div>
			</div>
		{/if}
		<div class="form-group row">
			<div class="offset-md-3 col-md-9">
				<div class="form-check">
					<label>
						<input type="checkbox" class="form-check-input" name="anonymous" value="1" {if $info.anonymous eq 'y'}checked{/if}>
						{tr}Allow anonymous access{/tr}
					</label>
					<div class="form-text">
						{tr}Warning: Enabling anonymous access will disable all permission checking for mailed-in content.{/tr}
					</div>
				</div>
			</div>
		</div>
		<div class="form-group row">
			<div class="offset-md-3 col-md-9">
				<div class="form-check">
					<label>
						<input type="checkbox" class="form-check-input" name="admin" value="1" {if $info.admin eq 'y'}checked{/if}>
						{tr}Allow admin access{/tr}
					</label>
					<div class="form-text">
						{tr}Administrators have full access to the system. Disabling admin mail-in is the safest option.{/tr}
					</div>
				</div>
			</div>
		</div>
		<div class="form-group row">
			<div class="offset-md-3 col-md-9">
				{if $prefs.feature_wiki_attachments eq 'y'}
					<div class="form-check">
						<label>
							<input type="checkbox" class="form-check-input" name="attachments" value="1" {if $info.attachments eq 'y'}checked{/if}>
							{tr}Allow attachments{/tr}
						</label>
					</div>
				{else}
					<a href="tiki-admin.php?page=wiki&cookietab=2&highlight=feature_wiki_attachments">Activate attachments</a>
				{/if}
			</div>
		</div>
		<div class="form-group row">
			<div class="offset-md-3 col-md-9">
				{if $prefs.trackerfield_files eq 'y'}
					<div class="form-check">
						<label>
							<input type="checkbox" class="form-check-input" name="tracker_attachments" value="1" {if $info.attachments eq 'y'}checked{/if}>
							{tr}Allow attachments for Store Mail in Tracker{/tr}
						</label>
					</div>
				{else}
					<a href="tiki-admin.php?page=trackers#content_admin1-3">Enable Files Tracker Field</a>
				{/if}
			</div>
		</div>
		<div class="form-group row">
			<div class="offset-md-3 col-md-9">
				{if $prefs.feature_wiki eq 'y'}
					<div class="form-check">
						<label>
							<input type="checkbox" class="form-check-input" name="routing" value="1" {if $info.routing eq 'y'}checked{/if}>
							{tr}Allow routing{/tr}
						</label>
						<div class="form-text">
							{tr}Allow per user routing of incoming email to structures.{/tr}
						</div>
					</div>
				{else}
					<a href="tiki-admin.php?page=wiki&cookietab=1&highlight=feature_wiki">Activate wiki</a>
				{/if}
			</div>
		</div>
		<div class="form-group row">
			<div class="offset-md-3 col-md-9">
				{if $prefs.feature_wiki_attachments eq 'y'}
					<div class="form-check">
						<label>
							<input type="checkbox" class="form-check-input" name="show_inlineImages" value="1" {if $info.show_inlineImages eq 'y'}checked{/if}>
							{tr}Show inline images{/tr}
						</label>
							<div class="form-text">
							{tr}For HTML email, attempt to create a WYSIWYG wiki-page.{/tr}
						</div>
					</div>
				{else}
					<a href="tiki-admin.php?page=wiki&cookietab=2&highlight=feature_wiki_attachments">Activate attachments</a>
				{/if}
			</div>
		</div>
		<div class="form-group row">
			<div class="offset-md-3 col-md-9">
				<div class="form-check">
					<label>
						<input type="checkbox" class="form-check-input" name="save_html" value="1" {if $info.save_html eq 'y'}checked{/if}>
						{tr}Keep HTML format{/tr}
					</label>
					<div class="form-text">
						{tr}Always save Email in HTML format as a wiki page in HTML format, regardless of editor availability or selection.{/tr}
					</div>
				</div>
			</div>
		</div>
		<div class="form-group row">
			<label for="discard_after" class="col-form-label col-md-3">{tr}Discard to the end from{/tr}</label>
			<div class="col-md-9">
				<input type="text" name="discard_after" value="{$info.discard_after|escape}" class="form-control">
			</div>
		</div>
		<div class="form-group row">
			<label for="cartegoryId" class="col-form-label col-md-3">{tr}Auto-assign category{/tr}</label>
			<div class="col-md-6">
				{if $prefs.feature_categories eq 'y'}
					{object_selector type='category' _simplename='categoryId' _simpleid='categoryId' _simplevalue=$info.categoryId|escape}
					<div class="form-text">{tr}Only affects wiki-put, when creating a new wiki page{/tr}</div>
				{else}
					<a href="tiki-admin.php?page=features&highlight=feature_categories">Activate categories</a>
				{/if}
			</div>
		</div>
		<div class="form-group row">
			<label for="namespace" class="col-form-label col-md-3">{tr}Auto-assign namespace{/tr}</label>
			<div class="col-md-6">
				{if $prefs.namespace_enabled eq 'y'}
					<input type="text" name="namespace" value="{$info.namespace|escape}" class="form-control">
					<div class="form-text">{tr}Only affects wiki-put, when creating a new wiki page{/tr}</div>
				{else}
					<a href="tiki-admin.php?page=wiki&cookietab=2&highlight=namespace_enabled">Activate namespaces</a>
				{/if}
			</div>
		</div>
		<div class="form-group row">
			<div class="offset-md-3 col-md-9">
				<div class="form-check">
					<label>
						<input type="checkbox" class="form-check-input" name="respond_email" value="1" {if $info.respond_email eq 'y'}checked{/if}>
						{tr}Email response when no access{/tr}
					</label>
				</div>
			</div>
		</div>
		<div class="form-group row">
			<div class="offset-md-3 col-md-9">
				<div class="form-check">
					<label>
						<input type="checkbox" class="form-check-input" name="leave_email" value="1" {if $info.leave_email eq 'y'}checked{/if}>
						{tr}Leave email on server on error{/tr}
					</label>
					<div class="form-text">
						{tr}Leave the email on the mail server, when an error occurs and the content has not been integrated into Tiki.{/tr}
					</div>
				</div>
			</div>
		</div>
		<div class="submit offset-md-3 col-md-9">
			<input type="submit" name="new_acc" value="{if $accountId eq 0}{tr}Add Account{/tr}{else}{tr}Save{/tr}{/if}" class="btn btn-primary">
		</div>
	</form>
{/block}
