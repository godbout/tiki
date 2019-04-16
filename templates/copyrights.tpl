<h2>{tr}Copyrights:{/tr} <a href="tiki-index.php?page={$page|escape:"url"}">{$page}</a></h2>

{section name=i loop=$copyrights}
	<form action="copyrights.php?page={$page}" method="post" role="form">
		<input type="hidden" name="page" value="{$page|escape}">
		<input type="hidden" name="copyrightId" value="{$copyrights[i].copyrightId|escape}">

		<div class="form-group row">
			<label class="col-sm-3 col-form-label" for="copyleft-title">{tr}Title{/tr}</label>
			<div class="col-sm-9">
				<input class="form-control wikitext" type="text" name="copyrightTitle" id="copyleft-title" value="{$copyrights[i].title|escape}">
			</div>
		</div>
		<div class="form-group row">
			<label class="col-sm-3 col-form-label" for="copyleft-year">{tr}Year{/tr}</label>
			<div class="col-sm-9">
				<input class="wikitext form-control" type="text" name="copyrightYear" id="copyleft-year" value="{$copyrights[i].year|escape}">
			</div>
		</div>
		<div class="form-group row">
			<label class="col-sm-3 col-form-label" for="copyleft-authors">{tr}Authors{/tr}</label>
			<div class="col-sm-9">
				<input class="wikitext form-control" type="text" name="copyrightAuthors" id="copyleft-authors" value="{$copyrights[i].authors|escape}">
			</div>
		</div>
		<div class="form-group row">
			<label class="col-sm-3 col-form-label" for="copyleft-holder">{tr}Copyright Holder{/tr}</label>
			<div class="col-sm-9">
				<input class="wikitext form-control" type="text" name="copyrightHolder" id="copyleft-holder" value="{$copyrights[i].holder|escape}">
			</div>
		</div>

		<div class="form-group row">
			<div class="col-sm-9 offset-sm-3">
				<input type="submit" class="btn btn-primary btn-sm" name="editcopyright" value="{tr}Edit{/tr}">

				<a class="tips text-danger" title=":{tr}Delete{/tr}" href="copyrights.php?page={$page|escape:"url"}&amp;action=delete&amp;copyrightId={$copyrights[i].copyrightId}" >
					{icon name='remove' alt="{tr}Remove{/tr}"}
				</a>
				<a class="tips" title=":{tr}Up{/tr}" href="copyrights.php?page={$page|escape:"url"}&amp;action=up&amp;copyrightId={$copyrights[i].copyrightId}">
					{icon name='up'}
				</a>
				<a class="tips" title=":{tr}Down{/tr}" href="copyrights.php?page={$page|escape:"url"}&amp;action=down&amp;copyrightId={$copyrights[i].copyrightId}">
					{icon name='down'}
				</a>
			</div>
		</div>
	</form>
{/section}

<form action="copyrights.php?page={$page}" role="form">
	<div class="form-group row">
		<label class="col-sm-3 col-form-label" for="copyleft-tit">{tr}Title{/tr}</label>
		<div class="col-sm-9">
			<input class="wikitext form-control" type="text" name="copyrightTitle" id="copyleft-tit" value="{$copyrights[i].title|escape}">
		</div>
	</div>
	<div class="form-group row">
		<label class="col-sm-3 col-form-label" for="copyleft-yyyy">{tr}Year{/tr}</label>
		<div class="col-sm-9">
			<input class="wikitext form-control" type="text" name="copyrightYear" id="copyleft-yyyy" value="{$copyrights[i].year|escape}">
		</div>
	</div>
	<div class="form-group row">
		<label class="col-sm-3 col-form-label" for="copyleft-auth">{tr}Authors{/tr}</label>
		<div class="col-sm-9">
			<input class="form-control wikitext" type="text" name="copyrightAuthors" id="copyleft-auth" value="{$copyrights[i].authors|escape}">
		</div>
	</div>
	<div class="form-group row">
		<label class="col-sm-3 col-form-label" for="copyleft-hold">{tr}Copyright Holder{/tr}</label>
		<div class="col-sm-9">
			<input class="form-control wikitext" type="text" name="copyrightHolder" id="copyleft-hold" value="{$copyrights[i].holder|escape}">
		</div>
	</div>
	<div class="form-group row">
		<div class="col-sm-9 offset-sm-3">
			<input type="submit" class="btn btn-primary btn-sm" name="addcopyright" value="{tr}Add{/tr}">
		</div>
	</div>
	<input type="hidden" name="page" value="{$page|escape}">
</form>
