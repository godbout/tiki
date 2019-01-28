{* $Id$ *}

{title help="Webmail" admpage="webmail"}{tr}Webmail{/tr}{/title}

{include file='tiki-mytiki_bar.tpl'}
<table class="table">
	<tr>
		<td>
			{self_link _icon_name='mailbox' _icon_size='3' locSection='mailbox'}{tr}Mailbox{/tr}{/self_link}
			<br>
			{self_link locSection='mailbox'}{tr}Mailbox{/tr}{/self_link}
		</td>
		<td>
			{self_link _icon_name='compose' _icon_size='3' locSection='compose' _width='48' _height='48'}{tr}Compose{/tr}{/self_link}
			<br>
			{self_link locSection='compose'}{tr}Compose{/tr}{/self_link}
		</td>
		{if $prefs.feature_contacts eq 'y'}
			<td>
				{self_link _icon_name='contacts' _icon_size='3' _script='tiki-contacts.php' _width='48' _height='48'}{tr}Contacts{/tr}{/self_link}
				<br>
				{self_link _script='tiki-contacts.php'}{tr}Contacts{/tr}{/self_link}
			</td>
		{/if}
		<td width="50%"></td>
		<td>
			{self_link _icon_name='settings' _icon_size='3' locSection='settings' _width='48' _height='48'}{tr}Settings{/tr}{/self_link}
			<br>
			{self_link locSection='settings'}{tr}Settings{/tr}{/self_link}
		</td>
	</tr>
</table>

<hr/>

