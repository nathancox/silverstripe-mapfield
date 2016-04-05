(function($) {

	var gmapsAPILoaded = false;

	$.entwine('ss', function($) {
		$('div.mapfield').entwine({
			onmatch: function() {
				if(gmapsAPILoaded) {
					this.initMap();
				}
			},

			initMap: function() {

				var centerLat = this.getFieldValue('CenterLat');
				var centerLng = this.getFieldValue('CenterLng');
				var zoom = this.getFieldValue('Zoom') * 1;
				var mapType = this.getFieldValue('MapType');

				var center = new google.maps.LatLng(centerLat, centerLng);

				var options = {
					streetViewControl: false,
					mapTypeControl: false,
					zoom: zoom,
					center: center,
					mapTypeId: mapType
				};

				var mapElement = this.find('.mapfield-map');

				var map = new google.maps.Map(mapElement[0], options);


				this.data('map', map);
				var $field = this;

				var objectID = this.getFieldValue('ID');
				var markerLat = this.getFieldValue('MarkerLat');
				var markerLng = this.getFieldValue('MarkerLng');
				if (objectID == 0) {
					markerLat = 0;
					markerLng = 0;
				}

				var $marker = this.makeMarker(markerLat, markerLng);

				google.maps.event.addListener(map, 'click', function(e) {
					$marker.setPosition(e.latLng);
					$field.setFieldValue('MarkerLat', e.latLng.lat());
					$field.setFieldValue('MarkerLng', e.latLng.lng());
					$field.reverseGeocode();
				});
				google.maps.event.addListener(map, 'center_changed', function() {
					var center = map.getCenter();

					$field.setFieldValue('CenterLat', center.lat());
					$field.setFieldValue('CenterLng', center.lng());
				});
				google.maps.event.addListener(map, 'zoom_changed', function() {
					$field.setFieldValue('Zoom', map.getZoom());
				});
			},

			makeMarker: function(lat, lng) {
				if (!this.data('marker')) {
					$field = this;
					var marker = new google.maps.Marker({
						position: new google.maps.LatLng(lat, lng),
						map: this.data('map'),
						title: "Position",
						draggable: true
					});
					google.maps.event.addListener(marker, 'dragend', function(e) {
						marker.setPosition(e.latLng);
						$field.setFieldValue('MarkerLat', e.latLng.lat());
						$field.setFieldValue('MarkerLng', e.latLng.lng());
						$field.reverseGeocode();
					});
					this.data('marker', marker);
				}
				return this.data('marker');
			},



			centerOnMarker: function() {
				var center = this.data('marker').getPosition();
				this.data('map').panTo(center);
			},

			reverseGeocode: function() {
				if (this.data('store-address') == 1) {
					var lat = this.getFieldValue('MarkerLat');
					var lng = this.getFieldValue('MarkerLng');
					var $field = this;
					var data = $.ajax({
					  dataType: "json",
					  url: 'http://maps.googleapis.com/maps/api/geocode/json?latlng='+lat+','+lng+'&sensor=false',
					  data: {},
					  success: function(data) {
					  	if (data.status == 'OK') {
					  		var address = data.results[0].formatted_address;
					  	//	$field.find('.mapfield-address').val(address);
					  		$field.setFieldValue('Search', address);
					  		$field.setFieldValue('Address', address);
					  	}

					  }
					});
				}

			},

			search: function(text) {
				var geocoder;
				if (!gmapsAPILoaded) {
					return;
				}
				if(text != '') {
					geocoder = new google.maps.Geocoder();
					var $field = this;
					geocoder.geocode({ address: text }, function(result, status) {
						if(status !== google.maps.GeocoderStatus.OK) {
							console.warn('Geocoding search failed');
							return;
						}
						$field.makeMarker();
						var marker = $field.data('marker');
						var newLocation = result[0].geometry.location;
						$field.setFieldValue('Address', text);

						if (marker.position.lat() != newLocation.lat() || marker.position.lng() != newLocation.lng()) {
							marker.setPosition(newLocation);
							$field.setFieldValue('MarkerLat', newLocation.lat());
							$field.setFieldValue('MarkerLng', newLocation.lng());
							if ($field.data('map').getBounds().contains(marker.getPosition())) {

							} else {
								$field.centerOnMarker();
							}

						}

					});
				}
			},

			getFieldValue: function(fieldName) {
				var value = $(this).find('[name="'+this.attr('name')+'['+fieldName+']"]').val();
				return value;
			},

			setFieldValue: function(fieldName, value) {
				$(this).find('[name="'+this.attr('name')+'['+fieldName+']"]').val(value);
				$('.cms-edit-form').addClass('changed');
			}
		});

		$('div.mapfield .mapfield-search-button').entwine({
			onclick: function(e) {
				this.getMapField().search(this.getAddressField().val());
			//	this.focusout();
				this.blur();
				return false;
			},

			getMapField: function() {
				return this.closest('.mapfield');
			},

			getAddressField: function() {
				return this.parent().find('.mapfield-address');
			},

		});

		$('div.mapfield .mapfield-address').entwine({

			onfocusout: function(e) {
			//	this.getMapField().search(this.val());
			},

			onkeydown: function(e) {
				if(e.which == 13) {
					this.getMapField().search(this.val());
					return false;
				}
			},

			getMapField: function() {
				return this.closest('.mapfield');
			}


		});


	});

	window.mapFieldInit = function() {
		gmapsAPILoaded = true;
		$().ready(function() {
			var mapFields = $('div.mapfield');
			mapFields.each(function(i, el) {
				$(el).entwine('ss').initMap();
			});

		});

	}

}(jQuery));
