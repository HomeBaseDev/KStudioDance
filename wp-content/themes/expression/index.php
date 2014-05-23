<?php get_header(); ?>
<?php get_sidebar(); ?>

    <div id="main">

	<div id="content" class="narrowcolumn" role="main">

	<?php if (have_posts()) : ?>

		<?php while (have_posts()) : the_post(); ?>

			<!--post title-->
	<h3 id="post-<?php the_ID(); ?>"><a href="<?php the_permalink(); ?>" title="<?php the_title(); ?>"><?php the_title(); ?></a></h3>

    <!--post meta info-->
	<div class="meta-top">

    </div>
			
	<!--post text with the read more link-->
	<?php the_content(); ?>
	
	<!--for paginate posts-->
	<?php link_pages('<p><strong>Pages:</strong> ', '</p>', 'number'); ?>

	<!--post meta info-->
	<div class="meta-bottom clearfix">

    	</div>
      <hr />


		<?php endwhile; ?>

		<div class="navigation">
			<div class="alignleft"><?php next_posts_link('&laquo; Older Entries') ?></div>
			<div class="alignright"><?php previous_posts_link('Newer Entries &raquo;') ?></div>
		</div>

	<?php else : ?>

		<h2 class="center">Not Found</h2>
		<p class="center">Sorry, but you are looking for something that isn't here.</p>
		<?php get_search_form(); ?>

	<?php endif; ?>

	</div>
    </div>
    
    <div id="right_sidebar">
    	
        <div id="view_photos">
  			<h2><a href="/?cat=3">Check Out<br />&nbsp;&nbsp;  Our Photos</a></h2>
        </div>
        
        <!-- Stretchable news div -->
        <div id="news_top"></div>
        <div id="news_mid">
        	
            <h2>News & Updates</h2>
			<ul>
			<?php wp_get_archives('title_li=&type=postbypost&limit=10'); ?>

                	<li><strong><a href="?page_id=185">Click here</a></strong> to sign up for our newsletter</li>
			<li><div id="soc_med_icons">
                		<a href="http://www.facebook.com/home.php?#!/group.php?gid=101662033215507&ref=ts"><img src="/wp-content/themes/expression/images/soc_icons/facebook.png" /></a>
                    	<a href="http://www.flickr.com/photos/kstudiodance/"><img src="/wp-content/themes/expression/images/soc_icons/flickr.png" /></a>
                    	<a href="http://twitter.com/kstudiodance"><img src="/wp-content/themes/expression/images/soc_icons/twitter.png" /></a>
                   		<a href="http://www.yelp.com/biz/k-studio-columbus"> <img src="/wp-content/themes/expression/images/soc_icons/yelp.png" /></a>
                    	<a href="http://www.youtube.com/kstudiodance"><img src="/wp-content/themes/expression/images/soc_icons/youtube.png" /></a>
                	</div> </li>
            </ul>
        </div>
        <div id="news_bot"></div>
        <!-- /Stretchable news div -->
        
    </div>
    
    <div id="clear"></div>
</div> <!-- /wrapper -->

<?php get_footer(); ?>
