jQuery(document).ready(function(a){a("form#track-frm input[name=service1]").click(function(b){b.preventDefault();a(this).attr("checked","checked");a("form#track-frm input[name=service2]").removeAttr("checked")});a("form#track-frm input[name=service2]").click(function(b){b.preventDefault();a(this).attr("checked","checked");a("form#track-frm input[name=service1]").removeAttr("checked")});if(typeof a("form#track-frm input[name=service1]").attr("checked")=="undefined"&&typeof a("form#track-frm input[name=service2]").attr("checked")=="undefined"){a("form#track-frm input[name=service1]").click()}a("form#track-frm").submit(function(c){c.preventDefault();$el=a(this).find('input[name="awb"]');if(a.trim($el.val())==""){$el.focus();alert(JNETIKITRACKING_LANG.empty_tracking);return false}$selected="";if(typeof a(this).find("input[name=service1]").attr("checked")!="undefined"){$selected="JNE"}else{if(typeof a(this).find("input[name=service2]").attr("checked")!="undefined"){$selected="TIKI"}}if($selected==""){alert(JNETIKITRACKING_LANG.empty_service);return false}if(!_tracking_is_numeric(a.trim($el.val()))){$el.focus();alert(JNETIKITRACKING_LANG.nan_tracking);return false}if($selected=="JNE"&&(""+a.trim($el.val())).length<10){$el.focus();alert(JNETIKITRACKING_LANG.jne_errlen);return false}if($selected=="TIKI"&&(""+a.trim($el.val())).length<10){$el.focus();alert(JNETIKITRACKING_LANG.tiki_errlen);return false}var b="";if(typeof a(this).attr("action")!="undefined"){b=a(this).attr("action")}else{b=window.location.href}if(b==""){b=window.location.href}a(this).find('input[name="do_tracking"]').val(Date());a("#wait").show();a("#ajax-result").fadeOut(300,function(){a(this).removeAttr("style").css({display:"none"})});a.ajax(a(this).attr("action"),{type:a(this).attr("method"),data:a(this).serialize(),async:true,global:false,cache:false,dataType:"text",complete:function(){a("#wait").hide();a("form#track-frm").find('input[name="jne_detail"]').val("")},success:function(d){if(typeof d!="undefined"){a("#ajax-result").html(d);a("#ajax-result a").attr("href","#").click(function(e){e.preventDefault();return _tracking_track_detail()});a("#ajax-result").fadeIn(300,function(){a(this).removeAttr("style").css({display:"block"})})}else{alert(JNETIKITRACKING_LANG.empty_result)}},error:function(d,f,e){alert(f+"\n"+e)}});return false});a("#jne-tiki-tracker .copyright:first").after('<div id="ajax-result"></div>')});function _tracking_track_detail(){(jQuery)(function(a){a("form#track-frm").find('input[name="jne_detail"]').val("1");a("form#track-frm").submit();return false})}function _tracking_is_numeric(a){a=a+"";if(a==""){return false}result=true;for(i=0;i<a.length;i++){s=a.substr(i,1);if(s=="0"||s=="1"||s=="2"||s=="3"||s=="4"||s=="5"||s=="6"||s=="7"||s=="8"||s=="9"){}else{result=false;break}}return result};