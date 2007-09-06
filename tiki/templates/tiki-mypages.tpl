<h1>{tr}My {if $smarty.request.type}{$smarty.request.type} {/if}Pages{/tr}</h1>
<div id='mypageeditdivparking' style='display: none;'>
<div id='mypageeditdiv' style='border: padding-left: 7px; padding-right: 7px;'>
 <input id='mypageedit_id' type='hidden' value=''>
 <table class="normal">
  <tr>
   <th>{tr}Name{/tr}</th>
   <td>
    <input id='mypageedit_name' type='text' name='name' value='' style='width: 100%' onkeyup='isNameFree();' />
    <input id='mypageedit_name_orig' type='hidden' name='name' value='' />
    <div id='mypageedit_name_unique' style='color: red;'></div>
   </td>
  </tr>

  <tr>
   <th>{tr}Description{/tr}</th>
   <td><input id='mypageedit_description' type='text' name='description' value='' style='width: 100%' /></td>
  </tr>
  <tr>
   <th>{tr}Access{/tr}</th>
   <td><img width="20" height="20" align="top" src="styles/netineo/unlock.png"/>
   <input class="register" type="radio" checked="" value="0" name="PageType"/>
     
     <img width="20" height="20" align="top" src="styles/netineo/lock.png"/>
      
      <input class="register" type="radio" value="1" name="PageType"/>
   </td>
  </tr>
  <tr id='mypageedit_tr_type' {if $id_types}style='display: none;'{/if}>
   <th>{tr}Object Type{/tr}</th>
   <td>
    <select id="mypageedit_type" onchange='mypageTypeChange(this.value);'>
    {foreach from=$mptypes item=mptype}
   	<option value="{$mptype.id|escape}">{$mptype.name|escape}</option>
    {/foreach}
   </select>
   </td>
  </tr>
  {if $feature_categories eq "y"}
	<tr><th>{tr}Categorize{/tr}</th><td id='mypageedit_categorize_tpl'>{$mypageedit_categorize_tpl}</td></tr>
  {/if}
  <tr id='mypageedit_tr_dimensions'>
   <th>{tr}Dimensions{/tr}</th>
   <td>
    <input id='mypageedit_width' type='text' name='width' value='' style='width: 40px'> x 
    <input id='mypageedit_height' type='text' name='height' value='' style='width: 40px'> ({tr}pixels{/tr})
   </td>
  </tr>
 </table>

 <form id='form_mypageedit_typeconf'><div id='mypageedit_typeconf'></div></form>

 <div style='text-align: right'>
  <input type='button' value='Cancel' onclick='closeMypageEdit();'>
  <input id='mypageedit_submit' type='button' value='Modify' onclick='saveMypageEdit();'>
 </div>
</div>
</div>

<input type='button' value='Create' onclick='showMypageEdit(0);'>

<table class="normal">
<tr>
 {foreach from=$mp_columns item=col}
  <th class="heading" {if $col.hidden}style='display: none;'{/if}>{if $col.header_tpl}{eval var=$col.header_tpl}{else}{tr}{$col.title}{/tr}{/if}</th>
 {/foreach}
</tr>

{foreach from=$mypages item=mypage}
<tr class="odd">
 {foreach from=$mp_columns item=col}
  <td {if $col.hidden}style='display: none;'{/if}>{eval var=$col.content_tpl}</td>
 {/foreach}
</tr>
{/foreach}
</table>
<select id='mypage_page_select' onchange='changepage();'>
 {foreach from=$pagesnum key=k item=v}
 <option value='{$k}'{if $showpage == $k} selected{/if}>{$v} / {$pcount}</option>
 {/foreach}
</select>

{literal}
<script type="text/javascript">
function changepage() {
	var p=$('mypage_page_select').value;
	window.location='?showpage='+p;
}

var curmodal=0;

function initMypageEdit() {
}

