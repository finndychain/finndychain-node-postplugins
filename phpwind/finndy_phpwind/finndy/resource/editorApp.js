;(function ( $, window, undefined ) {
	var appName = 'finndy';


	WindEditor.initOpenApp[appName](function(item, root_path){
		var _self = this,
			root_path = root_path + appName + '/';
		var editorDoc = _self.editorDoc = _self.iframe[0].contentWindow.document,
			plugin_icon = $('<div class="wind_icon" data-control="'+ appName +'"><span class="'+ appName +'" title="finndy" style="background:url('+ root_path +'images/icon.png) no-repeat center center;"></span></div>').appendTo(  _self.pluginsContainer  );
			plugin_icon.on('click',function() {
				if($(this).hasClass('disabled')) {
					return;
				}
				if(!$.contains(document.body,dialog[0]) ) {
					dialog.appendTo( document.body );
				}
				_self.showDialog(dialog);
			});

	});


})( jQuery, window);