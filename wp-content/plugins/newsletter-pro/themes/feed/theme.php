<?php
$posts = new WP_Query();
$posts->query(array('showposts'=>10, 'post_status'=>'publish'));
?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html><head><title></title></head>
<body style="font-family: 'Trebuchet MS'; font-size: 14px;">

<h2 style="color: #666;"><?php echo get_option('blogname'); ?></h2>

<table width="550" style="font-family: 'Trebuchet MS'; font-size: 14px; text-align: justify">
<?php
while ($posts->have_posts())
{
    $posts->the_post();
    if (newsletter_feed_is_old()) break;
    $image = nt_post_image(get_the_ID());
?>
<tr><td>
<p style="font-size: 18px; margin-top: 10px"><a style="color: #3333CC; text-decoration: none" href="<?php echo get_permalink(); ?>"><?php the_title(); ?></a></p>
    <?php if ($image != null) { ?>
        <img src="<?php echo $image; ?>" alt="COOL PHOTO" align="left" width="100" height="100" style="border: 1px solid #ddd" hspace="10"/>
    <?php } ?>
    <?php the_excerpt(); ?>
</td></tr>
<?php
}
?>
</table>

<p>Have a nice reading, <?php echo get_option('blogname'); ?>.</p>

<p><small>To unsubscribe this feed, <a href="{feed_unsubscription_url}">click here</a>.</small></p>

</body>
</html>
