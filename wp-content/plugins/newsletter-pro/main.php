<?php
function newsletter_to_byte($v)
{
    $l = substr($v, -1);
    $ret = substr($v, 0, -1);
    switch (strtoupper($l)) {
    case 'P':
        $ret *= 1024;
    case 'T':
        $ret *= 1024;
    case 'G':
        $ret *= 1024;
    case 'M':
        $ret *= 1024;
    case 'K':
        $ret *= 1024;
        break;
    }
    return $ret;
}

@include_once 'commons.php';

$options = get_option('newsletter_main');

if ($action == 'save') {
    $options = stripslashes_deep($_POST['options']);

    // Validation
    $options['sender_email'] = newsletter_normalize_email($options['sender_email']);
    if (!newsletter_is_email($options['sender_email'])) {
        $errors = __('Sender email is not correct');
    }

    $options['sender_name'] = trim($options['sender_name']);
    if (empty($options['sender_name'])) {
        $errors = __('Sender name cannot be empty');
    }

    $options['return_path'] = newsletter_normalize_email($options['return_path']);
    if (!newsletter_is_email($options['return_path'], true)) {
        $errors = __('Return path email is not correct');
    }
    // With some providers the return path must be left empty
    //if (empty($options['return_path'])) $options['return_path'] = $options['sender_email'];

    $options['test_email'] = newsletter_normalize_email($options['test_email']);
    if (!newsletter_is_email($options['test_email'], true)) {
        $errors = __('Test email is not correct');
    }

    $options['mode'] = (int)$options['mode'];
    $options['logs'] = (int)$options['logs'];

    if ($errors == null) {
        update_option('newsletter_main', $options);
    }

    if (empty($options['url'])) {
        $messages = __('Newsletter page should be created');
    }
}

if ($action == 'test') {
    $options = stripslashes_deep($_POST['options']);
    for ($i=0; $i<NEWSLETTER_MAX_TEST_SUBSCRIBERS; $i++) {
        if (!empty($options['test_email_' . $i])) {
            $r = newsletter_mail($options['test_email_' . $i], 'Test email from Newsletter Plugin', '<p>This is a test message from Newsletter Plugin. You are reading it, so the plugin is working.</p>');
        }
    }
    $messages = 'Test emails sent. Check the test mailboxes.';
}

$nc = new NewsletterControls($options);

