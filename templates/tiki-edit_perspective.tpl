{title help="Perspectives"}{tr}Perspectives{/tr}{/title}
{tabset}

	{tab name="{tr}List{/tr}"}
		<h2>{tr}List{/tr}</h2>
		<a href="tiki-switch_perspective.php">{tr}Return to the default perspective{/tr}</a>
		<div class="{if $js}table-responsive{/if}"> {* table-responsive class cuts off css drop-down menus *}
			<table class="table table-striped table-hover">
				<tr>
					<th>{tr}Perspective{/tr}</th>
					<th>{tr}Preferences{/tr}</th>
					<th></th>
				</tr>

				{foreach from=$perspectives item=persp}
					<tr>
						<td class="text">
							{if $persp.can_edit}
								{self_link _icon_name='edit' action=edit _ajax='y' _menu_text='y' _menu_icon='y' id=$persp.perspectiveId cookietab=3}
									{$persp.name|escape}
								{/self_link}
							{else}
								<a href="tiki-switch_perspective.php?perspective={$persp.perspectiveId|escape:url}">
									{icon name='move' _menu_icon='y' alt="{tr}Switch to{/tr}"} {$persp.name|escape}
								</a>
							{/if}
						</td>
						<td style="font-size:smaller;">
							{foreach from=$persp.preferences key=name item=val}
								{if is_array($val)}
									{$name}={$val|implode:','}<br>
								{else}
									{$name}={$val}<br>
								{/if}
							{/foreach}
						</td>
						<td class="action">
							{actions}
								{strip}
									<action>
										<a href="tiki-switch_perspective.php?perspective={$persp.perspectiveId|escape:url}">
											{icon name='move' _menu_text='y' _menu_icon='y' alt="{tr}Switch to{/tr}"}
										</a>
									</action>
									{if $persp.can_perms}
										<action>
											{permission_link mode=text type="perspective" id=$persp.perspectiveId title=$persp.name}
										</action>
									{/if}
										{if $persp.can_edit}
										<action>
											{self_link _icon_name='edit' action=edit _ajax='y' _menu_text='y' _menu_icon='y' id=$persp.perspectiveId cookietab=3}
												{tr}Edit{/tr}
											{/self_link}
										</action>
									{/if}
									{if $persp.can_remove}
										<action>
											{self_link action=remove id=$persp.perspectiveId _menu_text='y' _menu_icon='y' _icon_name='remove'}
												{tr}Delete{/tr}
											{/self_link}
										</action>
									{/if}
								{/strip}
							{/actions}
						</td>
					</tr>
				{/foreach}
			</table>
		</div>
		{pagination_links offset=$offset step=$prefs.maxRecords cant=$count}{/pagination_links}
	{/tab}

	{if $tiki_p_perspective_create eq 'y'}
		{tab name="{tr}Create{/tr}"}
			<h2>{tr}Create{/tr}</h2>
			<form method="post" action="tiki-edit_perspective.php" class="form-inline">
					<label for="name" class="col-form-label mr-2">{tr}Name:{/tr} </label>
					<input type="text" name="name" class="form-control mr-2">
				<input type="submit" class="btn btn-primary mr-2" name="create" value="{tr}Create{/tr}">
			</form>
		{/tab}
	{/if}

	{if $perspective_info && $perspective_info.can_edit}
		{tab name="{tr}Edit{/tr}"}
			<h2>{tr}Edit{/tr}</h2>
			<form method="post" action="tiki-edit_perspective.php" class="form-horizontal">
				<div class="form-group row clearfix">
					<label for="name" class="col-sm-2 col-form-label">{tr}Name{/tr}</label>
					<div class="col-sm-10">
						<input type="text" name="name" id="name" value="{$perspective_info.name|escape}" class="form-control">
					</div>
					<input type="hidden" name="id" value="{$perspective_info.perspectiveId|escape}">
				</div>
				<div class="col-sm-10 offset-sm-2">
					<fieldset id="preferences" class="card dropzone mb-4">
						<div class="card-header">{tr}Preference List{/tr}</div>
						<div class="card-body mb-4">
							{foreach from=$perspective_info.preferences key=name item=val}
								{preference name=$name source=$perspective_info.preferences}
							{/foreach}
					</fieldset>
				</div>
				<div class="col-sm-10 offset-sm-2 text-center mb-4">
					<input type="submit" class="btn btn-primary" name="save" value="{tr}Save{/tr}">
				</div>
			</form>
			<form method="post" id="searchform" action="tiki-edit_perspective.php" class="form offset-sm-2 clearfix" role="form">
				{remarksbox type="info" title="{tr}Hint{/tr}"}
					{tr}Search preferences below and drag them into the preference list above.{/tr}
				{/remarksbox}
				<div class="card">
					<input type="hidden" name="id" value="{$perspective_info.perspectiveId|escape}">
					<div class="card-body">
						<div class="input-group">
							<div class="input-group-append">
								<span class="input-group-text">
									{icon name="search"}
								</span>
							</div>
							<input id="criteria" type="text" name="criteria" class="form-control" placeholder="{tr}Search preferences{/tr}...">
							<div class="input-group-append">
								<input type="submit" class="btn btn-info" value="{tr}Search{/tr}">
							</div>
						</div>
					</div>
					<div class="card-footer">
						<fieldset id="resultzone" class="dropzone"></fieldset>
					</div>
				</div>
			</form>
			{jq}
				$('#preferences')
					.droppable( {
						activeClass: 'ui-state-highlight',
						drop: function( e, ui ) {
							$('#preferences').append( ui.draggable );
							$(ui.draggable)
								.draggable('destroy')
								.draggable( {
									distance: 50,
									handle: 'label',
									axis: 'x',
									stop: function( e, ui ) {
										$(this).remove();
									}
								} );
						}
					} )
					.find('div.adminoptionbox').draggable( {
						distance: 50,
						handle: 'label',
						axis: 'x',
						stop: function( e, ui ) {
							$(this).remove();
						}
					} );
				$('#searchform').submit( function(e) {
					e.preventDefault();
					if (typeof ajaxLoadingShow == 'function') { ajaxLoadingShow('resultzone'); }
					$('#resultzone').load( this.action, $(this).serialize(), function() {
						$('#resultzone div.adminoptionbox').draggable( {
							scroll: true,
							cursor: 'move',
							helper: 'clone'
						} );
						$(this).tiki_popover();
						if (typeof ajaxLoadingHide == 'function') { ajaxLoadingHide(); }
					} );
				} );
			{/jq}
		{/tab}
	{/if}
{/tabset}
