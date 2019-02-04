{* $Id$ *}

<div class="media">
	<div class="mr-4">
		<span class="fa-stack fa-lg" style="width: 100px;" title="Configuration Wizard">
			<i class="fas fa-cog fa-stack-2x"></i>
			<i class="fas fa-flip-horizontal fa-magic fa-stack-1x ml-4 mt-4"></i>
		</span>
	</div>
	<div class="media-body">
		{icon name="admin_fgal" size=3 iclass="float-sm-right"}
		<h4 class="mt-0 mb-4">{tr}Set up the file gallery and attachments{/tr}. {tr}Choose to store them either in the database or in files on disk, among other options{/tr}.</h4>

				<fieldset class="mb-4">
					{icon name="admin_fgal" size=2 iclass="adminWizardIconright"}
					<legend>{tr}File Gallery{/tr}</legend>

					{preference name='fgal_elfinder_feature'}
					<div class="adminoptionboxchild">
						{tr}This setting makes the feature available, go to next wizard page to apply elFinder to File Galleries.
						This setting also activates jQuery, which is required for elFinder{/tr}.
						{tr}See also{/tr} <a href="http://doc.tiki.org/elFinder" target="_blank">{tr}elFinder{/tr} @ doc.tiki.org</a>
					</div>
					<br>
					{preference name='fgal_use_db'}<br>
					<em>{tr}See also{/tr} <a href="tiki-admin.php?page=fgal#content1" target="_blank">{tr}File Gallery admin panel{/tr}</a></em>
				</fieldset>

				<fieldset>
					{icon name="admin_wiki" size=2 iclass="adminWizardIconright"}
					<legend>{tr}Wiki Attachments{/tr}</legend>
					{preference name=feature_wiki_attachments}
					{preference name=feature_use_fgal_for_wiki_attachments}
					<br>
					<em>{tr}See also{/tr} <a href="tiki-admin.php?page=wiki&amp;alt=Wiki#content2" target="_blank">{tr}Wiki admin panel{/tr}</a></em>
				</fieldset>

	</div>
</div>