$nc->errors($errors);
$nc->messages($messages);
?>
<div class="wrap">

    <h2><?php _e('Newsletter Configuration', 'newsletter'); ?> <a target="_blank" href="http://www.satollo.net/plugins/newsletter#main"><img src="<?php echo get_option('siteurl'); ?>/wp-content/plugins/newsletter-pro/help.png"/></a></h2>

    <?php require_once 'header.php'; ?>

    <form method="post" action="">
    <?php wp_nonce_field(); ?>
        <input type="hidden" value="<?php echo NEWSLETTER; ?>" name="options[version]"/>

        <h3>System check</h3>
        <table class="form-table">
            <tr valign="top">
                <th>Database</th>
                <td>
                    <?php $wait_timeout = $wpdb->get_var("select @@wait_timeout"); ?>
                    Wait timeout: <?php echo $wait_timeout; ?> seconds
                    <br />
                    <?php if ($wait_timeout > 300) { ?>
                    The timeout is ok
                    <?php } else { ?>
                        <?php $wpdb->query("set session wait_timeout=300"); ?>
                        <?php if (300 != $wpdb->get_var("select @@wait_timeout")) { ?>
                        Cannot rise wait timout, problems may occur while sending.
                        <?php } else { ?>
                        Wait timeout can be changed
                        <?php } ?>
                    <?php } ?>
                </td>
            </tr>
            <tr valign="top">
                <th>PHP Execution time</th>
                <td>
                    Max execution time: <?php echo ini_get('max_execution_time'); ?>
                    <br />
                    <?php @set_time_limit(NEWSLETTER_TIME_LIMIT); ?>
                    <?php if (NEWSLETTER_TIME_LIMIT != ini_get('max_execution_time')) { ?>
                    Cannot change max execution time
                    <?php } else { ?>
                    Max execution time can be changed
                    <?php } ?>
                </td>
            </tr>
            <tr valign="top">
                <th>Memory limit</th>
                <td>
                    <?php $ml = @newsletter_to_byte(ini_get('memory_limit')); ?>
                    Memory limit: <?php echo $ml; ?>
                    <br />
                    <?php if ($ml > NEWSLETTER_MEMORY_LIMIT) { ?>
                    The memory limit is ok
                    <?php } else { ?>
                        <?php @ini_set('memory_limit', NEWSLETTER_MEMORY_LIMIT); ?>
                        <?php if (NEWSLETTER_MEMORY_LIMIT != @ini_get('memory_limit')) { ?>
                        Cannot rise memory limitwait timout, problems may occur while sending.
                        <?php } else { ?>
                        Memory limit can be changed
                        <?php } ?>
                    <?php } ?>
                </td>
            </tr>
        </table>
        
        <h3><?php _e('Sender and newsletter page', 'newsletter'); ?></h3>
        <table class="form-table">
            <tr valign="top">
                <th><?php _e('Sender name', 'newsletter'); ?></th>
                <td>
                    <?php $nc->text('sender_name', 40); ?>
                </td>
            </tr>
            <tr valign="top">
                <th><?php _e('Sender email', 'newsletter'); ?></th>
                <td>
                    <?php $nc->text('sender_email', 40); ?>
                </td>
            </tr>
            <tr valign="top">
                <th><?php _e('Newsletter page', 'newsletter'); ?></th>
                <td>
                    <?php $nc->text('url', 70); ?>
                    <br />
                    <?php _e('This is the page where you placed the <strong>[newsletter]</strong> short tag. See help page of this panel.'); ?>
                </td>
            </tr>
            <tr valign="top">
                <th><?php _e('Return path', 'newsletter'); ?></th>
                <td>
                    <?php $nc->text('return_path', 40); ?>
                    <br />
                    <?php _e('
                    Email address where delivery error messages are sent.
                    Some providers do not accept this field and block emails is compiled. If so,
                    leave it blank.'); ?>
                </td>
            </tr>
            <tr valign="top">
                <th><?php _e('Notifications', 'newsletter'); ?></th>
                <td>
                    <?php $nc->yesno('notify'); ?> Send notification of subscription, unsubscription and so on.
                </td>
            </tr>
        <tr>
            <th>Generic test subscribers</th>
            <td>
                <?php for ($i=0; $i<NEWSLETTER_MAX_TEST_SUBSCRIBERS; $i++) { ?>
                email: <?php $nc->text('test_email_' . $i, 30); ?> name: <?php $nc->text('test_name_' . $i, 30); ?><br />
                <?php } ?>
            </td>
        </tr>
        </table>
        <p class="submit">
            <?php $nc->button('save', __('Save', 'newsletter')); ?>
            <?php $nc->button('test', __('Test', 'newsletter')); ?>
        </p>
        
        <h3><?php _e('General parameters', 'newsletter'); ?></h3>
        <table class="form-table">
            <tr valign="top">
                <th><?php _e('Enable access to editors?', 'newsletter'); ?></th>
                <td>
                    <?php $nc->yesno('editor'); ?>
                </td>
            </tr>
            <tr valign="top">
                <th><?php _e('Always show panels in english?', 'newsletter'); ?></th>
                <td>
                    <?php $nc->yesno('no_translation'); ?>
                </td>
            </tr>
            <tr valign="top">
                <th><?php _e('Logging', 'newsletter'); ?></th>
                <td>
                    <?php $nc->select('logs', array(0=>'None', 1=>'Normal', 2=>'Debug')); ?>
                    <br />
                    <?php _e('Debug level saves user data on file system, use only to debug problems.', 'newsletter'); ?>
                </td>
            </tr>
            <tr valign="top">
                <th><?php _e('Working mode', 'newsletter'); ?></th>
                <td>
                    <?php $nc->select('mode', array(0=>'Normal', 1=>'Development (experimental features)')); ?>
                </td>
            </tr>
        </table>
        <p class="submit">
            <?php $nc->button('save', __('Save', 'newsletter')); ?>
        </p>

        <h3><?php _e('Support', 'newsletter'); ?></h3>
        <table class="form-table">
            <tr valign="top">
                <th><?php _e('Debug data<br /><small>Can be useful to help you</small>', 'newsletter'); ?></th>
                <td>
                    <textarea cols="70" rows="10">
--- MAIN ---
<?php var_dump($options); ?>
--- SUBSCRIPTION ---
<?php var_dump(get_option('newsletter')); ?>
--- EMAIL --
<?php var_dump(get_option('newsletter_email')); ?>
--- FEED --
<?php var_dump(get_option('newsletter_feed')); ?>
--- FOLLOWUP --
<?php var_dump(get_option('newsletter_followup')); ?>
--- SMTP --
<?php var_dump(get_option('newsletter_smtp')); ?>
--- LISTS --
<?php var_dump(get_option('newsletter_lists')); ?>
                    </textarea>
                </td>
            </tr>
        </table>

    </form>
</div>
