{* $Id$ *}
{strip}
	{if $file.isgal eq 1}
		{if $file.perms.tiki_p_view_file_gallery eq 'y'}
			{self_link _icon_name='file-archive-open' _menu_text=$menu_text _menu_icon=$menu_icon galleryId=$file.id}
				{tr}Browse{/tr}
			{/self_link}
		{/if}

		{if $file.perms.tiki_p_create_file_galleries eq 'y'}
			{self_link _icon_name='edit' _menu_text=$menu_text _menu_icon=$menu_icon edit_mode=1 galleryId=$file.id}
				{tr}Properties{/tr}
			{/self_link}
		{/if}

		{if $file.perms.tiki_p_upload_files eq 'y'
			and ( $file.perms.tiki_p_admin_file_galleries eq 'y' or ($user and $file.user eq $user)
			or $file.public eq 'y' )}
			<a href="tiki-upload_file.php?galleryId={$file.id}{if !empty($filegals_manager)}&amp;filegals_manager={$filegals_manager|escape}{/if}">
				<div class="iconmenu">
					{icon name='upload'} {tr}Upload{/tr}
				</div>
			</a>
		{/if}

		{if $file.perms.tiki_p_assign_perm_file_gallery eq 'y'}
			<div class="iconmenu">
				{if $file.public neq 'y'}
					{permission_link mode=text type="file gallery" permType="file galleries" id=$file.id title=$file.name}
				{else}
					{permission_link mode=text type="file gallery" permType="file galleries" id=$file.id title=$file.name}
				{/if}
			</div>
		{/if}
		{if $prefs.feature_webdav eq 'y'}
			{assign var=virtual_path value=$file.id|virtual_path:'filegal'}
			<a style="behavior: url(#default#AnchorClick);" href="{$virtual_path}" folder="{$virtual_path}">
				{icon name="file-archive-open"}{tr}Open as WebFolder{/tr}
			</a>
		{/if}

		{if $file.perms.tiki_p_create_file_galleries eq 'y'}
			{self_link _icon_name='remove' _menu_text=$menu_text _menu_icon=$menu_icon removegal=$file.id _onclick="confirmSimple(event, '{tr}Delete gallery?{/tr}', '{ticket mode=get}')"}
				{tr}Delete{/tr}
			{/self_link}
		{/if}
	{else}
		{if $prefs.javascript_enabled eq 'y'}
			{if $menu_text neq 'y'}
				{* This is needed for the 'Upload New Version' action to be correctly displayed
				when there is only an icon menu (or actions in a column of the table) *}
				<div style="float:left">
			{/if}
		{/if}

		{if $file.type|truncate:6:'':true eq 'image/' and $file.perms.tiki_p_download_files eq 'y'}
			<a href="{$file.id|sefurl:display}">
				{icon name='view' _menu_text=$menu_text _menu_icon=$menu_icon alt="{tr}Display{/tr}"}
			</a>
			{if $file.perms.tiki_p_upload_files eq 'y' and $prefs.feature_draw eq 'y'}
				{if
					$file.type eq 'image/svg+xml' 	or
					$file.type eq 'image/jpeg' 		or
					$file.type eq 'image/gif' 		or
					$file.type eq 'image/png' 		or
					$file.type eq 'image/tiff'
				}
					<a class="draw dialog" data-name="{$file.filename}" title="{tr}Edit: {/tr}{$file.filename}" href="tiki-edit_draw.php?fileId={$file.id}&galleryId={$file.galleryId}" data-fileid='{$file.id}' data-galleryid='{$file.galleryId}' onclick='$(document).trigger("hideCluetip"); return $(this).ajaxEditDraw();'>
						{icon name='edit' _menu_text=$menu_text _menu_icon=$menu_icon alt="{tr}Edit{/tr}"}
					</a>
				{/if}
			{/if}
		{elseif $tiki_p_upload_files eq 'y' and $prefs.wikiplugin_diagram eq 'y'
			and $file.id|file_diagram}
			<a href="tiki-display.php?fileId={$file.id}">
				{icon name='view' _menu_text=$menu_text _menu_icon=$menu_icon alt="{tr}Display{/tr}"}
			</a>
			<form id="edit-diagram-1" target="_blank" action="tiki-editdiagram.php" method="post">
				<input type="hidden" value="{$file.id}" name="fileId">
				<a href="javascript:void(0)" onclick="$('#edit-diagram-1').submit()">
					{icon name='edit' _menu_text=$menu_text _menu_icon=$menu_icon alt="{tr}Edit{/tr}"}
				</a>
			</form>
		{elseif $file.type eq 'text/csv' and $prefs.feature_sheet eq 'y'}
			<a href="tiki-view_sheets.php?fileId={$file.id}">
				{icon name='view' _menu_text=$menu_text _menu_icon=$menu_icon alt="{tr}Display{/tr}"}
			</a>
		{elseif $prefs.fgal_pdfjs_feature eq 'y' and $file.type eq 'application/pdf'}
			<a href="tiki-display.php?fileId={$file.id}">
				{icon name='view' _menu_text=$menu_text _menu_icon=$menu_icon alt="{tr}Display{/tr}"}
			</a>
		{elseif $prefs.fgal_viewerjs_feature eq 'y' and ($file.type eq 'application/pdf' or $file.type|strpos:'application/vnd.oasis.opendocument.' !== false)}
			<a href="{$prefs.fgal_viewerjs_uri}#{$base_url}{$file.id|sefurl:display}">
				{icon name='view' _menu_text=$menu_text _menu_icon=$menu_icon alt="{tr}Display{/tr}"}
			</a>
		{elseif ($file.type eq 'application/vnd.oasis.opendocument.text'
			or $file.type eq 'application/octet-stream') and $prefs.feature_docs eq 'y'}
			<a href="tiki-edit_docs.php?fileId={$file.id}">
				{icon name='view' _menu_text=$menu_text _menu_icon=$menu_icon alt="{tr}Display{/tr}"}
			</a>
			{if $file.perms.tiki_p_upload_files eq 'y'}
				<a href="tiki-edit_docs.php?fileId={$file.id}&edit">
					{icon name='edit' _menu_text=$menu_text _menu_icon=$menu_icon alt="{tr}Edit{/tr}"}
				</a>
			{/if}
		{elseif $prefs.h5p_enabled eq 'y' and $file.type eq 'application/zip' and preg_match('/\.h5p$/i', $file.filename)}
			<a href="{service controller='h5p' action='embed' fileId=$file.id}">
				{icon name='view' _menu_text=$menu_text _menu_icon=$menu_icon alt="{tr}Display{/tr}"}
			</a>
		{/if}
		{if $prefs.fgal_pdfjs_feature eq 'y' and $prefs.fgal_convert_documents_pdf eq 'y' and ($file.type|file_can_convert_to_pdf)}
			<a href="tiki-display.php?fileId={$file.id}">
				{icon name='view' _menu_text=$menu_text _menu_icon=$menu_icon alt="{tr}Display as PDF{/tr}"}
			</a>
		{/if}

		{if (isset($file.p_download_files) and $file.p_download_files eq 'y')
			or (!isset($file.p_download_files) and $file.perms.tiki_p_download_files eq 'y')}
			{if $gal_info.type eq 'podcast' or $gal_info.type eq 'vidcast'}
				<a href="{$download_path}{$file.path}">
			{else}
				<a href="{$file.id|sefurl:file}">
			{/if}
				{if $prefs.feature_file_galleries_save_draft eq 'y' and $file.nbDraft gt 0}
					{assign var=download_action_title value="{tr}Download current version{/tr}"}
				{else}
					{assign var=download_action_title value="{tr}Download{/tr}"}
				{/if}
				{icon _menu_text=$menu_text _menu_icon=$menu_icon name='floppy' alt="$download_action_title"}
			</a>
		{/if}

		<a href="{$file.id|sefurl:display}">
			{icon name='eye' _menu_text=$menu_text _menu_icon=$menu_icon alt="{tr}Browser display{/tr} ({tr}Raw{/tr} / {tr}Download{/tr})"}
		</a>

		{if $gal_info.archives gt -1}
			{if isset($file.nbArchives) and $file.nbArchives gt 0}
				{assign var=nb_archives value=$file.nbArchives}
				<a href="tiki-file_archives.php?fileId={$file.fileId}{if !empty($filegals_manager)}&amp;filegals_manager={$filegals_manager|escape}{/if}">
					{icon _menu_text=$menu_text _menu_icon=$menu_icon name='file-archive' alt="{tr}Archives{/tr} ($nb_archives)"}
				</a>
			{else}
				{icon _menu_text=$menu_text _menu_icon=$menu_icon name='file-archive' alt="{tr}Archives{/tr}"}
			{/if}
			{assign var=replace_action_title value="{tr}Upload new version{/tr}"}
		{else}
			{assign var=replace_action_title value="{tr}Replace{/tr}"}
		{/if}

		{if $prefs.feature_file_galleries_save_draft eq 'y'}
			{if $file.nbDraft gt 0}
				{assign var=replace_action_title value="{tr}Replace draft{/tr}"}
			{else}
				{assign var=replace_action_title value="{tr}Upload draft{/tr}"}
			{/if}
		{/if}
		{* can edit if I am admin or the owner of the file or the locker of the file or if I have the perm to edit file on this gallery *}
		{if $file.perms.tiki_p_admin_file_galleries eq 'y'
			or ($file.lockedby and $file.lockedby eq $user)
			or (!$file.lockedby and (($user and $user eq $file.user)
			or $file.perms.tiki_p_edit_gallery_file eq 'y'))}
			{if $file.archiveId == 0}
				{if $prefs.feature_file_galleries_save_draft eq 'y' and $file.nbDraft gt 0}
					{self_link _icon_name='ok' _menu_text=$menu_text _menu_icon=$menu_icon validate=$file.fileId galleryId=$file.galleryId _onclick="confirmSimple(event, '{tr}Validate draft?{/tr}', '{ticket mode=get}')"}
						{tr}Validate your draft{/tr}
					{/self_link}
					{self_link _icon_name='remove' _menu_text=$menu_text _menu_icon=$menu_icon draft=remove remove=$file.fileId galleryId=$file.galleryId _onclick="confirmSimple(event, '{tr}Delete draft?{/tr}', '{ticket mode=get}')"}
						{tr}Delete your draft{/tr}
					{/self_link}
				{/if}

				{if $file.perms.tiki_p_admin_file_galleries eq 'y' or empty($file.locked)
					or (isset($file.locked) and $file.locked and $file.lockedby eq $user)
					or $gal_info.lockable ne 'y'}

					<a href="tiki-upload_file.php?galleryId={$file.galleryId}&amp;fileId={$file.id}{if !empty($filegals_manager)}&amp;filegals_manager={$filegals_manager|escape}{/if}">
						{icon _menu_text=$menu_text _menu_icon=$menu_icon name='upload' alt="{$replace_action_title}"}
					</a>

					{if $prefs.fgal_display_properties eq 'y'}
						<a href="tiki-upload_file.php?galleryId={$file.galleryId}&amp;fileId={$file.id}{if !empty($filegals_manager)}&amp;filegals_manager={$filegals_manager|escape}{/if}">
							{icon _menu_text=$menu_text _menu_icon=$menu_icon name='edit' alt="{tr}Edit properties{/tr}"}
						</a>
						{* using &amp; causes an error for some reason - therefore using plain & *}
						<a href="tiki-list_file_gallery.php?galleryId={$file.galleryId}&fileId={$file.id}&action=refresh_metadata{if isset($view)}&view={$view}{/if}" onclick="confirmSimple(event, '{tr}Refresh metadata?{/tr}', '{ticket mode=get}')">
							{icon _menu_text=$menu_text _menu_icon=$menu_icon name='tag' alt="{tr}Refresh metadata{/tr}"}
						</a>
						{if $view != 'page'}
							<a href="tiki-list_file_gallery.php?galleryId={$file.galleryId}&fileId={$file.id}&view=page">
								{icon _menu_text=$menu_text _menu_icon=$menu_icon name='textfile' alt="{tr}Page view{/tr}"}
							</a>
						{/if}
					{/if}
				{/if}

				{if $file.perms.tiki_p_assign_perm_file_gallery eq 'y'}
					<div class="iconmenu">
						{permission_link mode=text type="file" permType="file galleries" id=$file.id title=$file.name parentId=$file.galleryId}
					</div>
				{/if}

				{if $gal_info.lockable eq 'y' and $file.isgal neq 1}
					{if $file.lockedby}
						{* Notify user in confirm message when file is locked by another user *}
						{if $user && $user !== $file.user}
							{self_link _icon_name='unlock' _menu_text=$menu_text _menu_icon=$menu_icon lock='n' fileId=$file.fileId galleryId=$file.galleryId _onclick="confirmSimple(event, '{tr _0="$file.user"}File already locked by %0{/tr}', '{ticket mode=get}')"}
								{tr}Unlock{/tr}
							{/self_link}
						{else}
							<form action="{$smarty.server.PHP_SELF}" method="post">
								{ticket}
								<input type="hidden" name="lock" value="n">
								<input type="hidden" name="fileId" value="{$file.fileId|escape:'attr'}">
								<input type="hidden" name="galleryId" value="{$file.galleryId|escape:'attr'}">
								<button type="submit" class="btn btn-link link-list" onclick="checkTimeout()">
									{icon name='unlock'} {tr}Unlock{/tr}
								</button>
							</form>
						{/if}
					{else}
						{if (isset($file.p_download_files) and $file.p_download_files eq 'y')
							or (!isset($file.p_download_files) and $file.perms.tiki_p_download_files eq 'y')}
							{if $prefs.javascript_enabled eq 'y'}
								{* with javascript, the main page will be reloaded to lock the file and change its lockedby information *}
								{self_link _icon_name='download' _menu_text=$menu_text _menu_icon=$menu_icon lock='y' fileId=$file.fileId galleryId=$file.galleryId _onclick="window.open('{$file.fileId|sefurl:file:with_next}'); confirmSimple(event, '{tr}Lock file?{/tr}', '{ticket mode=get}')"}
									{tr}Download and lock{/tr}
								{/self_link}
							{else}
								{* without javascript, the lockedby information won't be refreshed until the user do it itself *}
								<a href="{$file.fileId|sefurl:file:with_next}lock=y">
									{icon _menu_text=$menu_text _menu_icon=$menu_icon name='download' alt="{tr}Download and lock{/tr}"}
								</a>
							{/if}
						{/if}
						<form action="{$smarty.server.PHP_SELF}" method="post">
							{ticket}
							<input type="hidden" name="lock" value="y">
							<input type="hidden" name="fileId" value="{$file.fileId|escape:'attr'}">
							<input type="hidden" name="galleryId" value="{$file.galleryId|escape:'attr'}">
							<button type="submit" class="btn btn-link link-list" onclick="checkTimeout()">
								{icon name='lock'} {tr}Lock{/tr}
							</button>
						</form>
					{/if}
				{/if}
			{/if}
		{/if}

		{if $prefs.feature_webdav eq 'y'}
			{assign var=virtual_path value=$file.fileId|virtual_path}

			{if $prefs.feature_file_galleries_save_draft eq 'y'}
				{self_link _icon_name="file-archive-open" _menu_text=$menu_text _menu_icon=$menu_icon _script="javascript:open_webdav('$virtual_path')" _noauto="y" _ajax="n"}
					{tr}Open your draft in WebDAV{/tr}
				{/self_link}
			{else}
				{self_link _icon_name="file-archive-open" _menu_text=$menu_text _menu_icon=$menu_icon _script="javascript:open_webdav('$virtual_path')" _noauto="y" _ajax="n"}
					{tr}Open in WebDAV{/tr}
				{/self_link}
			{/if}
		{/if}

		{if $prefs.feature_share eq 'y' and $tiki_p_share eq 'y'}
			<a href="tiki-share.php?url={$tikiroot}{$file.id|sefurl:file|escape:'url'}">
				{icon _menu_text=$menu_text _menu_icon=$menu_icon name='share' alt="{tr}Share a link to this file{/tr}"}
			</a>
		{/if}

		{if $file.perms.tiki_p_admin_file_galleries eq 'y'
			or (!$file.lockedby and (($user and $user eq $file.user)
			or ($file.perms.tiki_p_edit_gallery_file eq 'y'
			and $file.perms.tiki_p_remove_files eq 'y')))}
				<a href="tiki-list_file_gallery.php?remove={$file.fileId}&galleryId={$file.galleryId}" onclick="confirmSimple(event, '{tr}Delete file?{/tr}', '{ticket mode=get}')">
					{icon _menu_text=$menu_text _menu_icon=$menu_icon name='remove' alt="{tr}Delete{/tr}"}
				</a>
		{/if}
	{/if}
{/strip}
