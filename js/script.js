console.log("files_quota");

(function ($, OC) {

	$(document).ready(function () {

		$('.chosen-select').chosen({
    	search_contains: true
		}); 
		$('#submitNewDefaultQuota').click(function () {
			console.log("JE SUIS ICI OUHOUUUUUU");
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
						console.log("CONFIRME");
						console.log($('#defaultfilesnumber').val());
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
			console.log($(this).val());
			var username = $(this).val();
			console.log("USERNAME = " + username);
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
						console.log("CONFIRME 2");
						console.log($('#userquotafiles').val());
						console.log($('#filesquota-user-select').val());
						var url = OC.generateUrl('/apps/files_quota/setUserQuota');
						var data = {
							quota: $('#userquotafiles').val(),
							username: $('#filesquota-user-select').val()
						};
						$.post(url, data).success(function (response) {
							console.log(response);
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
