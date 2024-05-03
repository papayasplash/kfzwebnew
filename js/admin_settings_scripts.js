
function updateVehicleImportProgress() {
    jQuery.ajax({
        url: ajaxurl,
        type: 'POST',
        data: {
            action: 'get_vehicle_import_progress'
        },
        success: function(response) {
            var data = JSON.parse(response);
            jQuery('#vehicle-progress-bar').css('width', data.progress + '%');
            jQuery('#vehicle-progress-bar').text(data.progress + '%');
            if(data.progress < 100) {
                setTimeout(updateVehicleImportProgress, 1000); // Aktualisiert jede Sekunde
            }
        }
    });
}

jQuery(document).ready(function() {
    updateVehicleImportProgress();
});
jQuery(document).ready(function(){
	jQuery('#addAccount').click(function() {
		userRow=jQuery(this).parent().parent();
		passRow=jQuery(this).parent().parent().next();
		
		i=jQuery('.mob_password').length;

		newUserRow=userRow.clone();
		newUserRow.find('#addAccount').remove();
		newUserRow.find('#mob_username0').attr('name','MobileDE_option[mob_username]['+i+']').val('');

		newPassRow=passRow.clone();
		newPassRow.find('#mob_password0').attr('name','MobileDE_option[mob_password]['+i+']').val('');

		lastRow=jQuery('.mob_password:last').parent().parent();
		lastRow.after(newPassRow);
		lastRow.after(newUserRow);

	});

	jQuery.addLoading= function(obj){
		jQuery(obj).after('<div id="mob_loading" style="display:inline; margin:0 5px; padding:0 15px 10px 10px; background:url(\'//mobilede-fahrzeugintegration.de/loading-new.gif\') no-repeat;"</div>');
	}

	jQuery('#importData').click(function(){
		jQuery.addLoading(jQuery(this).next('input'));
		jQuery(this).prop('disabled','disabled');
		jQuery(this).next('input').prop('disabled','disabled');

		jQuery.get(ajaxurl,{action:"ajaxImportData"}, function(data){
			jQuery('#mob_loading').remove();
			jQuery('#importData').removeProp('disabled');
			jQuery('#deletePosts').removeProp('disabled');
			data = jQuery.trim(data);
			if(data=='1'){
				// $(location).attr('href')
				var pathname = window.location.pathname;
				jQuery('.submit').append('<div class="updated"><p><strong>Ihre Fahrzeuge wurden erfolgreich importiert!</strong></p></div>');
				jQuery('.updated').delay(90000).fadeOut('slow');
			}else {//error
				jQuery('.submit').append('<div class="error"><p><strong>Es ist ein unerwarteter Fehler aufgetreten, bitte wiederholen Sie die Aktion und kontaktieren Sie im Notfall den <a href="http://www.mobilede-fahrzeugintegration.de/kontakt/" target="_blank">Support</a>. </strong></p></div>');
				jQuery('.error').delay(90000).fadeOut('slow');
				console.log(data);
				// alert(data);
			}
		});
	});
	
	jQuery('#deletePosts').click(function(){
		jQuery.addLoading(this);
		jQuery(this).prop('disabled','disabled');
		jQuery(this).prev('input').prop('disabled','disabled');

		jQuery.get(ajaxurl,{action:"ajaxDeletePosts"}, function(data){
			jQuery('#mob_loading').remove();
			jQuery('#importData').removeProp('disabled');
			jQuery('#deletePosts').removeProp('disabled');

			if(data=='1'){
				jQuery('.submit').append('<div class="updated"><p><strong>Ihre Fahrzeuge wurden erfolgreich gelöscht!<strong></p></div>');
				jQuery('.updated').delay(3000).fadeOut('slow');
		//	$('#alert').fadeOut('slow')
			}else{
				jQuery('.submit').append('<div class="error"><p><strong>Konnte nicht alle Fahrzeuge löschen, bitte erneut versuchen!</strong></p></div>');			jQuery('.error').delay(3000).fadeOut('slow');
			//	alert('Konnte nicht alle Fahrzeuge löschen, bitte erneut versuchen');
			}
		});
	});
	jQuery('#enableNew').click(function() {
		if(jQuery(this).attr('checked')) {
			jQuery('#enableNewDays').removeAttr('disabled');
		} else {
			jQuery('#enableNewDays').attr('disabled', true);
		}
	});
	jQuery('#enableNew').triggerHandler('click');
});