{extends "layout_view.tpl"}

{block name="title"}
	{title}{$title}{/title}
{/block}

{block name="navigation"}
	<div class="navbar btn-group">
		{permission name=goal_admin}
			<a class="btn btn-primary" href="{service controller=goal action=admin}">{tr}Goal Administration{/tr}</a>
		{/permission}
		{permission name=goal_admin type=goal object=$goal.goalId}
			<a class="btn btn-primary" href="{service controller=goal action=edit goalId=$goal.goalId}">{tr}Edit Goal{/tr}</a>
		{/permission}
		{permission name=goal_modify_eligible type=goal object=$goal.goalId}
			<a class="btn btn-primary" href="{service controller=goal action=edit_eligible goalId=$goal.goalId}">{tr}Modify Eligibility{/tr}</a>
		{/permission}
	</div>
{/block}

{block name="content"}
	<div class="card bg-light">
		<div class="card-body">
			{$goal.description|escape}
		</div>
	</div>
	<ul>
		{foreach $goal.eligible as $groupName}
			<li><a href="{service controller=goal action=show goalId=$goal.goalId group=$groupName}">{$groupName|escape}</a></li>
		{foreachelse}
			<li>{tr}No groups are eligible to this goal.{/tr}</li>
		{/foreach}
	</ul>
{/block}
