<?php
@include_once 'commons.php';

$options = get_option('newsletter_feed');

if ($action == 'reset') {
    delete_option('newsletter_feed_batch');
    wp_clear_scheduled_hook('newsletter_feed_batch_job');
}

if ($action == 'now') {
    if ($options['enabled'] == 1) {
        newsletter_feed_job();
    }
}

$batch = get_option('newsletter_feed_batch', array());

if ($action == 'back') {
    $batch['last_time'] -= 86400;
    update_option('newsletter_feed_batch', $batch);
}

if ($action == 'forward') {
    $batch['last_time'] += 86400;
    update_option('newsletter_feed_batch', $batch);
}

if ($action == 'today') {
    $batch['last_time'] = time();
    $batch['completed'] = true;
    update_option('newsletter_feed_batch', $batch);
}

if ($action == 'save') {
    $options = stripslashes_deep($_POST['options']);

    // Validation
    if (!is_numeric($options['scheduler_max']) || $options['scheduler_max'] <= 0)
    {
        $errors = 'Max emails per hour not valid';
    }

    if (is_null($errors))
    {
        update_option('newsletter_feed', $options);

        wp_clear_scheduled_hook('newsletter_feed_job');

        if ($options['enabled'] == 1) {
            $day_offset = 0;
            $current_hour = (int)gmdate('G');
            $hour = (int)$options['hour'] - get_option('gmt_offset'); // to gmt
            if ($current_hour > $hour) $day_offset = 1;
            $time = gmmktime($hour, 0, 0, gmdate("m"), gmdate("d")+$day_offset, gmdate("Y"));
            wp_schedule_event($time, 'daily', 'newsletter_feed_job');
        }
        else {
            wp_clear_scheduled_hook('newsletter_feed_batch_job');
        }
    }
}

//if ($action == 'subscribe') {
//    $options = stripslashes_deep($_POST['options']);
//    $s = newsletter_get_subscriber_by_email($options['email']);
//    if ($s != null) {
//        newsletter_feed_subscribe($s->id, $s->token);
//        $messages = $options['email'] . ' subscribed.';
//    }
//    else {
//        $errors = 'Subscriber not found.';
//    }
//}

//if ($action == 'unsubscribe') {
//    $options = stripslashes_deep($_POST['options']);
//    $s = newsletter_get_subscriber_by_email($options['email']);
//    if ($s != null) {
//        newsletter_feed_unsubscribe($s->id, $s->token);
//        $messages = $options['email'] . ' unsubscribed.';
//    }
//    else {
//        $errors = 'Subscriber not found.';
//    }
//}

//if ($action == 'check') {
//    $options = stripslashes_deep($_POST['options']);
//    $s = newsletter_get_subscriber_by_email($_POST['options']['email']);
//    if ($s == null) {
//        $errors = 'Subscriber not found.';
//    }
//}

if ($action == 'test') {
    $subscribers = newsletter_get_test_subscribers();
    $message = newsletter_create_message('', 'feed');
    $newsletter_feed_last_time = 0;
    $posts = get_posts('numberposts=1');
    $subject = $posts[0]->post_title;

    foreach($subscribers as $subscriber)
    {
        $m = newsletter_replace_all_urls($message, $subscriber);
        $m = newsletter_replace($m, $subscriber);
        $s = newsletter_replace($subject, $subscriber);
        $x = newsletter_mail($subscriber->email, $s, $m, true);
    }
    $messages = 'Test email sent';
}

$nc = new NewsletterControls($options);
$nc->errors($errors);
$nc->errors($messages);
?>
<div class="wrap">

    <h2><?php _e('Newsletter Feed by Mail', 'newsletter'); ?></h2>

    <p><input class="button" type="button" value="Reload" onclick="location.href=location.href"/></p>

