<?php
/**
 * @package WordPress
 * @subpackage Classic_Theme
 */
?>
<!-- begin footer -->
</div>

<?php get_sidebar(); ?>

<p class="credit"><!--<?php echo get_num_queries(); ?> queries. <?php timer_stop(1); ?> seconds. --> <cite><?php echo sprintf(__("Powered by <a href='http://wordpress.org/' title='%s'><strong>WordPress</strong></a>"), __("Powered by WordPress, state-of-the-art semantic personal publishing platform.")); ?></cite></p>

</div>

<?php wp_footer(); ?>
<script type="text/javascript">

  var _gaq = _gaq || [];
  _gaq.push(['_setAccount', 'UA-8528059-4']);
  _gaq.push(['_trackPageview']);

  (function() {
    var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
    ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
  })();

</script>
</body>
</html>