{* $Id$ *}

<div class="media">
	<div class="mr-4">
		<span class="fa-stack fa-lg" style="width: 100px;" title="{tr}Configuration Profiles Wizard{/tr}" >
			<i class="fas fa-cubes fa-stack-2x"></i>
			<i class="fas fa-flip-horizontal fa-magic fa-stack-1x ml-4 mt-4"></i>
		</span>
	</div>
	<div class="media-body">
		<h4 class="mt-0 mb-4">{tr}Some profiles were created for use cases of project management.{/tr}</h4>
		<h3>{tr}Profiles:{/tr}</h3>
		<div class="row">
			<div class="col-md-6">

				<h4>{tr}Gantt Chart{/tr}</h4>
				(<a href="tiki-admin.php?ticket={ticket mode=get}&profile=GanttChart&show_details_for=GanttChart&categories%5B%5D={$tikiMajorVersion}.x&repository=http%3a%2f%2fprofiles.tiki.org%2fprofiles&page=profiles&preloadlist=y&list=List#step2" target="_blank">{tr}apply profile now{/tr}</a>)
				<br/>
				{tr}This profile show cases the use of Gantt Charts in Tiki for Project Management. You can visually edit task details through the Gantt Chart UI and tracker items get updated accordingly{/tr}
				<br/>
				<a href="https://profiles.tiki.org/GanttChart" target="tikihelp" class="tikihelp" title="{tr}Gantt Chart{/tr}:
	{tr}It creates:{/tr}
						<ul>
							<li>{tr}a sample tracker that will hold the tasks{/tr}</li>
							<li>{tr}a sample wiki page linked to that tracker to display the tasks{/tr}</li>
							<li>{tr}Some demo data to help you get started{/tr}</li>
						</ul>
						{tr}Click to read more{/tr}"
				>
					{icon name="help"}
				</a>
				<div class="row">
					<div class="col-md-8 offset-md-2">
						<a href="https://profiles.tiki.org/display7" class="thumbnail internal" data-box="box" title="{tr}Click to expand{/tr}">
							<img src="img/profiles/profile_thumb_ganttchart.png" alt="Click to expand" class="regImage pluginImg" title="{tr}Click to expand{/tr}" />
						</a>
						<div class="small text-center">
							{tr}Click to expand{/tr}
						</div>
					</div>
				</div>
			</div>
			<div class="col-md-6">
				<h4>{tr}Work custom pricing{/tr}</h4>
				(<a href="tiki-admin.php?ticket={ticket mode=get}&profile=Work_Custom_Pricing&show_details_for=Work_Custom_Pricing&categories%5B%5D={$tikiMajorVersion}.x&repository=http%3a%2f%2fprofiles.tiki.org%2fprofiles&page=profiles&preloadlist=y&list=List#step2" target="_blank">{tr}apply profile now{/tr}</a>)
				<br/>
				{tr}This profile is a showcase of how to setup trackers to allow defining work orders in one tracker, and linked them to billable tasks from another one.{/tr}
				<br/>
				<a href="https://profiles.tiki.org/Work_Custom_Pricing" target="tikihelp" class="tikihelp" title="{tr}Work Custom Pricing{/tr}:
						{tr}This allows to define a custom price aside of the guide and get some of the items selected summed in another field of tracker1. The tracker also demonstrates how item link field currently allows to create and link items in a second tracker on the fly and get them stored while adding the new item in the first tracker. Objects created by this profile:{/tr}
						<ul>
							<li>{tr}...{/tr}</li>
							<li>{tr}...{/tr}</li>
							<li>{tr}...{/tr}</li>
						</ul>
						{tr}Click to read more{/tr}"
				>
					{icon name="help"}
				</a>
				<div class="row">
					<div class="col-md-8 offset-md-2">
						<a href="https://profiles.tiki.org/display5" class="thumbnail internal" data-box="box" title="{tr}Click to expand{/tr}">
							<img src="img/profiles/profile_thumb_work_custom_pricing.png" alt="Click to expand" class="regImage pluginImg" title="{tr}Click to expand{/tr}" />
						</a>
						<div class="small text-center">
							{tr}Click to expand{/tr}
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-6 offset-md-3">
				<h4>{tr}Wildcard items{/tr}</h4>
				(<a href="tiki-admin.php?ticket={ticket mode=get}&profile=Wildcard_items&show_details_for=Wildcard_items&categories%5B%5D={$tikiMajorVersion}.x&repository=http%3a%2f%2fprofiles.tiki.org%2fprofiles&page=profiles&preloadlist=y&list=List#step2" target="_blank">{tr}apply profile now{/tr}</a>)
				<br/>
				{tr}This profile adds a way to provide reusable items in a tracker coming from another one working as templates.{/tr}
				<br/>
				<a href="https://profiles.tiki.org/Wildcard_items" target="tikihelp" class="tikihelp" title="{tr}Wildcard items{/tr}:
						{tr}This profile provides some tracker items to work as template items that you can include in other trackers when needed. Objects created are:{/tr}
						<ul>
							<li>{tr}A couple of trackers. One for current items, and another one for the reusable items working ass templates{/tr}</li>
							<li>{tr}A few wiki pages to list, view and edit items{/tr}</li>
							<li>{tr}Some example items in both trackers to help you get started{/tr}</li>
						</ul>
						{tr}Click to read more{/tr}"
				>
					{icon name="help"}
				</a>
				<div class="row">
					<div class="col-md-8 offset-md-2">
						<a href="https://doc.tiki.org/display1340" class="thumbnail internal" data-box="box" title="{tr}Click to expand{/tr}">
							<img src="img/profiles/profile_thumb_wildcard_items.png" alt="Click to expand" class="regImage pluginImg" title="{tr}Click to expand{/tr}" />
						</a>
						<div class="small text-center">
							{tr}Click to expand{/tr}
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
