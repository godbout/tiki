{* $Id$ *}
{title help="Categories" admpage="category"}{tr}Admin Categories{/tr}{/title}

<div class="t_navbar mb-4">
	{button href="tiki-browse_categories.php?parentId=$parentId" _type="link" _icon_name="view" _text="{tr}Browse Categories{/tr}" _title="{tr}Browse the category system{/tr}"}
	{button href="tiki-edit_categories.php" _type="link" _text="{tr}Organize Objects{/tr}" _icon_name="structure" _title="{tr}Organize Objects{/tr}"}
</div>

<div class="tree breadcrumb" id="top">
	<div class="treetitle">
		<a href="tiki-admin_categories.php?parentId=0" class="categpath">{tr}Top{/tr}</a>
		{if $parentId != 0}
			{foreach $path as $id=>$name}
				&nbsp;::&nbsp;
				<a class="categpath" href="tiki-admin_categories.php?parentId={$id}">{$name|escape}</a>
			{/foreach}
			({tr}ID:{/tr} {$parentId})
		{/if}
	</div>
</div>

{tabset}
	{tab name='{tr}Categories{/tr}'}
		{$tree}
	{/tab}
	{if empty($categId)}{$editLabel = "{tr}Create category{/tr}"}{else}{$editLabel = "{tr}Edit category{/tr}"}{/if}
	{tab name=$editLabel}
		{if $categId > 0}
			<h2>{tr}Edit this category:{/tr} <b>{$categoryName|escape}</b> </h2>
			{button href="tiki-admin_categories.php?parentId=$parentId#editcreate" _text="{tr}Create New{/tr}" _title="{tr}Create New{/tr}"}
		{else}
			<h2>{tr}Add new category{/tr}</h2>
		{/if}
		<form action="tiki-admin_categories.php" method="post" role="form">
			{ticket}
			<input type="hidden" name="categId" value="{$categId|escape}">
			<div class="form-group row">
				<label class="col-sm-3 col-form-label" for="parentId">{tr}Parent{/tr}</label>
				<div class="col-sm-9">
					<select name="parentId" id="parentId" class="form-control">
						{if $tiki_p_admin_categories eq 'y'}<option value="0">{tr}Top{/tr}</option>{/if}
						{foreach $categories as $category}
							<option value="{$category.categId}" {if $category.categId eq $parentId}selected="selected"{/if}>{$category.categpath|escape}</option>
						{/foreach}
					</select>
				</div>
			</div>
			<div class="form-group row">
				<label class="col-sm-3 col-form-label" for="name">{tr}Name{/tr}</label>
				<div class="col-sm-9">
					<input type="text" class="form-control" name="name" id="name" value="{$categoryName|escape}">
				</div>
			</div>
			<div class="form-group row">
				<label class="col-sm-3 col-form-label" for="description">{tr}Description{/tr}</label>
				<div class="col-sm-9">
					<textarea rows="2" class="form-control" name="description" id="description" maxlength=500>{$description|escape}</textarea>
				</div>
			</div>
			{if isset($role_groups) && count($role_groups) }
			<div class="form-group row">
				<label class="col-sm-3 col-form-label" for="description">{tr}Group Roles{/tr}</label>
				<div class="col-sm-9">
					{foreach $role_groups as $role}
					<div>
						{$role.groupRoleName}:
						<select name="categoryRole[{$role.iteration}][{$role.categId}][{$role.categRoleId}][{$role.groupRoleId}]" class="form-control">
							<option value="" >{tr}None{/tr}</option>
							{foreach $group_list as $group}
								<option value="{$group.id}" {if $group.id eq $selected_groups[$role.groupRoleId]}selected="selected"{/if}>{$group.groupName|escape}</option>
							{/foreach}
						</select>
					</div>
					{/foreach}
				</div>
			</div>
			{/if}
			{if $tiki_p_admin_categories == 'y'}
				<div class="form-group row">
					<div class="col-sm-9 offset-sm-3">
						<div class="form-check">
							<label class="form-check-label">
								<input type="checkbox" name="parentPerms" class="form-check-input" {if empty($categId)}checked="checked"{/if}>
								{tr}Apply parent category permissions{/tr}
							</label>
						</div>
					</div>
				</div>
			{/if}
			{if $tiki_p_admin_categories == 'y' and $prefs.feature_templated_groups eq 'y'}
				{jq}
					$('input[type=checkbox][name=applyRoles]').change(function(ele){
						if($('input[type=checkbox][name=applyRoles]:checked').length > 0){
							$('#rolesToApply').show();
						}else{
							$('#rolesToApply').hide();
						}
					});
					$('input[type=checkbox][name=applyRoles]').ready(function(ele){
						if($('input[type=checkbox][name=applyRoles]:checked').length > 0){
							$('#rolesToApply').show();
						}else{
							$('#rolesToApply').hide();
						}
					});
				{/jq}
				<div class="form-group row">
					<div class="col-sm-9 offset-sm-3">
						<div class="form-check">
							<label>
								<input type="checkbox" name="applyRoles" {if !empty($availableIds)}checked="checked"{/if}>
								{tr}Apply role permissions to sub-categories{/tr}
							</label>
							<select name="rolesToApply[]" id="rolesToApply" class="form-control" multiple="multiple"
									size="5">
								{foreach $roles as $role}
									<option value="{$role['id']}"
											{if isset($availableIds) && in_array($role['id'], $availableIds)}selected{/if} >
										{$role['groupName']|truncate:80:"(...)":true|escape}
									</option>
								{/foreach}
							</select>
						</div>
					</div>
				</div>
				{if $parentId == 0}
					{jq}
						$('#tplGroupContainer').change(function(ele){
							var v = $('#tplGroupContainer option:selected').val();
							if(v > 0){
								$('#patternGroupContainerDiv').show();
							}else{
								$('#patternGroupContainerDiv').hide();
							}
						});
						$('#tplGroupContainer').ready(function(ele){
							var v = $('#tplGroupContainer option:selected').val();
							if(v > 0){
								$('#patternGroupContainerDiv').show();
							}else{
								$('#patternGroupContainerDiv').hide();
							}
						});
					{/jq}
					<div class="form-group row">
						<div class="col-sm-9 offset-sm-3">
							<label>
								{tr}Automatically manage sub-categories for Templated Groups Container{/tr}
							</label>
							<select {if $tplGroupContainerId > 0}disabled {/if} name="tplGroupContainer" id="tplGroupContainer" class="form-control">
								<option>{tr}None{/tr}</option>
								{foreach $templatedGroups as $group}
									<option value="{$group['id']}"
											{if $group['id'] == $tplGroupContainerId}selected{/if} >
										{$group['groupName']|truncate:80:"(...)":true|escape}
									</option>
								{/foreach}
							</select>
						</div>
						<div class="col-sm-9 offset-sm-3" id="patternGroupContainerDiv">
							<label>
								{tr}Name Pattern for Templated Groups sub-categories{/tr}
							</label>
							<input name="tplGroupPattern" value="{($tplGroupPattern)?$tplGroupPattern:'--groupname--'}" id="patternGroupContainer" type="text" class="form-control">
						</div>
					</div>
				{/if}
			{/if}
			<div class="form-group row">
				<div class="col-sm-9 offset-sm-3">
					<input type="submit" class="btn btn-secondary" name="save" value="{tr}Save{/tr}">
				</div>
			</div>
		</form>
	{/tab}

	{if not empty($parentId) and empty($categId)}
		{tab name="{tr}Objects in category{/tr}"}
			<h2>{tr}Objects in category:{/tr} {$categ_name|escape}</h2>
			{if $objects}
				<form method="get" action="tiki-admin_categories.php">
					<label>{tr}Find:{/tr}<input type="text" name="find"></label>
					<input type="hidden" name="parentId" value="{$parentId|escape}">
					<input type="submit" class="btn btn-primary btn-sm" value="{tr}Filter{/tr}" name="search">
					<input type="hidden" name="sort_mode" value="{$sort_mode|escape}">
					<input type="hidden" name="find_objects" value="{$find_objects|escape}">
				</form>
			{/if}
			<div class="table-responsive">
				<form id="remove_object_form" method="post" action="{service controller='category' action='uncategorize'}">
					<table class="table">
						<tr>
							<th class="checkbox-cell">
								{select_all checkbox_names='objects[]'}
							</th>
							<th>&nbsp;</th>
							<th>
								<a href="tiki-admin_categories.php?parentId={$parentId}&amp;offset={$offset}&amp;sort_mode={if $sort_mode eq 'name_desc'}name_asc{else}name_desc{/if}#objects">
									{tr}Name{/tr}
								</a>
							</th>
							<th>
								<a href="tiki-admin_categories.php?parentId={$parentId}&amp;offset={$offset}&amp;sort_mode={if $sort_mode eq 'type_desc'}type_asc{else}type_desc{/if}#objects">
									{tr}Type{/tr}
								</a>
							</th>
						</tr>

						{section name=ix loop=$objects}
							<tr>
								<td class="checkbox-cell">
									<div class="form-check">
										<input type="checkbox" name="objects[]" value="{$objects[ix].type|escape}:{$objects[ix].itemId|escape}" class="form-check-input position-static">
									</div>
								</td>
								<td class="icon">
									<a href="tiki-admin_categories.php?parentId={$parentId}&amp;removeObject={$objects[ix].catObjectId}&amp;fromCateg={$parentId}" class="tips text-danger" title=":{tr}Remove from this category{/tr}" onclick="confirmPopup('{tr}Remove object from category?{/tr}', '{ticket mode=get}')">
										{icon name='remove'}
									</a>
								</td>
								<td class="text">
									<a href="{$objects[ix].href}" title="{$objects[ix].name}">
										{$objects[ix].name|truncate:80:"(...)":true|escape}
									</a>
								</td>
								<td class="text">{tr}{$objects[ix].type}{/tr}</td>
							</tr>
						{sectionelse}
							{norecords _colspan=4}
						{/section}
					</table>
					{if not empty($objects)}
						<div class="submit text-center p-1">
							{ticket}
							<input type="hidden" name="categId" value="{$parentId|escape}"}>
							<input type="submit" name="uncategorize" value="{tr}Remove checked{/tr}" class="btn btn-danger" onclick="return confirm('{tr}Remove objects from category?{/tr}');">
						</div>
					{/if}
				</form>
				{jq}
