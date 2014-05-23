<?php 
/**
 * @package Easy Google Analytics for Wordpress
 * @author Shaon
 * @version 1.1
 */
/*
Plugin Name: Easy Google Analytics for Wordpress
Plugin URI: http://www.intelisoftbd.com/open-source-projects/google-analytics.html
Description: GoogleAnalytics placed inside post
Author: Shaon
Version: 1.1
Author URI: http://www.intelisoftbd.com
*/
//{gmap|address|width|height}
function GoogleAnalytics(){
    
    echo "
    <script type=\"text/javascript\">

  var _gaq = _gaq || [];
  _gaq.push(['_setAccount', '".get_option("_ga_acc_id")."']);
  _gaq.push(['_trackPageview']);

  (function() {
    var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
    ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
    (document.getElementsByTagName('head')[0] || document.getElementsByTagName('body')[0]).appendChild(ga);
  })();
</script>
    ";
    
}

function _ga_AdminSettings(){
    include("ga_admin_set.php");
}
        
        
function gamenu(){   
    add_options_page('Google Analytics', 'Google Analytics', 'administrator', 'easy-ga', '_ga_AdminSettings');    
}

if(is_admin()){
    add_Action("admin_menu","gamenu");
}
        
add_action('wp_footer',"GoogleAnalytics");
 