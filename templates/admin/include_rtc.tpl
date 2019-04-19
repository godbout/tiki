{* $Id$ *}
<form action="tiki-admin.php?page=rtc" method="post" class="admin">
	{ticket}
	<div class="t_navbar mb-4 clearfix">
		{button href="tiki-admingroups.php" _class="btn-link tips" _type="text" _icon_name="group" _text="{tr}Groups{/tr}" _title=":{tr}Group Administration{/tr}"}
		{button href="tiki-adminusers.php" _class="btn-link tips" _type="text" _icon_name="user" _text="{tr}Users{/tr}" _title=":{tr}User Administration{/tr}"}
		{permission_link addclass="btn btn-link" _type="text" mode=text label="{tr}Permissions{/tr}"}
		<a href="{service controller=managestream action=list}" class="btn btn-link tips">{tr}Activity Rules{/tr}</a>
		{include file='admin/include_apply_top.tpl'}
	</div>
	{tabset name="admin_rtc"}
		{tab name="{tr}BigBlueButton{/tr}"}
			<br>
			{preference name=bigbluebutton_feature}
			<div class="adminoptionboxchild" id="bigbluebutton_feature_childcontainer">
				{preference name=bigbluebutton_server_location}
				{preference name=bigbluebutton_server_salt}
				{preference name=bigbluebutton_recording_max_duration}
				{preference name=wikiplugin_bigbluebutton}
			</div>
		{/tab}
		{tab name="XMPP"}
			<h2>XMPP</h2>
			{preference name=xmpp_feature}
			<div class="adminoptionboxchild" id="xmpp_feature_childcontainer">
				{preference name=xmpp_server_host}
				{preference name=xmpp_server_http_bind}
				{preference name=xmpp_muc_component_domain}
				{preference name=xmpp_auth_method}
				{preference name=xmpp_openfire_rest_api}
				{preference name=xmpp_openfire_rest_api_username}
				{preference name=xmpp_openfire_rest_api_password}
				{preference name=xmpp_conversejs_debug}
				{preference name=xmpp_conversejs_init_json}
			</div>
		{/tab}
	{/tabset}
	{include file='admin/include_apply_bottom.tpl'}
</form>