{if $locSection eq 'settings'}
	{tabset name='tabs_webmail_settings'}

		{tab name="{tr}List{/tr}"}
			{if count($accounts) != 0}
				<h2>{tr}Personal email accounts{/tr}</h2>
				<div class="{if $js}table-responsive{/if}"> {* table-responsive class cuts off css drop-down menus *}
					<table class="table table-striped table-hover">
						<tr>
							<th>{tr}Active{/tr}</th>
							<th>{tr}Account{/tr}</th>
							<th>{tr}Server{/tr}</th>
							<th>{tr}Username{/tr}</th>
							<th></th>
						</tr>

						{section name=ix loop=$accounts}
							{$active = ($accounts[ix].current eq 'y' and $accounts[ix].user eq $user or $accounts[ix].accountId eq $mailCurrentAccount)}
							<tr>
								<td class="icon">
									{if !$active}
										<span class="small">
											{self_link current=$accounts[ix].accountId _icon_name='star' _menu_text='y' _menu_icon='y' _class="text-muted"}
												{tr}Activate{/tr}
											{/self_link}
										</span>
									{else}
										<span class="text-warning">{icon name='star'}</span>
									{/if}
								</td>
								<td class="username">
									{self_link accountId=$accounts[ix].accountId _title="{$accounts[ix].account|escape}|{tr}Edit Account{/tr}" _class='tips'}
										{$accounts[ix].account|escape}
									{/self_link}
								</td>
								<td class="text">
									{if !empty($accounts[ix].imap)}{tr}IMAP:{/tr} {$accounts[ix].imap} ({$accounts[ix].port})
									{elseif !empty($accounts[ix].mbox)}{tr}Mbox:{/tr} {$accounts[ix].mbox}
									{elseif !empty($accounts[ix].maildir)}{tr}Maildir:{/tr} {$accounts[ix].maildir}
									{elseif !empty($accounts[ix].pop)}{tr}POP3:{/tr} {$accounts[ix].pop} ({$accounts[ix].port}){/if}
								</td>
								<td class="username">
									{$accounts[ix].username}
								</td>
								<td class="action">
									{actions}
										{strip}
											<action>
												{self_link accountId=$accounts[ix].accountId _icon_name='edit' _menu_text='y' _menu_icon='y'}
													{tr}Edit{/tr}
												{/self_link}
											</action>
											<action>
												{self_link remove=$accounts[ix].accountId _icon_name='remove' _menu_text='y' _menu_icon='y'}
													{tr}Delete{/tr}
												{/self_link}
											</action>
											{if !$active}
												<action>
													{self_link current=$accounts[ix].accountId _icon_name='ok' _menu_text='y' _menu_icon='y'}
														{tr}Activate{/tr}
													{/self_link}
												</action>
											{/if}
										{/strip}
									{/actions}
								</td>
							</tr>
						{sectionelse}
							{norecords _colspan=5}
						{/section}
					</table>
				</div>
			{/if}

			{if $tiki_p_use_group_webmail eq 'y'}
				{if count($pubAccounts) != 0}
					<h2>{tr}Group email accounts{/tr}</h2>
					<div class="{if $js}table-responsive{/if}"> {* table-responsive class cuts off css drop-down menus *}
						<table class="table table-striped table-hover">
							<tr>
								<th>{tr}Active{/tr}</th>
								<th>{tr}Account{/tr}</th>
								<th>{tr}Server{/tr}</th>
								<th>{tr}Username{/tr}</th>
								<th></th>
							</tr>

							{section name=ixp loop=$pubAccounts}
								{if $pubAccounts[ixp].current eq 'y' and $pubAccounts[ixp].user eq $user or $pubAccounts[ixp].accountId eq $mailCurrentAccount}{assign var=active value=true}{else}{assign var=active value=false}{/if}
								<tr>
									<td class="icon">
										{if !$active}
											{self_link _icon_name='star-half' current=$pubAccounts[ixp].accountId}{tr}Activate{/tr}{/self_link}
										{else}
											{icon name='star' iclass='tips' ititle=':{tr}This is the active account.{/tr}'}
										{/if}
									</td>
									<td class="username">
										{if !$active}
											{self_link current=$pubAccounts[ixp].accountId _title="{tr}Activate{/tr}"}{$pubAccounts[ixp].account}{/self_link}
										{else}
											{$pubAccounts[ixp].account|escape}
										{/if}
									</td>
									<td class="text">
										{if !empty($pubAccounts[ixp].imap)}
											{tr}IMAP:{/tr} {$pubAccounts[ixp].imap} ({$pubAccounts[ixp].port})
										{elseif !empty($pubAccounts[ixp].mbox)}
											{tr}Mbox:{/tr} {$pubAccounts[ixp].mbox}
										{elseif !empty($pubAccounts[ixp].maildir)}
											{tr}Maildir:{/tr} {$pubAccounts[ixp].maildir}
										{elseif !empty($pubAccounts[ixp].pop)}
											{tr}POP3:{/tr} {$pubAccounts[ixp].pop} ({$pubAccounts[ixp].port})
										{/if}
									</td>
									<td class="username">{$pubAccounts[ixp].username}</td>
									<td class="action">
										{actions}
											{strip}
												{if $tiki_p_admin_group_webmail eq 'y' or $tiki_p_admin eq 'y'}
													<action>
														{self_link _icon_name='edit' accountId=$pubAccounts[ixp].accountId _menu_text='y' _menu_icon='y'}
															{tr}Edit{/tr}
														{/self_link}
													</action>
													<action>
														{self_link _icon_name='delete' remove=$pubAccounts[ixp].accountId _menu_text='y' _menu_icon='y'}
															{tr}Delete{/tr}
														{/self_link}
													</action>
												{/if}
												{if !$active}
													<action>
														{self_link _icon_name='ok' current=$pubAccounts[ixp].accountId _menu_text='y' _menu_icon='y'}
															{tr}Activate{/tr}
														{/self_link}
													</action>
												{/if}
											{/strip}
										{/actions}
									</td>
								</tr>
							{sectionelse}
								{norecords _colspan=5}
							{/section}
						</table>
					</div>
				{/if}
			{/if}
		{/tab}

		{if $accountId eq 0}{assign var="tablab" value="{tr}Create Account{/tr}"}{else}{assign var="tablab" value="{tr}Edit Account{/tr}"}{/if}
		{tab name=$tablab}
			<h2>{$tablab}</h2>
			{if $tiki_p_admin_personal_webmail eq 'y' or $tiki_p_admin_group_webmail eq 'y' or !isset($info.user) or $user eq $info.user}
				<div id="settingsFormDiv">
					<form action="tiki-webmail.php" method="post" name="settings">
						<input type="hidden" name="accountId" value="{$accountId|escape}">
						<input type="hidden" name="locSection" value="settings">
						<table class="table">
							<tr>
								<td>
									<label for="account">{tr}Account name{/tr}</label>
								</td>
								<td colspan="3">
									<input type="text" name="account" id="account" value="{$info.account|escape}" class="form-control">
								</td>
							</tr>
							<tr>
								<td colspan="4">
									<h3>{tr}Incoming servers (used in this order){/tr}</h3>
								</td>
							</tr>
							<tr>
								<td><label for="imap">{tr}IMAP server{/tr}</label></td>
								<td>
									<input type="text" name="imap" id="imap" value="{$info.imap|escape}" class="form-control">
								</td>
								<td>
									<label for="port">{tr}Port{/tr}</label>
								</td>
								<td>
									<input type="text" name="port" id="port" value="{$info.port}" class="form-control">
								</td>
							</tr>
							<tr>
								<td>
									<label for="mbox">{tr}Mbox filepath{/tr}</label>
								</td>
								<td colspan="3">
									<input type="text" name="mbox" id="mbox" value="{$info.mbox|escape}" class="form-control">
								</td>
							</tr>
							<tr>
								<td>
									<label for="maildir">{tr}Maildir mail directory{/tr}</label>
								</td>
								<td>
									<input type="text" name="maildir" id="maildir" value="{$info.maildir|escape}" class="form-control">
								</td>
								<td>
									<label for="useSSL">{tr}Use SSL{/tr}</label>
								</td>
								<td>
									<input type="checkbox" name="useSSL" id="useSSL" value="y"{if $info.useSSL eq 'y'} checked="checked"{/if} class="form-control">
								</td>
							</tr>
							<tr>
								<td>
									<label for="pop">{tr}POP server{/tr}</label>
								</td>
								<td colspan="3">
									<input type="text" name="pop" id="pop" value="{$info.pop|escape}" class="form-control">
								</td>
							</tr>
							<tr>
								<td colspan="4">
									<h3>{tr}Outgoing server{/tr}</h3>
								</td>
							</tr>
							<tr>
								<td>
									<label for="smtp">{tr}SMTP server{/tr}</label>
								</td>
								<td>
									<input type="text" name="smtp" id="smtp" value="{$info.smtp|escape}" class="form-control">
								</td>
								<td>
									<label for="smtpPort">{tr}Port{/tr}</label>
								</td>
								<td>
									<input type="text" name="smtpPort" id="smtpPort" value="{$info.smtpPort}" class="form-control">
								</td>
							</tr>
							<tr>
								<td>{tr}SMTP requires authentication{/tr}</td>
								<td colspan="3" class="radio">
									<label>
										<input type="radio" name="useAuth" value="y" {if $info.useAuth eq 'y'}checked="checked"{/if}>
										{tr}Yes{/tr}
									</label>
									<label>
										<input type="radio" name="useAuth" value="n" {if $info.useAuth eq 'n'}checked="checked"{/if}>
										{tr}No{/tr}
									</label>
								</td>
							</tr>
							<tr>
								<td>
									<label for="fromEmail">{tr}From email{/tr}</label>
								</td>
								<td colspan="3">
									<input type="text" name="fromEmail" id="fromEmail" value="{$info.fromEmail}" class="form-control">
									<br>
									<em>{tr}Uses the user's login email address if empty{/tr} ({if !empty($userEmail)}{$userEmail}{else}<strong>{tr}No email set:{/tr}</strong> {icon name="next" href="tiki-user_preferences.php?cookietab=2"}{/if})</em>
								</td>
							</tr>
							<tr>
								<td colspan="4">
									<h3>{tr}Account details{/tr}</h3>
								</td>
							</tr>
							<tr>
								<td>
									<label for="username">{tr}Username{/tr}</label>
								</td>
								<td colspan="3">
									<input type="text" name="username" id="username" value="{$info.username|escape}" class="form-control">
								</td>
							</tr>
							<tr>
								<td>
									<label for="pass">{tr}Password{/tr}</label>
								</td>
								<td colspan="3">
									<input type="password" name="pass" id="pass" value="{$info.pass|escape}" class="form-control">
								</td>
							</tr>
							<tr>
								<td>
									<label for="msgs">{tr}Messages per page{/tr}</label>
								</td>
								<td>
									<input type="text" name="msgs" id="msgs" value="{$info.msgs|escape}" class="form-control">
								</td>
								<td></td>
								<td></td>
							</tr>

							{if ($tiki_p_admin_group_webmail eq 'y' and $tiki_p_admin_personal_webmail eq 'y') or $tiki_p_admin eq 'y'}
								<tr>
									<td>{tr}Group (shared mail inbox) or private{/tr}</td>
									<td colspan="3" class="radio">
										<label>
											<input type="radio" name="flagsPublic" value="y" {if $info.flagsPublic eq 'y'}checked="checked"{/if}>
											{tr}Group{/tr}
										</label>
										<label>
											<input type="radio" name="flagsPublic" value="n" {if $info.flagsPublic eq 'n'}checked="checked"{/if}>
											{tr}Private{/tr}
										</label>
									</td>
								</tr>
							{else}
								<tr>
									<td></td>
									<td>
										<input type="hidden" name="flagsPublic" {if $tiki_p_admin_group_webmail eq 'y'}value="y"{else} value="n"{/if}>
										{if $tiki_p_admin_group_webmail eq 'y'}
											{tr}This will be a group mail account.{/tr}{else}{tr}This will be a personal mail account.{/tr}
										{/if}
									</td>
								</tr>
							{/if}
							<tr>
								<td>
									<label for="autoRefresh">{tr}Auto-refresh page time{/tr}</label>
								</td>
								<td colspan="3">
									<input type="text" name="autoRefresh" id="autoRefresh" size="4" value="{$info.autoRefresh|escape}" class="form-control">
									{tr}seconds (0 = no auto refresh){/tr}
								</td>
							</tr>
							{if $tiki_p_admin_group_webmail eq 'y'}
								<tr>
									<td colspan="4">
										{include file='categorize.tpl'}
									</td>
								</tr>
							{/if}
							<tr>
								<td>&nbsp;</td>
								<td colspan="3">
									<input type="submit" class="btn btn-primary" name="new_acc" value="{if $accountId eq ''}{tr}Add{/tr}{else}{tr}Update{/tr}{/if}">
									<input type="submit" class="btn btn-secondary" name="cancel_acc" value="{tr}Cancel{/tr}">
								</td>
							</tr>
						</table>
					</form>
				</div>
			{else}
				{remarksbox type="info" title="{tr}Permissions{/tr}"}
					{tr}You do not have the correct permissions to Add or Edit a webmail account. <br>Please contact your administrator and ask for "admin_personal_webmail" or "admin_group_webmail" permission.{/tr}
				{/remarksbox}
			{/if}
		{/tab}

	{/tabset}
{/if}