function showMypageEdit(id) {
	curmodal=new Windoo({
		"modal": true,
		"width": 700,
		"height": 550,
		"container": false,
	}).adopt($('mypageeditdiv'));

	if (id > 0) {
		xajax_mypage_fillinfos(id);
		$('mypageedit_submit').value='{/literal}{tr}Modify{/tr}{literal}';
		curmodal.setTitle("Edit... ");
	} else {
		$('mypageedit_id').value=0;
		$('mypageedit_name').value='';
		$('mypageedit_name_orig').value='';
		$('mypageedit_description').value='';
		{/literal}{if $id_types}{literal}
		  $('mypageedit_type').value={/literal}{$id_types}{literal};
		  xajax_mypage_fillinfos(0, {/literal}{$id_types}{literal});
		{/literal}{else}{literal}
		  $('mypageedit_type').selectedIndex=0;
		  xajax_mypage_fillinfos(0, $('mypageedit_type').value);
		{/literal}{/if}{literal}
		$('mypageedit_width').value=mptypes[$('mypageedit_type').value].def_width;
		$('mypageedit_height').value=mptypes[$('mypageedit_type').value].def_height;
		$('mypageedit_submit').value='{/literal}{tr}Create{/tr}{literal}';
		mypageTypeChange($('mypageedit_type').value);
		curmodal.setTitle("New...");
	}

	curmodal.show();
}

function closeMypageEdit() {
	$('mypageeditdivparking').adopt($('mypageeditdiv'));
	curmodal.close();
	curmodal=null;
}

function saveMypageEdit() {
	var id=$('mypageedit_id').value;
	if ($('cat-check').value == 'on') {
	var vals={
		'name': $('mypageedit_name').value,
		'description': $('mypageedit_description').value,
		'id_types': $('mypageedit_type').value,
		'width': $('mypageedit_width').value,
		'height': $('mypageedit_height').value,
		'categories': $('cat_categories').value
	};
	} else {
	var vals={
		'name': $('mypageedit_name').value,
		'description': $('mypageedit_description').value,
		'id_types': $('mypageedit_type').value,
		'width': $('mypageedit_width').value,
		'height': $('mypageedit_height').value
	};

	}
	if (id > 0) {
		xajax_mypage_update(id, vals, xajax.getFormValues('form_mypageedit_typeconf'));
	} else {
		xajax_mypage_create(vals, xajax.getFormValues('form_mypageedit_typeconf'));
	}

	closeMypageEdit();	
}

function deleteMypage(id) {
	xajax_mypage_delete(id);
}

function isNameFree() {
	name=$('mypageedit_name').value;
	name_orig=$('mypageedit_name_orig').value;
	if ((name == '') || (name == name_orig))
		$('mypageedit_name_unique').innerHTML='';
	else xajax_mypage_isNameFree(name);
}

function mypageTypeChange(id) {
	if (id) {
		mptype=mptypes[id];
		$('mypageedit_tr_dimensions').style.display=(mptype.fix_dimensions == 'yes' ? 'none' : '');
		//$('mypageedit_tr_bgcolor').style.display=(mptype.fix_bgcolor == 'yes' ? 'none' : '');
	}
}

function htmlspecialchars(ch) {
	ch = ch.replace(/&/g,"&amp;");
	ch = ch.replace(/\"/g,"&quot;");
	ch = ch.replace(/\'/g,"&#039;");
	ch = ch.replace(/</g,"&lt;");
	ch = ch.replace(/>/g,"&gt;");
	return ch;
}

var mptypes={/literal}{$mptypes_js}{literal};
function updateMypageParams(id, vals) {
	for (var k in vals) {
		switch(k) {
			case 'name':
				$('mypagespan_name_'+id).innerHTML=htmlspecialchars(vals[k]);
				$('mypage_viewurl_'+id).href="tiki-mypage.php?mypage="+encodeURI(vals[k]);
				$('mypage_editurl_'+id).href="tiki-mypage.php?mypage="+encodeURI(vals[k])+"&edit=1";
				break;
			case 'description':
			case 'width':
			case 'height':
				$('mypagespan_'+k+'_'+id).innerHTML=htmlspecialchars(vals[k]);
				break;
			case 'type':
				$('mypagespan_type_'+id).innerHTML=htmlspecialchars(vals[k]);
				break;
			case 'id_types':
				$('mypagespan_type_'+id).innerHTML=htmlspecialchars(mptypes[vals[k].name]);
				break;
		}
	}
}

{/literal}
{if $feature_phplayers eq 'y'}{* this is an ugly hack ... *}
window.addEvent('load', initMypageEdit);
{else}
window.addEvent('domready', initMypageEdit);
{/if}
{literal}

</script>
{/literal}