$("#remove_object_form").unbind("submit").submit(function (e) {
	$.ajax($(this).attr('action'), {
		type: 'POST',
		dataType: 'json',
		data: $(e.currentTarget).serialize(),
		success: function (data) {
			location.href = location.href.replace(/#.*$/, "");
		},
		error: function (jqxhr) {
			$(form).showError(jqxhr);
		}
	});
	return false;
});
				{/jq}
			</div>

			{pagination_links cant=$cant_objects step=$prefs.maxRecords offset=$offset}{/pagination_links}
		{/tab}

		{tab name="{tr}Moving objects{/tr}"}
			<h2>{tr}Moving objects between categories{/tr}</h2>
			<h4>{tr}Current category:{/tr} {$categ_name|escape}</h4><br>
			<form method="post" action="tiki-admin_categories.php" name="move" role="form">
				{ticket}
				<fieldset>
					<legend>{tr}Perform an action on all objects in the current category:{/tr}</legend>
					<input type="hidden" name="parentId" value="{$parentId|escape}">
					<div class="form-group row">
						<label class="col-sm-4 col-form-label" for="unassign">
							{tr}Unassign{/tr}
						</label>
						<div class="col-sm-6 input-group">
							<input
								type="submit"
								class="btn btn-primary btn-sm"
								name="unassign"
								value="{tr}OK{/tr}"
								onclick="confirmPopup('{tr}Unassign objects from category?{/tr}')"
							>
						</div>
					</div>
					<div class="form-group row">
						<label class="col-sm-4 col-form-label" for="toId">
							{tr}Move to selected category{/tr}
						</label>
						<div class="col-sm-6 input-group">
							<select name="toId" id="toId" class="form-control">
								<option>{tr}Choose destination category{/tr}</option>
								{foreach $categories as $category}
									{if $category.categId neq $parentId}
										<option value="{$category.categId}">
											{$category.categpath|escape}
										</option>
									{/if}
								{/foreach}
							</select>
							<span class="input-group-append">
								<input type="submit" class="btn btn-primary" name="move_to" value="{tr}OK{/tr}">
							</span>
						</div>
					</div>
					<div class="form-group row">
						<label class="col-sm-4 col-form-label" for="to">
							{tr}Copy to selected category{/tr}
						</label>
						<div class="col-sm-6 input-group">
							<select name="to" class="form-control">
								<option>{tr}Choose destination category{/tr}</option>
								{foreach $categories as $category}
									{if $category.categId neq $parentId}
										<option value="{$category.categId}">
											{$category.categpath|escape}
										</option>
									{/if}
								{/foreach}
							</select>
							<span class="input-group-append">
								<input type="submit" class="btn btn-primary" name="copy_from" value="{tr}OK{/tr}">
							</span>
						</div>
					</div>
				</fieldset>
			</form>
		{/tab}

		{tab name="{tr}Add objects{/tr}"}
			<h2>{tr}Add objects to category:{/tr} <b>{$categ_name|escape}</b></h2>
			{if $prefs.feature_search eq 'y' and $prefs.unified_add_to_categ_search eq 'y'}
				<form id="add_object_form" method="post" action="{service controller=category action=categorize}" role="form">
					<div class="row">
						<label class="col-sm-4">Types of object
							<select id="add_object_type">
								<option value="">{tr}All{/tr}</option>
								{foreach $types as $type => $title}
									<option value="{$type|escape}">
										{$title|escape}
									</option>
								{/foreach}
							</select>
						</label>
						<label class="col-sm-8">
							{tr}Objects{/tr}
							{$filter = []}
							{$filter.categories = 'not '|cat:$parentId}
							{$filter.object_type = 'not activity and not category'}
							{object_selector_multi _id='add_object_selector' _filter=$filter}
						</label>
					</div>
					<div class="row">
						<div class="col-sm-8 offset-sm-4">
							{ticket}
							<input type="hidden" name="categId" value="{$parentId|escape}">
							<input type="submit" class="btn btn-primary btn-sm" value="{tr}Add{/tr}">
							<span id="add_object_message" style="display: none;"></span>
						</div>
					</div>
				</form>
				{jq}
$("#add_object_form").unbind("submit").submit(function (e) {
	var form = this,
		formdata;

	// turn the list of objects into an parameter "array"
	$.each($("#add_object_selector").val().split("\n"), function (i, v) {
		$(form).append($("<input name='objects[]' type='hidden'>").val(v));
	});

	formdata = $(form).serialize();
	$.ajax($(form).attr('action'), {
		type: 'POST',
		dataType: 'json',
		data: $(form).serialize(),
		success: function (data) {
			location.href = location.href.replace(/#.*$/, "");
		},
		error: function (jqxhr) {
			$(form).showError(jqxhr);
		}
	});
	return false;
});
$("#add_object_type").change(function () {
	$("#add_object_selector").object_selector_multi("setfilter", "type", $("#add_object_type").val());
});
				{/jq}
			{else}{* feature_search=n (not unified search) *}

				<form method="get" action="tiki-admin_categories.php" role="form">
					<div class="form-group row">
						<label class="col-sm-3 col-form-label" for="find_objects">
							{tr}Find{/tr}
						</label>
						<div class="col-sm-6 input-group">
							<input type="text" name="find_objects" id="find_objects" class="form-control">
							<span class="input-group-append">
								<input type="submit" class="btn btn-primary" value="{tr}Filter{/tr}" name="search_objects">
							</span>
						</div>
					</div>
					<input type="hidden" name="parentId" value="{$parentId|escape}">
					<input type="hidden" name="sort_mode" value="{$sort_mode|escape}">
					<input type="hidden" name="offset" value="{$offset|escape}">
					<input type="hidden" name="find" value="{$find|escape}">
				</form>
				{pagination_links cant=$maximum step=$maxRecords offset=$offset}{/pagination_links}
				<form action="tiki-admin_categories.php" method="post" role="form">
					{ticket}
					<input type="hidden" name="parentId" value="{$parentId|escape}">
					<fieldset>
						{if $prefs.feature_wiki eq 'y' and $pages}
							<div class="form-group row">
								<label class="col-sm-3 col-form-label" for="pageName">
									{tr}Page{/tr}
								</label>
								<div class="col-sm-6 input-group">
									<select name="pageName[]" id="pageName" class="form-control" multiple="multiple" size="5">
										{section name=ix loop=$pages}
											<option value="{$pages[ix].pageName|escape}">
												{$pages[ix].pageName|truncate:80:"(...)":true|escape}
											</option>
										{/section}
									</select>
									<div>
										<input
											type="submit"
											class="btn btn-primary"
											name="addpage"
											value="{tr}Add{/tr}"
										>
									</div>
								</div>
							</div>
						{/if}

						{if $prefs.feature_articles eq 'y' and $articles}
							<div class="form-group row">
								<label class="col-sm-3 col-form-label" for="articleId">
									{tr}Article{/tr}
								</label>
								<div class="col-lg-6 input-group">
									<select name="articleId" id="articleId" class="form-control">
										{section name=ix loop=$articles}
											<option value="{$articles[ix].articleId|escape}">
												{$articles[ix].title|truncate:80:"(...)":true|escape}
											</option>
										{/section}
									</select>
									<span class="input-group-append">
										<input
											type="submit"
											class="btn btn-primary"
											name="addarticle"
											value="{tr}Add{/tr}"
										>
									</span>
								</div>
							</div>
						{/if}

						{if $prefs.feature_blogs eq 'y' and $blogs}
							<div class="form-group row">
								<label class="col-sm-3 col-form-label" for="blogId">
									{tr}Blog{/tr}
								</label>
								<div class="col-sm-6 input-group">
									<select name="blogId" id="blogId" class="form-control">
										{section name=ix loop=$blogs}
											<option value="{$blogs[ix].blogId|escape}">
												{$blogs[ix].title|truncate:80:"(...)":true|escape}
											</option>
										{/section}
									</select>
									<span class="input-group-append">
										<input
											type="submit"
											class="btn btn-primary"
											name="addblog"
											value="{tr}Add{/tr}"
										>
									</span>
								</div>
							</div>
						{/if}

						{if $prefs.feature_directory === 'y'&& $directories}
							<div class="form-group row">
								<label class="col-sm-3 col-form-label" for="directoryId">
									{tr}Directory{/tr}
								</label>
								<div class="col-sm-6 input-group">
									<select name="directoryId" id="directoryId" class="form-control">
										{section name=ix loop=$directories}
											<option value="{$directories[ix].categId|escape}">
												{$directories[ix].name|truncate:40:"(...)":true|escape}
											</option>
										{/section}
									</select>
									<span class="input-group-append">
										<input
											type="submit"
											class="btn btn-primary"
											name="adddirectory"
											value="{tr}Add{/tr}"
										>
									</span>
								</div>
							</div>
						{/if}

						{if $prefs.feature_galleries eq 'y' and $galleries}
							<div class="form-group row">
								<label class="col-sm-3 col-form-label" for="galleryId">
									{tr}Image gallery{/tr}
								</label>
								<div class="col-sm-6 input-group">
									<select name="galleryId" id="galleryId" class="form-control">
										{section name=ix loop=$galleries}
											<option value="{$galleries[ix].galleryId|escape}">
												{$galleries[ix].name|truncate:80:"(...)":true|escape}
											</option>
										{/section}
									</select>
									<span class="input-group-append">
										<input
											type="submit"
											class="btn btn-primary"
											name="addgallery"
											value="{tr}Add{/tr}"
										>
									</span>
								</div>
							</div>
						{/if}

						{if $prefs.feature_file_galleries eq 'y' and $file_galleries}
							<div class="form-group row">
								<label class="col-sm-3 col-form-label" for="file_galleryId">
									{tr}File gallery{/tr}
								</label>
								<div class="col-sm-6 input-group">
									<select name="file_galleryId" id="file_galleryId" class="form-control">
										{section name=ix loop=$file_galleries}
											<option value="{$file_galleries[ix].id|escape}">
												{$file_galleries[ix].name|truncate:80:"(...)":true|escape}
											</option>
										{/section}
									</select>
									<span class="input-group-append">
										<input
											type="submit"
											class="btn btn-primary"
											name="addfilegallery"
											value="{tr}Add{/tr}"
										>
									</span>
								</div>
							</div>
						{/if}

						{if $prefs.feature_forums eq 'y' and $forums}
							<div class="form-group row">
								<label class="col-sm-3 col-form-label" for="forumId">
									{tr}Forum{/tr}
								</label>
								<div class="col-sm-6 input-group">
									<select name="forumId" id="forumId" class="form-control">
										{section name=ix loop=$forums}
											<option value="{$forums[ix].forumId|escape}">
												{$forums[ix].name|truncate:80:"(...)":true|escape}
											</option>
										{/section}
									</select>
									<span class="input-group-append">
										<input
											type="submit"
											class="btn btn-primary"
											name="addforum"
											value="{tr}Add{/tr}"
										>
									</span>
								</div>
							</div>
						{/if}

						{if $prefs.feature_polls eq 'y' and $polls}
							<div class="form-group row">
								<label class="col-sm-3 col-form-label" for="pollId">
									{tr}Poll{/tr}
								</label>
								<div class="col-sm-6 input-group">
									<select name="pollId" id="pollId" class="form-control">
										{section name=ix loop=$polls}
											<option value="{$polls[ix].pollId|escape}">
												{$polls[ix].title|truncate:80:"(...)":true|escape}
											</option>
										{/section}
									</select>
									<span class="input-group-append">
										<input
											type="submit"
											class="btn btn-primary"
											name="addpoll"
											value="{tr}Add{/tr}"
										>
									</span>
								</div>
							</div>
						{/if}

						{if $prefs.feature_faqs eq 'y' and $faqs}
							<div class="form-group row">
								<label class="col-sm-3 col-form-label" for="faqId">
									{tr}FAQ{/tr}
								</label>
								<div class="col-sm-6 input-group">
									<select name="faqId" id="faqId" class="form-control">
										{section name=ix loop=$faqs}
											<option value="{$faqs[ix].faqId|escape}">
												{$faqs[ix].title|truncate:80:"(...)":true|escape}
											</option>
										{/section}
									</select>
									<span class="input-group-append">
										<input
											type="submit"
											class="btn btn-primary"
											name="addfaq"
											value="{tr}Add{/tr}"
										>
									</span>
								</div>
							</div>
						{/if}

						{if $prefs.feature_trackers eq 'y' and $trackers}
							<div class="form-group row">
								<label class="col-sm-3 col-form-label" for="trackerId">
									{tr}Tracker{/tr}
								</label>
								<div class="col-sm-6 input-group">
									<select name="trackerId" id="trackerId" class="form-control">
										{section name=ix loop=$trackers}
											<option value="{$trackers[ix].trackerId|escape}">
												{$trackers[ix].name|truncate:80:"(...)":true|escape}
											</option>
										{/section}
									</select>
									<span class="input-group-append">
										<input
											type="submit"
											class="btn btn-primary"
											name="addtracker"
											value="{tr}Add{/tr}"
										>
									</span>
								</div>
							</div>
						{/if}

						{if $prefs.feature_quizzes eq 'y' and $quizzes}
							<div class="form-group row">
								<label class="col-sm-3 col-form-label" for="quizId">
									{tr}Quiz{/tr}
								</label>
								<div class="col-sm-6 input-group">
									<select name="quizId" id="quizId" class="form-control">
										{section name=ix loop=$quizzes}
											<option value="{$quizzes[ix].quizId|escape}">
												{$quizzes[ix].name|truncate:80:"(...)":true|escape}
											</option>
										{/section}
									</select>
									<span class="input-group-append">
										<input
											type="submit"
											class="btn btn-primary"
											name="addquiz"
											value="{tr}Add{/tr}"
										>
									</span>
								</div>
							</div>
						{/if}
					</fieldset>
				</form>
				{pagination_links cant=$maximum step=$maxRecords offset=$offset}{/pagination_links}
			{/if}
		{/tab}

	{/if}{* if not empty($parentId) and empty($categId) *}

	{if empty($categId)}
		{tab name="{tr}Batch upload{/tr}"}
			<h2>{tr}Batch upload{/tr}</h2>
			<form action="tiki-admin_categories.php" method="post" enctype="multipart/form-data" role="form">
				{ticket}
				<div class="form-group row">
					<label class="col-form-label col-sm-3">{tr}CSV File{/tr}</label>
					<div class="col-sm-9">
						<input type="file" class="form-control" name="csvlist">
						<div class="form-text">
							{tr}Sample file content{/tr}
<pre>{* can't indent <pre> tag contents *}
category,description,parent
vegetable,vegetable
potato,,vegetable
</pre>
						</div>
					</div>
				</div>
				<div class="form-group row">
					<div class="col-sm-3 offset-sm-3">
						<input type="submit" class="btn btn-secondary" name="import" value="{tr}Upload{/tr}">
					</div>
				</div>
			</form>
		{/tab}
	{/if} {* if empty($categId) *}

{/tabset}
