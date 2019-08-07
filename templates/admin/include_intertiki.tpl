{* $Id$ *}
<form action="tiki-admin.php?page=intertiki" method="post" name="intertiki">
	{ticket}
	<div class="t_navbar mb-4 clearfix">
		{include file='admin/include_apply_top.tpl'}
	</div>

	{tabset name="admin_interwiki"}
		{tab name="{tr}Intertiki Client{/tr}"}
			<em>{tr}Set up this Tiki site as the Intertiki client{/tr}</em><br><br>
			<fieldset>
				<legend>{tr}Activate the feature{/tr}</legend>
				{preference name=feature_intertiki}
			</fieldset>
			<fieldset>
				<legend>{tr}Client server settings{/tr}</legend>
				{preference name=tiki_key}
				{preference name=feature_intertiki_sharedcookie}
			</fieldset>
			<fieldset>
				<legend>{tr}Currently linked master server{/tr}</legend>
				{preference name=feature_intertiki_mymaster mode=notempty}
				<div class="adminoptionboxchild feature_intertiki_mymaster_childcontainer">
					{preference name=feature_intertiki_import_preferences}
					{preference name=feature_intertiki_import_groups}
					{preference name=feature_intertiki_imported_groups}
				</div>
			</fieldset>
			<fieldset>
				<legend>{tr}Add an available master server{/tr}{help desc='{tr}The InterTiki Server fields are for defining for every master server you want to have access to from this client{/tr}'}</legend>
					<div class="form-group row">
						<label class="col-sm-4 col-form-label">{tr}Server name{/tr}{help desc='{tr}Set the name of your target server as defined in the server name field of the master. Use a distinct, but easily understood value.{/tr}'}</label>
						<div class="col-sm-8">
							<input type="text" name="new[name]" value="" class="form-control">
						</div>
					</div>
					<div class="form-group row">
						<label class="col-sm-4 col-form-label">{tr}Server host{/tr}{help desc='{tr}The full URL of the master servers primary Tiki (ex: https://tiki.org). Even if your Tiki is not at the top level of your web directory, you will still use the site\'s URL per the ex. above.{/tr}'}</label>
						<div class="col-sm-8">
							<input type="text" name="new[host]" value="" class="form-control">
						</div>
					</div>
					<div class="form-group row">
						<label class="col-sm-4 col-form-label">{tr}Server port{/tr}{help desc='{tr}The port number the master tiki responds to HTTP on (usually 80).{/tr}'}</label>
						<div class="col-sm-8">
							<input type="text" name="new[port]" value="" class="form-control">
						</div>
					</div>
					<div class="form-group row">
						<label class="col-sm-4 col-form-label">{tr}Server path{/tr}{help desc='{tr}The full path (from the URL root) to the PHP file containing the XMLRPC handler on the server. EX 1: If the master tiki resides at the root of the site, you would enter "/remote.php". EX 2: Say the master tiki is found at http://www.mydomain.com/tiki/mytiki, you would enter "/tiki/mytiki/remote.php" in this field.{/tr}'}</label>
						<div class="col-sm-8">
							<input type="text" name="new[path]" value="" class="form-control">
						</div>
					</div>
					<div class="form-group row">
						<label class="col-sm-4 col-form-label">{tr}Server groups{/tr}{help desc='{tr}Groups on the master to authenticate to (only auth users in the groups defined, case-sensitive).{/tr}'}</label>
						<div class="col-sm-8">
							<input type="text" name="new[groups]" value="" class="form-control">
						</div>
					</div>
			</fieldset>
			{if $prefs.interlist}
				<fieldset>
					<legend>{tr}Available master Tiki servers{/tr}</legend>
					<div class="form-group row">
						<div class="col-sm-12">
							<table class="table">
								<thead>
								<tr>
									<td>{tr}Name{/tr}</td>
									<td>{tr}Host{/tr}</td>
									<td>{tr}Port{/tr}</td>
									<td>{tr}Path{/tr}</td>
									<td>{tr}Group{/tr}</td>
									<td></td>
								</tr>
								</thead>
								<tbody>
								{foreach key=k item=i from=$prefs.interlist}
									<tr>
										<td><input type="text" class="form-control" name="interlist[{$k}][name]" value="{$i.name}"></td>
										<td><input type="text" class="form-control" name="interlist[{$k}][host]" value="{$i.host}"></td>
										<td><input type="text" class="form-control" name="interlist[{$k}][port]" value="{$i.port}"></td>
										<td><input type="text" class="form-control" name="interlist[{$k}][path]" value="{$i.path}"></td>
										<td><input type="text" class="form-control" name="interlist[{$k}][groups]" value="{foreach item=g from=$i.groups name=f}{$g}{if !$smarty.foreach.f.last},{/if}{/foreach}"></td>
										<td>
											<button
												type="submit"
												name="del"
												value="{$k}"
												class="btn btn-link tips"
												title="{tr}Delete master server{/tr}:{$k}"
												onclick="confirmSimple(event, '{tr}Remove this server?{/tr}')"
											>
												{icon name='delete'}
											</button>
										</td>
									</tr>
								{/foreach}
								<tbody>
							</table>
						</div>
					</div>
				</fieldset>
			{/if}
		{/tab}
		{if $prefs.feature_intertiki_mymaster eq ''}
			{tab name="{tr}Intertiki Master Server{/tr}"}
				<em>{tr}Set up this Tiki site as the InterTiki master server{/tr}</em><br><br>
				<fieldset>
					<legend>{tr}Activate the feature{/tr}</legend>
					{preference name=feature_intertiki_server}
				</fieldset>
				<fieldset>
					<legend>{tr}Master server settings{/tr}</legend>
					{preference name=intertiki_logfile}
					{preference name=intertiki_errfile}
				</fieldset>
				<fieldset>
					<legend>{tr}Allowed client servers{/tr}</legend>
					<div class="form-group row">
						<div class="col-sm-12">
							<table class="table">
								<thead>
								<tr>
									<td>&nbsp;</td>
									<td><label for="known_hosts_name">{tr}Name{/tr}</label>{help desc="{tr}Arbitrary name used to uniquely identify this configuration (does not effect operation). Recommend use of a name that indicates the client server (ex: doc.tw.o){/tr}"}</td>
									<td><label for="known_hosts_key">{tr}Key{/tr}</label>{help desc="{tr}This is the shared key you define. It has to match the client configuration for your server. It can be as short or as long as you like. It is recommended you follow the same kind of password policies your organization would have for something like a wireless WEP key.{/tr}"}</td>
									<td><label for="known_hosts_ip">{tr}IP{/tr}</label>{help desc="{tr}The physical IP address the client machine will be making requests to the server from. If the client is on the same machine, you should be able to use 127.0.0.1{/tr}"}</td>
									<td><label for="known_hosts_contact">{tr}Contact{/tr}</label>{help desc="{tr}Username of primary contact on client machine. Useful for adminstration{/tr}"}</td>
									<td><label for="known_hosts_can_register">{tr}Can register{/tr}</label></td>
								</tr>
								</thead>
								<tbody>
								{if $prefs.known_hosts}
									{foreach key=k item=i from=$prefs.known_hosts}
										<tr>
											<td>
												<button
													type="submit"
													name="delk"
													class="btn btn-link tips"
													value="{$k|escape:'attr'}"
													title=":{tr}Delete{/tr}"
													onclick="confirmSimple(event, '{tr}Remove this host?{/tr}')"
												>
													{icon name='delete'}
												</button>
											</td>
											<td>
												<input type="text" class="form-control" id="known_hosts_name" name="known_hosts[{$k}][name]" value="{$i.name}">
											</td>
											<td>
												<input type="text" class="form-control tips" id="known_hosts_key" name="known_hosts[{$k}][key]" value="{$i.key}"
													readonly="readonly" title="|{tr}To change the host key you need to remove and add it as a new one{/tr}">
											</td>
											<td>
												<input type="text" class="form-control" id="known_hosts_ip" name="known_hosts[{$k}][ip]" value="{$i.ip}">
											</td>
											<td>
												<input type="text" class="form-control" id="known_hosts_contact" name="known_hosts[{$k}][contact]" value="{$i.contact}">
											</td>
											<td>
												<input type="checkbox" class="form-control" id="known_hosts_can_register" name="known_hosts[{$k}][allowusersregister]" {if isset($i.allowusersregister) && $i.allowusersregister eq 'y'}checked="checked"{/if} />
											</td>
										</tr>
									{/foreach}
								{/if}
								<tr>
									<td>{tr}New:{/tr}</td>
									<td><label class="sr-only" for="new_host_name">{tr}New{/tr}</label><input type="text" class="form-control" id="new_host_name" name="newhost[name]" value=""/></td>
									<td><label class="sr-only" for="new_host_key">{tr}Key{/tr}</label><input type="text" class="form-control" id="new_host_key" name="newhost[key]" value=""/></td>
									<td><label class="sr-only" for="new_host_ip">{tr}IP{/tr}</label><input type="text" class="form-control" id="new_host_ip" name="newhost[ip]" value=""/></td>
									<td><label class="sr-only" for="new_host_contact">{tr}Contact{/tr}</label><input type="text" class="form-control" id="new_host_contact" name="newhost[contact]" value=""/></td>
									<td><label class="sr-only" for="new_host_can_register">{tr}Can register{/tr}</label><input type="checkbox" id="new_host_can_register" name="newhost[allowusersregister]"/></td>
								</tr>
								</tbody>
							</table>
						</div>
					</div>
				</fieldset>
			{/tab}
		{/if}
	{/tabset}
	{include file='admin/include_apply_bottom.tpl'}
</form>

