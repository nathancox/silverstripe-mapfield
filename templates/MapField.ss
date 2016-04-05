<div class="mapfield $extraClass" $AttributesHTML data-store-address='$storeAddress'>
	<% loop ChildFields %>

	<% if ID = "Map-Search" %>
	<div class='mapfield-search-container'>
		$Field
		<button class='mapfield-search-button'>Search</button>
	</div>
	<% else %>
	$Field
	<% end_if %>

	<% end_loop %>
	<div class="mapfield-map" style='$MapCSS'></div>
</div>



