{* $Id$ *}
<div class="media">
	<div class="mr-4">
		<span class="fa-stack fa-lg" style="width: 100px;" title="Configuration Wizard">
			<i class="fas fa-cog fa-stack-2x"></i>
			<i class="fas fa-flip-horizontal fa-magic fa-stack-1x ml-4 mt-4"></i>
		</span>
	</div>
	<div class="media-body">
		{icon name="file-text-o" size=3 iclass="adminWizardIconright"}
		<h4 class="mt-0 mb-4">{tr}Set up the Wiki environment{/tr}</h4>
		<fieldset>
			<legend>{tr}Wiki environment{/tr}</legend>
			<div class="admin clearfix featurelist">
				{preference name=feature_categories}
				{preference name=wiki_auto_toc}
				<div class="adminoptionboxchild">
					{tr}See also{/tr} <a href="https://doc.tiki.org/Category" target="_blank">{tr}Category{/tr} @ doc.tiki.org</a>
				</div>
				{preference name=feature_wiki_structure}
				<div class="adminoptionboxchild">
					{tr}Look for the <img src="img/icons/camera.png" /> icon in the editor toolbar{/tr}. {tr}Requires Java{/tr}.<br/><a href="https://www.java.com/verify/" target="_blank">{tr}Verify your Java installation{/tr}</a>.<br>
				</div>
				{preference name=flaggedrev_approval}
				<div id="flaggedrev_approval_childcontainer">
					{if $prefs['feature_categories'] eq 'y'}
						{preference name=flaggedrev_approval_categories}
					{else}
						{remarksbox type="info" title="{tr}Info{/tr}"}
							{tr}Once you have the feature '<strong>Categories</strong>' enabled, you will need to define some content categories, and indicate which ones require revision approval for their wiki pages{/tr}.
							<br><br/>
							{tr}You will be able to set the category ids here when you come back with Categories enabled, or at the corresponding <a href="tiki-admin.php?page=wiki&cookietab=3" class="alert-link" target="_blank">Control Panel</a> with the '<em>Advanced</em>' features shown in the <a class='alert-link' target='tikihelp' href='https://doc.tiki.org/Preference+Filters'>Preference Filters</a>{/tr}.
						{/remarksbox}
					{/if}
				</div>
			</div>
			<br><br>
			<em>{tr}See also{/tr} <a href="tiki-admin.php?page=wiki&amp;alt=Wiki#content1" target="_blank">{tr}Wiki admin panel{/tr}</a></em>
		</fieldset>
	</div>
</div>
