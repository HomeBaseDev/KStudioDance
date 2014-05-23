<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" <?php language_attributes(); ?>>
<head profile="http://gmpg.org/xfn/11">
<meta http-equiv="Content-Type" content="<?php bloginfo('html_type'); ?>; charset=<?php bloginfo('charset'); ?>" />
<meta http-equiv="X-UA-Compatible" content="IE=EmulateIE7" />
<title><?php if (is_home () ) { bloginfo('name'); } elseif ( is_category() ) { single_cat_title(); echo ' - ' ; bloginfo('name'); }
 elseif (is_single() ) { single_post_title(); }
 elseif (is_page() ) { bloginfo('name'); echo ': '; single_post_title(); }
 else { wp_title('',true); } ?></title>
<script type="text/javascript" src="<?php bloginfo('template_url'); ?>/script.js"></script>
<link rel="stylesheet" href="<?php bloginfo('stylesheet_url'); ?>" type="text/css" media="screen" />
<!--[if IE 6]><link rel="stylesheet" href="<?php bloginfo('template_url'); ?>/style.ie6.css" type="text/css" media="screen" /><![endif]-->
<link rel="alternate" type="application/rss+xml" title="<?php printf(__('%s RSS Feed', 'kubrick'), get_bloginfo('name')); ?>" href="<?php bloginfo('rss2_url'); ?>" />
<link rel="alternate" type="application/atom+xml" title="<?php printf(__('%s Atom Feed', 'kubrick'), get_bloginfo('name')); ?>" href="<?php bloginfo('atom_url'); ?>" /> 
<link rel="stylesheet" href="<?php bloginfo('template_url'); ?>/dd-formmailer.css" type="text/css" media="screen" />
<link rel="pingback" href="<?php bloginfo('pingback_url'); ?>" />
<title>Google Maps JavaScript API v3 Example: Geocoding Simple</title>
<script type="text/javascript" src="http://maps.google.com/maps/api/js?sensor=false"></script>
<script type="text/javascript">
  var directionDisplay;
  var directionsService = new google.maps.DirectionsService();
  var map;

  function initialize() {
    directionsDisplay = new google.maps.DirectionsRenderer();
    var home = new google.maps.LatLng(40.063516, -83.119499);
    var myOptions = {
      zoom:10,
      mapTypeId: google.maps.MapTypeId.ROADMAP,
      center: home
    }
    map = new google.maps.Map(document.getElementById("map_canvas"), myOptions);
    directionsDisplay.setMap(map);
    directionsDisplay.setPanel(document.getElementById("directionsPanel"));
    var contentString = 'K Studio<br />3799 Aqueduct Court Columbus Ohio 43221<br />614-313-3773<br /><a href="mailto:kelly@kstudiodance.com">kelly@kstudiodance.com</a>'
        
    var infowindow = new google.maps.InfoWindow({
        content: contentString
    });
    var marker = new google.maps.Marker({
      position: home , 
      map: map, 
      title:'K Studio'
    });   
    google.maps.event.addListener(marker, 'click', function() {
      infowindow.open(map,marker);
    });
  }
  
  function calcRoute() {
    var start = document.getElementById("start").value;
    var end = document.getElementById("end").value;
    var request = {
        origin:start, 
        destination:end,
        travelMode: google.maps.DirectionsTravelMode.DRIVING
    };
    directionsService.route(request, function(response, status) {
      if (status == google.maps.DirectionsStatus.OK) {
        directionsDisplay.setDirections(response);
      }
    });
  }  

  function codeAddress() {
   initialize(); 
    var address = '3799 Aqueduct Court Columbus Ohio 43221';
    if (geocoder) {
      geocoder.geocode( { 'address': address}, function(results, status) {
        if (status == google.maps.GeocoderStatus.OK) {
          map.setCenter(results[0].geometry.location);
          var marker = new google.maps.Marker({
              map: map, 
              position: results[0].geometry.location
          });
        } else {
          alert("Geocode was not successful for the following reason: " + status);
        }
      });
    }
  }
</script>
</head>
<body onload="codeAddress()">
    <div class="PageBackgroundGradient"></div>
<div class="Main">
<div class="Sheet">
    <div class="Sheet-tl"></div>
    <div class="Sheet-tr"><div></div></div>
    <div class="Sheet-bl"><div></div></div>
    <div class="Sheet-br"><div></div></div>
    <div class="Sheet-tc"><div></div></div>
    <div class="Sheet-bc"><div></div></div>
    <div class="Sheet-cl"><div></div></div>
    <div class="Sheet-cr"><div></div></div>
    <div class="Sheet-cc"></div>
    <div class="Sheet-body">
<div class="Header">
    <div class="Header-png"></div>
    <div class="Header-jpeg"></div>

<div class="logo">


&nbsp;

</div>

</div>
<div class="nav">
    <ul class="artmenu">
        <?php art_menu_items(); ?>
    </ul>
    <div class="l">
    </div>
    <div class="r">
        <div>
        </div>
    </div>
</div>

