<?php
@include_once 'commons.php';

$options = get_option('newsletter_followup', array());

if ($action == 'save') {
    $options = stripslashes_deep($_POST['options']);

    if ($options['enabled'] == 1) {
        $options['interval'] = (int)$options['interval'];
        if ($options['interval'] <= 0) $errors = 'Interval must be greater that zero.';
    }

    if ($errors == null) {
        $options['steps'] = 0;
        for ($i=1; $i<=NEWSLETTER_FOLLOWUP_MAX_STEPS; $i++) {
            $options['step_' . $i . '_subject'] = trim($options['step_' . $i . '_subject']);
            if (empty($options['step_' . $i . '_subject'])) break;
            $options['steps']++;
        }

        update_option('newsletter_followup', $options);

        if ($options['enabled'] == 1) {
            wp_schedule_single_event(time(), 'newsletter_followup_job');
        }
        else {
            wp_clear_scheduled_hook('newsletter_followup_job');
        }
    }
}


//if ($action == 'subscribe') {
//    $options = stripslashes_deep($_POST['options']);
//    $s = newsletter_get_subscriber_by_email($options['email']);
//    if ($s != null) {
//        newsletter_followup_subscribe($s->id, $s->token);
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
//        newsletter_followup_unsubscribe($s->id, $s->token);
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
    $options = stripslashes_deep($_POST['options']);
    $step = $options['test_step'];

    $subscribers = newsletter_get_test_subscribers();

    // This code is copied and adapted from newsnewsletter_get_test_subscriber()letter_followup_job()
    $message = $options['step_' . $step . '_message'];
    $message = newsletter_create_message($message, 'followup');

    foreach ($subscribers as $subscriber) {
        $message = newsletter_replace($message, $subscriber);

        $message = newsletter_replace_url($message, 'FOLLOWUP_UNSUBSCRIPTION_URL',
            newsletter_add_qs($newsletter_options_main['url'],
                'na=fu&amp;ni=' . $subscriber->id . '&amp;nt=' . $subscriber->token));
        if ($options['track'] == 1) $message = newsletter_relink($message, $subscriber->id, 'followup');

        $subject = $options['step_' . $step . '_subject'];
        $subject = newsletter_replace($subject, $subscriber);

        newsletter_mail($subscriber->email, $subject, $message);
    }
}
$nc = new NewsletterControls($options);

$nc->errors($errors);
$nc->errors($messages);
?>

<div class="wrap">

    <h2><?php _e('Newsletter Follow Up', 'newsletter'); ?></h2>

<?php if ($options['novisual'] != 1) { ?>
<script type="text/javascript" src="<?php echo get_option('siteurl'); ?>/wp-content/plugins/newsletter-pro/tiny_mce/tiny_mce.js"></script>

<script type="text/javascript">
    tinyMCE.init({
        mode : "specific_textareas",
        editor_selector : "visual",
        theme : "advanced",
        theme_advanced_disable : "styleselect",
        relative_urls : false,
        remove_script_host : false,
        theme_advanced_buttons3: "",
        theme_advanced_toolbar_location : "top",
        document_base_url : "<?php echo get_option('home'); ?>/",
        content_css : "<?php echo get_option('blogurl'); ?>/wp-content/plugins/newsletter-pro/editor.css?" + new Date().getTime()
    });

    function newsletter_test(f, i)
    {
        f.elements['options[test_step]'].value = i;
        f.submit();
    }
</script>
<?php } ?>

<p>The message sequence stops on first message with empty subject.</p>

<form action="" method="post">
    <?php wp_nonce_field(); ?>
    <?php $nc->hidden('test_step'); ?>

    <h3>Status</h3>
    <table class="form-table">
        <tr>
            <th>Next run</th>
            <td>
                <?php $time = wp_next_scheduled('newsletter_followup_job'); ?>
                <?php if ($time === false) { ?>
                No next run found (if follow up is disabled it's ok).
                <?php } else { ?>
                Next run on <?php echo newsletter_date($time, true, true); ?>
                <?php } ?>
            </td>
        </tr>
    </table>

    <?php /*
    <h3>Manually subscribe or unsubscribe</h3>
    <table class="form-table">
        <tr>
            <th>Email address</th>
            <td><?php $nc->text('email'); ?></td>
        </tr>
        <?php if ($action == 'check' && $errors == null) { ?>
        <tr>
            <th>Follow up details</th>
            <td>
                Email: <?php echo htmlspecialchars($s->email); ?><br />
                Name: <?php echo htmlspecialchars($s->name); ?><br />
                Id: <?php echo $s->id; ?><br />
                    <?php if ($s->followup == 0) { ?>
                Not subscribed
                    <?php } else { ?>
                Last step sent: <?php echo $s->followup_step; ?><br />
                Next email on <?php echo newsletter_date($s->followup_time); ?>
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
    */ ?>
    
    <h3>Configuration</h3>
    <table class="form-table">
        <tr>
            <th>Enabled?</th>
            <td><?php $nc->yesno('enabled'); ?></td>
        </tr>
        <tr>
            <th>Interval between steps</th>
            <td><?php $nc->text('interval', 5); ?> (hours)</td>
        </tr>
        <tr>
            <th>Add to follow up every new subscriber?</th>
            <td><?php $nc->yesno('add_new'); ?></td>
        </tr>
        <tr valign="top">
            <th>Track link clicks?</th>
            <td><?php $nc->yesno('track'); ?></td>
        </tr>
        <tr valign="top">
            <th>Disable visual editors?</th>
            <td><?php $nc->yesno('novisual'); ?></td>
        </tr>
    </table>
    <p class="submit"><?php $nc->button('save', 'Save'); ?></p>

    <h3>Steps</h3>
    <table class="form-table">
        <tr>
            <th>Steps</th>
            <td><?php $nc->value('steps'); ?></td>
        </tr>
    </table>
    <?php for ($i=1; $i<=NEWSLETTER_FOLLOWUP_MAX_STEPS; $i++) { ?>
    <table class="form-table">
        <tr>
            <th>Message <?php echo $i; ?></th>
            <td><?php $nc->email('step_' . $i); ?></td>
        </tr>
    </table>
    <p class="submit"><?php $nc->button('save', 'Save'); ?> <?php $nc->button('test', 'Test', 'newsletter_test(this.form,' . $i . ')'); ?> </p>
    <?php } ?>
</form>


</div>