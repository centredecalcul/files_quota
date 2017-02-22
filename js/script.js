console.log("files_quota");

(function ($, OC) {

	$(document).ready(function () {

		$('.chosen-select').chosen({
    	search_contains: true
		}); 
		$('#submitNewDefaultQuota').click(function () {
			if ($('#defaultfilesnumber').val() == "")
			{
				return;
			}
			OCdialogs.confirm(
				t('filesquota_defaultquota', 'Are you sure you want to change the default files quota?'),
				t('filesquota_defaultquota', 'Change quota?'),
				function( confirmed )
				{
					if ( confirmed )
					{
						var url = OC.generateUrl('/apps/files_quota/setDefaultQuota');
						var data = {
							quota: $('#defaultfilesnumber').val()
						};
						$.post(url, data).success(function (response) {
							console.log(response);
							OCdialogs.info( response.message, t('Default Quota', 'New default files quota'), null, true );
						});
					}
				},
				true
			);
		});

		$('#filesquota-user-select').change( function() {
			var username = $(this).val();
			if ( username == "")
			{
				return;
			}
			$('.chosen-select').chosen();
			$('#filesquota-user-block').show();
		});

		$('#submitNewUserQuota').click(function() {
			if ($('#userquotafiles').val() == "")
			{
				return;
			}
			OCdialogs.confirm(
				t('filesquota_userquota', 'Are you sure you want to change the user files quota?'),
				t('filesquota_defaultquota', 'Change quota?'),
				function( confirmed )
				{
					if ( confirmed )
					{
						var url = OC.generateUrl('/apps/files_quota/setUserQuota');
						var data = {
							quota: $('#userquotafiles').val(),
							username: $('#filesquota-user-select').val()
						};
						$.post(url, data).success(function (response) {
							if (response.error == 0)
							{
								OCdialogs.info( response.message, t('Default Quota', 'New user files quota set'), null, true );
								$('#filesquota-user-block').hide();
								}
							else
							{
								OCdialogs.info( response.message, t('Default Quota', 'The files quota failed to update'), null, true );
							}
						});
					}
				},
				true
			);
		})
	});
})(jQuery, OC);
