{* $Id$ *}
{title admpage="i18n"}{tr}Edit languages{/tr}{/title}
<div class="t_navbar mb-4">
	{if $smarty.session.interactive_translation_mode eq 'on'}
		{button href="tiki-interactive_trans.php?interactive_translation_mode=off" _text="{tr}Turn off interactive translation{/tr}" _ajax="n"}
	{else}
		{button href="tiki-interactive_trans.php?interactive_translation_mode=on" _text="{tr}Turn on interactive translation{/tr}" _ajax="n"}
	{/if}
	<a class="btn btn-link tips" href="{service controller=language action=manage_custom_translations}" title="{tr}Customized String Translation{/tr}:{tr}Manage local translations in a custom.php file{/tr}">
		{icon name="file-code-o"} {tr}Custom Translations{/tr}
	</a>
	<a class="btn btn-link tips" href="{service controller=language action=upload language={$edit_language}}" title="{tr}Upload Translations{/tr}:{tr}Upload a file with translations for the selected language.{/tr}">
		{icon name="upload"} {tr}Upload Translations{/tr}
	</a>
</div>
<form action="tiki-edit_languages.php" id="select_action" method="post">
	{if isset($find)}
		<input type="hidden" name="find" value="{$find}">
	{/if}
	{if isset($maxRecords)}
		<input type="hidden" name="maxRecords" value="{$maxRecords}">
	{/if}
	<div class="adminoptionbox">
		<div class="form-group row">
			<label for="edit_language" class="col-md-3 col-form-label">{tr}Language{/tr}</label>
			<div class="col-md-6">
				<select id="edit_language" class="translation_action form-control" name="edit_language">
					{section name=ix loop=$languages}
						<option value="{$languages[ix].value|escape}" {if $edit_language eq $languages[ix].value}selected="selected"{/if}>{$languages[ix].name}</option>
					{/section}
				</select>
			</div>
			<div class="col-md-3">
				<a class="btn btn-link tips" href="{service controller=language action=download language={$edit_language} file_type=language_php}" title="{tr}Download{/tr}:{tr}Download language.php file for the selected language.{/tr}">
					{icon name="download"}
				</a>
				<a class="btn btn-link tips" href="{service controller=language action=download_db_translations language={$edit_language}}" title="{tr}Download Database Translations{/tr}:{tr}Download a file with all the translations in the database for the selected language.{/tr}">
					{icon name="file-text-o"}
				</a>
				<a class="btn btn-link tips" href="{bootstrap_modal controller=language action=write_to_language_php language={$edit_language}}" title="{tr}Write to language.php{/tr}:{tr}Translations in the database will be merged with the other translations in language.php for the selected language.{/tr}">
					{icon name="flash"}
				</a>
			</div>
		</div>
	</div>
	<div class="adminoptionbox">
		<div class="form-group row">
			<label for="add_tran_sw" class="col-md-4 col-form-label">{tr}Add a translation{/tr}</label>
			<div class="col-md-8">
				<input id="add_tran_sw" class="translation_action" type="radio" name="action" value="add_tran_sw" {if $action eq 'add_tran_sw'}checked="checked"{/if}>
			</div>
		</div>
	</div>
	<div class="adminoptionbox">
		<div class="form-group row">
			<label for="edit_rec_sw" class="col-md-4 col-form-label">{tr}Untranslated strings{/tr}</label>
			<div class="col-md-8">
				<input id="edit_rec_sw" class="translation_action" type="radio" name="action" value="edit_rec_sw" {if $action eq 'edit_rec_sw'}checked="checked"{/if}>
				{if $prefs.record_untranslated eq 'y'}
				<div class="adminoptionboxchild form-check">
					<label class="form-check-label"><input id="only_db_untranslated" class="form-check-input translation_action" type="checkbox" name="only_db_untranslated" {if $only_db_untranslated eq 'y'}checked="checked"{/if}>{tr}Show only database stored untranslated strings{/tr}</label>
				</div>
				{/if}
			</div>
		</div>
	</div>
	<div class="adminoptionbox">
		<div class="form-group row">
			<label for="edit_tran_sw" class="col-md-4 col-form-label">{tr}Edit translations{/tr}</label>
			<div class="col-md-8">
				<input id="edit_tran_sw" class="translation_action" type="radio" name="action" value="edit_tran_sw" {if $action eq 'edit_tran_sw'}checked="checked"{/if}>
				<div class="adminoptionboxchild form-check">
					<label class="form-check-label"><input id="only_db_translations" class="translation_action form-check-input" type="checkbox" name="only_db_translations" {if $only_db_translations eq 'y'}checked="checked"{/if}>{tr}Show only database stored translations{/tr}</label>
				</div>
			</div>
		</div>
	</div>
