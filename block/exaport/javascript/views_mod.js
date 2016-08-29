// This file is part of Exabis Eportfolio
//
// (c) 2016 GTN - Global Training Network GmbH <office@gtn-solutions.com>
//
// Exabis Eportfolio is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This script is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You can find the GNU General Public License at <http://www.gnu.org/licenses/>.
//
// This copyright notice MUST APPEAR in all copies of the script!

var exaportViewEdit = {};

(function($){

	var $E = window.block_exaport;

	var newItem = null, lastclicked = null;
	
	$.extend(exaportViewEdit, {
	
		checkFields: function() {
			var ok = true;
			
			$E.last_popup.$body.find('.not-empty-check').each(function(){
				var input = $E.last_popup.$body.find('input[name='+$(this).attr('for')+']');
				
				if (input.length == 0) {
					// input not found, ignore
					$(this).hide();
					return;
				}
				
				if (input.val().length) {
					// has value
					$(this).hide();
					return;
				}
				
				// show error
				$(this).show();
				input.focus();
				ok = false;
				return false;
			});

			// all checks ok
			return ok;
		},
		
		addItem: function(id) {

			if (!this.checkFields()) return;

			if (id != -1) 
				newItem = lastclicked;	
			var i = 0;
			$E.last_popup.$body.find('input[name="add_items[]"]:checked').each(function () {
				i = i+1;
				if (i>1) {
					var clone = $(newItem).clone();
					newItem.after(clone);
					newItem = clone;
					};
				data = {};
				data.type = 'item';
				data.itemid = $(this).val();
				newItem.data('portfolio', data);			
				generateItem('update', $(newItem));
			});
			$E.last_popup.remove();
			newItem=null;
			
			saveBlockData();
		},
		
		addBadge: function(id) {

			if (!this.checkFields()) return;

			if (id != -1) 
				newItem = lastclicked;	
			var i = 0;
			$E.last_popup.$body.find('input[name="add_badges[]"]:checked').each(function () {
				i = i+1;
				if (i>1) {
					var clone = $(newItem).clone();
					newItem.after(clone);
					newItem = clone;
					};
				data = {};
				data.type = 'badge';
				data.itemid = $(this).val();
				newItem.data('portfolio', data);			
				generateItem('update', $(newItem));
			});
			$E.last_popup.remove();
			newItem=null;
			
			saveBlockData();
		},
		
		cancelAddEdit: function() {
			$E.last_popup.remove();

			updateBlockData();
		},
		
		addText: function(id) {

			if (!this.checkFields()) return;

			if (id != -1) 
				newItem = lastclicked;
			data = {};
			data.type = 'text';
			data.id = id;
			data.block_title = $('#block_title').val();			
			data.text = tinymce.get('block_text').getContent();
	//		data.text = $('#id_text').val();
			newItem.data('portfolio', data);
			generateItem('update', $(newItem));
			$E.last_popup.remove();
			newItem=null;
			
			saveBlockData();
		},

		addHeadline: function(id) {

			if (!this.checkFields()) return;

			if (id != -1) 
				newItem = lastclicked;	
			data = {};
			data.text = $('#headline').val();
			data.type = 'headline';
			data.id = id;
			newItem.data('portfolio', data);		
			generateItem('update', $(newItem));	
			$E.last_popup.remove();
			newItem=null;
			
			saveBlockData();
		},

		addPersonalInfo: function(id) {

			if (!this.checkFields()) return;

			if (id != -1) 
				newItem = lastclicked;		
			data = {};
			data.type = 'personal_information';
			data.id = id;	
			data.block_title = $('#block_title').val();			
			if ($('#firstname').attr('checked')=='checked')
				data.firstname = $('#firstname').val();
			if ($('#lastname').attr('checked')=='checked')
				data.lastname = $('#lastname').val();
			data.picture = $('form input[name=picture]:checked').val();
			data.email = $('form input[name=email]:checked').val();
			data.text = tinyMCE.get('block_intro').getContent();
			newItem.data('portfolio', data);			
			generateItem('update', $(newItem));	
			$E.last_popup.remove();
			newItem=null;

			saveBlockData();
		},
		
		addMedia: function(id) {

			if (!this.checkFields()) return;

			if (id != -1) 
				newItem = lastclicked;	
			data = {};
			data.block_title = $('#block_title').val();
			data.type = 'media';
			data.contentmedia = $('#block_media').val();
			data.width = $('#block_width').val();
			data.height = $('#block_height').val();			
			data.create_as_note = $('input[name=create_as_note]:checked').length ? 1 : 0;
			data.id = id;
			newItem.data('portfolio', data);		
			generateItem('update', $(newItem));	
			$E.last_popup.remove();
			newItem=null;
			
			saveBlockData();
		},
		
		resetViewContent: function(){
			// load stored blocks			
			var blocks = $('form :input[name=blocks]').val();
			if (blocks) {
				blocks = $.parseJSON(blocks);
			}
			if (!blocks) {
				// start with headline
				blocks = [{
					type: 'headline'
				}];
			}
			var portfolioDesignBlocks = $('.portfolioDesignBlocks');
			portfolioDesignBlocks.empty();
			$.each(blocks, function(){
				generateItem('new', this).appendTo(
					// wenn vorhanden zur richtigen spalte hinzufügen, sonst immer zur 1ten
					(this.positionx && portfolioDesignBlocks[this.positionx-1]) ? portfolioDesignBlocks[this.positionx-1] : portfolioDesignBlocks[0]
				);
			});
			resetElementStates();
			updateBlockData();
		},

		initContentEdit: function(){
		
			exaportViewEdit.resetViewContent();

			$(".portfolioDesignBlocks").sortable({ 
				beforeStop: function (event, ui) { 
					newItem = ui.item;
				},	
				receive: function(e, ui){
					// get ajax only for item from the top block
					var uiattr = $(ui.item[0]).closest('ul').prop("className");
					if (uiattr.search("portfolioDesignBlocks")==-1) {

						$.ajax({
							url: M.cfg['wwwroot'] + '/blocks/exaport/blocks.json.php',
							type: 'POST',
							data: {
								type_block: ui.item.attr('block-type'),
								action: 'add',
								'viewid': $('form :input[name=viewid]').val(),
								'text[itemid]': $('form :input[name=draft_itemid]').val(),
								sesskey: $('form :input[name=sesskey]').val()
							},
							success: function(res) {
								var data = JSON.parse (res);

								var popup = $E.popup({ bodyContent: data.html, onhide: function(){
									if (newItem) $(newItem).remove();
								} });

								// focus first element
								$E.last_popup.$body.find('input:visible:first').focus();
								
								$('#blockform').on('change keydown paste input', '#filterByTitle', exaportViewEdit.filterItemsByTitle);
							}
						});
						updateBlockData();
					}
				},
				update: function(e, ui){
					updateBlockData();
				},
				handle: '.header',
				placeholder: "block-placeholder",
				forcePlaceholderSize: true,
				connectWith: ['.portfolioDesignBlocks'],
			});
			$(".portfolioOptions").sortable({ 
				connectWith: ['.portfolioDesignBlocks'],
				placeholder: "block-placeholder",
				forcePlaceholderSize: true,
				stop: function(e, ui){	
					// listenelemente zurücksetzen
					resetElements();
				}
				/*
				remove: function(e, ui){
					console.log(ui);
					console.log(ui.element.html());
					// ui.item.after(ui.placeholder.clone().css('visibility', ''));
					console.log('remove');
				}
				*/
			});
			$(".portfolioElement").draggable({ 
				connectToSortable: '.portfolioDesignBlocks',
				placeholder: ".block-placeholder",
				forcePlaceholderSize: true,
				helper: "clone",
				stop: function(e, ui){	
				}		
			});
		},

		setPopupTitle: function(title){
			if (block_exaport.last_popup) {
				block_exaport.last_popup.set('headerContent', title);
			}
		},

		initAddItems: function(title){
			$('#add-items-list .add-item').click(function(event){
				var $input = $(this).find('input');
				
				if (!$(event.target).is(':input')) {
					// toggle checkbox (if the user clicked the div and not the checkbox)
					$input.prop('checked', !$input.prop('checked'));
				}
				
				if ($input.prop('checked')) {
					$(this).addClass('checked');
				} else {
					$(this).removeClass('checked');
				}
			});
		},
		
		filterItemsByTag: function() {
			// clear the search by title
			$('#blockform #filterByTitle').val('')
			$('div.add-item-category').hide();
			$('div.add-item').show();
			$('div.add-item-sub').show();
			$('tr.sharedArtefacts').show();
			var selectedTag = $('.tagfilter').val();
			if (selectedTag != '') {
				$('div.add-item').each(function() {
					var elementTags = $(this).data('tags');
					if (elementTags !== undefined) {
						if (elementTags.indexOf(selectedTag) == -1)
							$(this).hide();
					} else {
						$(this).hide();
					};
				});
			} else {
				 $('div.add-item-category, div.add-item-sub').show();
			};
			// hide category names if it has no visible artefact
			$('div.add-item-sub').each(function(){
				if($(this).find('div.add-item:visible').length == 0) {
				   $(this).hide();
				};
			});
			$('div.add-item:visible').each(function(){
				var categoryId = $(this).data('category');
				$('div.add-item-category[data-category="'+categoryId+'"]').show();
			});		
			// list of shared artefacts
			if ($('div.add-item[data-category="sharedFromUser"]:visible').length == 0)
				$('tr.sharedArtefacts').hide();		
		},
		
		filterItemsByTitle: function() {
			// reset filter by tag;
			if ($('.tagfilter').length)
				$('.tagfilter')[0].selectedIndex = 0;
			var text = $('#blockform #filterByTitle').val().toLowerCase();
 			$('div.add-item-category').hide();
			$('div.add-item').show();
			$('div.add-item-sub').show();
			$('tr.sharedArtefacts').show();
			if (text != '') {
				$('div.add-item:visible').each(function() {
					var elementText = $(this).text();
					if (elementText.toLowerCase().indexOf(text) > -1) {
						$(this).show();
					} else {
						$(this).hide();
					};
				});
			} else {
				 $('div.add-item-category, div.add-item-sub').show();
			};
			// hide category names if it has no visible artefact
			$('div.add-item-sub').each(function(){
				if($(this).find('div.add-item:visible').length == 0) {
				   $(this).hide();
				};
			});
			$('div.add-item:visible').each(function(){
				var categoryId = $(this).data('category');
				$('div.add-item-category[data-category="'+categoryId+'"]').show();
			});		
			// list of shared artefacts
			if ($('div.add-item[data-category="sharedFromUser"]:visible').length == 0)
				$('tr.sharedArtefacts').hide();		 
		},
		
		clearItemFilters: function() {
			if ($('.tagfilter').length)
				$('.tagfilter')[0].selectedIndex = 0;
			$('#blockform #filterByTitle').val('');
			exaportViewEdit.filterItemsByTitle();
		}
	});
	
	function updateBlockData()
	{
		var blocks = [];
		$('.portfolioDesignBlocks').each(function(positionx){
			// immediate li children, because content can have li too
			$(this).children('li:visible').not('.block-placeholder').each(function(positiony){
				blocks.push($.extend($(this).data('portfolio'), {
					positionx: positionx+1,
					positiony: positiony+1
				}));
			});
		});

		$('form :input[name=blocks]').val($.toJSON(blocks));
//console.log($.toJSON(blocks));
	}
	
	function saveBlockData() {
		var data = $('form#view_edit_form').serializeArray();
		data.push({name: 'ajax', value: 1});
		$.ajax({
			url: document.location.href,
			type: 'POST',
			data: data,
			success: function(res) {
				var data = JSON.parse(res);
				$('form :input[name=blocks]').val(data.blocks);
				exaportViewEdit.resetViewContent();
			}
		});
	}

	function generateItem(type, data)
	{	
		var $item;
		if (type == 'new') {
			$item = $('<li></li>');
			$item.data('portfolio', data);
		} else { 						
			$item = $(data);
			//console.log($item.data('portfolio'));
			data = $item.data('portfolio');
			if (!data) { 
				data = {}; 
				if ($item.attr('itemid')) { 
					data.type = 'item';
					data.itemid = $item.attr('itemid');
				} else {
					data.type = $item.attr('block-type');
					if ($item.attr('text'))
						data.text = $item.attr('text');				
					if ($item.attr('block_title'))
						data.block_title = $item.attr('block_title');
					if ($item.attr('firstname'))
						data.firstname = $item.attr('firstname');
					if ($item.attr('lastname'))
						data.lastname = $item.attr('lastname');
					if ($item.attr('picture'))
						data.picture = $item.attr('picture');
					if ($item.attr('email'))
						data.email = $item.attr('email');						
				}
				// store data
				$item.data('portfolio', data);
			}
		}
//		alert(data.itemid);		

		$item.addClass('item');
		$item.css('position', 'relative');
		/*
		// bug, wenn auf relativ setzen
		if ($.browser.msie) {
			$item.css('height', '1%');
		}
		*/
		var header_content = '';

		if (data.itemid && !data.item && portfolioItems && portfolioItems[data.itemid]) {
			data.item = portfolioItems[data.itemid];
		}
		if (data.type == 'item' && data.itemid && data.item) {  
			var itemData = data.item;
			var ilink=itemData.link
			if (ilink!="")  ilink=$E.translate('link') + ': ' + ilink + '<br />';
			
			if(itemData.competences){
				$item.html(
					'<div id="id_holder" style="display:none;"></div>' +	
					'<div class="item_info" style="overflow: hidden;">' +
					'<div class="header">'+$E.translate('viewitem')+': '+itemData.name+'</div>' +
					'<div class="picture" style="float:right; position: relative; height: 100px; width: 100px;">' +
					'<img style="max-width: 100%; max-height: 100%;" src="'+M.cfg['wwwroot'] + '/blocks/exaport/item_thumb.php?item_id='+itemData.id+'">' +
					'</div>' +
					'<div class="body">'+$E.translate('type')+': '+$E.translate(itemData.type)+'<br />' +
					$E.translate('category')+': '+itemData.category+'<br />'+ ilink + 
					$E.translate('comments')+': '+itemData.comments+'<div class="exaport-item-intro"></div>' +
					'<script type="text/javascript" src="javascript/wz_tooltip.js"></script><a onmouseover="Tip(\''+itemData.competences+'\')" onmouseout="UnTip()"><img src="'+M.cfg['wwwroot']+'/pix/t/grades.png" class="iconsmall" alt="'+'competences'+'" /></a>'+
					'</div></div>'
				);
			}else{
				$item.html(
					'<div id="id_holder" style="display:none;"></div>' +	
					'<div class="item_info" style="overflow: hidden;">' +
					'<div class="header">'+$E.translate('viewitem')+': '+itemData.name+'</div>' +
					'<div class="picture" style="float:right; position: relative; height: 100px; width: 100px;">' +
					'<img style="max-width: 100%; max-height: 100%;" src="'+M.cfg['wwwroot'] + '/blocks/exaport/item_thumb.php?item_id='+itemData.id+'">' +
					'</div>' +
					'<div class="body">'+$E.translate('type')+': '+$E.translate(itemData.type)+'<br />' +
					$E.translate('category')+': '+itemData.category+'<br />' + ilink + 
					$E.translate('comments')+': '+itemData.comments+'<div class="exaport-item-intro"></div>' +
					'</div></div>'
				);
				// user html may be malformed, so savely inject it here
				$item.find('.exaport-item-intro').html(itemData.intro);
			}
		} else if (data.type == 'personal_information') {
			$item.html(
				'<div id="id_holder" style="display:none;"></div>' +
				'<div class="personal_info" style="overflow: hidden;">' +
				'<div class="header">' + $E.translate('personalinformation') +': '+'</div>' +
				'<div class="picture" style="float:right; position: relative;"></div>' +
				'<div class="name"></div>' +
				'<div class="email"></div>' +
				'<div class="body"></div>' +
				'</div>'
			);
			$item.find('div.header').append(data.block_title);
			if (data.picture != '' && data.picture != null)
				$item.find('div.picture').append('<img src="'+data.picture+'">');
			$item.find('div.name').append(data.firstname); $item.find('div.name').append(' '); $item.find('div.name').append(data.lastname);
			$item.find('div.email').append(data.email);
			$item.find('div.body').append(data.print_text);
		} else if (data.type == 'headline') {
			$item.html(
				'<div id="id_holder" style="display:none;"></div>' +
				'<div class="header">'+$E.translate('view_specialitem_headline')+'<div class="body"></div></div>'
			);
			$item.find('div.body').append(data.print_text);
		} else if (data.type == 'media') {
			$item.html(
				'<div id="id_holder" style="display:none;"></div>' +
				'<div class="header"></div><div class="body"></div>'
			);
			$item.find('div.header').append($E.translate('view_specialitem_media')+($.trim(data.block_title).length?': '+data.block_title:''));
			$item.find('div.body').append(data.contentmedia);
		} else if (data.type == 'badge') {
			if (typeof data.badge == 'undefined') {
				$item.html('loading');
			} else {
				var badge = data.badge;
				$item.html(
					'<div id="id_holder" style="display:none;"></div>' +	
					'<div class="item_info" style="overflow: hidden;">' +
					'<div class="header">'+$E.translate('view_specialitem_badge')+': '+badge.name+'</div>' +
					'<div class="picture" style="float:right; position: relative; height: 100px; width: 100px;">' +
					'<img style="max-width: 100%; max-height: 100%;" src="'+badge.imageUrl+'">' +
					'</div>'+
					'<div class="body">'+badge.description+'</div>'
	/*				+
					'</div>' +
					'<div class="body">'+$E.translate('type')+': '+$E.translate(itemData.type)+'<br />' +
					$E.translate('category')+': '+itemData.category+'<br />' + ilink + 
					$E.translate('comments')+': '+itemData.comments+'<br />' + itemData.intro +
					'</div>
					*/
				);
			}
		} else {		
			data.type = 'text';
			$item.html(
				'<div id="id_holder" style="display:none;"></div>' +
				'<div class="header"></div>' +
				'<div class="body"><p class="text" '+$E.translate('view_specialitem_text_defaulttext')+'"></p></div>'
			);
			$item.find('div.header').append($E.translate('view_specialitem_text')+($.trim(data.block_title).length?': '+data.block_title:''));
			$item.find('div.body').append(data.print_text);
		}

		// insert default texts
		$item.find(':input[default-text]').focus(function(){
			$(this).removeClass('default-text');
			if ($(this).attr('default-text') == $(this).val()) {
				$(this).val('');
			}
		}).blur(function(){
			if (!$.trim($(this).val())) {
				$(this).addClass('default-text');
				$(this).val($(this).attr('default-text'));
			}
		}).blur();
		$item.find('div#id_holder').append(data.id);		
		
		if (type == 'new') {
			if ((data.type != 'item') && (data.type != 'badge')) {
				// no edit button for items and badges
				$('<a class="edit" title="Edit"><span>Edit</span></a>').prependTo($item).click(editItemClick);
			}
		}
		else 
			$item.append('<a class="unsaved" title="This block was not saved"><span>Unsaved</span></a>'); 
		$('<a class="delete" title="'+$E.translate('delete')+'"><span>'+$E.translate('delete')+'</span></a>').prependTo($item).click(deleteItemClick);
		$item.find(':input').change(function(){
			$item.data('portfolio').text = $(this).val(); 			
		}); /**/
		updateBlockData();
		// unshared blocks
		if (data.unshared == 1) {
			$item.find('div.item_info').addClass('unshared_block');
			$item_header = $item.find('div.header').html();
			$item.find('div.header').html($item_header + '<span class="unshared_message">Unshared</span>');
		}

		return $item;
	}
	
	function deleteItemClick()
	{
		$(this).parents('.item').remove();
		resetElementStates();
		updateBlockData();
	}	
	
	function editItemClick()
	{
		var item_id = $(this).parent().find("#id_holder").html();

		lastclicked = $(this).parent();
		
		$.ajax({
			url: M.cfg['wwwroot'] + '/blocks/exaport/blocks.json.php',
			type: 'POST',
			data: {
				item_id: item_id,
				action: 'edit',
				'viewid': $('form :input[name=viewid]').val(),
				'text[itemid]': $('form :input[name=draft_itemid]').val(),
				sesskey: $('form :input[name=sesskey]').val()
			},
			success: function(res) {
				var data = JSON.parse(res);

				var popup = $E.popup({ bodyContent: data.html });

				// focus first element
				popup.$body.find('input:visible:first').focus();
				
			}
		});
	}		
	
	function resetElementStates()
	{
		$('.portfolioOptions li').removeClass('selected');
		$('.portfolioDesignBlocks > li').each(function(){
			$('.portfolioOptions li[itemid='+$(this).data('portfolio').itemid+']').addClass('selected');
		});
	}	


	var originalOptions = [];
	function resetElements()
	{
		// listenelemente zurücksetzen
		$('.portfolioOptions').each(function(i){
			$(this).html(originalOptions[i]);
		});
		resetElementStates();
	}

	$(function(){
		$(".portfolioOptions").each(function(i){
			originalOptions[i] = $(this).html();
		});
	});

	$(function(){
		//ExabisEportfolio.load_userlist('views_mod');
	});

	// sharing
	function update_sharing()
	{
		var share_text = '';
		var $form = $('#exaport-view-mod');

		if ($form.find(':input[name=externaccess]').is(':checked')) {
			share_text += $E.translate('externalaccess')+' ';
			$('#externaccess-settings').show();
		} else {
			$('#externaccess-settings').hide();
		}

		if ($form.find(':input[name=internaccess]').is(':checked')) {
			$('#internaccess-settings').show();
			$('#internaccess-groups').hide();
			if (share_text) {
				share_text += ' '+$E.translate('viewand')+' ';
			}
			share_text += $E.translate('internalaccess')+': ';
			
			if ($form.find(':input[name=shareall]:checked').val() == 1) {
				share_text += $E.translate('internalaccessall');
				$('#internaccess-users').hide();
				$('#internaccess-groups').hide();
			} else if ($form.find(':input[name=shareall]:checked').val() == 2) {
				share_text += $E.translate('internalaccessgroups');
				$('#internaccess-users').hide();
				$('#internaccess-groups').show();
				ExabisEportfolio.load_grouplist('views_mod');
			} else {
				share_text += $E.translate('internalaccessusers');
				$('#internaccess-groups').hide();
				$('#internaccess-users').show();
				ExabisEportfolio.load_userlist('views_mod');
			}
		} else {
			$('#internaccess-settings').hide();
		}
		
		if ($form.find(':input[name=sharedemails]').is(':checked')) {
			if (share_text) {
				share_text += ' '+$E.translate('viewand')+' ';
			};
			share_text += $E.translate('emailaccess')+' ';
			$('#emailaccess-settings').show();
		} else {
			$('#emailaccess-settings').hide();
		};

		if (!share_text) {
			share_text = $E.translate('view_sharing_noaccess');
		}
		$('#view-share-text').html(share_text);
	}
	
	$(function(){
		// changing the checkboxes / radiobuttons update the sharing text, visible options, etc.
		$('.view-sharing input[type=checkbox], .view-sharing input[type=radio]').click(update_sharing);
		update_sharing();
	});

})(jQueryExaport);
