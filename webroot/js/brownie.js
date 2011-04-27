function hoverRowsColors() {
   $('tr.list').hover(function() {
		$(this).css('background-color', '#E1EBF5');
	},
	function() {
		$(this).css('background-color', '#fff');
	});
	
	$('select').jDoubleSelect();
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
	$('#flashMessage').click(function(){
		$(this).fadeOut();
	})
	toclone();
	bindRichEditor();
});

function toclone() {
	$('.hide').css('display', 'none');
	$('.cloneLink').click(function(){
		parts = $(this).attr('id').split('_');
		var i = parts[1];
		$('#fieldset' + i).clone().css('display', 'none').removeClass('hide').appendTo('#cloneHoder' + i).fadeIn();
		bindRemove();
		return false;
	});
}

function bindRemove() {
	$('.cloneRemove').click(function(){
		$(this).parents('div.fieldsetUploads').fadeOut('fast', function() {
			$(this).remove();
		});
		return false;
	});
}


function bindRichEditor() {
	if (typeof jQuery.fn.tinymce == 'function') {
		bindTinyMCE();
	} else if (typeof FCKeditor == 'function') {
		bindFckEditor();
	}
}


function bindTinyMCE() {
	$('textarea.richEditor').tinymce({
		script_url : APP_BASE + 'js/tiny_mce/tiny_mce.js',
		theme: 'advanced',
		plugins: 'contextmenu,paste,table,inlinepopups',
		theme_advanced_buttons1 : "bold,italic,underline,strikethrough,link,unlink,|,justifyleft,justifycenter,justifyright,justifyfull,|,bullist,numlist,blockquote,|,undo,redo,|,code,removeformat,forecolor,table",
		skin : "o2k7",
		skin_variant : "silver",
		theme_advanced_buttons2: '',
		theme_advanced_toolbar_location : "top",
		theme_advanced_toolbar_align : "left",
		theme_advanced_statusbar_location : "bottom",
		theme_advanced_resizing : true,
		paste_auto_cleanup_on_paste: true,
		content_css: APP_BASE + 'brownie/css/tinymce.css'
	});
}

function bindFckEditor(id) {
	$('textarea.richEditor').each(function(){
		id = $(this).attr('id');
		oFCKeditor = new FCKeditor(id);
		oFCKeditor.BasePath = APP_BASE + 'js/fckeditor/';
		oFCKeditor.Config['CustomConfigurationsPath'] = APP_BASE + 'brownie/js/fckconfig.js';
		oFCKeditor.ToolbarSet = "Brownie";
		oFCKeditor.ReplaceTextarea();
	})
}