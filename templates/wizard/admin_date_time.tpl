{* $Id$ *}
<div class="media">
	<div class="mr-4">
		<span class="fa-stack fa-lg" style="width: 100px;" title="Configuration Wizard">
			<i class="fas fa-cog fa-stack-2x"></i>
			<i class="fas fa-flip-horizontal fa-magic fa-stack-1x ml-4 mt-4"></i>
		</span>
	</div>
	<div class="media-body">
		{icon name="clock-o" size=3 iclass="float-sm-right"}
		<h4 class="mt-0 mb-4">{tr}Set the site time zone and format for displaying dates and times{/tr}.</h4>
		<fieldset>
			<legend>{tr}Date and Time options{/tr}</legend>
			<div class="admin clearfix featurelist">
				{preference name=server_timezone}
				{preference name=users_prefs_display_12hr_clock}
				{preference name=users_prefs_display_timezone}
				{preference name=display_field_order}
			</div>
			<br>
			<em>{tr}See also{/tr} <a href="tiki-admin.php?page=general&amp;alt=General#content4" target="_blank">{tr}Date and Time admin panel{/tr}</a></em>
		</fieldset>
		<br>
	</div>
</div>
