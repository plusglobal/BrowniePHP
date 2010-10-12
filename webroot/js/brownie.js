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
	$("a[href$=.jpg],a[href$=.jpeg],a[href$=.png],a[href$=.gif]").fancybox({
		'titlePosition'	:	'inside'
	});
}

$(document).ready(function(){
	multipleComboSelect();
	hoverRowsColors();
	bindFancyBox();
	$('.flash').click(function(){
		$(this).fadeOut();
	})
});