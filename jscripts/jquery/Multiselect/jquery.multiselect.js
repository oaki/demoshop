/* 
 * @NAME@
 * 
 * Copyright (c) 2009 Jan Smitka <jan@smitka.org>
 * 
 * This source file is part of @NAME@.
 * You are permitted to use, copy, modify, and distribute this application
 * only with permission of the original author.
 * 
 * This application is powered by Nette Framework (http://nettephp.com),
 * developed by Nette Foundation (http://nettefoundation.com/)
 * 
 * @author Jan Smitka <jan@smitka.org>
 * @package @PACKAGE@
 * @copyright Copyright (c) 2009 Jan Smitka <jan@smitka.org>
 */


$(function() {
	$('select.multiselect').each(function () {
		var select = $(this);
		select.hide();
		var wrapper = $(document.createElement('div'));
		wrapper.addClass('multiselect-dual-list');
		var selected = $(document.createElement('div'));
		selected.addClass('list');
		selected.addClass('selected');
		var unselected = $(document.createElement('div'));
		unselected.addClass('list');

		select.children('option').each(function () {
			var li = $(document.createElement('div'));
			li.addClass('item');
			li.data('value', $(this).val());
			li.text($(this).text())
			if ($(this).attr('selected'))
				selected.append(li);
			else
				unselected.append(li);
		});

		wrapper.append(selected);
		wrapper.append(unselected);

		wrapper.children('.list').sortable({
			connectWith: '.multiselect-dual-list > .list',
			items: '> .item',
			update: function (event, ui) {
				select.children('[value=' + ui.item.data('value') + ']')
					.attr('selected', ui.item.parent().hasClass('selected'));
			}
		});

		select.after(wrapper);
	});

});