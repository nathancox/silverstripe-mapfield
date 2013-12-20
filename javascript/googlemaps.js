$().ready(function() {
	if (typeof google !== 'undefined') {
		google.maps.event.addDomListener(window, 'load', googleMaps.init);
	}
});

var googleMaps = {

	init: function() {
		var maps = $('.google-map');
		if (maps.length) {
			var i;
			for(i = 0; i < maps.length; i++) {
				googleMaps.attach(maps[i]);
			}
		}
	},

	attach: function(el) {
		var mapEl = $(el);
		var settings = mapEl.data('map-settings');

		var lat = mapEl.data('marker-lat');
		var lng = mapEl.data('marker-lng');

	  var myLatlng = new google.maps.LatLng(settings.CenterLat, settings.CenterLng);

	  var mapOptions = {
			streetViewControl: false,
			mapTypeControl: false,
	    mapTypeId: settings.MapType,
	    zoom: settings.Zoom*1,
	    center: new google.maps.LatLng(settings.CenterLat, settings.CenterLng)
	  }
	  
	  var map = new google.maps.Map(el, mapOptions);
	  
	  var marker = new google.maps.Marker({
      position: new google.maps.LatLng(settings.MarkerLat, settings.MarkerLng),
      map: map,
      title: ''
	  });

	}
}