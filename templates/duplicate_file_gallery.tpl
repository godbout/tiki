{* $Id$ *}
{if $tiki_p_create_file_galleries eq 'y' and $gal_info.type neq 'user'}
	<h2>{tr}Duplicate File Gallery{/tr}</h2>
	<form role="form" action="tiki-list_file_gallery.php{if isset($filegals_manager) and $filegals_manager neq ''}?filegals_manager={$filegals_manager}{/if}" method="post">
		{ticket}
		<div class="form-group row">
			<label for="name" class="col-sm-4 col-form-label">{tr}Name{/tr}</label>
			<div class="col-sm-8">
				<input type="text" class="form-control" size="50" id="name" name="name" value="">
			</div>
		</div>
		<div class="form-group row">
			<label for="description" class="col-sm-4 col-form-label">{tr}Description{/tr}</label>
			<div class="col-sm-8">
				<textarea id="description" name="description" rows="4" class="form-control">{if isset($description)}{$description|escape}{/if}</textarea>
			</div>
		</div>
		<div class="form-group row">
			<label for="galleryId" class="col-sm-4 col-form-label">{tr}File gallery{/tr}</label>
			<div class="col-sm-8">
				<select id="galleryId" class="form-control" name="galleryId"{if $all_galleries|@count eq '0'} disabled="disabled"{/if}>
					{section name=ix loop=$all_galleries}
						<option value="{$all_galleries[ix].id}"{if $galleryId eq $all_galleries[ix].id}
							selected="selected"{/if}>{$all_galleries[ix].label|escape}
						</option>
					{sectionelse}
						<option value="">{tr}None{/tr}</option>
					{/section}
				</select>
			</div>
		</div>
		<div class="form-group row">
			<div class="form-check offset-sm-4 col-sm-8">
				<label for="dupCateg" class="form-check-label">
					<input type="checkbox" id="dupCateg" name="dupCateg" class="mr-2">{tr}Duplicate categories{/tr}
				</label>
			</div>
		</div>
		<div class="form-group row">
			<div class="form-check offset-sm-4 col-sm-8">
				<label for="dupPerms" class="form-check-label">
					<input type="checkbox" id="dupPerms" name="dupPerms" class="mr-2">{tr}Duplicate permissions{/tr}
				</label>
			</div>
		</div>
		<div class="submit text-center">
			<input type="submit" class="btn btn-primary" name="duplicate" value="{tr}Duplicate{/tr}">
		</div>
	</form>
{/if}
