{* $Id$ *}

<div class="media">
	<div class="mr-4">
		<span class="fa-stack fa-lg" style="width: 100px;" title="Configuration Wizard">
			<i class="fa fa-gear fa-stack-2x"></i>
			<i class="fa fa-rotate-270 fa-magic fa-stack-2x ml-5"></i>
		</span>
	</div>
	<div class="media-body">
		<h4 class="mt-0 mb-4">{tr}File storage{/tr}</h4>
		{if isset($promptElFinder) AND $promptElFinder eq 'y'}
			<div>
				<fieldset>
					{icon name="files-o" size=2 iclass="adminWizardIconright"}
					<legend>{tr}elFinder{/tr}</legend>
					<input type="checkbox" class="form-check-input" name="useElFinderAsDefault" {if !isset($useElFinderAsDefault) or $useElFinderAsDefault eq true}checked='checked'{/if} /> {tr}Set elFinder as the default file gallery viewer{/tr}.
					<div class="adminoptionboxchild">
						{tr}See also{/tr} <a href="http://doc.tiki.org/elFinder" target="_blank">{tr}elFinder{/tr} @ doc.tiki.org</a>
					</div>
					<br>
				</fieldset>
			</div>
		{/if}
		{if isset($promptFileGalleryStorage) AND $promptFileGalleryStorage eq 'y'}
			<div>
				<fieldset>
					{icon name="files-o" size=2 iclass="adminWizardIconright"}
						<legend>{tr}File Gallery storage{/tr}</legend>
						{preference name='fgal_use_dir'}
				</fieldset>
			</div>
		{/if}
		{if isset($promptAttachmentStorage) AND $promptAttachmentStorage eq 'y'}
			<div>
				<fieldset>
					{icon name="files-o" size=2 iclass="adminWizardIconright"}
					<legend>{tr}Attachment storage{/tr}</legend>
					{preference name=w_use_db}
					{preference name=w_use_dir}
				</fieldset>
			</div>
		{/if}
	</div>
</div>
