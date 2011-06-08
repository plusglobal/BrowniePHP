$(document).ready(function(){
	multipleComboSelect();
	hoverRowsColors();
	bindFancyBox();
	$('#flashMessage').click(function(){
		$(this).fadeOut();
	})
	toclone();
	bindRichEditor();
	checkMultiple();
	$('select').jDoubleSelect();
});

function hoverRowsColors() {
	$('tr.list').hover(function() {
		$(this).addClass('hover');
	},
	function() {
		$(this).removeClass('hover');
	});
}

function multipleComboSelect() {
	$('.combo-select').comboselect({ sort: 'none', addbtn: '&raquo;',  rembtn: '&laquo;' });
}

function bindFancyBox() {
	$(".images-gallery a.brw-image").fancybox({'titlePosition': 'inside'});
	$('a[target=modal]').fancybox({'titleShow': false});
}

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

function checkMultiple() {
	$('td.delete_multiple input[type=checkbox]').change(function(){
		if ($(this).is(':checked')) {
			$(this).parents('tr').addClass('checked');
		} else {
			$(this).parents('tr').removeClass('checked');
			$('#deleteCheckAll').attr('checked', false);
		}
	});

	$('#deleteCheckAll').change(function(){
		var mustBeChecked = $(this).is(':checked');
		$('td.delete_multiple input[type=checkbox]').each(function(){
			if (mustBeChecked) {
				$(this).attr('checked', true);
			} else {
				$(this).attr('checked', false);
			}
			$(this).change();
		});
	}).css('visibility', 'visible');

	$('form#deleteMultiple td.field').click(function(){
		$(this).parents('tr').children('td').children('input').each(function(){
			if (!$(this).is(':checked')) {
				$(this).attr('checked', true);
			} else {
				$(this).attr('checked', false);
			}
			$(this).change();
		});
	});
		
	$('#deleteMultiple').submit(function(){
		var allowSubmit = false;
		$('td.delete_multiple input[type=checkbox]').each(function(){
			if ($(this).is(':checked')) {
				allowSubmit = true;
				return;
			}
		});
		if (!allowSubmit) {
			alert('no!');
		}
		return allowSubmit;
	});
}