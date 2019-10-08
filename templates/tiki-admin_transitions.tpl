{title help="Transitions"}{tr}Transitions{/tr}{/title}

{tabset}
	{tab name="{tr}Subset Selection{/tr}"}
		<h2>{tr}Subset Selection{/tr}</h2>
		<form method="post" action="tiki-admin_transitions.php?action=subset" style="text-align: left;">

			<fieldset>
				<legend>{tr}Type{/tr}</legend>
				<div class="form-group row mt-2 mb-4">
					{if $prefs.feature_categories eq 'y'}
						<div class="col-sm-3">
							<label>
								<input type="radio" name="transition_mode" value="category" id="transition-mode-category"{if $transition_mode eq 'category'} checked="checked"{/if}>
								{tr}Category{/tr}
							</label>
						</div>
					{/if}
					<div class="col-sm-3">
						<label>
							<input type="radio" name="transition_mode" value="group" id="transition-mode-group"{if $transition_mode eq 'group' or $prefs.feature_categories ne 'y'} checked="checked"{/if}>
							{tr}Group{/tr}
						</label>
					</div>
				</div>
			</fieldset>

			{if $prefs.feature_categories eq 'y'}
				<fieldset id="transition-category-selection">
					<legend>{tr}Category Selection{/tr}</legend>
					{$cat_tree}
				</fieldset>
			{/if}

			<fieldset id="transition-group-selection">
				<legend>{tr}Group Selection{/tr}</legend>
				<ul id="transition-group-list">
					{if $transition_mode eq 'group'}
						{foreach from=$available_states item=state}
							<li>
								<input type="hidden" name="groups[]" value="{$state|escape}">
								{$state|escape}
								{icon name='remove' class="removeitem"}
							</li>
						{/foreach}
					{/if}
				</ul>
				<div class="form-row align-items-center mb-4">
					<label class="col-auto col-form-label" for="transition-group-auto">{tr}Add Group{/tr}</label>
					<div class="col-auto">
						<input class="form-control" type="text" id="transition-group-auto">
					</div>
					<div class="form-text col-auto">
						{tr}Press Enter for each group{/tr}
					</div>
				</div>
			</fieldset>

			<p>
				<input type="submit" class="btn btn-secondary" value="{tr}Select{/tr}">
				<div class="form-text">
					{tr}At least two elements are required to create transitions. Additional tabs appear once the selection is completed.{/tr}
				</div>
			</p>
		</form>
		{jq}
			var blocks = $('#transition-group-selection, #transition-category-selection');
			$(':radio[name=transition_mode]').change( function( e ) {
				if( $(this).prop('checked') ) {
					blocks.hide();
					blocks.filter( '#transition-' + $(this).val() + '-selection' ).show();
				}
			} ).change();

			$('#transition-group-auto')
				.tiki('autocomplete','groupname')
				.keypress( function( e ) {
					if( e.which !== 13 ) {
						return;
					}
					e.preventDefault();
					if( $(this).val() === '' ) {
						return;
					}
					$('#transition-group-list').append(
						$('<li/>').text( $(this).val() )
							.append( $('<input type="hidden" name="groups[]">').val( $(this).val() ) )
							.append( $('{{icon name=remove class=removeitem}}') )
					);
					$(this).val('');
				} );
			$('#transition-group-list .removeitem').on( 'click', function( e ) {
				$(this).parent().remove();
			} );
		{/jq}
	{/tab}

	{if $available_states|@count > 0}
		{tab name="{tr}Transitions{/tr}"}
			<h2>{tr}Transitions{/tr}</h2>
			{* former add_dracula() *}
			{$headerlib->add_jsfile('lib/dracula/raphael-min.js', true)}
			{$headerlib->add_jsfile('lib/dracula/graffle.js', true)}
			{$headerlib->add_jsfile('lib/dracula/graph.js', true)}
			<div id="graph-canvas" class="graph-canvas" data-graph-nodes="{$graph_nodes|escape}" data-graph-edges="{$graph_edges|escape}"></div>
			<a href="#" id="graph-draw" class="button">{tr}Draw Transition Diagram{/tr}</a>
			{jq}
			$('#graph-draw').click( function( e ) {
				$(this).hide();
				$('#graph-canvas').drawGraph();
				return false;
			} );
			{/jq}
			<div class="{if $js}table-responsive{/if}"> {* table-responsive class cuts off css drop-down menus *}
				<table class="table table-striped table-hover">
					<thead>
						<tr>
							<th>{tr}Label{/tr}</th>
							<th>{tr}From{/tr}</th>
							<th>{tr}To{/tr}</th>
							<th>{tr}Guards{/tr}</th>
							<th></th>
						</tr>
					</thead>
					<tbody>
						{foreach from=$transitions item=trans}
							<tr>
								<td class="text">{$trans.name|escape}</td>
								<td class="text">{$trans.from_label|escape} {if $trans.preserve}<span class="label label-info">{tr}preserved{/tr}</span>{/if}</td>
								<td class="text">{$trans.to_label|escape}</td>
								<td class="integer">{self_link transitionId=$trans.transitionId action=edit cookietab=4}{$trans.guards|@count|escape}{/self_link}</td>
								<td class="action">
									{actions}
										{strip}
											<action>
												{permission_link mode=text type=transition id=$trans.transitionId title=$trans.name}
											</action>
											<action>
												{self_link transitionId=$trans.transitionId action=edit cookietab=3 _menu_text='y' _menu_icon='y' _icon_name='edit'}
													{tr}Edit{/tr}
												{/self_link}
											</action>
											<action>
												{self_link transitionId=$trans.transitionId action=remove _icon_name="remove" _menu_text='y' _menu_icon='y'}
													{tr}Remove{/tr}
												{/self_link}
											</action>
										{/strip}
									{/actions}
								</td>
							</tr>
						{foreachelse}
							<tr>
								<td colspan="5">{tr}No transitions{/tr}</td>
							</tr>
						{/foreach}
					</tbody>
				</table>
			</div>
		{/tab}

		{tab name="{tr}New / Edit{/tr}"}
			<form method="post" action="tiki-admin_transitions.php?action={if $selected_transition}edit{else}new{/if}&amp;cookietab=2" style="text-align: left;">
				{if $selected_transition}
					<h2>
						{tr _0=$selected_transition.name}Edit <em>%0</em>{/tr}
						<input type="hidden" name="transitionId" value="{$selected_transition.transitionId|escape}">
						(<a href="tiki-admin_transitions.php">{tr}Create new{/tr}</a>)
					</h2>
				{else}
					<h2>{tr}New transition{/tr}</h2>
				{/if}
				<fieldset>
					<legend>{tr}General{/tr}</legend>
					<div class="form-group row mt-0">
						<label class="col-form-label" for="new-transition-name">{tr}Label{/tr}</label>
						<input type="text" name="label" {if $selected_transition}value="{$selected_transition.name|escape}"{/if} class="form-control">
					</div>
					<div class="form-check">
						<label class="form-check-label">
							<input type="checkbox" class="form-check-input" name="preserve" value="y" id="new-transition-preserve" {if $selected_transition && $selected_transition.preserve}checked="checked"{/if}>
							{tr}Upon trigger, preserve the initial state{/tr}
						</label>
					</div>
				</fieldset>
				<div class="form-group row mt-4">
					<div class="col-sm-6">
						<fieldset>
							<legend>{tr}From{/tr}</legend>
							{foreach from=$available_states item=label key=value}
								<div class="form-check mt-1">
									<label class="form-check-label" for="state-from-{$value|escape}">
										<input type="radio" name="from" class="form-check-input" value="{$value|escape}" id="state-from-{$value|escape}"{if $selected_transition && $selected_transition.from eq $value} checked="checked"{/if}>
										{$label|escape}
									</label>
								</div>
							{/foreach}
						</fieldset>
					</div>
					<div class="col-sm-6">
						<fieldset>
							<legend>{tr}To{/tr}</legend>
							{foreach from=$available_states item=label key=value}
								<div class="form-check mt-1">
									<label class="form-check-label" for="state-to-{$value|escape}">
										<input type="radio" name="to" class="form-check-input" value="{$value|escape}" id="state-to-{$value|escape}"{if $selected_transition && $selected_transition.to eq $value} checked="checked"{/if}>
										{$label|escape}
									</label>
								</div>
							{/foreach}
						</fieldset>
					</div>
				</div>
				<div class="submit mt-4">
					<input type="submit" class="btn btn-secondary" value="{tr}Save{/tr}">
				</div>
			</form>
		{/tab}

		{if $selected_transition}
			{tab name="{tr}Guards{/tr}"}
				<h2>{tr}Guards{/tr}</h2>
				<div class="table-responsive">
					<table class="table">
						<thead>
							<tr>
								<th>{tr}Type{/tr}</th>
								<th>{tr}Count{/tr}</th>
								<th>{tr}Members{/tr}</th>
								<th>{tr}Actions{/tr}</th>
							</tr>
						</thead>
						<tbody>
							{foreach from=$guards item=guard key=key}
								<tr>
									<td>{$guard.type|escape}</td>
									<td>{$guard.count|escape}</td>
									<td>
										<ul>
											{foreach from=$guard.members item=name}
												<li>{$name|escape}</li>
											{/foreach}
										</ul>
									</td>
									<td>
										{self_link transitionId=$selected_transition.transitionId action=removeguard guard=$key cookietab=4}
											{icon name='remove'}
										{/self_link}
									</td>
								</tr>
							{foreachelse}
								<tr>
									<td colspan="4">{tr}No guards on this transition.{/tr}</td>
								</tr>
							{/foreach}
						</tbody>
					</table>
				</div>
				<form method="post" class="mt-4" action="tiki-admin_transitions.php?action=addguard&amp;transitionId={$selected_transition.transitionId|escape}&amp;cookietab=4" style="text-align: left;">
					<h2>{tr}New Guard{/tr}</h2>
					<fieldset>
						<legend>{tr}General{/tr}</legend>
						<div class="form-group row mt-0">
							<label class="col-form-label" for="guard-type">{tr}Type{/tr}</label>
							<select id="guard-type" name="type" class="form-control">
								<option value="exactly">{tr}Exactly{/tr}</option>
								<option value="atLeast">{tr}At Least{/tr}</option>
								<option value="atMost">{tr}At Most{/tr}</option>
							</select>
						</div>
						<div class="form-group row">
							<label for="guard-count" class="col-form-label">{tr}Count{/tr}</label>
							<input type="text" name="count" class="form-control">
						</div>
					</fieldset>
					<div class="form-group row mt-2">
						<div class="col-sm-6">
							<fieldset>
								<legend>{tr}States{/tr}</legend>
								{foreach from=$available_states item=label key=value}
									<div class="form-check mt-1">
										<label for="guard-state-{$value|escape}" class="form-check-label">
											<input type="checkbox" name="states[]" class="form-check-input" value="{$value|escape}" id="guard-state-{$value|escape}">
											{$label|escape}
										</label>
									</div>
								{/foreach}
							</fieldset>
						</div>
					</div>
					<div class="submit">
						<input type="submit" class="btn btn-secondary" value="{tr}Add{/tr}">
					</div>
				</form>
			{/tab}
		{/if}

	{/if}
{/tabset}