{if $locSection eq 'mailbox'}
	<table width="100%">
		<tr>
			<td>
				{if empty($filter)}<strong>{tr}Show All{/tr}</strong>{else}{self_link filter=''}{tr}Show All{/tr}{/self_link}{/if} |
				{if $filter eq 'unread'}<strong>{tr}Show Unread{/tr}</strong>{else}{self_link filter='unread'}{tr}Show Unread{/tr}{/self_link}{/if} |
				{if $filter eq 'flagged'}<strong>{tr}Show Flagged{/tr}</strong>{else}{self_link filter='flagged'}{tr}Show Flagged{/tr}{/self_link}{/if} |
				{if $autoRefresh != 0}
					{assign var=tip value="{tr}Auto refresh set for every $autoRefresh seconds.{/tr}"}
					{self_link refresh_mail=1 _title=$tip}{tr}Refresh now{/tr}{/self_link}
					<em></em>
				{else}
					{self_link refresh_mail=1}{tr}Refresh{/tr}{/self_link}
				{/if}
			</td>
			<td align="right" style="text-align:right">
				{if $total gt 0}
					{if $flagsPublic eq 'y'}
						{tr}Group messages{/tr}
					{else}
						{tr}Messages{/tr}
					{/if}
					{$showstart} {tr}to{/tr} {$showend} {tr}of{/tr} {$total}
				{else}
					{tr}No messages{/tr}
				{/if}
				| {if $first}{self_link start=$first}{tr}First{/tr}{/self_link}{else}{tr}First{/tr}{/if}
				| {if $prevstart}{self_link start=$prevstart}{tr}Prev{/tr}{/self_link}{else}{tr}Prev{/tr}{/if}
				| {if $nextstart}{self_link start=$nextstart}{tr}Next{/tr}{/self_link}{else}{tr}Next{/tr}{/if}
				| {if $last}{self_link start=$last}{tr}Last{/tr}{/self_link}{else}{tr}Last{/tr}{/if}
			</td>
		</tr>
	</table>
	<br>
	<form action="tiki-webmail.php" method="post" name="mailb">
		<div class="row form-group row">
			<div class="col-sm-1">
				<input type="submit" class="btn btn-danger btn-sm" name="delete" value="{tr}Delete{/tr}">
				<input type="hidden" name="quickFlag" value="">
				<input type="hidden" name="quickFlagMsg" value="">
				<input type="hidden" name="locSection" value="mailbox">
				<input type="hidden" name="start" value="{$start|escape}">
				<input type="hidden" name="refresh_mail" value="">
			</div>
			<div class="col-sm-4">
				<select name="action" class="form-control">
					<option value="flag">{tr}Mark as flagged{/tr}</option>
					<option value="unflag">{tr}Mark as unflagged{/tr}</option>
					<option value="read">{tr}Mark as read{/tr}</option>
					<option value="unread">{tr}Mark as unread{/tr}</option>
				</select>
			</div>
			<div class="col-sm-1">
				<input type="submit" class="btn btn-primary btn-sm" name="operate" value="{tr}Mark{/tr}">
			</div>
			<label for="folder" class="col-sm-2 form-label">
				{tr}Folder{/tr}
			</label>
			<div class="col-sm-4">
				<select name="folder" id="folder" class="form-control" onchange="$(this).form().find('input[name=start]').val('').nextAll('input[name=refresh_mail]').val('1').form().submit();return false;">
					{foreach $folders as $globalName => $folder}
						<option value="{$globalName|escape}"{if $folder.disabled} disabled="disabled"{/if}{if $globalName eq $currentFolder} selected="selected"{/if}>
							{$folder.label|escape}
						</option>
					{/foreach}
				</select>
			</div>
		</div>
		<div class="row">
			<div class="col-sm-12">
				<div class="table-responsive">
					<table class="table table-hover table-striped webmail_list">
						<tr>
							<th>{select_all checkbox_names='msg[]'}</th>
							<th>&nbsp;</th>
							<th>{tr}Subject{/tr}</th>
							<th>
								{if strpos($currentFolder|lower, 'sent') === false}
									{tr}Sender{/tr}
								{else}
									{tr}To{/tr}
								{/if}
							</th>
							<th>{tr}Date{/tr}</th>
							<th>{tr}Size{/tr}</th>
						</tr>
						{foreach $list as $msg}
							{if $msg.isRead eq 'y'}
								{assign var=class value="webmail_read text-muted"}
							{elseif $msg.isFlagged eq 'y'}
								{assign var=class value="bg-warning webmail_flagged"}
							{else}
								{assign var=class value="webmail_unread"}
							{/if}
							<tr class="{$class}">
								<td class="checkbox-cell">
									<div class="form-check">
										<input type="checkbox" name="msg[]" value="{$msg.msgid}">
										<input type="hidden" name="realmsg[{$msg.msgid}]" value="{$msg.realmsgid|escape}">
									</div>
								</td>
								<td class="icon">
									{if $msg.isFlagged eq 'y'}
										{icon class="" name="flag" title="{tr}Flagged{/tr}" href="javascript: submit_form('{$msg.realmsgid|escape}','n')"}
									{else}
										{if $prefs.webmail_quick_flags eq 'y'}
										{icon class="" name="flag-o" title="{tr}Unflagged{/tr}" href="javascript: submit_form('{$msg.realmsgid|escape}','y')"}
										{/if}
									{/if}
									{if $msg.isReplied eq 'y'}
										{icon class="" name="reply" title="{tr}Replied{/tr}"}
									{/if}
								</td>
								<td class="text">
									{if $msg.isRead neq 'y'}<strong>{/if}{self_link msgid=$msg.msgid locSection='read'}{$msg.subject}{/self_link}{if $msg.isRead neq 'y'}</strong>{/if}
									{if $msg.has_attachment}<img src="img/webmail/clip.gif" alt="{tr}Clip{/tr}">{/if}
								</td>
								<td class="email">
									{if strpos($currentFolder|lower, 'sent') === false}
										{$msg.sender.name}
									{else}
										{$msg.to}
									{/if}
								</td>
								<td class="date">{$msg.timestamp|tiki_short_datetime}</td>
								<td class="integer">{$msg.size|kbsize}</td>
							</tr>
						{/foreach}
					</table>
				</div>
			</div>
		</div>
	</form>
{/if}


