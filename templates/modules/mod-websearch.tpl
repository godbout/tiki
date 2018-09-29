{* $Id$ *}
{jq}
$(document).ready(function(){
    var pagehelp ="";
    $("#search-form").submit( function () {
        var selectvalue = $("#selectsearch").val() ;
        var textsearch = $("#text-search").val();
        var textsend =  textsearch.replace(" ","+");
        $("#search-form").attr("action",selectvalue+""+textsend);
    });
});
{/jq}
{tikimodule error=$module_params.error title=$tpl_module_title name="websearch" flip=$module_params.flip decorations=$module_params.decorations nobox=$module_params.nobox notitle=$module_params.notitle style=$module_params.style}
	<form method="get"  id="search-form" action="#" target="Google" role="form" accept-charset="UTF-8">
		<div class="form-group row mx-0">
			<!--<div class="col-sm-2">
				<img src="img/googleg.gif" alt="Google" />
			</div>//-->
			<div class="col-sm-12">
				<input type="text" name="q" id="text-search" class="form-control" maxlength="100" placeholder = "{tr}Search{/tr}" required="true"/>
			</div>
		</div>
        <div class="form-group row mx-0">
			<div class="col-sm-12">
				<select  id="selectsearch" class="form-control" id="sel1">
                    {foreach from=$engines key=k item=v}
                      <option value="{$v}">{$k}</option>
                    {/foreach}
                 </select>
               <span> <a href="{$url_page_info_engines}" id="help" target="tikihelp" class="tikihelp" title="" data-original-title="Configuration Wizard">
						<span class="icon icon-help fa fa-question-circle fa-fw "></span>
				</a></span>
			</div>
		</div>
		<div class="text-center">
            <button
                type="submit"
                class="btn btn-secondary"
            >
                {tr}Search{/tr}
            </button>
        </div>
	</form>

{/tikimodule}
