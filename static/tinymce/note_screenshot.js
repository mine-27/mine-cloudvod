(function() {
	tinymce.create('tinymce.pluginss.mcv_note_screenshot', {
		
		init : function(ed, murl) {
		// Register the command so that it can be invoked by using tinyMCE.activeEditor.execCommand('mceExample');
			pluginurl = murl.replace("/tinymce",""); 
			ed.addCommand('mcv_note_screenshot', function() {
				var divId = getQueryString('did');
				var nonce = getQueryString('nonce');
				var pid = getQueryString('pid');
				var player = jQuery("#"+divId+" video", window.parent.document);
				player.attr("crossOrigin", "anonymous");
				var canvas = parent.document.createElement("canvas");
				canvas.width = player.width();
				canvas.height = player.height();
				canvas.getContext("2d").drawImage(player.get(0), 0, 0, canvas.width, canvas.height);
				var dataURL = canvas.toDataURL("image/png");
				jQuery.post(ajaxurl,{"action":"mcv_note_screenshot","img":dataURL,"nonce":nonce,"pid":pid,"did":divId}, function(data){
					if(data && data.src){
						var img = '<img src="'+data.src+'" />';
						window.tinyMCE.activeEditor.insertContent(img);
					}
				},"json");
				
			});

			// Register example button
			ed.addButton('mcv_note_screenshot', {
				title : 'Insert Screenshot',
				cmd : 'mcv_note_screenshot',
				image : pluginurl + '/img/screenshot.png',
				class: 'mcv_note_screenshot'
			});
			
			// Add a node change handler, selects the button in the UI when a image is selected
			ed.onNodeChange.add(function(ed, cm, n) {
				cm.setActive('mcv_note_screenshot', n.nodeName == 'IMG');
			});
		},
		createControl : function(n, cm) {
			return null;
		},
		getInfo : function() {
			return {
					longname  : 'Insert Screenshot',
					author 	  : 'mine27',
					authorurl : 'https://www.mine27.cn/',
					infourl   : 'https://www.mine27.cn/',
					version   : "2.3"
			};
		}
	});

	// Register plugin
	tinymce.PluginManager.add('mcv_note_screenshot', tinymce.pluginss.mcv_note_screenshot);
})();

function screenShot(id){
	var player = document.getElementById(id);   //获取video的Dom节点
	player.setAttribute("crossOrigin", "anonymous");  //添加srossOrigin属性，解决跨域问题
	var canvas = document.createElement("canvas");
	var img = document.createElement("img");
	canvas.width = player.clientWidth;
	canvas.height = player.clientHeight;
	canvas.getContext("2d").drawImage(player, 0, 0, canvas.width, canvas.height);//截
	var dataURL = canvas.toDataURL("image/png");  //将图片转成base64格式
	img.src = dataURL;
	img.width = player.clientWidth-200;   //控制截出来的图片宽的大小
	img.height = player.clientHeight-200; //控制截出来的图片高的大小
	img.style.border="1px solid #333333"   //控制截出来的图片边框的样式
	document.getElementById("cutImage").appendChild(img);   //显示在页面中
	this.downFile(dataURL, "图片.jpg");   //下载截图
}
function getQueryString(name) {
	var reg = new RegExp("(^|&)" + name + "=([^&]*)(&|$)", "i");
	var r = window.location.search.substr(1).match(reg);
	if (r != null) return unescape(r[2]); return null;
} 