{* $Id$ *}
<div class="media">
	<div class="mr-4">
		<span class="fa-stack fa-lg" style="width: 100px;" title="Configuration Wizard">
			<i class="fas fa-cog fa-stack-2x"></i>
			<i class="fas fa-flip-horizontal fa-magic fa-stack-1x ml-4 mt-4"></i>
		</span>
	</div>
	<div class="media-body">
		{icon name="admin_textarea" size=3 iclass="adminWizardIconright float-sm-right"}
		<h4 class="mt-0 mb-4">{tr}Select editor type{/tr}</h4>
		<div class="adminWizardContent">
			<fieldset>
				<legend>{tr}Editor{/tr}</legend>
				<br>
				<table style="border:0px;padding-left:20px">
					<tr>
						<td>
							<input type="radio" name="editorType" value="text" {if empty($editorType) || $editorType eq 'text'}checked="checked"{/if} /> {tr}Only Plain Text Editor (Disable Wysiwyg){/tr}
							{icon name="file-alt" style="outline" size=2 iclass="adminWizardIconright"}
							<div style="display:block; margin-left:40px">
								{tr}Use only the plain text editor, which is the most stable editor mode and most compatible with Tiki functionality. The Full WYSIWYG Editor will be disabled, but you will still be able to insert wysiwyg sections through the Plain Text editor with <a href="https://doc.tiki.org/PluginWysiwyg" alt="Link to Plugin Wysiwyg doc. page" target="blank">Plugin Wysiwyg</a>{/tr}.
							</div>
						</td>
					</tr>
					<tr>
						<td>&nbsp;</td>
					</tr>
					<tr>
						<td>
							<input type="radio" name="editorType" value="wysiwyg" {if $editorType eq 'wysiwyg'}checked="checked"{/if} /> {tr}Wysiwyg{/tr}
							{icon name="file-alt" style="outline" size=2 iclass="adminWizardIconright"}
							{icon name="file-alt" size=2 iclass="adminWizardIconright"}
							<div style="display:block; margin-left:40px">
								{tr}Use a What You See Is What You Get (Wysiwyg) editor, by default in all new pages or only in some when selected. Provides a visual interface preferred by many. You will be able to configure the Full WYSIWYG Editor options in a next wizard page{/tr}.
								{tr}It won’t change the editor on existing pages. If you just install your Tiki, note that the HomePage has already been created with the plain editor.{/tr}
							</div>
						</td>
					</tr>
				</table>
			</fieldset>
		</div>
	</div>
</div>
