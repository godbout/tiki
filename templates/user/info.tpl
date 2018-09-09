{extends 'layout_view.tpl'}

{block name="title"}
	{title}{$title|escape}{/title}
{/block}

{block name="content"}
{strip}
{if $error}
	<div class="user-info">
		<span>{$error}</span>
	</div>
{else}
	<div class="user-info friend-container list-group list-group-flush" data-controller="user" data-action="info" data-params='{ldelim}"username":"{$other_user}"{rdelim}'>
		<div class="list-group-item flex-column align-items-start">
			<div class="d-flex flex-row">
				{if $avatarHtml}
					<div class="w-25 mr-2">
						{$avatarHtml}
					</div>
				{else}
					{icon name='user'}
				{/if}
				<h5>{$fullname|escape} <span class="star">{$starHtml}</span></h5>
			</div>
		</div>
		{if $gender}
			<div class="list-group-item flex-column align-items-start" style="background: transparent;">
				<h6 class="mb-1">{tr}Gender{/tr}</h6>
				<p class="mb-1">{$gender}</p>
			</div>
		{/if}
		{if $country}
			<div class="list-group-item flex-column align-items-start">
				<h6 class="mb-1">{tr}Country{/tr}</h6>
				<p class="mb-1">{$country|stringfix}
					{if !empty($distance)}<br><span class="distance">{tr _0=$distance}%0 away{/tr}</span>{/if}</p>
			</div>
		{/if}
		{if $email}
			<div class="list-group-item flex-column align-items-start">
				<h6 class="mb-1">{tr}Email{/tr}</h6>
				<p class="mb-1">{$email}</p>
			</div>
		{/if}
		{if $prefs.feature_community_mouseover_lastlogin eq 'y'}
			<div class="list-group-item flex-column align-items-start">
				<h6 class="mb-1">{tr}Last login{/tr}</h6>
				<p class="mb-1">{if !empty($lastSeen)}{$lastSeen|tiki_short_datetime}{else}{tr}Never logged in{/tr}{/if}</p>
			</div>
		{/if}
		{if $shared_groups}
			<div class="list-group-item flex-column align-items-start">
				<h6 class="mb-1">{tr}Shared groups{/tr}</h6>
				<p class="mb-1">{$shared_groups|escape}</p>
			</div>
		{/if}
		{if $friendship|count}
			<div class="list-group-item flex-column align-items-start">
				<h6 class="mb-1">{tr}Friendship{/tr}</h6>
				<p class="mb-1">
					<ul class="friendship list-unstyled">
						{foreach from=$friendship item=relation}
							{if $relation.type == 'incoming'}
								{$icon = 'login'}
							{elseif $relation.type == 'outgoing'}
								{$icon = 'logout'}
							{elseif $relation.type == 'friend'}
								{$icon = 'group'}
							{elseif $relation.type == 'following'}
								{$icon = 'share'}
							{elseif $relation.type == 'follower'}
								{$icon = 'backlink'}
							{/if}
						<li>
							{icon name=$icon}<span class="small"> {$relation.label|escape}</span>
							<div class="friendship-actions float-sm-right">
								{if !empty($relation.remove)}
									<a class="float-sm-right remove-friend btn btn-primary" href="{service controller=social action=remove_friend friend=$other_user}"
										title="{$relation.remove}" data-confirm="{tr _0=$other_user}Do you really want to remove %0?{/tr}">
										{icon name='delete'}
									</a>
								{/if}
								{if !empty($relation.add)}
									<a class="float-sm-right add-friend btn btn-primary" title="{$relation.add}" href="{service controller=social action=add_friend username=$other_user}">
										{icon name='add'}
									</a>
								{/if}
								{if !empty($relation.approve)}
									<a class="float-sm-right approve-friend btn btn-primary" title="{$relation.approve}" href="{service controller=social action=approve_friend friend=$other_user}">
										{icon name='ok'}
									</a>
								{/if}
							</div>
						</li>
						{/foreach}
					</ul>
				</p>
			</div>
		{/if}

	</div>
		{if $add_friend_button}
			<a class="add-friend btn btn-primary btn-sm mx-auto" href="{service controller=social action=add_friend username=$other_user}">
				{$add_friend_button}
			</a>
		{/if}
	</div>
{/if}
{/strip}
{/block}