</form>
<form action="tiki-edit_languages.php" method="post">
	<input type="hidden" name="edit_language" value="{$edit_language}">
	<input type="hidden" name="action" value="{$action}">
	{if $only_db_translations eq 'y'}
		<input type="hidden" name="only_db_translations" value="{$only_db_translations}">
	{/if}
	{if $only_db_untranslated eq 'y'}
		<input type="hidden" name="only_db_untranslated" value="{$only_db_untranslated}">
	{/if}
	{if $action eq 'add_tran_sw'}
		<div class="card">
			<div class="card-header">
				{tr}Add a translation{/tr}
			</div>
			<div class="card-body">
				<div class="form-group row">
					<label class="col-md-4 col-form-label">{tr}Original:{/tr}</label>
					<div class="col-md-8">
						<input name="add_tran_source" maxlength="255" class="form-control">
					</div>
				</div>
				<div class="form-group row">
					<label class="col-md-4 col-form-label">{tr}Translation:{/tr}</label>
					<div class="col-md-8">
						<input name="add_tran_tran" maxlength="255" class="form-control">
					</div>
				</div>
			</div>
			<div class="card-footer text-center">
				<input type="submit" class="btn btn-primary" name="add_tran" value="{tr}Add{/tr}">
			</div>
		</div>
	{/if}
	{if $action eq 'edit_tran_sw' || $action eq 'edit_rec_sw'}
		<div class="card">
			<div class="card-header">
				{if $action eq 'edit_tran_sw'}
					{tr}Edit translations{/tr}
				{else}
					{tr}Untranslated strings{/tr}
				{/if}
			</div>

			<div class="card-body" id="edit_translations">
				<div class="d-none d-md-block">
					<div class="row">
						<h4 class="col-md-6">{tr}Original string{/tr}</h4>
						<h4 class="col-md-6">{tr}Translation{/tr}</h4>
					</div>
				</div>
				{foreach from=$translations name=translations item=item}
					<div class="row mb-3">
						{* Source string *}
						<div class="col-md-6">
							<label for="source_{$smarty.foreach.translations.index}" class="d-md-none mt-2">{tr}Original string{/tr}</label>
							<textarea id="source_{$smarty.foreach.translations.index}"
								name="source_{$smarty.foreach.translations.index}"
								class="form-control" rows="2" readonly="readonly">{$item.source|escape}</textarea>
						</div>

						{* Translation *}
						<div class="col-md-6">
							<label for="tran_{$smarty.foreach.translations.index}" class="d-md-none mt-2">{tr}Translation{/tr}</label>
							<textarea id="tran_{$smarty.foreach.translations.index}"
								name="tran_{$smarty.foreach.translations.index}"
								tabindex="{counter start=1}"
								class="form-control autoheight" rows="2">{$item.tran|escape}</textarea>
						</div>

						<div class="col-md-12">
							{if isset($item.originalTranslation)}
								<table class="table table-bordered mt-1" id="diff_{$smarty.foreach.translations.index}" style="display:none">
									<tbody>
										{$item.diff}
									</tbody>
								</table>
							{/if}

							<div class="mt-1 text-right">
								{if isset($item.user) && isset($item.lastModif)}
								<div class="form-text">
									<small>{tr _0=$item.user|userlink _1=$item.lastModif|tiki_short_date}Last changed by %0 on %1{/tr}</small>
								</div>
								{/if}

								<div class="form-inline float-right">
									{if $prefs.lang_control_contribution eq 'y'}										
										<div class="form-group mx-md-1" {if ! isset($item.id)}style="display: none"{/if}{* Only translations in the database have an id. *}>
											<label class="my-1 mr-sm-2" for="scope_{$smarty.foreach.translations.index}" >{tr}Contribute:{/tr}</label>
											<select class="custom-select my-1 mr-sm-2" name="scope_{$smarty.foreach.translations.index}" id="scope_{$smarty.foreach.translations.index}">
												<option {if ! isset($item.general)}selected {/if}value="">{tr}Undecided{/tr}</option>
												<option {if $item.general === true}selected {/if}value="general">{tr}Yes{/tr}</option>
												<option {if $item.general === false}selected {/if}value="local">{tr}No{/tr}</option>
											</select>]
										</div>
									{/if}

									<div class="form-group mx-sm-1">
										<button type="submit" class="btn btn-primary tips" name="edit_tran_{$smarty.foreach.translations.index}" title=":{tr}Save translation in the database{/tr}">
											{tr}Translate{/tr}
										</button>
									</div>

									{if $action eq 'edit_tran_sw' && isset($item.changed)}
										<div class="form-group mx-sm-1">
											<button type="submit" class="btn btn-danger tips" name="del_tran_{$smarty.foreach.translations.index}" title=":{tr}Delete translation from the database{/tr}">
												{tr}Delete{/tr}
											</button>
										</div>
									{/if}

									{assign var=itemIndex value=$smarty.foreach.translations.index}
									{if isset($item.originalTranslation)}
										<div class="form-group mx-sm-1">
											{button _flip_id="diff_$itemIndex" _flip_hide_text="n" _text="{tr}Compare{/tr}" _title=":{tr}Compare the original translation with the database translation{/tr}" _class="btn btn-primary btn-sm tips"}
										</div>
									{/if}
								</div>
							</div>
						</div>
					</div>
					<hr />
				{foreachelse}
					{norecords _colspan=3}
				{/foreach}
				
				{jq}
					jQuery('select[name^="scope_"]').tooltip(
						{title: "{tr}For translations specific to this Tiki instance, select No. If this translation can be contributed to the Tiki community, select Yes.{/tr}"}
						);
					
					// Allow setting scope of database translations
					jQuery('textarea[name^="tran_"]').change(function() {
							jQuery(this).closest('tr').find("label[for^='scope_']").show();
						});
				{/jq}
			
				<div class="text-center">
					<input type="hidden" name="offset" value="{$offset|escape}">
					{if !empty($translations)}
						<input tabindex="{counter}" type="submit" class="btn btn-primary" name="translate_all" value="{tr}Translate all{/tr}">
						{if $action eq 'edit_rec_sw' && $hasDbTranslations == true && $only_db_untranslated eq 'y'}
							<input type="submit" class="btn btn-danger btn-sm" name="tran_reset" value="{tr}Delete all{/tr}" onclick="return confirm('{tr}Are you sure you want to delete all untranslated strings from database?{/tr}')">
						{/if}
						{if $action eq 'edit_tran_sw' && $only_db_translations eq 'y' && $tiki_p_admin eq 'y'}
							<input type="submit" class="btn btn-danger btn-sm" name="delete_all" value="{tr}Delete all{/tr}" onclick="return confirm('{tr}Are you sure you want to delete all translations from database?{/tr}')">
						{/if}
					{/if}
				</div>
			</div>

			<div class="card-footer text-center">
				{pagination_links cant=$total step=$maxRecords offset=$offset _ajax='n'}{strip}
				tiki-edit_languages.php?edit_language={$edit_language}&action={$action}&maxRecords={$maxRecords}&only_db_translations={$only_db_translations}&only_db_untranslated={$only_db_untranslated}{if isset($find)}&find={$find}{/if}
				{/strip}{/pagination_links}
			</div>
		</div>
	{/if}
</form>
