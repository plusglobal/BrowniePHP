function hoverRowsColors() {
   $('tr.list').hover(function() {
		$(this).css('background-color', '#E1EBF5');
	},
	function() {
		$(this).css('background-color', '#fff');
	});
}

function multipleComboSelect() {
	$('.combo-select').comboselect({ sort: 'none', addbtn: '&raquo;',  rembtn: '&laquo;' });
}

function bindFancyBox() {
	$(".images-gallery a.brw-image").fancybox({'titlePosition': 'inside'});
	$('a[target=modal]').fancybox({'titleShow': false});
}

$(document).ready(function(){
	multipleComboSelect();
	hoverRowsColors();
	bindFancyBox();
	$('.flash').click(function(){
		$(this).fadeOut();
	})
});