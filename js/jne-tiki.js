jQuery(document).ready(function($){
	$('form#track-frm input[name=service1]').click( function(ev){
		ev.preventDefault();
		$(this).attr('checked','checked');
		$('form#track-frm input[name=service2]').removeAttr('checked');
	});
	$('form#track-frm input[name=service2]').click( function(ev){
		ev.preventDefault();
		$(this).attr('checked','checked');
		$('form#track-frm input[name=service1]').removeAttr('checked');
	});
	if( typeof $('form#track-frm input[name=service1]').attr('checked')=='undefined' && typeof $('form#track-frm input[name=service2]').attr('checked')=='undefined' ){
		$('form#track-frm input[name=service1]').click();
	}

	$('form#track-frm').submit(function(ev){
		ev.preventDefault();
		$el = $(this).find('input[name="awb"]');
		if( $.trim($el.val())=='' ){
			$el.focus();
			alert(JNETIKITRACKING_LANG.empty_tracking);
			return false;
		}
		$selected = '';
		if( typeof $(this).find('input[name=service1]').attr('checked')!='undefined' ){
			$selected = 'JNE';
		}else if( typeof $(this).find('input[name=service2]').attr('checked')!='undefined' ){
			$selected = 'TIKI';
		}
		if( $selected == '' ){
			alert(JNETIKITRACKING_LANG.empty_service);
			return false;
		}
		if( !_tracking_is_numeric( $.trim($el.val()) ) ){
			$el.focus();
			alert(JNETIKITRACKING_LANG.nan_tracking);
			return false;
		}
		if( $selected=='JNE' && (''+$.trim($el.val())).length<10 ){
			$el.focus();
			alert(JNETIKITRACKING_LANG.jne_errlen);
			return false;
		}
		if( $selected=='TIKI' && (''+$.trim($el.val())).length<10 ){
			$el.focus();
			alert(JNETIKITRACKING_LANG.tiki_errlen);
			return false;
		}
		var $url = '';
		if( typeof $(this).attr('action')!='undefined' ){
			$url = $(this).attr('action');
		}else{
			$url = window.location.href;
		}
		if( $url == "" ) $url = window.location.href;
		$(this).find('input[name="do_tracking"]').val(Date());
		$("#wait").show();
		$('#ajax-result').fadeOut(300, function(){
			$(this).removeAttr('style').css({display:'none'});
		});
		$.ajax($(this).attr('action'), {
			type: $(this).attr('method'),
			data:$(this).serialize(),
			async:true,
			global:false,
			cache:false,
			dataType:'text',
			complete:function(){
				$("#wait").hide();
				$('form#track-frm').find('input[name="jne_detail"]').val('');
			},
			success: function(data) {
				if(typeof data!='undefined' ){
					$('#ajax-result').html(data);
					$('#ajax-result a').attr('href', '#').click(function(ev){
						ev.preventDefault();
						return _tracking_track_detail();
					});
					$('#ajax-result').fadeIn(300, function(){
						$(this).removeAttr('style').css({display:'block'});
					});
				}else{
					alert(JNETIKITRACKING_LANG.empty_result);
				}
			},
			error: function(XMLHttpRequest, textStatus, errorThrown){
				alert(textStatus+'\n'+errorThrown);
			}
		});
		return false;
	});
	$('#jne-tiki-tracker .copyright:first').after('<div id="ajax-result"></div>');
});

function _tracking_track_detail(){
	(jQuery)(function($){
		$('form#track-frm').find('input[name="jne_detail"]').val('1');
		$('form#track-frm').submit();
		return false;
	});
}

function _tracking_is_numeric(v){
	v = v+'';
	if( v=='' ) return false;
	result = true;
	for(i=0; i<v.length; i++){
		s = v.substr(i,1);
		if( s=='0' || s=='1' || s=='2' || s=='3' || s=='4' || s=='5' || s=='6' || s=='7' || s=='8' || s=='9' ){
			//
		}else{
			result = false;
			break;
		}
	}
	return result;
}