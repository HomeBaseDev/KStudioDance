<?php

@include_once 'commons.php';

$options_main = get_option('newsletter_main', array());
$options = get_option('newsletter_email', array());
$options_lists = get_option('newsletter_lists', array());
$lists = array('0'=>'To all subscribers');
for ($i=1; $i<=9; $i++)
{
    if (empty($options_lists['name_' . $i])) continue;
    $lists['' . $i] = $options_lists['name_' . $i];
}

if (empty($options['max'])) $options['max'] = 100;
if (empty($options['scheduler_max'])) $options['scheduler_max'] = 100;

if ($action == 'save') {
    $options = stripslashes_deep($_POST['options']);
    if (!is_numeric($options['scheduler_max']) || $options['scheduler_max'] <= 0)
    {
        $errors = 'Max emails per hour not valid';
    }
    if (!is_numeric($options['max']) || $options['max'] < 0)
    {
        $errors = 'Max emails per batch not valid';
    }
    update_option('newsletter_email', $options);
}

// Auto composition
if ($action == 'auto') {
// Load the theme
    $options = stripslashes_deep($_POST['options']);

    $file = newsletter_get_theme_dir($options['theme']) . '/theme.php';

    // Execute the theme file and get the content generated
    ob_start();
    @include($file);
    $options['message'] = ob_get_contents();
    ob_end_clean();

    if ($options['novisual']) {
        $options['message'] = "<html>\n<head>\n<style type=\"text/css\">\n" . newsletter_get_theme_css($options_email['theme']) .
            "\n</style>\n</head>\n<body>\n" . $options['message'] . "\n</body>\n</html>";
    }
}

// Reset the batch
if ($action == 'reset') {
    //newsletter_delete_batch_file();
    wp_clear_scheduled_hook('newsletter_job');
    newsletter_data_remove('batch');
    newsletter_data_remove('job');
}

if ($action == 'scheduled_simulate') {

    $options = stripslashes_deep($_POST['options']);
    update_option('newsletter_email', $options);
    
    wp_clear_scheduled_hook('newsletter_job');
    newsletter_data_remove('job');

    $batch = array();
    $batch['id'] = 0;
    $batch['list'] = $options['list'];
    $batch['scheduled'] = true;
    $batch['simulate'] = true;

    newsletter_data_set('batch', $batch);

    newsletter_job();
}

if ($action == 'scheduled_send') {
    $options = stripslashes_deep($_POST['options']);
    update_option('newsletter_email', $options);

    wp_clear_scheduled_hook('newsletter_job');
    newsletter_data_remove('job');
    
    @$wpdb->insert($wpdb->prefix . 'newsletter_emails', array(
       'subject'=>$options['subject'],
       'message'=>$options['message'],
       'name'=>$options['name']
    ));

    $batch = array();
    $batch['id'] = 0;
    $batch['list'] = $options['list'];
    $batch['scheduled'] = true;
    $batch['simulate'] = false;

    newsletter_data_set('batch', $batch);

    newsletter_job();
}

if ($action == 'restore') {
    $batch = newsletter_load_batch_file();
    update_option('newsletter_batch', $batch);
    newsletter_delete_batch_file();
}

// Theme style

$css_url = null;
$theme_dir = newsletter_get_theme_dir($options['theme']);
if (file_exists($theme_dir . '/style.css')) {
    $css_url = newsletter_get_theme_url($options['theme']) . '/style.css';
}

$nc = new NewsletterControls($options, 'composer');

$nc->errors($errors);
$nc->messages($messages);

?>
<?php if (!isset($options['novisual'])) { ?>
<script type="text/javascript" src="<?php echo get_option('siteurl'); ?>/wp-content/plugins/newsletter-pro/tiny_mce/tiny_mce.js"></script>
<script type="text/javascript">
    tinyMCE.init({
        mode : "textareas",
        theme : "advanced",
        plugins: "table,fullscreen",
        theme_advanced_disable : "styleselect",
        theme_advanced_buttons1_add: "forecolor,blockquote,code",
        theme_advanced_buttons3 : "tablecontrols,fullscreen",
        relative_urls : false,
        remove_script_host : false,
        theme_advanced_toolbar_location : "top",
        document_base_url : "<?php echo get_option('home'); ?>/"
    <?php
    if ($css_url != null) {
        echo ', content_css: "' . $css_url . '?' . time() . '"';
    }
    ?>
        });
</script>
<?php } ?>