<form method="post" action="">
    <?php wp_nonce_field(); ?>
    <?php /*
    <h3>Manually subscribe or unsubscribe</h3>
    <table class="form-table">
        <tr>
            <th>Email address</th>
            <td><?php $nc->text('email', 50); ?></td>
        </tr>
        <?php if ($action == 'check' && $errors == null) { ?>
        <tr>
            <th>Feed subscription details</th>
            <td>
                Email: <?php echo htmlspecialchars($s->email); ?><br />
                Name: <?php echo htmlspecialchars($s->name); ?><br />
                Id: <?php echo $s->id; ?><br />
                    <?php if ($s->feed == 0) { ?>
                Not subscribed
                    <?php } else { ?>
                Subscribed
                    <?php } ?>
            </td>
        </tr>
        <?php } ?>
    </table>
    <p class="submit">
        <?php $nc->button('subscribe', 'Subscribe'); ?>
        <?php $nc->button('unsubscribe', 'Unsubscribe'); ?>
        <?php $nc->button('check', 'Check'); ?>
    </p>
    */?>
    
    <h3>Delivery process</h3>
    <?php if (empty($batch)) { ?>

    <p><strong>No data found, it's ok! (and it may take some time to appear)</strong></p>

    <?php } else { ?>

    <table class="form-table">
        <tr>
            <th>Status</th>
            <td>
                    <?php
                        if ($batch['completed']) echo 'Delivery completed.';
                        else {
                            $time = wp_next_scheduled('newsletter_feed_batch_job');
                            if ($time === false) {
                                echo 'Delivery not completed but no next run found! (errors?)';
                            }
                            else {
                                echo 'Delivery not completed, next run on ' . newsletter_date($time);
                                echo ' (' . ((int)(($time-time())/60)) . ' minutes left)';
                            }
                        }
                    ?>
                <br />
                    <?php echo $batch['message']; ?>
            </td>
        </tr>
        <tr>
            <th>Emails sent/total</th>
            <td><?php echo $batch['sent']; ?>/<?php echo $batch['total']; ?> (last subscriber id: <?php echo $batch['id']; ?>)</td>
        </tr>
    </table>
    <?php } ?>

    <h3>Configuration</h3>
    <table class="form-table">
        <tr valign="top">
            <th>Last new post check</th>
            <td>
                <?php if (empty($batch)) { ?>
                    Never checked <?php $nc->button('today', 'Set as today'); ?>
                <?php } else { ?>
                    <?php echo newsletter_date((int)$batch['last_time']); ?>
                    <?php $nc->button('back', '1 day back'); ?>
                    <?php $nc->button('forward', '1 day forward'); ?>
                <?php } ?>
            </td>
        </tr>
        <tr valign="top">
            <th>Enabled?</th>
            <td>
                <?php $nc->yesno('enabled'); ?>
                <br />
                Next feed check on: <?php echo newsletter_date(wp_next_scheduled('newsletter_feed_job'), true); ?>
            </td>
        </tr>
        <tr valign="top">
            <th>Day</th>
            <td>
                <?php $nc->days('day'); ?>
            </td>
        </tr>
        <tr valign="top">
            <th>Delivery hour</th>
            <td>
                <?php $nc->hours('hour'); ?>
            </td>
        </tr>
        <tr valign="top">
            <th>Track link clicks?</th>
            <td><?php $nc->yesno('track'); ?></td>
        </tr>
        <tr valign="top">
            <th>Max emails per hour</th>
            <td>
                <?php $nc->text('scheduler_max', 5); ?>
                (good value is 200 to 500)
            </td>
        </tr>
        <tr>
            <th>Add to feed by mail every new subscriber?</th>
            <td><?php $nc->yesno('add_new'); ?></td>
        </tr>
    </table>

    <p class="submit">
        <?php $nc->button('save', 'Save'); ?>
        <?php $nc->button('test', 'Test'); ?>
        <?php $nc->button('now', 'Send now!'); ?>
        <?php $nc->button('reset', 'Reset'); ?>
    </p>

    <h3>Post on queue from last check</h3>
    <table class="form-table">
        <tr valign="top">
            <td>
                <?php
                $posts = new WP_Query();
                $posts->query(array('showposts'=>10, 'post_status'=>'publish'));

                while ($posts->have_posts())
                {
                    $posts->the_post();
                    if (mysql2date('U', $post->post_date_gmt) <= (int)$batch['last_time']) break;
                ?>
                [<?php echo the_ID(); ?>] <?php echo newsletter_date(newsletter_m2t($post->post_date_gmt)); ?> <a target="_blank" href="<?php echo get_permalink(); ?>"><?php the_title(); ?></a><br />
                <?php } ?>
            </td>
        </tr>
    </table>
    <br />

</form>

</div>