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

		console.log("Page now loaded!");

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

	});

})( jQuery );
