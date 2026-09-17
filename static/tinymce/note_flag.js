(function() {
	tinymce.create('tinymce.pluginss.mcv_note_flag', {
		
		init : function(ed, murl) {
			pluginurl = murl.replace("/tinymce",""); 
			var mcvplayer;
			var divId = getQueryString('did');
			var plyr = getQueryString('plyr');
			if(plyr && plyr == 'dplayer'){
				if(divId == 'post-style-5-player')
					mcvplayer = eval('parent.window.mcv_b2_player');
				else{
					mcvplayer = eval('parent.window.dplayer_'+divId.replace('-','_'));
				}
			}
			else{
				if(divId == 'post-style-5-player')
					mcvplayer = eval('parent.window.mcv_b2_player');
				else{
					mcvplayer = eval('parent.window.aliplayer_'+divId.replace('-','_'));
				}
			}
			ed.addCommand('mcv_note_flag', function() {
				var cur = 0;
				if(plyr == 'dplayer'){
					cur = mcvplayer.video.currentTime;
				}
				else{
					cur = mcvplayer.getCurrentTime();
				}
				cur = parseInt(cur);
				var sf = (parseInt(cur/60)+'').padStart(2,'0') + ":" + (cur%60+'').padStart(2,'0');
				var title = ptitle + ' - ' + sf;
				var flag = '<div class="time-tag-item" data-time="'+cur+'"><i class="iconicon_flag_s"></i><span class="time-tag-item__text" title="'+title+'">'+title+'</span></div><br />';
				window.tinyMCE.activeEditor.insertContent(flag);
				jQuery(".time-tag-item",jQuery("#mcv_note_editor_ifr").contents()).on('click',function(){
					var sec = jQuery(this).data('time');
					mcvplayer.seek(sec);
					mcvplayer.play();
				});
			});
			jQuery(function(){
				jQuery(".time-tag-item",jQuery("#mcv_note_editor_ifr").contents()).on('click',function(){
					var sec = jQuery(this).data('time');
					mcvplayer.seek(sec);
					mcvplayer.play();
				});
			});

			ed.addButton('mcv_note_flag', {
				title : 'Insert Video Flag',
				cmd : 'mcv_note_flag',
				image : pluginurl + '/img/flag.png',
			});
			
			ed.onNodeChange.add(function(ed, cm, n) {
				cm.setActive('mcv_note_flag', n.nodeName == 'IMG');
			});
		},
		createControl : function(n, cm) {
			return null;
		},
		getInfo : function() {
			return {
					longname  : 'Insert Video Flag',
					author 	  : 'mine27',
					authorurl : 'https://www.mine27.cn/',
					infourl   : 'https://www.mine27.cn/',
					version   : "1.0"
			};
		}
	});

	// Register plugin
	tinymce.PluginManager.add('mcv_note_flag', tinymce.pluginss.mcv_note_flag);
})();


