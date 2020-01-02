{extends "layout_view.tpl"}

{block name="title"}
	{title}{$title}{/title}
{/block}

{block name="navigation"}
	<div class="t_navbar mb-4">
		{permission name=admin_trackers}
			<a class="btn btn-link" href="{service controller=tabular action=create}">{icon name=create} {tr}New{/tr}</a>
			<a class="btn btn-link" href="{service controller=tabular action=manage}">{icon name=list} {tr}Manage{/tr}</a>
		{/permission}
	</div>
{/block}

{block name="content"}
	<div class="table-responsive">
		<form class="edit-tabular" method="post" action="{service controller=tabular action=edit tabularId=$tabularId}">
			<div class="form-group row">
				<label class="col-form-label col-sm-2">{tr}Name{/tr}</label>
				<div class="col-sm-10">
					<input class="form-control" type="text" name="name" value="{$name|escape}" required>
				</div>
			</div>
			<div class="form-group row">
				<label class="col-form-label col-sm-2">{tr}Fields{/tr}</label>
				<div class="col-sm-10">
					<table class="table fields">
						<thead>
							<tr>
								<th>{tr}Field{/tr}</th>
								<th>{tr}Mode{/tr}</th>
								<th><abbr title="{tr}Primary Key{/tr}">{tr}PK{/tr}</abbr></th>
								<th><abbr title="{tr}Unique Key{/tr}">{tr}UK{/tr}</abbr></th>
								<th><abbr title="{tr}Read-Only{/tr}">{tr}RO{/tr}</abbr></th>
								<th><abbr title="{tr}Export-Only{/tr}">{tr}EO{/tr}</abbr></th>
								<th></th>
							</tr>
						</thead>
						<tbody>
							<tr class="d-none">
								<td>
									<div class="input-group input-group-sm">
										<div class="input-group-prepend">
											<span class="input-group-text">{icon name=sort}</span>
										</div>
										<input type="text" class="field-label form-control">
										<div class="input-group-append">
											<button type="button" class="btn btn-light dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
												<span class="align">{tr}Left{/tr}</span>
												<input class="display-align" type="hidden" value="left">
											</button>
											<div class="dropdown-menu dropdown-menu-right" role="menu">
												<a class="dropdown-item align-option" href="#left">{tr}Left{/tr}</a>
												<a class="dropdown-item align-option" href="#center">{tr}Center{/tr}</a>
												<a class="dropdown-item align-option" href="#right">{tr}Right{/tr}</a>
												<a class="dropdown-item align-option" href="#justify">{tr}Justify{/tr}</a>
											</div>
										</div>
									</div>
								</td>
								<td><span class="field">Field Name</span>:<span class="mode">Mode</span></td>
								<td><input class="primary" type="radio" name="pk"></td>
								<td><input class="unique-key" type="checkbox"></td>
								<td><input class="read-only" type="checkbox"></td>
								<td><input class="export-only" type="checkbox"></td>
								<td class="text-right"><button class="remove btn-sm btn-outline-warning">{icon name=remove}</button></td>
							</tr>
							{foreach $schema->getColumns() as $column}
								<tr>
									<td>
										<div class="input-group input-group-sm">
											<div class="input-group-prepend">
												<span class="input-group-text">{icon name=sort}</span>
											</div>
											<input type="text" class="field-label form-control" value="{$column->getLabel()|escape}">
											<div class="input-group-append">
												<button type="button" class="btn btn-light dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
													<span class="align">{$column->getDisplayAlign()|ucfirst|tra}</span>
													<input class="display-align" type="hidden" value="{$column->getDisplayAlign()|escape}">
												</button>
												<div class="dropdown-menu dropdown-menu-right" role="menu">
													<a class="dropdown-item align-option" href="#left">{tr}Left{/tr}</a>
													<a class="dropdown-item align-option" href="#center">{tr}Center{/tr}</a>
													<a class="dropdown-item align-option" href="#right">{tr}Right{/tr}</a>
													<a class="dropdown-item align-option" href="#justify">{tr}Justify{/tr}</a>
												</div>
											</div>
										</div>
									</td>
									<td>
										<a href="{service controller=tabular action=select trackerId=$trackerId permName=$column->getField()
												columnIndex=$column@index mode=$column->getMode()}"
										   		class="btn btn-sm btn-secondary add-field tips"
												title="{tr}Field{/tr} {$column->getField()|escape}|{tr}Mode:{/tr} {$column->getMode()|escape}">
											<span class="field d-none">{$column->getField()|escape}</span>:
											<span class="mode">{$column->getMode()|escape}</span>
										</a>
									</td>
									<td><input class="primary" type="radio" name="pk" {if $column->isPrimaryKey()} checked {/if}></td>
									<td><input class="unique-key" type="checkbox" {if $column->isUniqueKey()} checked {/if}></td>
									<td><input class="read-only" type="checkbox" {if $column->isReadOnly()} checked {/if}></td>
									<td><input class="export-only" type="checkbox" {if $column->isExportOnly()} checked {/if}></td>
									<td class="text-right"><button class="remove btn-sm btn-outline-warning">{icon name=remove}</button></td>
								</tr>
							{/foreach}
						</tbody>
						<tfoot>
							<tr>
								<td>
									<select class="selection form-control">
										<option disabled="disabled" selected="selected">{tr}Select a field...{/tr}</option>
										{foreach $schema->getAvailableFields() as $permName => $label}
											<option value="{$permName|escape}">{$label|escape}</option>
										{/foreach}
									</select>
								</td>
								<td>
									<a href="{service controller=tabular action=select trackerId=$trackerId}" class="btn btn-secondary add-field">{tr}Select Mode{/tr}</a>
									<textarea name="fields" class="d-none">{$schema->getFormatDescriptor()|json_encode}</textarea>
								</td>
								<td colspan="3">
									<div class="radio">
										<label>
											<input class="primary" type="radio" name="pk" {if ! $schema->getPrimaryKey()} checked {/if}>
											{tr}No primary key{/tr}
										</label>
									</div>
								</td>
							</tr>
						</tfoot>
					</table>
					<div class="form-text">
						<p><strong>{tr}Primary Key:{/tr}</strong> {tr}Required to import data. Can be any field as long as it is unique.{/tr}</p>
						<p><strong>{tr}Unique Key:{/tr}</strong> {tr}Impose unique value requirement for the target column. This only works with Transactional Import feature.{/tr}</p>
						<p><strong>{tr}Read-only:{/tr}</strong> {tr}When importing a file, read-only fields will be skipped, preventing them from being modified, but also speeding-up the process.{/tr}</p>
						<p>{tr}When two fields affecting the same value are included in the format, such as the ID and the text value for an Item Link field, one of the two fields must be marked as read-only to prevent a conflict.{/tr}</p>
					</div>
				</div>
			</div>
			<div class="form-group row submit">
				<div class="col-sm-10 offset-sm-2">
					<input type="submit" class="btn btn-primary" value="{tr}Update{/tr}">
				</div>
			</div>
			<div class="form-group row">
				<label class="col-form-label col-sm-2">{tr}Filters{/tr}</label>
				<div class="col-sm-10">
					<table class="table filters">
						<thead>
							<tr>
								<th>{tr}Field{/tr}</th>
								<th>{tr}Mode{/tr}</th>
								<th></th>
							</tr>
						</thead>
						<tbody>
							<tr class="d-none">
								<td>
									<div class="input-group input-group-sm">
										<div class="input-group-prepend">
											<span class="input-group-text">{icon name=sort}</span>
										</div>
										<input type="text" class="filter-label form-control" value="Label">
										<div class="input-group-append">
											<button type="button" class="btn btn-light dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
												<span class="position-label">{tr}Default{/tr}</span>
												<input class="position" type="hidden" value="default">
											</button>
											<div class="dropdown-menu dropdown-menu-right" role="menu">
												<a class="dropdown-item position-option" href="#default">{tr}Default{/tr}</a>
												<a class="dropdown-item position-option" href="#primary">{tr}Primary{/tr}</a>
												<a class="dropdown-item position-option" href="#side">{tr}Side{/tr}</a>
											</div>
										</div>
									</div>
								</td>
								<td><span class="field">Field Name</span>:<span class="mode">Mode</span></td>
								<td class="text-right"><button class="remove btn-sm btn-outline-warning">{icon name=remove}</button></td>
							</tr>
							{foreach $filterCollection->getFilters() as $filter}
								<tr>
									<td>
										<div class="input-group input-group-sm">
											<div class="input-group-prepend">
												<span class="input-group-text">{icon name=sort}</span>
											</div>
											<input type="text" class="field-label form-control" value="{$filter->getLabel()|escape}">
											<div class="input-group-append">
												<button type="button" class="btn btn-light dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
													<span class="position-label">{$filter->getPosition()|ucfirst|tra}</span>
													<input class="position" type="hidden" value="{$filter->getPosition()|escape}">
												</button>
												<div class="dropdown-menu dropdown-menu-right" role="menu">
													<a class="dropdown-item position-option" href="#default">{tr}Default{/tr}</a>
													<a class="dropdown-item position-option" href="#primary">{tr}Primary{/tr}</a>
													<a class="dropdown-item position-option" href="#side">{tr}Side{/tr}</a>
												</div>
											</div>
										</div>
									</td>
									<td><span class="field">{$filter->getField()|escape}</span>:<span class="mode">{$filter->getMode()|escape}</td>
									<td class="text-right"><button class="remove btn-sm btn-outline-warning">{icon name=remove}</button></td>
								</tr>
							{/foreach}
						</tbody>
						<tfoot>
							<tr>
								<td>
									<select class="selection form-control">
										<option disabled="disabled" selected="selected">{tr}Select a field...{/tr}</option>
										{foreach $filterCollection->getAvailableFields() as $permName => $label}
											<option value="{$permName|escape}">{$label|escape}</option>
										{/foreach}
									</select>
								</td>
								<td>
									<a href="{service controller=tabular action=select_filter trackerId=$trackerId}" class="btn btn-secondary add-filter">{tr}Select Mode{/tr}</a>
									<textarea name="filters" class="d-none">{$filterCollection->getFilterDescriptor()|json_encode}</textarea>
								</td>
							</tr>
						</tfoot>
					</table>
					<div class="form-text">
						<p>{tr}Filters will be available in partial export menus.{/tr}</p>
					</div>
				</div>
			</div>
			<div class="form-group row submit">
				<div class="col-sm-10 offset-sm-2">
					<input type="submit" class="btn btn-primary" value="{tr}Update{/tr}">
				</div>
			</div>
			<div class="form-group row mb-4">
				<label class="col-form-label col-sm-2">{tr}Options{/tr}</label>
				<div class="col-sm-5">
					<div class="form-check">
						<input type="checkbox" class="form-check-input" name="config[simple_headers]" value="1" {if $config['simple_headers']} checked {/if}>
						<label class="form-check-label">{tr}Simple headers{/tr}</label>
						<a class="tikihelp text-info" title="{tr}Simple headers{/tr}: {tr}Allow using field labels only as a header row when importing rather than the full &quot;Field [permName:type]&quot; format.{/tr}">
							{icon name=information}
						</a>
					</div>
					<div class="form-check">
						<input type="checkbox" class="form-check-input" name="config[import_update]" value="1" {if $config['import_update']} checked {/if}>
						<label class="form-check-label">{tr}Import updates{/tr}</label>
						<a class="tikihelp text-info" title="{tr}Import update{/tr}: {tr}Allow updating existing entries matched by PK when importing. If this is disabled, only new items will be imported.{/tr}">
							{icon name=information}
						</a>
					</div>
					<div class="form-check">
						<input type="checkbox" class="form-check-input" name="config[ignore_blanks]" value="1" {if $config['ignore_blanks']} checked {/if}>
						<label class="form-check-label">{tr}Ignore blanks{/tr}</label>
						<a class="tikihelp text-info" title="{tr}Ignore blanks{/tr}: {tr}Ignore blank values when import is updating existing items. Only non-blank values will be updated this way.{/tr}">
							{icon name=information}
						</a>
					</div>
				</div>
				<div class="col-sm-5">
					<div class="form-check">
						<input type="checkbox" class="form-check-input" name="config[import_transaction]" value="1" {if $config['import_transaction']} checked {/if}>
						<label class="form-check-label">{tr}Transactional import{/tr}</label>
						<a class="tikihelp text-info" title="{tr}Import transaction{/tr}: {tr}Import in a single transaction. If any of the items fails validation, the whole import is rejected and nothing is saved.{/tr}">
							{icon name=information}
						</a>
					</div>
					<div class="form-check">
						<input type="checkbox" class="form-check-input" name="config[bulk_import]" value="1" {if $config['bulk_import']} checked {/if}>
						<label class="form-check-label">{tr}Bulk import{/tr}</label>
						<a class="tikihelp text-info" title="{tr}Bulk Import{/tr}: {tr}Import in 'bulk' mode so the search index is not updated for each item and no notifications should be sent.{/tr}">
							{icon name=information}
						</a>
					</div>
					<div class="form-check">
						<input type="checkbox" class="form-check-input" name="config[skip_unmodified]" value="1" {if $config['skip_unmodified']} checked {/if}>
						<label class="form-check-label">{tr}Skip Unmodified{/tr}</label>
						<a class="tikihelp text-info" title="{tr}Skip Unmodified{/tr}: {tr}Will not re-import items that have not changed.{/tr}">
							{icon name=information}
						</a>
					</div>
				</div>
			</div>
			<div class="form-group row submit">
				<div class="col-sm-10 offset-sm-2">
					<input type="submit" class="btn btn-primary" value="{tr}Update{/tr}">
				</div>
			</div>
		</form>
	</div>
{/block}
