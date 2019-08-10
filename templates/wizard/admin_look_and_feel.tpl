{* $Id$ *}
<div class="media">
	<div class="mr-4">
		<span class="fa-stack fa-lg" style="width: 100px;" title="Configuration Wizard">
			<i class="fas fa-cog fa-stack-2x"></i>
			<i class="fas fa-flip-horizontal fa-magic fa-stack-1x ml-4 mt-4"></i>
		</span>
	</div>
	<div class="media-body">
		{icon name="admin_look" size=3 iclass="adminWizardIconright"}
		<h4 class="mt-0 mb-4">{tr}Configure the Tiki theme and other look & feel preferences{/tr}</h4>
		<fieldset>
			<legend>{tr}Look & Feel options{/tr}</legend>
				<div class="row">
					<div class="col-md-3 offset-md-9">
{*
						<div class="thumbnail">
							{if $thumbfile}
								<img src="{$thumbfile}" alt="{tr}Theme Screenshot{/tr}" id="theme_thumb">
							{else}
								<span>{icon name="image"}</span>
							{/if}
						</div>
*}
					</div>
					<div class="col-md-9 pull-md-3 adminoptionbox">
						{preference name=theme}
						<div class="adminoptionbox theme_childcontainer custom_url">
							{preference name=theme_custom_url}
						</div>
						{preference name=theme_option}

						{preference name=site_layout}
						{preference name=site_layout_per_object}

						{preference name=theme_admin}
						{preference name=theme_option_admin}
						{preference name=site_layout_admin}
					</div>
				</div>
{*
			<div style="position:relative;">
				<div class="adminoptionbox">
					{preference name=feature_fixed_width}
					<div class="adminoptionboxchild" id="feature_fixed_width_childcontainer">
						{preference name=layout_fixed_width}
					</div>
				</div>
			</div>
*}
			<br>
			<em>{tr}See also{/tr} <a href="tiki-admin.php?page=look&amp;alt=Look+%26+Feel" target="_blank">{tr}Look & Feel admin panel{/tr}</a></em>
			<br><br>
		</fieldset>

		<fieldset>
			<legend>{tr}Title{/tr}</legend>
			{preference name=sitetitle}
			{preference name=sitesubtitle}
		</fieldset>
		<fieldset>
			<legend>{tr}Logo{/tr}</legend>
			{preference name=sitelogo_src}
		</fieldset>
	</div>
</div>
