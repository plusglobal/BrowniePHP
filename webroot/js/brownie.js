$(document).ready(function(){
	multipleComboSelect();
	hoverRowsColors();
	bindFancyBox();
	flashClick();
	toclone();
	bindRichEditor();
	checkMultiple();
	$('select').jDoubleSelect();
	filterCheckbox();
	showAdvancedFilters();
	$('.submit .cancel').click(function(){ history.go(-1); return false;})
	toggleMenu();
});

function flashClick() {
	$('#flashMessage a').click(function(e){
		e.stopPropagation();
		return true;
	});
	$('#flashMessage').click(function(){
		$(this).fadeOut();
	});
}

function hoverRowsColors() {
	$('tr.list').hover(function() {
		$(this).addClass('hover');
	},
	function() {
		$(this).removeClass('hover');
	});
}

function multipleComboSelect() {
	$('.combo-select').comboselect({ sort: 'none', addbtn: brwMsg.select + ' &raquo;',  rembtn: '&laquo; ' + brwMsg.unselect});
}

function bindFancyBox() {
	$('a.brw-image').fancybox({'titlePosition': 'inside'});
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
	} else if (typeof CKEDITOR == 'object') {
		bindCKEditor();
	}
}


function bindTinyMCE() {
	$('textarea.richEditor').tinymce({
		script_url : APP_BASE + 'js/tiny_mce/tiny_mce.js',
		theme: 'advanced',
		plugins: 'contextmenu,paste,table,inlinepopups',
		theme_advanced_buttons1 : "bold,italic,underline,strikethrough,link,unlink,|,justifyleft,justifycenter,justifyright,justifyfull,|,bullist,numlist,blockquote,|,undo,redo,|,code,removeformat,forecolor,table",
		skin : "default",
		skin_variant : "silver",
		theme_advanced_buttons2: '',
		theme_advanced_toolbar_location : "top",
		theme_advanced_toolbar_align : "left",
		theme_advanced_statusbar_location : "bottom",
		theme_advanced_resizing : true,
		paste_auto_cleanup_on_paste: true,
		theme_advanced_path : false,
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

function bindCKEditor(id) {
	$('textarea.richEditor').each(function(){
		id = $(this).attr('id');
		options = {
			uiColor: '#E6E6E6',
			removePlugins: 'elementspath',
			entities: false,
			toolbar : [
				['Bold','Italic','Underline','TextColor','-','Link','Unlink','-','JustifyLeft','JustifyCenter','JustifyRight','JustifyFull','BulletedList','NumberedList','Table'],
				['Source','RemoveFormat']
			]
		};
		if (typeof customCKEditor != 'undefined' && id in customCKEditor) {
			jQuery.extend(options, customCKEditor[id]);
		}
		CKEDITOR.replace(id, options);		
	});
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

	$('form#deleteMultiple tr').click(function(e){
		if (e.target.type == "checkbox") {
			e.stopPropagation();
		} else {
			$checkbox = $(this).find(':checkbox');
			$checkbox.attr('checked', !$checkbox.attr('checked'));
			$checkbox.change();
		};
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
			alert(brwMsg.no_checked_for_deletion);
		}
		return allowSubmit;
	});
	
	$('#deleteMultiple a').click(function(e){
		e.stopPropagation();
		return true;
	});
}

function showAdvancedFilters() {
	if ($('.advanced').length) {
		$('#filterSubmit').after('<a href="#" id="show_advanced">' + brwMsg.show_advanced + '</a>');
		$('#show_advanced').toggle(function(){
			$('.advanced').show();
			$(this).html(brwMsg.hide_advanced)
		}, function() {
			$('.advanced').hide();
			$(this).html(brwMsg.show_advanced)
		});
	}
}

function filterCheckbox() {
	$('.filter-checkbox select').each(function() {
		options = $(this).children('option').size();
		$(this).multiselect({
			checkAllText: 'all',
			uncheckAllText: 'none',
			selectedList: 5,
		});
		if (options > 30) {
			$(this).multiselectfilter();
		}
	});
	$('.filter select.single-select').multiselect({
	   multiple: false,
	   header: false,
	   selectedList: 1,
	   header: false,
	});
	$('.filter-checkbox').css('display', 'block');
}

function toggleMenu() {
	$('#toggle-menu a').click(function() {
		$('#menu').slideToggle('fast', function(){
			if ($('#menu').is(':visible')) {
				$('#toggle-menu a').removeClass('toggle-hidden');
				$('#toggle-menu a').attr('title', brwMsg.hide_menu);
			} else {
				$('#toggle-menu a').addClass('toggle-hidden');
				$('#toggle-menu a').attr('title', brwMsg.show_menu);
			}
		});
		$.get(APP_BASE + 'admin/brownie/toggle_menu');
		return false;
	});
}

