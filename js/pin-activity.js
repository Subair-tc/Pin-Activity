$('.pinpost').live('click',function(){
	
	var action = 'pin_unpin_this_post';
	var to_do = $(this).attr('to-do');
	var next_to = 'unpin';
	var text = 'unpin this post';
	if ( to_do == 'unpin'){
		 next_to = 'topin';
		 text = 'pin this post';
	}
	var data = {
		action: action,
		to_do: $(this).attr('to-do'),
		ac_id: $(this).attr('ac_id')
	};
	var current_item = $(this);
	jQuery.ajax({
		type:"POST",
		url: ajaxurl,
		data: data,  
		success:function(response){
			if(response){
				current_item.attr('to-do',next_to);
				current_item.text(text);
				Alert.render('got it, thanks!','alert-rare-courage');
			}
		}
	});
	
});
