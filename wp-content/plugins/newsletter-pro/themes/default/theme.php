<?php
/*
 * This is an example theme which create a newsletter content with latest 10 post
 * and an image if available.
 * WordPress has a special API to extract posts called WP_Query(). Documentation
 * can be found here:
 *      http://codex.wordpress.org/Function_Reference/WP_Query
 * and the list of options here:
 *      http://codex.wordpress.org/Template_Tags/query_posts.
 *
 * In HTML newsletters it's better to use tables, because the email clients are not
 * so smart in rendering "modern" web designs. Another tip is to use inline styles and
 * many time "non elegant" HTML it's the best one.
 */
$posts = new WP_Query();
$posts->query(array('showposts'=>10, 'post_status'=>'publish'));
// To show posts only from one or more categories (categories 4 and 5 in example below)
//$posts->query(array('showposts'=>10, 'post_status'=>'publish', 'cat'=>'4,5'));
?>

<div style="background-color: #444444;">

<table width="550" cellpadding="10" align="center" style="margin-top: 30px; background-color: #ffffff; border: 5px solid #3B3B3B; ">
<tr>
<td>
    <!-- change the url below if you are working on a copy o this theme -->
    <img src="<?php echo get_option('blogurl'); ?>/wp-content/plugins/newsletter-pro/themes/default/banner.jpg"/>

    <p>Hi <strong>{name}</strong>, here the lastest news from <?php echo get_option('blogname'); ?>.</p>

<table>
<?php
while ($posts->have_posts())
{
    $posts->the_post();
    $image = nt_post_image(get_the_ID());
?>
<tr><td style="border-bottom: 1px solid #ddd">
    <h3><a href="<?php echo get_permalink(); ?>"><?php the_title(); ?></a></h3>
    <?php if ($image != null) { ?>
        <img src="<?php echo $image; ?>" alt="COOL PHOTO" align="left" width="100" hspace="10"/>
    <?php } ?>
    <?php the_excerpt(); ?>
    <br />
</td></tr>

<?php
}
?>
</table>

<p>Have a nice day, <?php echo get_option('blogname'); ?>.
<p>To unsubscribe <a href="{unsubscription_url}">click here</a>.</p>

    </td></tr>
</table>
</div>