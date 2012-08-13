<!-- Third party script for BrowserPlus runtime (Google Gears included in Gears runtime now) -->
<link rel="stylesheet" href="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.9/themes/base/jquery-ui.css" type="text/css" />
<link rel="stylesheet" href="<?php echo Router::url('/brownie/css/plupload/jquery.ui.plupload.css'); ?>" type="text/css" />
<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.5.1/jquery.min.js"></script>
<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.9/jquery-ui.min.js"></script>


<!-- Load plupload and all it's runtimes and finally the jQuery queue widget -->
<script type="text/javascript" src="<?php echo Router::url('/brownie/js/plupload/plupload.full.js'); ?>"></script>
<script type="text/javascript" src="<?php echo Router::url('/brownie/js/plupload/jquery.ui.plupload.js'); ?>"></script>

<form  method="post" action="dump.php">
	<div id="uploader"><p>Loading uploader...</p></div>
</form>

<script type="text/javascript">
$(function() {
	$("#uploader").plupload({
		// General settings
		runtimes : 'html5,gears,flash',
		url : '<?php echo Router::url(array(
			'controller' => 'upload', 'action' => 'upload',
			$model, $uploadModel, $record_id, $category_code
		)); ?>',
		max_file_size : '10mb',
		max_file_count: 200, // user can add no more then 20 files at a time
		//chunk_size : '1mb',
		unique_names : true,
		multiple_queues : true,

		// Resize images on clientside if we can
		//resize : {width : 320, height : 240, quality : 90},

		// Rename files by clicking on their titles
		//rename: true,

		// Sort files
		sortable: true,

		// Specify what files to browse for
		filters : [
			{title : "Image files", extensions : "jpg,gif,png,jpeg"}
			//,{title : "Zip files", extensions : "zip,avi"}
		],

		// Flash settings
		flash_swf_url : '<?php echo Router::url('/brownie/js/plupload/plupload.flash.swf'); ?>',

		// Silverlight settings
		//silverlight_xap_url : '../../js/plupload.silverlight.xap'
	});

	// Client side form validation
	$('form').submit(function(e) {
        var uploader = $('#uploader').plupload('getUploader');

        // Files in queue upload them first
        if (uploader.files.length > 0) {
            // When all files are uploaded submit form
            uploader.bind('StateChanged', function() {
                if (uploader.files.length === (uploader.total.uploaded + uploader.total.failed)) {
                    $('form')[0].submit();
                }
            });
            uploader.start();
        } else {
            alert('You must at least upload one file.');
        }

        return false;
    });


});
</script>