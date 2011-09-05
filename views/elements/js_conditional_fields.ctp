<script type="text/javascript">
var brwConditionals = <?php echo json_encode($brwConfig['fields']['conditional_camelized']) ?>;
var brwModel = '<?php echo $model ?>';
$(document).ready(function(){
	$.each(brwConditionals, function(index, value) {
		$('#' + brwModel + index).change(function(){
			$.each(value.Hide, function(index, value){
				if (typeof value == 'object') {
					$.each(value, function(index, value2){
						$('#' + value2 + value2).parent().hide();
					});
				} else {
					$('#brw' + brwModel + value).hide();
				}
			});
			if ($(this).attr('type') == 'checkbox') {
				val = $(this).is(':checked') ? 1 : 0;
			} else {
				val = $(this).val();
			}
			toShow = value.ShowConditions[val];
			if (toShow) {
				$.each(toShow, function(index, value) {
					if (typeof value == 'object') {
						$.each(value, function(index, value2){
							$('#' + value2 + value2).parent().fadeIn();
						});
					} else {
						$('#brw' +  brwModel + value).fadeIn();
					}
				});
			}
		}).change();
	});
});
</script>