<span class="like_block">
	{if not $count_only}
		<a class="like_button" data-type="{$type}" data-object="{$object}" href="#">
			{if $has_relation}
				<i class="fas fa-thumbs-up fa-lg"></i>
			{else}
				<i class="far fa-thumbs-up fa-lg"></i>
			{/if}
		</a><!--close anchor-->
	{/if}
	<span class="numlikes">{$count}</span> {tr}{$count_label}{/tr}
</span>

{jq}
	$(".like_button").click(function(e) {
		e.preventDefault();
		var element = $(this);
		$.post($.service(
			'relation',
			'toggle',
			{
				relation:"tiki.user.like",
				target_type:$(this).data('type'),
				target_id:$(this).data('object'),
				source_type:"user",
				source_id:"{{$user}}",
			}
			), function(data) {
				if (data && data['relation_id']){ //if relation_id,
					$(element).find("i").removeClass('far');
					$(element).find("i").addClass('fas');
					$(element).parent().find('.numlikes').html(parseInt($('.numlikes').html(), 10)+1);
				} else {
					$(element).find("i").removeClass('fas');
					$(element).find("i").addClass('far');
					$(element).parent().find('.numlikes').html(parseInt($('.numlikes').html(), 10)-1);
				}
			},
		'json');
	});
{/jq}