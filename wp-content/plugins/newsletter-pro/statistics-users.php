<?php
@include_once 'commons.php';

$options = stripslashes_deep($_POST['options']);

$newsletter_lists = get_option('newsletter_lists');
$nc = new NewsletterControls($options);
$nc->errors($errors);
$nc->messages($messages);

$list_count = $wpdb->get_results("select count(if(list_1=1,1,null)) as l1, count(if(list_2=1,1,null)) as l2, count(if(list_3=1,1,null)) as l3,
count(if(list_4=1,1,null)) as l4, count(if(list_5=1,1,null)) as l5, count(if(list_6=1,1,null)) as l6, count(if(list_7=1,1,null)) as l7,
count(if(list_8=1,1,null)) as l8, count(if(list_9=1,1,null)) as l9
from " . $wpdb->prefix . "newsletter");

$total = $wpdb->get_var("select count(*) from " . $wpdb->prefix . "newsletter");
?>

<div class="wrap">

    <h2>Newsletter Statistics - Users</h2>
    <p>This panel shows how many subscribers there is in each list. A subscriber can be in any, one or more lists. Subscribers can edit
    their list subscriptions using a special link you can insert in emails (like a newsletter or a welcome email). The link is generated
    by Newsletter Pro when it finds the placeholder {profile_url}.</p>

    <form action="" method="post">
            <?php wp_nonce_field(); ?>
        <h3>Statistics</h3>

        <table class="form-table">
            <tr valign="top">
                <th>Total</th>
                <td>
                    <?php echo $total; ?>
                </td>
            </tr>
            <?php for ($i=1; $i<=NEWSLETTER_LISTS_MAX; $i++) { ?>
            <?php $attr = 'l'.$i; ?>
            <tr valign="top">
                <th><?php echo $newsletter_lists['name_' . $i]; ?></th>
                <td>
                    <?php echo $list_count[0]->$attr; ?>
                </td>
            </tr>
            <?php } ?>
        </table>
    </form>

</div>
