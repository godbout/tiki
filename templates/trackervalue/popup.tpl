{capture name=popup}
	<div class="cbox">
		<table>
			{cycle values="odd,even" print=false}
			{foreach from=$popupFields item=field}
				 <tr class="{cycle}"><th>{$field.name}</th><td>{trackervalue field=$field item=$item showpopup=n}</th></tr>
			{/foreach}
		</table>
	</div>
{/capture}
{popup text=$smarty.capture.popup|escape:"javascript"|escape:"html" fullhtml="1" hauto=true vauto=true sticky=$stickypopup}
