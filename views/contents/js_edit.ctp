var brwConditionals = <?php echo json_encode($brwConfig['fields']['conditional']) ?>;
var brwModel = '<?php echo $model ?>';
$(document).ready(function(){
	//console.log(brwConditionals);
	$.each(brwConditionals, function(index, value) {
		$('#' + brwModel + index).change(function(){
			$.each(value.Hide, function(index, value){
				$('#brw' + brwModel + value).hide();
			});
			toShow = value.ShowConditions[$(this).val()]
			if(toShow) {
				$.each(toShow, function(index, value) {
					$('#brw' +  brwModel + value).show();
				});
			}
			
		}).change();
	});
});