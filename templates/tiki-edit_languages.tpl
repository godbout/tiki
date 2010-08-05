{title}{tr}Edit or ex/import Languages{/tr}{/title}

<div class="navbar">
	{if $interactive_translation_mode eq 'on'}
		{button href="tiki-edit_languages.php?interactive_translation_mode=off" _text="{tr}Toggle interactive translation off{/tr}"}
	{else}
		{button href="tiki-edit_languages.php?interactive_translation_mode=on" _text="{tr}Toggle interactive translation on{/tr}"}
	{/if}
</div>

<table >
	<tr>
		<td valign="top" >
			<form action="tiki-edit_languages.php" method="post">
				<div class="cbox">
					<div class="cbox-title">{tr}Edit and create languages{/tr}</div>
					<div class="cbox-data">
						<div class="simplebox">
							<table>
								<tr><td align="center" colspan=3>{tr}Create Language{/tr}</td></tr>
								<tr><td class="form">{tr}Shortname{/tr}:</td><td><input name="cr_lang_short" size=6 maxlength=16 value="{$cr_lang_short|escape}" /></td><td>({tr}like{/tr} en)</td></tr>
								<tr><td class="form">{tr}Longname{/tr}:</td><td><input name="cr_lang_long" size=20 maxlength=255 value="{$cr_lang_long|escape}" /></td><td>({tr}like{/tr} English)</td></tr>
								<tr><td align="center"><input type="submit" name="createlang" value="{tr}Create{/tr}" /></td></tr>
								{if $crmsg}
									<tr><td align="center" colspan=3>{$crmsg}</td></tr>
								{/if}
							</table>
						</div>
						<div class="simplebox">
							<table>
								<tr>
									<td class="form">{tr}Select the language to edit{/tr}:</td>
									<td>
										<select name="edit_language">
											{section name=ix loop=$languages}
												<option value="{$languages[ix]|escape}" {if $edit_language eq $languages[ix]}selected="selected"{/if}>{$languages[ix]}</option>
											{/section}
										</select>
									</td>
								</tr>
								<tr>
									<td><input align="right" type="radio" name="whataction" value="add_tran_sw" {if $whataction eq 'add_tran_sw'}checked="checked"{/if}/></td>
									<td class="form">{tr}Add a translation{/tr}</td>
								</tr>
								<tr>
									<td><input align="right" type="radio" name="whataction" value="edit_tran_sw" {if $whataction eq 'edit_tran_sw'}checked="checked"{/if}/></td>
									<td class="form">{tr}Edit translations{/tr}</td>
								</tr>
								<tr>
									<td><input align="right" type="radio" name="whataction" value="edit_rec_sw" {if $whataction eq 'edit_rec_sw'}checked="checked"{/if}/></td>
									<td class="form">{tr}Translate recorded{/tr}</td>
								</tr>
								<tr>
									<td align="center" colspan=2><input type="submit" name="langaction" value="{tr}Set{/tr}" /></td>
								</tr>
							</table>
						</div>
						{if $whataction eq 'add_tran_sw'}
							<div class="simplebox">
								{tr}Add a translation{/tr}:<br />
								<table>
									<tr>
										<td class="form">{tr}Original{/tr}:</td>
										<td><input name="add_tran_source" size=20 maxlength=255></td>
										<td class="form">{tr}Translation{/tr}:</td>
										<td><input name="add_tran_tran" size=20 maxlength=255></td>
										<td align="center"><input type="submit" name="add_tran" value="{tr}Add{/tr}" /></td>
									</tr>
								</table>
							</div>
						{/if}
						{if $whataction eq 'edit_rec_sw'}
							<div class="simplebox">
								{tr}Translate recorded{/tr}:<br />
								<table>
									<tr>
										<td align="right"><input name="tran_search" value="{$tran_search|escape}" size=10	maxlength=255></td>
										<td align="center"><input type="submit" name="tran_search_sm" value="{tr}Search{/tr}" /></td>
									</tr>
									{section name=it loop=$untranslated}
										<tr>
											<td class="form">{tr}Original{/tr}:</td>
											<td><input name="edit_rec_source_{$smarty.section.it.index}" value="{$untranslated[it]|escape}" size=20 maxlength=255></td>
											<td class="form">{tr}Translation{/tr}:</td>
											<td><input name="edit_rec_tran_{$smarty.section.it.index}" size=20 maxlength=255></td>
											<td align="center"><input type="submit" name="edit_rec_{$smarty.section.it.index}" value="{tr}Translate{/tr}" /></td>
										</tr>
									{/section}
									<tr><td align="center"><input type="submit" name="tran_reset" value="{tr}reset table{/tr}" /></td></tr>
								</table>
								{if $tr_recnum != 0}
									<input type="submit" name="lessrec" value="{tr}previous page{/tr}" />
								{/if}
								{if $untr_numrows > $tr_recnum+$maxRecords}
									<input type="submit" name="morerec" value="{tr}next page{/tr}" />
								{/if}
								<input type="hidden" name="tr_recnum" value="{$tr_recnum|escape}" />
							</div>
						{/if}
						{if $whataction eq 'edit_tran_sw'}
							<div class="simplebox">
								{tr}Edit translations{/tr}:<br />
								<table>
									<tr>
										<td align="left" colspan=4>
											<input name="tran_search" value="{$tran_search|escape}" size=10 maxlength=255 />
											<input type="submit" name="tran_search_sm" value="{tr}Search{/tr}" />
										</td>
									</tr>
									{section name=it loop=$untranslated}
										<tr>
											<td class="form">{tr}Original{/tr}:</td>
											<td><input name="edit_edt_source_{$smarty.section.it.index}" value="{$untranslated[it]|escape}" size=30 maxlength=255 /></td>
											<td class="form">{tr}Translation{/tr}:</td>
											<td><input name="edit_edt_tran_{$smarty.section.it.index}" value="{$translation[it]|escape}" size=42 maxlength=255 /></td>
											<td align="center"><input type="submit" name="edt_tran_{$smarty.section.it.index}" value="{tr}Translate{/tr}" /></td>
											<td align="center"><input type="submit" name="del_tran_{$smarty.section.it.index}" value="{tr}Delete{/tr}" /></td>
										</tr>
									{/section}
								</table>
								{if $tr_recnum != 0}
									<input type="submit" name="lessrec" value="{tr}previous page{/tr}" />
								{/if}
								{if $untr_numrows > $tr_recnum+$maxRecords}
									<input type="submit" name="morerec" value="{tr}next page{/tr}" />
								{/if}
								<input type="hidden" name="tr_recnum" value="{$tr_recnum|escape}" />
								{$dbgmsg}
							</div>
						{/if}

						{$dbgmsg}
					</div>
				</div>
				<div class="cbox">
					<div class="cbox-title">{tr}Export languages{/tr}</div>
						<div class="cbox-data">
							<div class="simplebox">
								<table>
									<tr><td align="center" colspan="2">{tr}Export{/tr}</td></tr>
									<tr>
										<td class="form">{tr}Select the language to Export{/tr}:</td>
										<td>
											<select name="exp_language">
												{section name=ix loop=$exp_languages}
													<option value="{$exp_languages[ix].value|escape}"
														{if $exp_language eq $exp_languages[ix].value}selected="selected"{/if}>
														{$exp_languages[ix].name}
													</option>
												{/section}
											</select>
										</td>
									</tr>
									<tr>
										<td align="center" colspan="2">
											<input type="submit" name="downloadFile" value="{tr}Download{/tr}" />
											<input type="submit" name="exportToLanguage" value="{tr}Write to language.php{/tr}" />
											<input type="submit" name="exportToCustom" value="{tr}Write to custom.php{/tr}" />
										</td>
									</tr>
									{if isset($expmsg)}
										<tr><td align="center" colspan="2">{$expmsg}</td></tr>
									{/if}
								</table>
							</div>
						</div>
					</div>
				</div>
			</form>
		</td>
	</tr>
</table>
{$dbgmsg}
