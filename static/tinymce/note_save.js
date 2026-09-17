(function() {
	tinymce.create('tinymce.pluginss.mcv_note_save', {
		
		init : function(ed, murl) {
			pluginurl = murl.replace("/tinymce",""); 
			ed.addCommand('mcv_note_save', function() {
				var divId = getQueryString('did');
				var pid   = getQueryString('pid');
				var nonce = getQueryString('nonce');
				var cont  = window.tinyMCE.activeEditor.getContent();
				jQuery.post(ajaxurl,{"action":"mcv_note_save","content":cont,"nonce":nonce,"pid":pid,"did":divId}, function(data){
					if(data && data.success){
						parent.layer.msg('saved');
					}
				},"json");
			});

			ed.addButton('mcv_note_save', {
				title : 'Save Note',
				cmd : 'mcv_note_save',
				image : pluginurl + '/img/save.png',
			});
			
			ed.onNodeChange.add(function(ed, cm, n) {
				cm.setActive('mcv_note_save', n.nodeName == 'IMG');
			});
		},
		createControl : function(n, cm) {
			return null;
		},
		getInfo : function() {
			return {
					longname  : 'Save Note',
					author 	  : 'mine27',
					authorurl : 'https://www.mine27.cn/',
					infourl   : 'https://www.mine27.cn/',
					version   : "1.0"
			};
		}
	});

	// Register plugin
	tinymce.PluginManager.add('mcv_note_save', tinymce.pluginss.mcv_note_save);
})();