{if $locSection eq 'read'}
	{if $prev}{self_link msgid=$prev}{tr}Prev{/tr}{/self_link} |{/if}
	{if $next}{self_link msgid=$next}{tr}Next{/tr}{/self_link} |{/if}
	{self_link locSection=mailbox}{tr}Back To Mailbox{/tr}{/self_link} |
	{if $fullheaders eq 'n'}
		{self_link msgid=$msgid fullheaders='1'}{tr}Full Headers{/tr}{/self_link}
	{else}
		{self_link msgid=$msgid}{tr}Normal Headers{/tr}{/self_link}
	{/if}
	<table>
		<tr>
			<td>
				<form method="post" action="tiki-webmail.php">
					<input type="submit" class="btn btn-danger btn-sm" name="delete_one" value="{tr}Delete{/tr}">
					{if $next}
						<input type="hidden" name="locSection" value="read">
						<input type="hidden" name="msgid" value="{$next|escape}">
					{else}
						<input type="hidden" name="locSection" value="mailbox">
					{/if}
					<input type="hidden" name="msgdel" value="{$msgid|escape}">
				</form>
			</td>
			<td>
				<form method="post" action="tiki-webmail.php">
					<input type="hidden" name="locSection" value="compose">
					<input type="submit" class="btn btn-primary btn-sm" name="reply" value="{tr}Reply{/tr}">
					<input type="hidden" name="realmsgid" value="{$realmsgid|escape}">
					<input type="hidden" name="to" value="{$headers.replyto|escape}">
					<input type="hidden" name="subject" value="Re: {$headers.subject}">
					<input type="hidden" name="body" value="{$plainbody|escape}">
				</form>
			</td>
			<td>
				<form method="post" action="tiki-webmail.php">
					<input type="hidden" name="locSection" value="compose">
					<input type="submit" class="btn btn-primary btn-sm" name="replyall" value="{tr}Reply To All{/tr}">
					<input type="hidden" name="to" value="{$headers.replyto|escape}">
					<input type="hidden" name="realmsgid" value="{$realmsgid|escape}">
					<input type="hidden" name="cc" value="{$headers.replycc|escape}">
					<input type="hidden" name="subject" value="Re: {$headers.subject}">
					<input type="hidden" name="body" value="{$plainbody|escape}">
				</form>
			</td>
			<td>
				<form method="post" action="tiki-webmail.php">
					<input type="submit" class="btn btn-primary btn-sm" name="forward" value="{tr}Forward{/tr}">
					<input type="hidden" name="locSection" value="compose">
					<input type="hidden" name="realmsgid" value="{$realmsgid|escape}">
					<input type="hidden" name="to" value="">
					<input type="hidden" name="cc" value="">
					<input type="hidden" name="subject" value="Fwd: {$headers.subject}">
					<input type="hidden" name="body" value="{$plainbody|escape}">
				</form>
			</td>
		</tr>
	</table>

	<table class="webmail_message_headers table">
		{if $fullheaders eq 'n'}
			<tr>
				<th><strong>{tr}Subject{/tr}</strong></th>
				<td><strong>{$headers.subject|escape}</strong></td>
			</tr>
			<tr>
				<th>{tr}From{/tr}</th>
				<td>{$headers.from|escape}</td>
			</tr>
			<tr>
				<th>{tr}To{/tr}</th>
				<td>{$headers.to|escape}</td>
			</tr>
			{if $headers.cc}
				<tr>
					<th>{tr}Cc{/tr}</th>
					<td>{$headers.cc|escape}</td>
				</tr>
			{/if}
			<tr>
				<th>{tr}Date{/tr}</th>
				<td>{$headers.timestamp|tiki_short_datetime:'':'n'}</td>
			</tr>
		{/if}
		{if $fullheaders eq 'y'}
			{foreach key=key item=item from=$headers}
				<tr>
					<th>{$key}</th>
					<td>
						{if is_array($item)}
							{foreach from=$item item=part}
								{$part}
								<br>
							{/foreach}
						{else}
							{$item}
						{/if}
					</td>
				</tr>
			{/foreach}
		{/if}
	</table>

	{section name=ix loop=$bodies}
		{assign var='wmid' value='webmail_message_'|cat:$msgid|cat:'_'|cat:$smarty.section.ix.index}
		{assign var='wmopen' value='y'}
		{if $bodies[ix].contentType eq 'text/plain'}
			{if count($bodies) gt 1}
				{assign var='wmopen' value='n'}
			{/if}
			{assign var='wmclass' value='webmail_message webmail_mono'}
		{else}
			{if $bodies[ix].contentType neq 'text/html'}
				{assign var='wmopen' value='n'}
			{/if}
			{assign var='wmclass' value='webmail_message'}
		{/if}
		<div>
			{button _flip_id=$wmid _text="{tr}Part:{/tr} "|cat:$bodies[ix].contentType _flip_default_open=$wmopen}
		</div>
		<div id="{$wmid}" class="{$wmclass}" {if $wmopen eq 'n'}style="display:none"{/if}>
			{$bodies[ix].body}
		</div>
	{/section}
	<div>
		{button _flip_id='webmail_message_source_'|cat:$msgid _text="{tr}Source:{/tr} " _flip_default_open='n'}
	</div>
	<div id="webmail_message_source_{$msgid}" class="webmail_message webmail_mono" style="display:none">
		{$allbodies|nl2br}
	</div>

	{section name=ix loop=$attachs}
		<div class="card">
			<div class="card-body">
				<a class="link" href="tiki-webmail_download_attachment.php?locSection=read&amp;msgid={$msgid}&amp;getpart={$attachs[ix].part}">{$attachs[ix].name|iconify}{$attachs[ix].name}</a>
			</div>
		</div>
	{/section}
{/if}
{if $locSection eq 'compose'}
	{if $attaching eq 'n'}
		{if $sent eq 'n'}
			<form action="tiki-webmail.php" method="post">
				<table class="table">
					<tr>
						<td> </td>
						<td colspan="3">
							<em>{tr}Sending from webmail account:{/tr} <code>{$sendFrom}</code></em>
						</td>
					</tr>
					<tr>
						<td>
							<label for="to">
								<a title="|{tr}Select from address book{/tr}" class="tips" href="#" onclick="window.open('tiki-webmail_contacts.php?element=to','','menubar=no,width=452,height=550');">
									{tr}To{/tr}
								</a>:
							</label>
						</td>
						<td colspan="3">
							<input size="69" type="text" id="to" name="to" value="{$to|escape}" class="form-control">
						</td>
					</tr>
					<tr>
						<td>
							<label for="cc">
								<a title="|{tr}Select from address book{/tr}" class="tips" href="#" onclick="window.open('tiki-webmail_contacts.php?element=cc','','menubar=no,width=452,height=550');">
									{tr}CC{/tr}
								</a>:
							</label>
						</td>
						<td>
							<input id="cc" type="text" name="cc" value="{$cc|escape}" class="form-control">
						</td>
						<td>
							<label for="bcc">
								<a title="|{tr}Select from address book{/tr}" class="tips" href="#" onclick="window.open('tiki-webmail_contacts.php?element=bcc','','menubar=no,width=452,height=550');">
									{tr}BCC{/tr}
								</a>:
							</label>
						</td>
						<td>
							<input type="text" name="bcc" value="{$bcc|escape}" id="bcc" class="form-control">
						</td>
					</tr>
					<tr>
						<td><label for="subject">{tr}Subject{/tr}</label></td>
						<td colspan="3">
							<input size="69" type="text" name="subject" id="subject" value="{$subject|escape}" class="form-control">
						</td>
					</tr>
					<tr>
						<td>&nbsp;</td>
						<td colspan="3">
							<!--textarea name="body" cols="60" rows="30">{$body}</textarea-->
							{textarea name='body'}{$body}{/textarea}
						</td>
					</tr>
					<tr>
						<td>{tr}Use HTML mail{/tr}</td>
						<td>
							<input type="checkbox" name="useHTML"{if $useHTML eq "y" || $smarty.session.wysiwyg eq "y"} checked="checked"{/if}>
						</td>
						<td>{tr}Attachments{/tr}</td>
						<td>
							{if $attach1}
								({$attach1})
							{/if}
							{if $attach2}
								({$attach2})
							{/if}
							{if $attach3}
								({$attach3})
							{/if}
							{if $fattId}
								(File Gallery file: {$fattId})
							{/if}
							<input type="submit" class="btn btn-primary btn-sm" name="attach" value="{tr}Add{/tr}">
						</td>
					</tr>
					<tr>
						<td><label for="pageaftersend">{tr}Wiki page after send{/tr}</label></td>
						<td colspan="3">
							<input size="69" type="text" name="pageaftersend" id="pageaftersend" value="{$pageaftersend|escape}" class="form-control">
						</td>
					</tr>
					<tr>
						<td> </td>
						<td>
							<input type="hidden" name="locSection" value="compose">
							<input type="hidden" name="current" value="{$curacctId|escape}">
							<input type="hidden" name="attach1" value="{$attach1|escape}">
							<input type="hidden" name="attach2" value="{$attach2|escape}">
							<input type="hidden" name="attach3" value="{$attach3|escape}">
							<input type="hidden" name="attach1file" value="{$attach1file|escape}">
							<input type="hidden" name="attach2file" value="{$attach2file|escape}">
							<input type="hidden" name="attach3file" value="{$attach3file|escape}">
							<input type="hidden" name="attach1type" value="{$attach1type|escape}">
							<input type="hidden" name="attach2type" value="{$attach2type|escape}">
							<input type="hidden" name="attach3type" value="{$attach3type|escape}">
							<input type="hidden" name="fattId" value="{$fattId|escape}">
							<input type="submit" class="btn btn-primary" name="send" value="{tr}Send{/tr}" onclick="needToConfirm=false;">
						</td>
					</tr>
				</table>
			</form>
		{elseif $pageaftersend ne ''}
			{$msg}
			<br><br>
			<form action="tiki-index.php?page={$pageaftersend}" method="post">
			{tr}Click to go to:{/tr} {$pageaftersend} <input type="submit" class="btn btn-primary btn-sm" name="pageafter" value="{tr}Go to page{/tr}">
			</form>
		{else}
			{$msg}
			<br><br>
			{if $notcon eq 'y'}
				{tr}The following addresses are not in your address book{/tr}
				<br><br>
				<form action="tiki-webmail.php" method="post">
					<div class="table-responsive">
						<table class="table">
							<tr>
								<th>&nbsp;</th>
								<th>{tr}Email{/tr}</th>
								<th>{tr}First Name{/tr}</th>
								<th>{tr}Last Name{/tr}</th>
								<th>{tr}Nickname{/tr}</th>
							</tr>
							{section name=ix loop=$not_contacts}
								<tr>
									<td class="checkbox-cell">
										<div class="form-check">
											<input type="checkbox" name="add[{$smarty.section.ix.index}]">
											<input type="hidden" name="addemail[{$smarty.section.ix.index}]" value="{$not_contacts[ix]|escape}">
										</div>
									</td>
									<td class="email">{$not_contacts[ix]}</td>
									<td class="text">
										<input type="text" name="addFirstName[{$smarty.section.ix.index}]">
									</td>
									<td class="text">
										<input type="text" name="addLastName[{$smarty.section.ix.index}]">
									</td>
									<td class="text">
										<input type="text" name="addNickname[{$smarty.section.ix.index}]">
									</td>
								</tr>
							{/section}
							<tr>
								<td>&nbsp;</td>
								<td>
									<input type="submit" class="btn btn-primary btn-sm" name="add_contacts" value="{tr}Add Contacts{/tr}">
								</td>
							</tr>
						</table>
					</div>
				</form>
			{/if}
		{/if}
	{else}
		<form enctype="multipart/form-data" action="tiki-webmail.php" method="post">
			<input type="hidden" name="locSection" value="compose">
			<input type="hidden" name="current" value="{$curacctId|escape}">
			<input type="hidden" name="to" value="{$to|escape}">
			<input type="hidden" name="cc" value="{$cc|escape}">
			<input type="hidden" name="bcc" value="{$bcc|escape}">
			<input type="hidden" name="subject" value="{$subject|escape}">
			<input type="hidden" name="body" value="{$body|escape}">
			<input type="hidden" name="attach1" value="{$attach1|escape}">
			<input type="hidden" name="attach2" value="{$attach2|escape}">
			<input type="hidden" name="attach3" value="{$attach3|escape}">
			<input type="hidden" name="attach1file" value="{$attach1file|escape}">
			<input type="hidden" name="attach2file" value="{$attach2file|escape}">
			<input type="hidden" name="attach3file" value="{$attach3file|escape}">
			<input type="hidden" name="attach1type" value="{$attach1type|escape}">
			<input type="hidden" name="attach2type" value="{$attach2type|escape}">
			<input type="hidden" name="attach3type" value="{$attach3type|escape}">
			<input type="hidden" name="fattId" value="{$fattId|escape}">
			<input type="hidden" name="pageaftersend" value="{$pageaftersend|escape}">
			<table class="formcolor">
				{if $attach1}
					<tr>
						<td>{tr}Attachment 1{/tr}</td>
						<td>{$attach1} <input type="submit" class="btn btn-primary btn-sm" name="remove_attach1" value="{tr}Remove{/tr}"></td>
					</tr>
				{else}
					<tr>
						<td>{tr}Attachment 1{/tr}</td>
						<td>
							<input type="hidden" name="MAX_FILE_SIZE" value="1500000">
							<input name="userfile1" type="file">
						</td>
					</tr>
				{/if}
				{if $attach2}
					<tr>
						<td>{tr}Attachment 2{/tr}</td>
						<td>
							{$attach2} <input type="submit" class="btn btn-warning btn-sm" name="remove_attach2" value="{tr}Remove{/tr}">
						</td>
					</tr>
				{else}
					<tr>
						<td>
							{tr}Attachment 2{/tr}
						</td>
						<td>
							<input type="hidden" name="MAX_FILE_SIZE" value="1500000"><input name="userfile2" type="file" />
						</td>
					</tr>
				{/if}
				{if $attach3}
					<tr>
						<td>{tr}Attachment 3{/tr}</td>
						<td>
							{$attach3} <input type="submit" class="btn btn-warning btn-sm"name="remove_attach3" value="{tr}Remove{/tr}">
						</td>
					</tr>
				{else}
					<tr>
						<td>{tr}Attachment 3{/tr}</td>
						<td>
							<input type="hidden" name="MAX_FILE_SIZE" value="1500000" /><input name="userfile3" type="file">
						</td>
					</tr>
				{/if}
				<tr>
					<td>{tr}Attach a File Gallery file{/tr}</td>
					<td>
						<input size="10" type="text" id="fattId" name="fattId" value="{$fattId|escape}"> :FileId
					</td>
				</tr>
				<tr>
					<td>{tr}Attach a File Gallery file{/tr}</td>
					<td>
						<input size="10" type="text" id="fattId" name="fattId" value="{$fattId|escape}"> :FileId
					</td>
				</tr>
				<tr>
					<td>&nbsp;</td>
					<td>
						<input type="submit" class="btn btn-primary btn-sm" name="attached" value="{tr}Done{/tr}">
					</td>
				</tr>
			</table>
		</form>
	{/if}
{/if}
