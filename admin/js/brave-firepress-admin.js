(function( $ ) {
	'use strict';

	function doAjax(command, data, onSuccess, onFail)
	{
		$.ajax({
			dataType: "json",
			method: "POST",
			url: ajaxurl,

			data: {
				action: command,
				data: data
			},
			success: onSuccess})
			.fail(onFail);
	}

	$(document).ready(function(e){


		$('.btn-resyncfirebase').click(function(e){
			e.preventDefault();
			var txtstatus = $('.sync-output');
			txtstatus.html("");

			if (confirm("Are you sure you want to clear the FireBase path specified and replace ALL it's contents?"))
			{
				txtstatus.html("Performing Sync...");

				doAjax("bfp_resync", {}, function(data){

					console.log("Got back data: ", data);
					txtstatus.html((data && data.msg) ? data.msg : "Unexpected data returned!");

				}, function(){
					txtstatus.html("Unfortunately an unexpected error occured. Please try again.");
				});

			}
			else
			{
				txtstatus.html("Sync cancelled. No action taken.");
			}
		});

		var $txt = $('.txt-import');

		$('.importexport').on('click', '.btn-showimport', function(e){
			e.preventDefault();
			$txt.val('').attr('placeholder', 'Paste your imported string here and click the Import button again.');
			$txt.show();
			$(this).removeClass('button-secondary btn-showimport').addClass('button-primary btn-import');
		});

		$('.importexport').on('click', '.btn-import', function(e){

			e.preventDefault();
			if ($txt.val().trim() === '')
			{
				$txt.hide().attr('placeholder', '');
				$(this).removeClass('button-primary btn-import').addClass('button-secondary btn-showimport');
			}
			else
			{

				doAjax("bfp_import", $txt.val(), function (data) {

					console.log("Got back data: ", data);

					if (data.success)
					{
						$txt.hide().attr('placeholder', '');
						$(this).removeClass('button-primary btn-import').addClass('button-secondary btn-showimport');

						window.location.reload();
					}
					else
					{
						alert(data.msg);
					}

				}, function () {
					alert("Sorry, an unexpected error occured while trying to import. Please try again later.");
				});
			}
		});

		$('.btn-export').click(function(e){
			e.preventDefault();
			$('.btn-import').removeClass('button-primary btn-import').addClass('button-secondary btn-showimport');

			$txt.show().attr('placeholder', '');

			doAjax("bfp_export", {}, function(data){

				$txt.val(data);

			}, function(){
				alert("Sorry, an unexpected error occured while trying to export. Please try again later.");
			});

		});





	});

})( jQuery );
