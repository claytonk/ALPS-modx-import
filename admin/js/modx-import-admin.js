(function( $ ) {
	'use strict';

	/**
	 * All of the code for your admin-facing JavaScript source
	 * should reside in this file.
	 *
	 * Note: It has been assumed you will write jQuery code here, so the
	 * $ function reference has been prepared for usage within the scope
	 * of this function.
	 *
	 * This enables you to define handlers, for when the DOM is ready:
	 *
	 * $(function() {
	 *
	 * });
	 *
	 * When the window is loaded:
	 *
	 * $( window ).load(function() {
	 *
	 * });
	 *
	 * ...and/or other possibilities.
	 *
	 * Ideally, it is not considered best practise to attach more than a
	 * single DOM-ready or window-load handler for a particular page.
	 * Although scripts in the WordPress core, Plugins and Themes may be
	 * practising this, we should strive to set a better example in our own work.
	 */


	function monitor(total,completed) {
		console.log('monitor running');
		if (!completed){
			$.get( "/wp-content/plugins/modx-import/admin/logs/completed.csv", function( data ) {
				if (data){
					var completed = $.csv.toArrays(data);
					$('#bar').css('width',completed.length/total*100+'%');
					$('#count-current').text(completed.length);
				}else{
					$('#count-current').text(0);
				}
			});
		}else{
			var status = completed/total*100;
			$('#bar').css('width',status+'%');
			$('#count-current').text(completed);
		}
	}

	function phrase(total){
		var phrase = total+' resources';
		if (total == 1){
			phrase = total+' resource'
		}
		$('#count, #count-total').text(phrase);
	}

	function running(){
		$('#message').text('Import in progress...');
		$('p.button').hide();
	}

	function finished(message){
		$('#message').text(message);
		$('#count-current').text('Import');
	}

	$(function() {

		// if monitor is ready process import
		if ($('#modx-import-monitor').length){

			let modxComplete = {};
			let modxIncomplete = {};
			let toRun = [];
			let total = 0;
			let csvReady = false;

			var csvPlugin = $.getScript( "/wp-content/plugins/modx-import/admin/js/jquery.csv.min.js");
			var csvComplete = $.get( "/wp-content/plugins/modx-import/admin/logs/completed.csv");
			var csvIncomplete = $.get( "/wp-content/plugins/modx-import/admin/logs/incomplete.csv");

			$.when(csvComplete,csvIncomplete,csvPlugin).done(function(csvComplete,csvIncomplete){
				let logComplete = $.csv.toArrays(csvComplete[0]);
				logComplete.forEach(function(log){
					modxComplete[log[0]] = {'postid':log[1],'menuid':log[2]}
				});
				let logIncomplete = $.csv.toArrays(csvIncomplete[0],{delimiter:"'"});
				logIncomplete.forEach(function(log){
					modxIncomplete[log[0]] = JSON.parse(log[1]);
				});
				csvReady = true;
			});

			$.get( "/wp-content/plugins/modx-import/admin/logs/import.json").done(function(data){
				var ids = Object.keys(data);
				total = ids.length;
				monitor(total,false);
				phrase(total);
				ids.forEach(function(id){
					if (id in modxComplete == false){
						toRun[id] = true;
					}
				});
				$('#sequential').on('click',function(){
					sequential(toRun,data,total);
				})
			})

			function sequential(ids,data,total){
				running();
				let runCount = 0;
				let postCount = 0;
				ids.forEach(function(run,id){
					if (run && id in modxComplete == false){
						runCount++;
						let processed = {"complete":modxComplete,"incomplete":modxIncomplete};
						var post = { "process-single":true, "constant":constant, "importID": id, "post": data[id], "processed": processed };
						console.log(post);
						$.post( "/wp-content/plugins/modx-import/admin/partials/process.php", post).done(function( result ) {
							postCount++;
							result = JSON.parse(result);
							console.log(result);
							if (result.status){
								ids[id] = false;
								if (id in modxComplete == false){
									modxComplete[id] = result.result;
								}
								if (id in modxIncomplete){
									delete modxIncomplete[id];
								}
								monitor(total,Object.keys(modxComplete).length);
							}
							console.log(runCount+':'+postCount);
							if (postCount == runCount){
								sequential(ids,data,total);
							}
						});
					}
				});
				if (runCount == 0){
					$.post( "/wp-content/plugins/modx-import/admin/partials/process.php", {"constant":constant,"updateLogs":true,"processed":{"complete":modxComplete,"incomplete":modxIncomplete}}).done(function( result ) {
						//result = JSON.parse(result);
						console.log(result);
					});
				}
				if (runCount == 0){
					finished('Import completed successfully.');
				}
			}

			$('#batch').on('click',function(){
				running();
				var monitorTimer = setInterval(function(){monitor(total,false);},2000);
				$.post( "/wp-content/plugins/modx-import/admin/partials/process.php", { "process-batch":true, "constant":constant }).done(function( result ) {
					result = JSON.parse(result);
					console.log(result);
					if (result.status){
						clearInterval(monitorTimer);
						monitor(total, false);
						finished('Import completed successfully.');
					}else{
						finished('The import failed. Please contact your plugin administrator for assistance.');
					}
				});
			})

		}


		// import form dynamics
		if ($('#form-import').length){

			function getDrawer(element){
				var entry = element.parents('.entry');
				var drawer = entry.next('.drawer');
				return drawer;
			}

			function select(boxes,selects,value){
				boxes.each(function(i,b){
					b.checked = value;
				})
				if (value == 0){
					value = '';
				}
				if (selects.length){
					selects.each(function(){
						$(this).val(value);
						console.log($(this).val());
					})
				}
				count();
			}

			var form = document.getElementById('form-import');
			var total;

			function count(){
				total = 0;
				var post = new FormData(form);
				for(var pair of post.entries()) {
				   if(pair[0].match(/resources\[[0-9]+\]\[type\]/)){
					   total++
				   }
				}
				phrase(total);
			}

			count();

			$('#form-import').on('click',function(){
				count();
			})
			$('select').on('change',function(){
				count();
			})

			$('.warning .toggle').on('click',function(){
				var drawer = $(this).next('.text');
				if (drawer.hasClass('show')){
					drawer.removeClass('show').slideUp(300);
					$(this).html('Show Warnings <i>&plus;</i>');
				}else{
					drawer.addClass('show').slideDown(300);
					$(this).html('Hide Warnings <i>&minus;</i>');
				}
			})

			$('.toggles-outer').on('click','p',function(){
				var selects = $('.entry select');
				if ($(this).hasClass('remove')){
					var boxes = $('.entry input');
					select(boxes,selects,0);
				}
				if ($(this).hasClass('select')){
					var boxes = $('.options1 .import,.options1 .asPost,.options2 .all,.options2 .asPost');
					select(boxes,selects,1);
				}
			})

			$('.children.toggle').on('click',function(){
				var toggle = $(this);
				var icon = $(this).children('i');
				var text = $(this).children('span');
				var drawer = getDrawer($(this));
				if (drawer.hasClass('open')){
					drawer.removeClass('open');
					icon.html('&plus;');
					text.html('Show/select children')
				}else{
					drawer.addClass('open');
					icon.html('&minus;');
					text.html('Hide children');
				}
			})

			$('select.children').on('change',function(){
				var value = $(this).val();
				var drawer = getDrawer($(this));
				var parent = $(this).parents('.section');
				if (value == ''){
					value = 0;
					var children = drawer.find('.import,.asPost,.asPage,.comments input');
					var siblings = parent.find('.asPost,.asPage,.comments input');
					select(siblings,[],0);
				}
				if (value > 0){
					var siblings = parent.find('.asPost');
					select(siblings,[],1);
				}
				if (value == 1){
					var children = drawer.find('.import,.asPost');
				}
				if (value == 2){
					value == 1;
					var children = drawer.find('li:not(.folder) .import,li:not(.folder) .asPost');
				}
				select(children, [], value);
			})

			$('input.children:radio').on('click',function(){
				var drawer = getDrawer($(this));
				if ($(this).hasClass('asPage')){
					var boxes = drawer.find('input.asPage, .import');
				}else{
					var boxes = drawer.find('input.asPost, .import');
				}
				select(boxes,[],1);
			})

			$('.options1 .import').on('click',function(){
				if ($(this).prop('checked')){
					if (document.import['resources['+$(this).val()+'][type]'].value == ''){
						document.import['resources['+$(this).val()+'][type]'].value = 'post';
					}
				}else{
					var radio = $(this).parents('.options1').find('input[type=radio]');
					radio.each(function(){
						$(this).prop('checked',false);
					})
					var drawer = getDrawer($(this));
					var boxes = drawer.find('input[type=checkbox], input[type=radio]');
					select(boxes,[],0);
				}

			})

			$('.children.comments').on('click',function(){
				var drawer = getDrawer($(this));
				var boxes = drawer.find('.comments input');
				select(boxes, [], $(this).prop('checked'));
			})

			$('.drawer .import').on('click',function(){
				if ($(this).prop('checked')){
					var parents = $(this).parents('.folder');
					parents.each(function(p){
						var entry = $(this).children('.entry');
						var boxes = entry.find('.import, .asPost');
						select(boxes,[],1);
					})
				}
			})

		}


	});

})( jQuery );
