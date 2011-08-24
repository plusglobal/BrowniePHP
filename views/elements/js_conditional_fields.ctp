<script type="text/javascript">
var brwConditionals = <?php echo json_encode($brwConfig['fields']['conditional_camelized']) ?>;
var brwModel = '<?php echo $model ?>';
$(document).ready(function(){
	$.each(brwConditionals, function(index, value) {
		$('#' + brwModel + index).change(function(){
			console.log($(this).val());
			$.each(value.Hide, function(index, value){
				$('#brw' + brwModel + value).hide();
			});
			if ($(this).attr('type') == 'checkbox') {
				val = $(this).is(':checked') ? 1 : 0;
			} else {
				val = $(this).val();
			}
			console.log(val);
			toShow = value.ShowConditions[val];
			if(toShow) {
				$.each(toShow, function(index, value) {
					$('#brw' +  brwModel + value).show();
				});
			}
		}).change();
	});
});
</script>