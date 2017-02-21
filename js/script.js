console.log("files_quota");

(function ($, OC) {

	$(document).ready(function () {
		$('#submitNewDefaultQuota').click(function () {
			console.log("JE SUIS ICI OUHOUUUUUU");
			OCdialogs.confirm(
				t('filesquota_defaultquota', 'Are you sure you want to change the default files quota?'),
				t('filesquota_defaultquota', 'Change quota?'),
				function( confirmed )
				{
					if ( confirmed )
					{
						console.log("CONFIRME");
						var url = OC.generateUrl('/apps/files_quota/default-quota');
						var data = {
							defaultquota: $('#defaultfilesnumber').val()
						}
						$.post(url, data).success(function (response){
							OCdialogs.info( response.message, t('filesquota_defaultquota', 'New Default Quota Set'), null, true );
						});
					}
				},
				true
			);
		});
	});
})(jQuery, OC);
