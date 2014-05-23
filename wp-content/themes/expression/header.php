<?php
/**
 * @package WordPress
 * @subpackage Expression
 */
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" <?php language_attributes(); ?>>

<head profile="http://gmpg.org/xfn/11">
<meta http-equiv="Content-Type" content="<?php bloginfo('html_type'); ?>; charset=<?php bloginfo('charset'); ?>" />

<title><?php wp_title('&laquo;', true, 'right'); ?> <?php bloginfo('name'); ?></title>

<link rel="stylesheet" href="<?php bloginfo('stylesheet_url'); ?>" type="text/css" media="screen" />
<link rel="pingback" href="<?php bloginfo('pingback_url'); ?>" />

<?php if ( is_singular() ) wp_enqueue_script( 'comment-reply' ); ?>

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
    var contentString = 'K Studio<br />1222 Kenny Centre Columbus Ohio 43220<br />614-313-3773<br /><a href="mailto:kelly@kstudiodance.com">kelly@kstudiodance.com</a>'
        
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
    var address = '1222 Kenny Centre Columbus Ohio 43220';
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

<?php wp_head(); ?>
</head>

<body onload="codeAddress()">


<div id="header_big">
	<div id="header">
		<a href="/index.php"><img src="<?php bloginfo('template_directory'); ?>/images/logo.png" alt="Kstudio" border="0" /></a>
	</div>
</div>
<div id="header_shadow"></div>

<div id="wrapper">

    <div id="main_nav">
    	<ul>
        	<li><a href="/index.php">Home</a></li>
            <li><a href="/?page_id=43">Contact</a></li>
        </ul>
    </div>
    <div id="clear"></div>
    