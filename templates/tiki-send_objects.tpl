{* $Id$ *}

{title help="Communication Center"}{tr}Send objects{/tr}{/title}

{if $msg}
	<div class="card">
		<div class="card-header">
			{tr}Transmission results{/tr}
		</div>
		<div class="card-body">
			{$msg}
		</div>
	</div>
{/if}

<br>
<br>

<form method="post" action="tiki-send_objects.php">
	<div class="card">
		<div class="card-header">
			{tr}Filter{/tr}
		</div>
		<div class="card-body">
			<div class="form-inline">
			<label>{tr}Filter:{/tr}&nbsp;</label><input type="text" name="find" value="{$find|escape}" class="form-control">
			<input type="submit" class="btn btn-primary" name="filter" value="{tr}Filter{/tr}"><br>
			</div>
		</div>
	</div>

	<br>
	<br>

	{if $tiki_p_send_pages eq 'y'}
		<div class="card">
			<div class="card-header">
				{tr}Send Wiki Pages{/tr}
			</div>
			<div class="card-body">
				<div class="card">
					<div class="card-body">
						<b>{tr}Pages{/tr}</b>:
						{section name=ix loop=$sendpages}
							{$sendpages[ix]}&nbsp;
						{/section}
					</div>
				</div>
				<div class="form-inline">
					<select name="pageName" class="form-control">
						{section name=ix loop=$pages}
							<option value="{$pages[ix].pageName|escape}">{$pages[ix].pageName|escape}</option>
						{/section}
					</select>
					<input type="submit" class="btn btn-primary" name="addpage" value="{tr}Add Page{/tr}">
					<input type="submit" class="btn btn-primary" name="clearpages" value="{tr}Clear{/tr}">
				</div>
			</div>
		</div>

		<br>
		<br>

		{if count($structures)}
			<div class="card">
				<div class="card-header">
					{tr}Send a structure{/tr}
				</div>
				<div class="card-body">
					<div class="card">
						<div class="card-body">
							<b>{tr}Structures{/tr}</b>:
							{section name=ix loop=$sendstructures_names}
								{$sendstructures_names[ix]}&nbsp;
							{/section}
						</div>
					</div>
					<div class="form-inline">
						<select name="structure" class="form-control">
							{foreach item=struct from=$structures}
								<option value="{$struct.page_ref_id|escape}">{$struct.pageName|escape}{if $struct.page_alias} (alias: {$struct.page_alias}){/if}</option>
							{/foreach}
						</select>
						<input type="submit" class="btn btn-primary" name="addstructure" value="{tr}Add Structure{/tr}">
						<input type="submit" class="btn btn-primary" name="clearstructures" value="{tr}Clear{/tr}">
					</div>
				</div>
			</div>
		{/if}
	{/if}

	<br>
	<br>

	{if $tiki_p_send_articles eq 'y'}
		<div class="card">
			<div class="card-header">
				{tr}Send Articles{/tr}
			</div>
			<div class="card-body">
				<div class="card">
					<div class="card-body">
						<b>{tr}Articles{/tr}</b>:
						{section name=ix loop=$sendarticles}
							{$sendarticles[ix]}&nbsp;
						{/section}
					</div>
				</div>
				<div class="form-inline">
					<select name="articleId" class="form-control">
						{section name=ix loop=$articles}
							<option value="{$articles[ix].articleId|escape}">{$articles[ix].articleId}: {$articles[ix].title|escape}</option>
						{/section}
					</select>
					<input type="submit" class="btn btn-primary" name="addarticle" value="{tr}Add Article{/tr}">
					<input type="submit" class="btn btn-primary" name="cleararticles" value="{tr}Clear{/tr}">
				</div>
			</div>
		</div>
	{/if}

	<br>
	<br>

	<div class="card">
		<div class="card-header">
			{tr}Send objects to this site{/tr}
		</div>
		<div class="card-body">
			<input type="hidden" name="sendpages" value="{$form_sendpages|escape}">
			<input type="hidden" name="sendstructures" value="{$form_sendstructures|escape}">
			<input type="hidden" name="sendarticles" value="{$form_sendarticles|escape}">

				<div class="form-group row">
					<label class="col-form-label col-sm-3">{tr}Site:{/tr}</label>
					<div class="col-sm-7">
						<input type="text" name="site" value="{$site|escape}" class="form-control">
						<div class="form-text">
							{tr}Ex: http://tiki.org or localhost{/tr}
						</div>
					</div>
				</div>
				<div class="form-group row">
					<label class="col-form-label col-sm-3">{tr}Path:{/tr}</label>
					<div class="col-sm-7">
						<input type="text" name="path" value="{$path|escape}" class="form-control">
						<div class="form-text">
							{tr}Use /commxmlrpc.php if your Tiki site is installed at the root, otherwise adapt /tiki to your need{/tr}
						</div>
					</div>
				</div>
				<div class="form-group row">
					<label class="col-form-label col-sm-3">{tr}Username:{/tr}</label>
					<div class="col-sm-7">
						<input type="text" name="username" value="{$username|escape}" class="form-control">
					</div>
				</div>
				<div class="form-group row">
					<label class="col-form-label col-sm-3">{tr}Password:{/tr}</label>
					<div class="col-sm-7">
						<input type="password" name="password" value="{$password|escape}" class="form-control" autocomplete="new-password">
					</div>
				</div>
				<div class="form-group row">
					<label class="col-form-label col-sm-3">{tr}Debug:{/tr}</label>
					<div class="col-sm-7">
						<div class="form-check">
							<label class="form-check-label">
								<input type="checkbox" class="form-check-input" name="dbg"{if $dbg eq 'on'} checked="checked"{/if}>{tr}Enable{/tr}
							</label>
						</div>
					</div>
				</div>
				<div class="form-group row">
					<label class="col-form-label col-sm-3"></label>
					<div class="col-sm-7">
						<input type="submit" class="btn btn-primary" name="send" value="{tr}Send{/tr}">
					</div>
				</div>
			</div>

		</div>

</form>