<div class="wrap">

    <h2>Newsletter Composer</h2>
    
    <p><input class="button" type="button" value="Reload" onclick="location.href=location.href"/></p>

    <?php if (!touch(dirname(__FILE__) . '/test.tmp')) { ?>
    <div class="error fade" style="background-color:red;"><p><strong>It seems that Newsletter plugin folder is not writable. Make it writable to let
                Newsletter write logs and save date when errors occour.</strong></p></div>
    <?php } ?>

    <?php require_once 'header.php'; ?>

    <form method="post" action="">
        <?php wp_nonce_field(); ?>

        <?php if ($action == 'restart') { ?>

        <h3>Continuing with previous batch</h3>
        <div class="form-table">
                <?php
                $options = stripslashes_deep($_POST['options']);
                update_option('newsletter_email', $options);
                $batch = newsletter_data_get('batch');

                if ($batch['scheduled']) {
                    newsletter_job();
                }
                else {
                    newsletter_send_batch();
                }
                ?>
        </div>

        <?php } ?>


        <?php if ($action == 'simulate') { ?>

        <h3>Simulation</h3>
        <div class="form-table">
                <?php
                $options = stripslashes_deep($_POST['options']);
                update_option('newsletter_email', $options);
                $batch = array();
                $batch['id'] = 0;
                $batch['list'] = $options['list'];
                $batch['scheduled'] = false;
                $batch['simulate'] = true;

                update_option('newsletter_batch', $batch);

                newsletter_send_batch();
                ?>
        </div>

        <?php } ?>



        <?php if ($action == 'send') { ?>

        <h3>Sending</h3>
        <div class="form-table">
                <?php
                $options = stripslashes_deep($_POST['options']);
                update_option('newsletter_email', $options);

                $wpdb->insert($wpdb->prefix . 'newsletter_emails', array(
                   'subject'=>$options['subject'],
                   'message'=>$options['message'],
                   'name'=>$options['name']
                ));
                $batch = array();
                $batch['id'] = 0;
                $batch['list'] = $options['list'];
                $batch['scheduled'] = false;
                $batch['simulate'] = false;

                newsletter_data_set('batch', $batch);

                newsletter_send_batch();
                ?>
        </div>

        <?php } ?>



        <?php if ($action == 'test') { ?>

        <h3>Sending to test subscribers</h3>
        <div class="form-table">
                <?php
                $options = stripslashes_deep($_POST['options']);
                update_option('newsletter_email', $options);
                newsletter_send_test();
                ?>
        </div>

        <?php } ?>



        <?php/*
        $batch_file = newsletter_load_batch_file();
        if ($batch_file != null) {
            ?>
        <h3>Warning!!!</h3>
        <p>There is a batch saved to disk. That means an error occurred while sending.
            Would you try to restore
            that batch?<br />
            <input class="button" type="submit" name="restore" value="Restore batch data"  onclick="return confirm('Restore batch data?')"/>
        </p>
        }*/ ?>

        <h3>Delivery status</h3>

        <?php if ($newsletter_batch == null) $newsletter_batch = newsletter_data_get('batch', array()); ?>
        <?php if (empty($newsletter_batch)) { ?>

        <p><strong>No status found, it's ok!</strong></p>

        <?php } else { ?>

        <table class="form-table">
            <tr>
                <th>Status</th>
                <td>
                        <?php
                        if ($newsletter_batch['scheduled']) {

                            if ($newsletter_batch['completed']) echo 'Completed';
                            else {
                                $time = wp_next_scheduled('newsletter_job');
                                if ($time == 0) {
                                    echo 'Not completed but no next run found (may be it is sending, check the message below, if not try to reactivate)';
                                }
                                else {
                                    echo 'Not completed, next run on ' . newsletter_date($time, true, true);
                                }
                            }
                        }
                        else {
                            if ($newsletter_batch['completed']) echo 'Completed';
                            else echo 'Not completed (you should run next batch)';
                        }
                        ?>
                    <br />
                    Last message: <?php echo $newsletter_batch['message']; ?>
                </td>
            </tr>
            <tr>
                <th>Emails sent/total</th>
                <td><?php echo $newsletter_batch['sent']; ?>/<?php echo $newsletter_batch['total']; ?> (last id: <?php echo $newsletter_batch['id']; ?>)</td>
            </tr>
            <tr>
                <th>Send to list</th>
                <td><?php echo htmlspecialchars($lists[$newsletter_batch['list']]); ?></td>
            </tr>
            <tr>
                <th>Sending type</th>
                <td><?php echo $newsletter_batch['scheduled']?"Automatic":"Manual"; ?>/<?php echo $newsletter_batch['simulate']?"Simluation":"Real"; ?></td>
            </tr>
        </table>

        <p class="submit">
            <?php if (!$newsletter_batch['completed']) { ?>
                <?php $nc->button_confirm('restart', 'Run next batch/Reactivate', 'Want to run next batch or reactivate?'); ?>
            <?php } ?>
            <?php $nc->button_confirm('reset', 'Abort', 'Want to abort any delivery process?'); ?>
        </p>

        <?php } ?>



        <h3>Newsletter message</h3>

        <table class="form-table">
            <tr valign="top">
                <th>Newsletter name and tracking</th>
                <td>
                    <input name="options[name]" type="text" size="25" value="<?php echo htmlspecialchars($options['name'])?>"/>
                    <input name="options[track]" value="1" type="checkbox" <?php echo $options['track']?'checked':''; ?>/>
                    Track link clicks
                    <br />
                    When this option is enabled, each link in the email text will be rewritten and clicks
                    on them intercepted.
                    The symbolic name will be used to track link clicks and associate them to a specific newsletter.
                    Keep the name compact and significative.
                </td>
            </tr>

            <tr valign="top">
                <th>Subject</th>
                <td>
                    <?php $nc->text('subject', 70); ?>
                    <br />
                    <?php _e('Tags: <strong>{name}</strong> receiver name.', 'newsletter'); ?>
                </td>
            </tr>

            <tr valign="top">
                <th>Message</th>
                <td>
                    <?php $nc->checkbox('novisual', 'disable the visual editor'); ?>
                    (save to apply and be sure to <a href="http://www.satollo.net/plugins/newsletter#composer">read here</a>)
                    <br />
                    <textarea name="options[message]" wrap="off" rows="20" style="font-family: monospace; width: 100%"><?php echo htmlspecialchars($options['message'])?></textarea>
                    <br />
                    <?php _e('Tags: <strong>{name}</strong> receiver name;
<strong>{unsubscription_url}</strong> unsubscription URL;
<strong>{token}</strong> the subscriber token; <strong>{profile_url}</strong> link to the subscription options page.', 'newsletter'); ?>
                    <?php if ($options_lists['enabled'] != 1) { ?>
                    <input type="hidden" name="options[list]" value="0"/>
                    <?php } ?>
                </td>
            </tr>
            
            <?php if ($options_lists['enabled'] == 1) { ?>
            <tr valign="top">
                <th>To list</th>
                <td>
                    <?php $nc->select('list', $lists); ?>
                </td>
            </tr>
            <?php } ?>

            <tr valign="top">
                <th>Theme</th>
                <td>
                    <?php $nc->select_grouped('theme', array(
                            array(''=>'Basic themes', 'blank'=>'Blank', 'default'=>'Default', 'extra-1'=>'Extra 1', 'extra-2'=>'Extra 2'),
                            array_merge(array(''=>'Custom themes'), newsletter_get_themes()),
                            ));
                    ?>
                    <?php $nc->button('auto', 'Auto compose'); ?>
                </td>
            </tr>
        </table>

        <p class="submit">
            <?php $nc->button('save', 'Save'); ?>
            <?php $nc->button_confirm('test', 'Send test emails', 'Send test emails to test addresses?'); ?>
            <?php $nc->button_confirm('send', 'Manual delivery', 'Start a real delivery?'); ?>
            <?php $nc->button_confirm('scheduled_send', 'Automatic delivery', 'Start a real delivery?'); ?>

            <?php if ($options_main['mode'] == 1) { ?>
                <?php $nc->button_confirm('simulate', 'Simulate', 'Send all emails to simulation address?'); ?>
                <?php $nc->button('scheduled_simulate', 'Scheduled simulation'); ?>
            <?php } ?>
        </p>

        <h3>Delivery options</h3>
        <table class="form-table">
            <tr valign="top">
                <th>Max emails per batch<br/><small>for manual delivery</small></th>
                <td>
                    <?php $nc->text('max', 5); ?>
                </td>
            </tr>
            <tr valign="top">
                <th>Max emails per hour<br/><small>for automatic delivery</small></th>
                <td>
                    <?php $nc->text('scheduler_max', 5); ?>
                    (good value is 100 to 5000)
                </td>
            </tr>
            <?php if ($options_main['mode'] == 1) { ?>
            <tr valign="top">
                <th>Receiver address for simulation</th>
                <td>
                    <?php $nc->text('simulate_email', 50); ?>
                    <br />
                    <?php _e('When you simulate a sending process, emails are really sent but all to this
email address. That helps to test out problems with mail server.', 'newsletter'); ?>
                </td>
            </tr>
            <?php } ?>
        </table>
        <p class="submit">
            <?php $nc->button('save', 'Save'); ?>
        </p>

    </form>
</div>
