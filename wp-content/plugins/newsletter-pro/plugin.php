<?php
/*
Plugin Name: Newsletter Pro
Plugin URI: http://www.satollo.net/plugins/newsletter
Description: Newsletter is a cool plugin to create your own subscriber list, to send newsletters, to build your business. <strong>Before update give a look to <a href="http://www.satollo.net/plugins/newsletter#update">this page</a> to know what's changed.</strong>
Version: 2.0.0
Author: Satollo
Author URI: http://www.satollo.net
Disclaimer: Use at your own risk. No warranty expressed or implied is provided.
*/

/*
Copyright 2010 Stefano Lissa (email: info@satollo.net, web: http://www.satollo.net)
*/

define('NEWSLETTER', '2.0.0');
define('NEWSLETTER_STATUS_CONFIRMED', 'C');
define('NEWSLETTER_STATUS_UNCONFIRMED', 'S');

define('NEWSLETTER_MEMORY_LIMIT', 64000000); // bytes
define('NEWSLETTER_WAIT_TIMEOUT', 300); // seconds
define('NEWSLETTER_TIME_LIMIT', 3600); // seconds

function newsletter_set_limits() {
    global $wpdb;
    newsletter_debug(__FUNCTION__, "Setting limits");
    set_time_limit(NEWSLETTER_TIME_LIMIT);
    $wpdb->query("set session wait_timeout=" . NEWSLETTER_WAIT_TIMEOUT);
    ini_set('memory_limit', NEWSLETTER_MEMORY_LIMIT);
}

$newsletter_options_main = get_option('newsletter_main', array());
$newsletter_options_i18n = get_option('newsletter_i18n', array());

// Labels loading, after that $newsletter_labels is filled
$newsletter_labels = null;
@include_once(dirname(__FILE__) . '/languages/en_US.php');
if (WPLANG != '') @include_once(dirname(__FILE__) . '/languages/' . WPLANG . '.php');
@include_once(ABSPATH . 'wp-content/plugins/newsletter-custom/languages/en_US.php');
if (WPLANG != '') @include_once(ABSPATH . 'wp-content/plugins/newsletter-custom/languages/' . WPLANG . '.php');

require_once(dirname(__FILE__) . '/widget.php');

// Working global variables
$newsletter_step = 'subscription';
$newsletter_subscriber = null;
$newsletter_batch = null;

function newsletter_label($name, $default='') {
    global $newsletter_labels;

    if (isset($newsletter_labels[$name])) return $newsletter_labels[$name];
    return $default;
}

function newsletter_echo($name, $default) {
    echo newsletter_label($name, $default);
}

function newsletter_request($name, $default=null ) {
    if (!isset($_REQUEST[$name])) return $default;
    return stripslashes_deep($_REQUEST[$name]);
}

function newsletter_subscribers_count() {
    global $wpdb;

    return $wpdb->get_var("select count(*) from " . $wpdb->prefix . "newsletter where status='C'");
}

/*******************************************************************************
 * EMBEDDED FORM
 ******************************************************************************/

add_shortcode('newsletter_form', 'newsletter_form_call');

function newsletter_embed_form($form=null) {
    global $newsletter_options_main, $newsletter_options_i18n;

    $buffer = newsletter_form($form, 'embedded_form');
    $buffer = newsletter_replace_labels($buffer);
    $buffer = str_replace('{newsletter_url}', $newsletter_options_main['url'], $buffer);

    echo $buffer;
}

function newsletter_form_call($attrs, $content=null) {
    global $newsletter_options_main;

    $buffer = newsletter_form($attrs['form'], 'embedded_form');
    $buffer = newsletter_replace_labels($buffer);
    $buffer = str_replace('{newsletter_url}', $newsletter_options_main['url'], $buffer);

    return $buffer;
}


/*******************************************************************************
 * SUBSCRIPTION FORM
 ******************************************************************************/

add_shortcode('newsletter', 'newsletter_call');

/*
 * Called on pages containing [newsletter] short tag.
 */
function newsletter_call($attrs, $content=null) {
    global $newsletter_step, $newsletter_subscriber, $newsletter_options_main, $newsletter_options_i18n;

    $options = get_option('newsletter');

    $buffer = '';

    if ($newsletter_step == 'profile') {
        //$buffer .= $options['subscription_text'];
        $form = newsletter_form(null, 'profile_form', true);
        $buffer .= $form;
//        if ($newsletter_step == 'profile_saved') {
//            $buffer .= '<script>alert(")'
//        }
    }

    // When a user is starting the subscription process
    if ($newsletter_step == 'subscription') {
        $buffer .= $options['subscription_text'];
        $form = newsletter_replace_labels(newsletter_form($attrs['form'], 'subscription_form'));
        if (strpos($buffer, '{form}') !== false) $buffer = str_replace('{form}', $form, $buffer);
        else $buffer .= $form;
    }

    // When a user asked to subscribe and the connfirmation request has been sent
    if ($newsletter_step == 'subscribed') {
        $text = newsletter_replace($options['subscribed_text'], $newsletter_subscriber);
        $buffer .= $text;
    }

    if ($newsletter_step == 'confirmed') {
        $text = newsletter_replace($options['confirmed_text'], $newsletter_subscriber);
        $buffer .= $text;

        if (isset($options['confirmed_tracking'])) {
            ob_start();
            eval('?>' . $options['confirmed_tracking'] . "\n");
            $buffer .= ob_get_clean();
        }
    }

    // Here we are when an unsubscription is requested. There are two kind of unsubscription: the
    // ones with email and token, so the user has only to confire and the ones without
    // data, so the user is requested to insert his email. In the latter case an email
    // will be sent to the user with alink to confirm the email removal.
    if ($newsletter_step == 'unsubscription' || $newsletter_step == 'unsubscription_error') {
        $newsletter_subscriber = newsletter_get_subscriber($_REQUEST['ni']);
        $buffer = newsletter_replace($options['unsubscription_text'], $newsletter_subscriber);
        $url = newsletter_add_qs($newsletter_options_main['url'], 'na=uc&amp;ni=' . $newsletter_subscriber->id .
            '&amp;nt=' . $_REQUEST['nt']);
        $buffer = newsletter_replace_url($buffer, 'UNSUBSCRIPTION_CONFIRM_URL', $url);
    }

    // Last message shown to user to say good bye
    if ($newsletter_step == 'unsubscribed') {
        $text = $options['unsubscribed_text'];
        $text = newsletter_replace($text, $newsletter_subscriber);
        $buffer .= $text;
    }

    return $buffer;
}

/**
 * Sends out newsletters.
 *
 * I recipients is an array of subscribers, other parameters are ignored and a test
 * batch is started. This parameter has priority over all.
 *
 * If continue is true, the system try to continue a previous batch keeping its
 * configuration (eg. if it was a simulation or not).
 *
 * If continue is false, simulate indicates if the batch is a simulation and forces
 * the subscriber's email to a test one, as specified in the configuration.
 *
 * Return true if the batch is completed.
 */
function newsletter_send_batch() {
    global $wpdb, $newsletter_options_main, $newsletter_batch;

    newsletter_info(__FUNCTION__, 'Start');

    $options = get_option('newsletter');
    $options_email = get_option('newsletter_email');
    $newsletter_batch = newsletter_data_get('batch');

    //if ($batch['scheduled'] && isset($batch['time']) && (time()-$batch['time'])<NEWSLETTER_JOB_FREQUENCY) return true;

    if ($newsletter_batch == null) {
        newsletter_error(__FUNCTION__, 'No batch found');
        return;
    }

    newsletter_debug(__FUNCTION__, "Batch:\n" . print_r($newsletter_batch, true));

    $id = (int)$newsletter_batch['id'];
    $list = (int)$newsletter_batch['list'];
    $simulate = (bool)$newsletter_batch['simulate'];
    $scheduled = (bool)$newsletter_batch['scheduled']; // Used to avoid echo

    if ($scheduled) {
        $max = $options_email['scheduler_max']; // per hour from 2.0.0
        if (!is_numeric($max)) $max = 100;
        $max = floor($max/(3600/NEWSLETTER_JOB_FREQUENCY));
        if ($max == 0) $max = 1;
    }
    else {
        $max = $options_email['max'];
        if (!is_numeric($max)) $max = 100;
    }

    $query = "select * from " . $wpdb->prefix . "newsletter where status='C'";

    if ($list != 0) {
        $query .= " and list_" . $list . "=1";
    }
    $query .= " and id>" . $id . " order by id limit " . $max;

    newsletter_debug(__FUNCTION__, $query);

    $recipients = $wpdb->get_results($query);

    if (empty($recipients)) {
        newsletter_info(__FUNCTION__, 'Sending completed!');
        $newsletter_batch['completed'] = true;
        $newsletter_batch['message'] = 'All fine!';
        newsletter_data_set('batch', $newsletter_batch);
        return;
    }

    // For a new batch save some info
    if ($id == 0) {
        $count_query = "select count(*) from " . $wpdb->prefix . "newsletter where status='C'";
        if ($list != 0) {
            $count_query .= " and list_" . $list . "=1";
        }
        //newsletter_delete_batch_file();
        $newsletter_batch['total'] = $wpdb->get_var($count_query);
        $newsletter_batch['sent'] = 0;
        $newsletter_batch['completed'] = false;
        $newsletter_batch['message'] = 'Sending...';
    }

    newsletter_data_set('batch', $newsletter_batch);

    newsletter_set_limits();
    
    newsletter_debug(__FUNCTION__, "Limits set");

    $start_time = time();
    $max_time = (int)(ini_get('max_execution_time') * 0.8);
    $db_time = time();

    if (!$scheduled) {
        echo 'Sending to: <br />';
        flush();
    }

    if (isset($options_email['novisual'])) {
        $message = $options_email['message'];
    }
    else {
        $message = '<html><head><style type="text/css">' . newsletter_get_theme_css($options_email['theme']) .
            '</style></head><body>' . $options_email['message'] . '</body></html>';
    }

    //    add_action('phpmailer_init','newsletter_phpmailer_init');
    //    newsletter_mail_init();
    foreach ($recipients as $r) {

        $headers = array('List-Unsubscribe'=>'<' .
            newsletter_add_qs($newsletter_options_main['url'],
            'na=u&ni=' . $r->id . '&nt=' . $r->token) . '>');

        $m = newsletter_replace_all_urls($message, $r);

        $m = newsletter_replace($m, $r);

        if (isset($options_email['track']))
            $m = newsletter_relink($m, $r->id, $options_email['name']);

        $s = $options_email['subject'];
        $s = newsletter_replace($s, $r);

        if ($simulate) {
            $x = newsletter_mail($options_email['simulate_email'], $s, $m, true, $headers);
        }
        else {
            $x = newsletter_mail($r->email, $s, $m, true, $headers);
        }

        if (!$scheduled) {
            echo htmlspecialchars($r->name) . ' (' . $r->email . ') ';

            if ($x) {
                echo '[OK] - ';
                newsletter_debug(__FUNCTION__, 'Sent to ' . $r->id . ' success');
            } else {
                echo '[KO] - ';
                newsletter_debug(__FUNCTION__, 'Sent to ' . $r->id . ' failed');
            }
            flush();
        }

        $newsletter_batch['sent']++;
        $newsletter_batch['id'] = $r->id;

        // Try to avoid database timeout
        if (time()-$db_time > 15) {
            newsletter_debug(__FUNCTION__, 'Batch saving to avoid database timeout');
            $db_time = time();
            newsletter_data_set('batch', $newsletter_batch);
        }

        // Timeout check, max time is zero if set_time_limit works
        if (($max_time != 0 && (time()-$start_time) > $max_time)) {
            newsletter_info(__FUNCTION__, 'Batch saving max time limit reached');
            $newsletter_batch['message'] = 'Batch max time limit reached (it is ok)';
            newsletter_data_set('batch', $newsletter_batch);
            return;
        }
    }
    newsletter_data_set('batch', $newsletter_batch);
}

/*
 * Function called by cron to send out newsletter when a user start the automated
 * sending process.
 */
define('NEWSLETTER_JOB_FREQUENCY', 300);
add_action('newsletter_job', 'newsletter_job');
function newsletter_job() {
    global $newsletter_batch;

    newsletter_data_lock();
    $job = newsletter_data_get('job', array('time'=>0));
    newsletter_error(__FUNCTION__, print_r($job, true));
    if ((time()-$job['time'])<NEWSLETTER_JOB_FREQUENCY) {
        newsletter_data_unlock();
        newsletter_error(__FUNCTION__, 'Job called too quickly...');
        return;
    }
    $job['time'] = time();
    newsletter_data_set('job', $job);
    newsletter_data_unlock();

    newsletter_send_batch();

    if ($newsletter_batch['completed'] === false)
        wp_schedule_single_event(time()+NEWSLETTER_JOB_FREQUENCY, 'newsletter_job');
}


/**
 * Send a set of test emails to a list of recipents. The recipients are created
 * in the composer page using the test addresses.
 */
function newsletter_send_test() {
    global $wpdb;

    $recipients = newsletter_get_test_subscribers();

    newsletter_info(__FUNCTION__, 'Start');

    $options = get_option('newsletter');
    $options_email = get_option('newsletter_email');

    @set_time_limit(NEWSLETTER_TIME_LIMIT);

    echo 'Sending to: <br />';

    if (isset($options_email['novisual'])) {
        $message = $options_email['message'];
    }
    else {
        $message = '<html><head><style type="text/css">' . newsletter_get_theme_css($options_email['theme']) .
            '</style></head><body>' . $options_email['message'] . '</body></html>';
    }

    foreach ($recipients as $r) {

        $m = newsletter_replace_all_urls($message, $r);
        $m = newsletter_replace($m, $r);

        if (isset($options_email['track']))
            $m = newsletter_relink($m, $r->id, $options_email['name']);

        $s = $options_email['subject'];
        $s = newsletter_replace($s, $r);

        $x = newsletter_mail($r->email, $s, $m, true);

        echo htmlspecialchars($r->name) . ' (' . $r->email . ') ';
        flush();

        if ($x) {
            echo '[OK] -- ';
            newsletter_debug(__FUNCTION__, 'Sent to ' . $r->id . ' success');
        } else {
            echo '[KO] -- ';
            newsletter_debug(__FUNCTION__, 'Sent to ' . $r->id . ' failed');
        }
    }
}

/**
 * Add a request of newsletter subscription into the database with status "S" (waiting
 * confirmation) and sends out the confirmation request email to the subscriber.
 * The email will contain an URL (or link) the user has to follow to complete the
 * subscription (double opt-in).
 */
function newsletter_subscribe($email, $name='', $profile=null, $lists=null) {
    global $wpdb, $newsletter_subscriber, $newsletter_options_main;

    $options = get_option('newsletter');

    $email = newsletter_normalize_email($email);

    $name = newsletter_normalize_name($name);

    $list = 0;

    if ($profile == null) $profile = array();

    // Check if this email is already in our database: if so, just resend the
    // confirmation email.
    $newsletter_subscriber = newsletter_get_subscriber_by_email($email, $list);
    if (!$newsletter_subscriber) {

        $token = md5(rand());

        $data = array(
            'email'=>$email,
            'name'=>$name,
            'token'=>$token,
            'list'=>$list,
            'status'=>'S'
        );

        // Lists
        $options_lists = get_option('newsletter_lists', array('enabled'=>0));
        if ($options_lists['enabled'] == 1 && is_array($lists)) {
            for ($i=1; $i<=9; $i++) {
                if (empty($options_lists['name_' . $i])) continue;
                if ($options_lists['type_' . $i] != 'public') continue;
                if (!in_array($i, $lists)) continue;
                $data['list_' . $i] = 1;
            }
        }

        @$wpdb->insert($wpdb->prefix . 'newsletter', $data);
        $id = $wpdb->insert_id;

        // Profile saving
        foreach ($profile as $key=>$value) {
            @$wpdb->insert($wpdb->prefix . 'newsletter_profiles', array(
                'newsletter_id'=>$id,
                'name'=>$key,
                'value'=>$value));
        }

        // Send the welcome email, too
        if (isset($options['noconfirmation'])) newsletter_confirm($id, $token);
        else {
            $newsletter_subscriber = newsletter_get_subscriber($id);
            newsletter_send_confirmation($newsletter_subscriber);
        }


    }
    // Email already registered
    else {
        if (isset($options['noconfirmation'])) {
            newsletter_send_welcome($newsletter_subscriber);
        }
        else {
            newsletter_send_confirmation($newsletter_subscriber);
        }
    }

    if ($newsletter_options_main['notify'] == 1) {
        $message = 'There is a new subscriber to ' . get_option('blogname') . ' newsletter:' . "\n\n" .
            $name . ' <' . $email . '>' . "\n\n" .
            'Have a nice day,' . "\n" . 'your Newsletter plugin.';

        $subject = 'New subscription';
        newsletter_notify_admin($subject, $message);
    }
}


function newsletter_save($subscriber) {
    global $wpdb;

    $email = newsletter_normalize_email($email);
    $name = newsletter_normalize_name($name);
    if (isset($subscriber['id'])) {
        $wpdb->query($wpdb->prepare("update " . $wpdb->prefix . "newsletter set email=%s, name=%s," .
            "list_1=%d,list_2=%d,list_3=%d,list_4=%d,list_5=%d,list_6=%d,list_7=%d,list_8=%d,list_9=%d where id=%d",
            $subscriber['email'], $subscriber['name'],
            $subscriber['list_1'], $subscriber['list_2'], $subscriber['list_3'],
            $subscriber['list_4'], $subscriber['list_5'], $subscriber['list_6'],
            $subscriber['list_7'], $subscriber['list_8'], $subscriber['list_9'],
            $subscriber['id']));
    }
    else {
        $subscriber['status'] = 'C';
        $subscriber['token'] = md5(rand());

        $wpdb->insert($wpdb->prefix . 'newsletter', $subscriber);
    }
}


/**
 * Sends the confirmation message.
 */
function newsletter_send_confirmation($subscriber) {
    global $newsletter_options_main;

    $options = get_option('newsletter');

    newsletter_debug(__FUNCTION__, "Confirmation request to:\n" . print_r($subscriber, true));

    $message = newsletter_create_message($options['confirmation_message']);
    $message = newsletter_replace($message, $subscriber);
    $message = newsletter_replace_all_urls($message, $subscriber);

    $subject = newsletter_replace($options['confirmation_subject'], $subscriber);

    newsletter_mail($subscriber->email, $subject, $message);
}

/*
 * Returns a subscriber as object identified by $id and $token, checking the token
 * matching. Die if there is no such user. $token is mandatory.
 */
function newsletter_get_subscriber_strict($id, $token) {
    if (is_null($token)) {
        newsletter_fatal(__FUNCTION__, 'Ivalid token');
        return null;
    }
    $s = newsletter_get_subscriber($id, $token);
    if (is_null($s)) {
        newsletter_fatal(__FUNCTION__, 'Subscriber not found or invalid token');
    }
    return $s;
}

/**
 * Returns a subscriber as object. If $token is specified token matching is
 * controlled, else only $id is used to retrieve the subscriber data.
 * If not found or token not match (when specified) returns null.
 */
function newsletter_get_subscriber($id, $token=null, $check_token=false) {
    global $wpdb;

    $recipients = $wpdb->get_results($wpdb->prepare("select * from " . $wpdb->prefix .
        "newsletter where id=%d", $id));
    if (!$recipients) return null;
    if ((!is_null($token) || $check_token) && $recipients[0]->token != $token) return null;
    return $recipients[0];

}

/*
 * Return a subscriber (as object) by it's email. Email field is unique in the database
 * with list field (no more used and may be already removed). Return null
 * id not subscriber was found.
 */
function newsletter_get_subscriber_by_email($email) {
    global $wpdb;

    $recipients = $wpdb->get_results($wpdb->prepare("select * from " . $wpdb->prefix .
        "newsletter where email=%s", $email));
    if (!$recipients) return null;
    return $recipients[0];
}

function newsletter_update_profile($profile) {
    global $newsletter_subscriber, $wpdb;

    foreach ($profile as $name=>$value) {
        $query = $wpdb->prepare("insert into " . $wpdb->prefix . "newsletter_profiles (newsletter_id,name,value) values " .
            "(%d,%s,%s) on duplicate key update value=%s", $newsletter_subscriber->id, $name, $value, $value);
        $wpdb->query($query);
    }
    return true;

}

function newsletter_search($text, $status='', $order='email') {
    global $wpdb;

    if (empty($order)) $order = 'email';
    if ($order == 'id') $order = 'id desc';

    $query = "select * from " . $wpdb->prefix . "newsletter where 1=1";
    if (!empty($status)) {
        $query .= " and status='" . $wpdb->escape($status) . "'";
    }

    if ($text == '') {
        $recipients = $wpdb->get_results($query . " order by " . $order . ' limit 100');
    }
    else {
        $recipients = $wpdb->get_results($query . " and email like '%" .
            $wpdb->escape($text) . "%' or name like '%" . $wpdb->escape($text) . "%' order by " . $order . ' limit 100');
    }
    if (!$recipients) return null;
    return $recipients;
}

define('NEWSLETTER_MAX_TEST_SUBSCRIBERS', 5);
function newsletter_get_test_subscribers() {
    global $newsletter_options_main;

    $subscribers = array();
    for ($i=0; $i<NEWSLETTER_MAX_TEST_SUBSCRIBERS; $i++) {
        if (!empty($newsletter_options_main['test_email_' . $i])) {
            $subscriber = new stdClass();
            $subscriber->name = $newsletter_options_main['test_name_' . $i];
            $subscriber->email = $newsletter_options_main['test_email_' . $i];
            $subscriber->token = 'notokenitsatest';
            $subscriber->id = 0;
            $subscribers[] = $subscriber;
        }
    }
    return $subscribers;
}

/**
 * Normalize an email address, making it lowercase and trimming spaces.
 */
function newsletter_normalize_email($email) {
    return strtolower(trim($email));
}

function newsletter_normalize_name($name) {
    $name = str_replace(';', ' ', $name);
    $name = strip_tags($name);
    return $name;
}

add_action('init', 'newsletter_init');
/**
 * Intercept the request parameters which drive the subscription and unsubscription
 * process.
 */
function newsletter_init() {
    global $newsletter_step, $wpdb, $newsletter_subscriber;
    global $hyper_cache_stop, $newsletter_options_main;

    // "na" always is the action to be performed - stands for "newsletter action"
    $action = $_REQUEST['na'];
    if (!$action) return;

    $hyper_cache_stop = true;

    $options = get_option('newsletter');

    // Subscription request from a subscription form (in page or widget), can be
    // a direct subscription with no confirmation
    if ($action == 's') {
        if (!newsletter_is_email($_REQUEST['ne'])) {
            newsletter_die(newsletter_label('error_email'));
        }
        // If not set, the subscription form is not requesting the name, so we do not
        // raise errors.
        if (isset($_REQUEST['nn'])) {
            if (trim($_REQUEST['nn']) == '') {
                newsletter_die(newsletter_label('error_name'));
            }
        }
        else {
            $_REQUEST['nn'] = '';
        }

        $profile1 = $_REQUEST['np'];
        if (!isset($profile1) || !is_array($profile1)) $profile1 = array();

        // keys starting with "_" are removed because used internally
        $profile = array();
        foreach ($profile1 as $k=>$v) {
            if ($k[0] == '_') continue;
            $profile[$k] = stripslashes($v);
        }

        $profile['_ip'] = $_SERVER['REMOTE_ADDR'];
        $profile['_referrer'] = $_SERVER['HTTP_REFERER'];

        newsletter_subscribe($_REQUEST['ne'], stripslashes($_REQUEST['nn']), $profile, $_REQUEST['nl']);

        if (isset($options['noconfirmation'])) {
            $newsletter_step = 'confirmed';
        }
        else {
            $newsletter_step = 'subscribed';
        }
        return;
    }

    // A request to confirm a subscription
    if ($action == 'c') {
        $id = $_REQUEST['ni'];
        newsletter_confirm($id, $_REQUEST['nt']);
        header('Location: ' . newsletter_add_qs($newsletter_options_main['url'], 'na=cs&ni=' . $id . '&nt=' . $_REQUEST['nt'], false));
        newsletter_die();
    }

    // Show the confirmed message after a redirection (to avoid mutiple email sending).
    // Redirect is sent by action "c".
    if ($action == 'cs') {
        $newsletter_subscriber = newsletter_get_subscriber_strict($_REQUEST['ni'], $_REQUEST['nt']);
        $newsletter_step = 'confirmed';
        return;
    }

    // Unsubscription
    if ($action == 'u') {
        $newsletter_step = 'unsubscription';
        return;
    }

    // User confirmed he want to unsubscribe clicking the link on unsubscription
    // page
    if ($action == 'uc') {
        newsletter_unsubscribe($_REQUEST['ni'], $_REQUEST['nt']);
        $newsletter_step = 'unsubscribed';
        return;
    }

    //--------------------------------------------------------------------------

    // Follow up subscription
    if ($action == 'fs') {
        newsletter_followup_subscribe($_REQUEST['ni'], $_REQUEST['nt']);
        newsletter_die('Follow up subscription successful. <a href="' . get_option('home') . '">Go to home page</a>');
        $newsletter_step = 'followup_subscribed';
        return;
    }

    if ($action == 'fu') {
        newsletter_followup_unsubscribe($_REQUEST['ni'], $_REQUEST['nt']);
        newsletter_die('Follow up unsubscription successful. <a href="' . get_option('home') . '">Go to home page</a>');
        $newsletter_step = 'followup_unsubscribed';
        return;
    }

    // Feed subscription/unbsubscription
    if ($action == 'es') {
        newsletter_feed_subscribe($_REQUEST['ni'], $_REQUEST['nt']);
        newsletter_die('Feed subscription successful. <a href="' . get_option('home') . '">Go to home page</a>');
        $newsletter_step = 'feed_subscribed';
        return;
    }

    if ($action == 'eu') {
        newsletter_feed_unsubscribe($_REQUEST['ni'], $_REQUEST['nt']);
        newsletter_die('Feed unsubscription successful. <a href="' . get_option('home') . '">Go to home page</a>');
        $newsletter_step = 'feed_unsubscribed';
        return;
    }

    if ($action == 'r') {
        $url = base64_decode($_GET['nr']);
        $url = explode(';', $url, 4);

        $wpdb->insert($wpdb->prefix . 'newsletter_stats',
            array(
            'newsletter'=>$url[0],
            'newsletter_id'=>$url[1],
            'url'=>$url[3],
            'anchor'=>$url[2]
            )
        );

        header('Location: ' . $url[3]);
        die();
    }

    // Lists subscription and unsubscription
    if ($action == 'ls') {
        newsletter_list_subscribe($_REQUEST['ni'], $_REQUEST['nt'], $_REQUEST['nl']);
        newsletter_die('List subscription successful. <a href="' . get_option('home') . '">Go to home page</a>');
        $newsletter_step = 'followup_subscribed';
    }

    if ($action == 'lu') {
        newsletter_list_unsubscribe($_REQUEST['ni'], $_REQUEST['nt'], $_REQUEST['nl']);
        newsletter_die('List unsubscription successful. <a href="' . get_option('home') . '">Go to home page</a>');
        $newsletter_step = 'followup_unsubscribed';
    }

    // Actions below need valid subscriber identification data
    $id = $_REQUEST['ni'];
    $token = $_REQUEST['nt'];
    $newsletter_subscriber = newsletter_get_subscriber_strict($id, $token); // Global (find or die)

    // Member access
    if ($action == 'm') {
        $newsletter_protect_options = get_option('newsletter_protect');
        $days = $newsletter_protect_options['cookie_days'];
        if (!is_numeric($days)) $days = 30;
        setcookie('newsletter', $id . '-' . $token, time()+60*60*24*$days, '/');

        header('Location: ' . $newsletter_protect_options['url']);
        die();
    }

    // Profile update
    if ($action == 'p') {       
        $profile1 = $_REQUEST['np'];
        if (isset($profile1) && is_array($profile1)) {
        // keys starting with "_" are removed because used internally
            $profile = array();
            foreach ($profile1 as $k=>$v) {
                if ($k[0] == '_') continue;
                $profile[$k] = $v;
            }
            newsletter_update_profile($profile);
            header('Location: ' . get_option('home'));
            newsletter_die();
        }
        return;
    }

    // Profile edit
    if ($action == 'pe') {
        $newsletter_step = 'profile';
        return;
    }

    // Receive the name and the lists
    if ($action == 'ps') {
        $data = array();
        if (!newsletter_is_email($_REQUEST['ne'])) {
            newsletter_die(newsletter_label('error_email'));
        }

        $data['email'] = newsletter_normalize_email(stripslashes($_REQUEST['ne']));
        $name = newsletter_normalize_name(stripslashes($_REQUEST['nn']));
        if (!empty($name)) $data['name'] = $name;

        // Lists
        $lists = $_REQUEST['nl'];
        $options_lists = get_option('newsletter_lists', array('enabled'=>0));
        if ($options_lists['enabled'] == 1 && is_array($lists)) {
            for ($i=1; $i<=9; $i++) {
                if (empty($options_lists['name_' . $i])) continue;
                if ($options_lists['type_' . $i] != 'public') continue;
                if (!in_array($i, $lists)) 
                    $data['list_' . $i] = 0;
                else
                    $data['list_' . $i] = 1;
            }
        }

        $wpdb->update($wpdb->prefix . 'newsletter', $data, array('id'=>$id));

        // Profile
        // ???

        $newsletter_subscriber = newsletter_get_subscriber_strict($id, $token);
        
        $newsletter_step = 'profile';
        return;
    }
}


/**
 * Deletes a subscription (no way back). Fills the global $newsletter_subscriber
 * with subscriber data to be used to build up messages.
 */
function newsletter_unsubscribe($id, $token) {
    global $newsletter_subscriber, $wpdb;

    $newsletter_subscriber = newsletter_get_subscriber_strict($id, $token);

    $wpdb->query($wpdb->prepare("delete from " . $wpdb->prefix . "newsletter where id=%d" .
        " and token=%s", $id, $token));

    $options = get_option('newsletter');

    $message = newsletter_create_message($options['unsubscribed_message']);
    $message = newsletter_replace($message, $newsletter_subscriber);

    $subject = newsletter_replace($options['unsubscribed_subject'], $newsletter_subscriber);


    newsletter_mail($newsletter_subscriber->email, $subject, $message);


    if ($newsletter_options_main['notify'] == 1) {
        $message = 'There is an unsubscription to ' . get_option('blogname') . ' newsletter:' . "\n\n" .
            $newsletter_subscriber->name . ' <' . $newsletter_subscriber->email . '>' . "\n\n" .
            'Have a nice day,' . "\n" . 'your Newsletter plugin.';

        $subject = 'Unsubscription';
        newsletter_notify_admin($subject, $message);
    }
}

/*
 * Deletes a specific subscription. Called only from the admin panel.
 */
function newsletter_delete($id) {
    global $wpdb;

    $wpdb->query($wpdb->prepare("delete from " . $wpdb->prefix . "newsletter where id=%d", $id));
}

function newsletter_delete_all($status=null) {
    global $wpdb;

    if ($status == null) {
        $wpdb->query("delete from " . $wpdb->prefix . "newsletter");
    }
    else {
        $wpdb->query("delete from " . $wpdb->prefix . "newsletter where status='" . $wpdb->escape($status) . "'");
    }
}

function newsletter_set_status_all($status) {
    global $wpdb;

    $wpdb->query("update " . $wpdb->prefix . "newsletter set status='" . $status . "'");
}

/**
 * Confirms a subscription identified by id and token, changing it's status on
 * database. Fill the global $newsletter_subscriber with user data.
 * If the subscription id already confirmed, the welcome email is still sent to
 * the subscriber (the welcome email can contains somthing reserved to the user
 * and he may has lost it).
 * If id and token do not match, the function does nothing.
 */
function newsletter_confirm($id, $token) {
    global $wpdb, $newsletter_subscriber;

    newsletter_info(__FUNCTION__, "Starting confirmation of subscriber " . $id);

    $newsletter_subscriber = newsletter_get_subscriber_strict($id, $token);

    newsletter_debug(__FUNCTION__, "Confirming subscriber:\n" . print_r($newsletter_subscriber, true));

    $count = $wpdb->query($wpdb->prepare("update " . $wpdb->prefix . "newsletter set status='C' where id=%d", $id));

    $options_followup = get_option('newsletter_followup', array());
    if ($options_followup['add_new'] == 1) {
        newsletter_followup_subscribe($id, $token);
    }

    $options_feed = get_option('newsletter_feed', array());
    newsletter_debug(__FUNCTION__, "Options feed:\n" . print_r($options_feed, true));

    if ($options_feed['add_new'] == 1) {
        newsletter_feed_subscribe($id, $token);
    }

    newsletter_send_welcome($newsletter_subscriber);
}

function newsletter_send_welcome($subscriber) {
    global $newsletter_options_main;

    $options = get_option('newsletter');

    newsletter_debug(__FUNCTION__, "Welcome message to:\n" . print_r($subscriber, true));

    $message = newsletter_create_message($options['confirmed_message']);
    $message = newsletter_replace_all_urls($message, $subscriber);
    $message = newsletter_replace($message, $subscriber);

    $subject = newsletter_replace($options['confirmed_subject'], $subscriber);

    newsletter_mail($subscriber->email, $subject, $message);
}

/*
 * Changes the status of a subscription identified by its id.
 */
function newsletter_set_status($id, $status) {
    global $wpdb;

    $wpdb->query($wpdb->prepare("update " . $wpdb->prefix . "newsletter set status=%s where id=%d", $status, $id));
}

/*
 * Sends a notification message to the blog admin.
 */
function newsletter_notify_admin(&$subject, &$message) {
    $to = get_option('admin_email');
    $headers = "Content-type: text/plain; charset=UTF-8\n";
    wp_mail($to, '[' . get_option('blogname') . '] ' . $subject, $message, $headers);
}

/**
 * Sends out an email (html or text). From email and name is retreived from
 * Newsletter plugin options. Return false on error. If the subject is empty
 * no email is sent out without warning.
 * The function uses wp_mail() to really send the message.
 */
function newsletter_mail($to, $subject, $message, $html=true, $headers=array()) {
    global $newsletter_mailer, $newsletter_options_main;

    if (empty($subject)) {
        newsletter_debug(__FUNCTION__, 'Subject empty, skipped');
        return true;
    }

    newsletter_mail_init();

    $newsletter_mailer->IsHTML($html);
    $newsletter_mailer->Body = $message;

    $newsletter_mailer->Subject = $subject;

    $newsletter_mailer->ClearCustomHeaders();
    foreach ($headers as $key=>$value) {
        $newsletter_mailer->AddCustomHeader($key . ': ' . $value);
    }

    $newsletter_mailer->ClearAddresses();
    $newsletter_mailer->AddAddress($to);
    $newsletter_mailer->Send();
    if ($newsletter_mailer->IsError()) {
        newsletter_error(__FUNCTION__, $newsletter_mailer->ErrorInfo);
        return false;
    }
    return true;
}


register_activation_hook(__FILE__, 'newsletter_activate');
function newsletter_activate() {
    global $wpdb;

    $options = get_option('newsletter', array());
    $options_main = get_option('newsletter_main', array());
    $options_i18n = get_option('newsletter_i18n', array());

    // Load the default options
    @include_once(dirname(__FILE__) . '/languages/en_US_options.php');
    if (WPLANG != '') @include_once(dirname(__FILE__) . '/languages/' . WPLANG . '_options.php');

    $options = array_merge($newsletter_default_options, $options);
    $options_main = array_merge($newsletter_default_options_main, $options_main);
    $options_i18n = array_merge($newsletter_default_options_i18n, $options_i18n);

    // SQL to create the table
    $sql = 'create table if not exists ' . $wpdb->prefix . 'newsletter (
        `id` int not null auto_increment primary key,
        `name` varchar (100) not null default \'\',
        `email` varchar (100) not null default \'\',
        `token` varchar (50) not null default \'\',
        `status` varchar (1) not null default \'S\',
        `group` int not null default 0,
        `profile` text
        ) DEFAULT CHARSET=utf8';

    @$wpdb->query($sql);

    // DUPLICATES
    $sql = "select email from " . $wpdb->prefix . "newsletter group by email having count(*)>1";
    $res = @$wpdb->get_results($sql);

    foreach($res as $r) {
    //newsletter_log($r->email);
    //echo $r->email;
        $sql = "select id from " . $wpdb->prefix . "newsletter where email='" . $r->email . "'";
        $ss = @$wpdb->get_results($sql);
        //newsletter_log(print_r($ss, true));
        for ($i=1; $i<count($ss); $i++) {
            $sql = "delete from " . $wpdb->prefix . "newsletter where id=" . $ss[$i]->id;
            @$wpdb->query($sql);
        }
    }

    $sql = 'create table if not exists ' . $wpdb->prefix . 'newsletter_work (
        `name` varchar (50) not null default \'\',
        `value` text,
        `updated` bigint default 0,
        primary key (name)
        ) DEFAULT CHARSET=utf8';
    @$wpdb->query($sql);

    $sql = 'create table if not exists ' . $wpdb->prefix . 'newsletter_profiles (
        `newsletter_id` int not null,
        `name` varchar (100) not null default \'\',
        `value` text,
        primary key (newsletter_id, name)
        ) DEFAULT CHARSET=utf8';

    @$wpdb->query($sql);

    $sql = "alter table " . $wpdb->prefix . "newsletter drop primary key";
    @$wpdb->query($sql);

    $sql = "alter table " . $wpdb->prefix . "newsletter add column id int not null auto_increment primary key";
    @$wpdb->query($sql);

    $sql = "alter table " . $wpdb->prefix . "newsletter add column list int not null default 0";
    @$wpdb->query($sql);

    $sql = "alter table " . $wpdb->prefix . "newsletter drop key email_token";
    @$wpdb->query($sql);

    $sql = "alter table " . $wpdb->prefix . "newsletter drop key email_list";
    @$wpdb->query($sql);

    $sql = "ALTER TABLE " . $wpdb->prefix . "newsletter ADD UNIQUE INDEX email (email)";
    @$wpdb->query($sql);

    $sql = "alter table " . $wpdb->prefix . "newsletter add column profile text";
    @$wpdb->query($sql);

    $sql = "alter table " . $wpdb->prefix . "newsletter add column created timestamp not null default current_timestamp";
    @$wpdb->query($sql);


    $sql = "alter table " . $wpdb->prefix . "newsletter CONVERT TO CHARACTER SET utf8";
    @$wpdb->query($sql);

    $sql = "alter table " . $wpdb->prefix . "newsletter_profiles CONVERT TO CHARACTER SET utf8";
    @$wpdb->query($sql);

    $sql = "alter table " . $wpdb->prefix . "newsletter add column followup_time bigint not null default 0";
    @$wpdb->query($sql);

    $sql = "alter table " . $wpdb->prefix . "newsletter add column followup_step tinyint not null default 0";
    @$wpdb->query($sql);

    $sql = "alter table " . $wpdb->prefix . "newsletter add column followup tinyint not null default 0";
    @$wpdb->query($sql);

    $sql = "alter table " . $wpdb->prefix . "newsletter add column feed tinyint not null default 0";
    @$wpdb->query($sql);

    for ($i=1; $i<=9; $i++) {
        $sql = "alter table " . $wpdb->prefix . "newsletter add column list_" . $i . " tinyint not null default 0";
        @$wpdb->query($sql);
    }

    $sql = 'create table if not exists ' . $wpdb->prefix . 'newsletter_emails (
        `id` int auto_increment,
        `subject` varchar(255) not null default \'\',
        `message` text,
        `name` varchar(255) not null default \'\',
        `subject2` varchar(255) not null default \'\',
        `message2` text,
        `name2` varchar(255) not null default \'\',
        `date` timestamp not null default CURRENT_TIMESTAMP,
        primary key (id)
        )';

    @$wpdb->query($sql);

    // Statistics table
    $sql = 'create table if not exists ' . $wpdb->prefix . 'newsletter_stats (
        `id` int auto_increment,
        `newsletter_id` int not null default 0,
        `date` timestamp not null default CURRENT_TIMESTAMP,
        `url` varchar(255) not null default \'\',
        `newsletter` varchar(50) not null default \'\',
        `anchor` varchar(200) not null default \'\',
        primary key (id)
        ) DEFAULT CHARSET=utf8';

    @$wpdb->query($sql);

    $sql = "update " . $wpdb->prefix . "options set autoload='no' where option_name like 'newsletter%'";
    @$wpdb->query($sql);

    newsletter_info(__FUNCTION__, 'Activated');

    $options_main['version'] = NEWSLETTER;
    update_option('newsletter_main', $options_main);
    update_option('newsletter', $options);
    update_option('newsletter_i18n', $options_i18n);
}


add_action('admin_menu', 'newsletter_admin_menu');
function newsletter_admin_menu() {

    global $newsletter_options_main;

    $level = $newsletter_options_main['editor']?7:10;

    if (function_exists('add_menu_page')) {
        add_menu_page('Newsletter Pro', 'Newsletter Pro', $level, 'newsletter-pro/main.php', '', '');
    }

    if (function_exists('add_submenu_page')) {
        add_submenu_page('newsletter-pro/main.php', 'Configuration', 'Configuration', $level, 'newsletter-pro/main.php');
        add_submenu_page('newsletter-pro/main.php', 'Subscription', 'Subscription', $level, 'newsletter-pro/options.php');
        add_submenu_page('newsletter-pro/main.php', 'Composer', 'Composer', $level, 'newsletter-pro/newsletter.php');
        add_submenu_page('newsletter-pro/main.php', 'Lists', 'Lists', $level, 'newsletter-pro/lists.php');
        add_submenu_page('newsletter-pro/main.php', 'Email archive', 'Email archive', $level, 'newsletter-pro/emails.php');
        if ($newsletter_options_main['mode'] == 1) {
            add_submenu_page('newsletter-pro/main.php', 'Feed by mail', 'Feed by mail', $level, 'newsletter-pro/feed.php');
            add_submenu_page('newsletter-pro/main.php', 'Locked content', 'Locked content', $level, 'newsletter-pro/protect.php');
        }
        add_submenu_page('newsletter-pro/main.php', 'Statistics', 'Statistics', $level, 'newsletter-pro/statistics.php');
        add_submenu_page('newsletter-pro/main.php', 'Subscribers', 'Subscribers', $level, 'newsletter-pro/manage.php');
        add_submenu_page('newsletter-pro/main.php', 'Follow Up', 'Follow Up', $level, 'newsletter-pro/followup.php');
        add_submenu_page('newsletter-pro/main.php', 'Import', 'Import', $level, 'newsletter-pro/import.php');
        add_submenu_page('newsletter-pro/main.php', 'Export', 'Export', $level, 'newsletter-pro/export.php');
        add_submenu_page('newsletter-pro/main.php', 'Forms', 'Forms', $level, 'newsletter-pro/forms.php');
        add_submenu_page('newsletter-pro/main.php', 'SMTP', 'SMTP', $level, 'newsletter-pro/smtp.php');
        add_submenu_page('newsletter-pro/main.php', 'Labels', 'Labels', $level, 'newsletter-pro/i18n.php');
        //add_submenu_page('newsletter-pro/main.php', 'Bounce', 'Bounce', $level, 'newsletter-pro/bounce.php');
        add_submenu_page('newsletter-pro/main.php', 'Update', 'Update', $level, 'newsletter-pro/convert.php');

        // Hidden sub menu
        add_submenu_page('newsletter-pro/statistics.php', 'Statistics Clicks', 'Statistics Clicks', $level, 'newsletter-pro/statistics-clicks.php');
        add_submenu_page('newsletter-pro/statistics.php', 'Statistics Profiles', 'Statistics Profiles', $level, 'newsletter-pro/statistics-profiles.php');
        add_submenu_page('newsletter-pro/statistics.php', 'Statistics Users', 'Statistics Users', $level, 'newsletter-pro/statistics-users.php');
    }
}

add_action('admin_head', 'newsletter_admin_head');
function newsletter_admin_head() {
    if (strpos($_GET['page'], 'newsletter-pro/') === 0) {
        echo '<link type="text/css" rel="stylesheet" href="' .
            get_option('siteurl') . '/wp-content/plugins/newsletter-pro/style.css"/>';
    }
}


/**
 * Fills a text with sunscriber data and blog data replacing some place holders.
 */
function newsletter_replace($text, $subscriber) {
    $text = str_replace('{home_url}', get_option('home'), $text);
    $text = str_replace('{blog_title}', get_option('blogname'), $text);
    $text = str_replace('{email}', $subscriber->email, $text);
    $text = str_replace('{id}', $subscriber->id, $text);
    $text = str_replace('{name}', $subscriber->name, $text);
    $text = str_replace('{token}', $subscriber->token, $text);
    $text = str_replace('%7Btoken%7D', $subscriber->token, $text);
    $text = str_replace('%7Bid%7D', $subscriber->id, $text);

    return $text;
}

/*******************************************************************************
 * WORKING DATA
 ******************************************************************************/
function newsletter_data_lock() {
    global $wpdb;
    $wpdb->query("lock tables " . $wpdb->prefix . "newsletter_work write");
}

function newsletter_data_unlock() {
    global $wpdb;
    $wpdb->query("unlock tables");
}

function newsletter_data_set($name, $data) {
    global $wpdb;
    $data = $wpdb->escape(serialize($data));
    $wpdb->query("insert into " . $wpdb->prefix . "newsletter_work (name,value) values " .
        "('" . $name . "','" . $data . "') on duplicate key update value='" .
        $data . "'");
}

function newsletter_data_get($name, $def=null) {
    global $wpdb;
    $data = $wpdb->get_results("select value,updated from " . $wpdb->prefix . "newsletter_work where name='" .
        $name . "'");
    if (empty($data)) return $def;
    return unserialize($data[0]->value);
}

function newsletter_data_remove($name) {
    global $wpdb;
    return $wpdb->query("delete from " . $wpdb->prefix . "newsletter_work where name='" .
        $name . "'");
}

/*******************************************************************************
 * LISTS
 ******************************************************************************/
define('NEWSLETTER_LISTS_MAX', 9);
function newsletter_list_set($id, $list, $status) {
    global $wpdb;
    $list = 'list_' . $list;
    $a = array($list=>$status);
    $wpdb->update($wpdb->prefix . 'newsletter', $a, array('id'=>$id));
}

/*
 * Creates a checkbox set with all public newsletter list
 */
function newsletter_list_checkboxes() {
    global $newsletter_subscriber;

    $options_lists = get_option('newsletter_lists');
    if ($options_lists['enabled'] != 1) return '';
    $tmp = '';
    for ($i=1; $i<=NEWSLETTER_LISTS_MAX; $i++) {
        if (empty($options_lists['name_' . $i])) continue;
        if ($options_lists['type_' . $i] != 'public') continue;
        $tmp .= '<input type="checkbox" name="nl[]" value="' . $i . '"';
        $list = 'list_' . $i;
        if ($newsletter_subscriber->$list == 1) {
            $tmp .= ' checked';
        }
        $tmp .= '/> ' .
            htmlspecialchars($options_lists['name_' . $i]) . '<br />';
    }
    return $tmp;
}

/*
 * Checks if a list is public or not (returns true or false)
 */
function newsletter_list_is_public($list) {
    $options_lists = get_option('newsletter_lists');
    if (empty($options_lists['name_' . $list])) return false;
    if ($options_lists['type_' . $list] != 'public') return false;
    return true;
}

/*
 * Add the subscriber by id and token to the list, with strict
 * checking about subscriber existence, token matching and public availability
 * of the list.
 */
function newsletter_list_subscribe($id, $token, $list) {
    global $newsletter_subscriber;

    if (!newsletter_list_is_public($list)) newsletter_die('Ivalid list.');
    $newsletter_subscriber = newsletter_get_subscriber_strict($id, $token);
    newsletter_list_set($newsletter_subscriber->id, $list, 1);
}

/*
 * Remove a subscriber by id and token from a list.
 * See newsletter_list_subscribe for constrains.
 */
function newsletter_list_unsubscribe($id, $token, $list) {
    global $newsletter_subscriber;

    if (!newsletter_list_is_public($list)) newsletter_die('Ivalid list.');
    $newsletter_subscriber = newsletter_get_subscriber_strict($id, $token);
    newsletter_list_set($newsletter_subscriber->id, $list, 0);
}


/*******************************************************************************
 * UTILITIES
 ******************************************************************************/
function newsletter_add_qs($url, $qs, $amp=true) {
    if (strpos($url, '?') !== false) {
        if ($amp) return $url . '&amp;' . $qs;
        else return $url . '&' . $qs;
    }
    else return $url . '?' . $qs;
}

function newsletter_replace_all_urls($message, $subscriber) {
    global $newsletter_options_main;

    $base = $newsletter_options_main['url'];
    $id = $subscriber->id;
    $token = $subscriber->token;
    $id_token = '&amp;ni=' . $id . '&amp;nt=' . $token;

    $message = newsletter_replace_url($message, 'SUBSCRIPTION_CONFIRM_URL',
        newsletter_add_qs($base, 'na=c' . $id_token));

    $message = newsletter_replace_url($message, 'UNSUBSCRIPTION_URL',
        newsletter_add_qs($base, 'na=u' . $id_token));

    $message = newsletter_replace_url($message, 'FOLLOWUP_SUBSCRIPTION_URL',
        newsletter_add_qs($base, 'na=fs' . $id_token));
    $message = newsletter_replace_url($message, 'FOLLOWUP_UNSUBSCRIPTION_URL',
        newsletter_add_qs($base, 'na=fu' . $id_token));

    $message = newsletter_replace_url($message, 'FEED_SUBSCRIPTION_URL',
        newsletter_add_qs($base, 'na=es' . $id_token));
    $message = newsletter_replace_url($message, 'FEED_UNSUBSCRIPTION_URL',
        newsletter_add_qs($base, 'na=eu' . $id_token));

    $message = newsletter_replace_url($message, 'PROFILE_URL',
        newsletter_add_qs($base, 'na=pe' . $id_token));

    $message = newsletter_replace_url($message, 'UNLOCK_URL',
        newsletter_add_qs($base, 'na=m' . $id_token));
    
    for ($i=1; $i<=9; $i++) {
        $message = newsletter_replace_url($message, 'LIST_' . $i . '_SUBSCRIPTION_URL',
            newsletter_add_qs($base, 'na=ls&amp;nl=' . $i . $id_token));
        $message = newsletter_replace_url($message, 'LIST_' . $i . '_UNSUBSCRIPTION_URL',
            newsletter_add_qs($base, 'na=lu&amp;nl=' . $i . $id_token));
    }

    return $message;
}

/**
 * Replaces the URL placeholders. There are two kind of URL placeholders: the ones
 * lowercase and betweeb curly brakets and the ones all uppercase. The tag to be passed
 * is the one all uppercase but the lowercase one will also be replaced.
 */
function newsletter_replace_url($text, $tag, $url) {
    $home = get_option('home') . '/';
    $tag_lower = strtolower($tag);
    $text = str_replace($home . '{' . $tag_lower . '}', $url, $text);
    $text = str_replace($home . '%7B' . $tag_lower . '%7D', $url, $text);
    $text = str_replace('{' . $tag_lower . '}', $url, $text);

    // for compatibility
    $text = str_replace($home . $tag, $url, $text);

    return $text;
}

/*
 * Replaces i18n labels in texts and some other common tags.
 */
function newsletter_replace_labels($text) {
    global $newsletter_options_i18n, $newsletter_subscriber;

    foreach($newsletter_options_i18n as $k=>$v) {
        $text = str_replace('{label_' . $k . '}', $v, $text);
    }

    if ($newsletter_subscriber != null) {
        $text = str_replace('{nn}', htmlspecialchars($newsletter_subscriber->name), $text);
        $text = str_replace('{ne}', htmlspecialchars($newsletter_subscriber->email), $text);
    }
    else {
        $text = str_replace('{nn}', '', $text);
        $text = str_replace('{ne}', '', $text);
    }
    $options = get_option('newsletter');
    if (isset($options['noname'])) {
        newsletter_remove_block('name', $text);
    }
    $options_lists = get_option('newsletter_lists');
    if ($options_lists['enabled'] != 1) {
        newsletter_remove_block('lists', $text);
    }

    $text = str_replace('{count}', newsletter_subscribers_count(), $text);
    $text = str_replace('{lists}', newsletter_list_checkboxes(), $text);
    $text = str_replace('{newsletter_url}', $newsletter_options_main['url'], $text);

    return $text;
}

function newsletter_remove_block($name, &$text) {
    $x = strpos($text, '<!--' . $name . '-->');
    if ($x !== false) {
        $y = strpos($text, '<!--/' . $name . '-->');
        $text = substr($text, 0, $x) . substr($text, $y);
    }
}

function newsletter_is_email($email, $empty_ok=false) {
    $email = strtolower(trim($email));
    if ($empty_ok && $email == '') return true;

    if (eregi("^([a-z0-9_\.-])+@(([a-z0-9_-])+\\.)+[a-z]{2,6}$", trim($email))) {
        if (strpos($email, 'mailinator.com') !== false) return false;
        if (strpos($email, 'guerrillamailblock.com') !== false) return false;
        return true;
    }
    else
        return false;
}

function newsletter_delete_batch_file() {
//    @unlink(dirname(__FILE__) . '/batch.dat');
}

function newsletter_save_batch_file($batch) {
//    $file = @fopen(dirname(__FILE__) . '/batch.dat', 'w');
//    if (!$file) return;
//    @fwrite($file, serialize($batch));
//    @fclose($file);
}

function newsletter_load_batch_file() {
//    $content = @file_get_contents(dirname(__FILE__) . '/batch.dat');
//    return @unserialize($content);
}


/*******************************************************************************
 * LOGGING
 ******************************************************************************/
/**
 * Write a line of log in the log file if the logs are enabled or force is
 * set to true.
 */
function newsletter_log($text) {
    $file = @fopen(dirname(__FILE__) . '/newsletter.log', 'a');
    if (!$file) return;
    @fwrite($file, date('Y-m-d h:i') . ' ' . $text . "\n");
    @fclose($file);
}

function newsletter_debug($fn, $text) {
    global $newsletter_options_main;
    if ($newsletter_options_main['logs'] < 2) return;
    newsletter_log('- DEBUG - ' . $fn . ' - ' . $text);
}

function newsletter_info($fn, $text) {
    global $newsletter_options_main;
    if ($newsletter_options_main['logs'] < 1) return;
    newsletter_log('- INFO  - ' . $fn . ' - ' . $text);
}

function newsletter_fatal($fn, $text) {
    global $newsletter_options_main;
    newsletter_log('- FATAL - ' . $fn . ' - ' . $text);
    newsletter_die($text);
}

function newsletter_error($fn, $text) {
    global $newsletter_options_main;
    if ($newsletter_options_main['logs'] < 1) return;
    newsletter_log('- ERROR - ' . $fn . ' - ' . $text);
}

/*******************************************************************************
 * THEMES
 ******************************************************************************/
/**
 * Retrieves a list of custom themes located under wp-plugins/newsletter-custom/themes.
 * Return a list of theme names (which are folder names where the theme files are stored.
 */
function newsletter_get_themes() {
    $handle = @opendir(ABSPATH . 'wp-content/plugins/newsletter-custom/themes');
    $list = array();
    if (!$handle) return $list;
    while ($file = readdir($handle)) {
        if ($file == '.' || $file == '..') continue;
        if (!is_dir(ABSPATH . 'wp-content/plugins/newsletter-custom/themes/' . $file)) continue;
        if (!is_file(ABSPATH . 'wp-content/plugins/newsletter-custom/themes/' . $file . '/theme.php')) continue;
        $list['*' . $file] = $file;
    }
    closedir($handle);
    return $list;
}

/*******************************************************************************
 * TRACKING
 ******************************************************************************/
global $newsletter_relink_id; // user id
global $newsletter_relink_newsletter; // newsletter name

function newsletter_relink($msg, $id, $newsletter=null) {
    global $newsletter_relink_id;
    global $newsletter_relink_newsletter;

    $newsletter_relink_id = $id;
    $newsletter_relink_newsletter = trim(str_replace(';', ' ', $newsletter));
    return preg_replace_callback('/(<[aA][^>]+href=["\'])([^>"\']+)(["\'][^>]*>)(.*?)(<\/[Aa]>)/', newsletter_relink_callback, $msg);
}

function newsletter_relink_callback($matches) {
    global $newsletter_relink_id;
    global $newsletter_relink_newsletter;

    $href = str_replace('&amp;', '&', $matches[2]);
    if (strpos($href, 'na=') !== false && strpos($href, 'ni=') !== false && strpos($href, 'nt=') !== false) return $matches[0];

    $anchor = trim(str_replace(';', ' ', $matches[4]));
    if (!$anchor) $anchor = '-';

    $options_stats = get_option('options_stats');
    if ($options_stats['alternative'] == 1) $url = get_option('siteurl') . '/wp-load.php';
    else $url = get_option('home') . '/';
    $url .= '?na=r&amp;nr=' .
        urlencode(base64_encode($newsletter_relink_newsletter . ';' . $newsletter_relink_id . ';' .
        $anchor . ';' . $href));

    return $matches[1] . $url . $matches[3] . $matches[4] . $matches[5];
}



/*******************************************************************************
 * FEED BY MAIL
 ******************************************************************************/
define('NEWSLETTER_FEED_STATUS_SUBSCRIBED', 1);
define('NEWSLETTER_FEED_STATUS_UNSUBSCRIBED', 0);
$newsletter_feed_last_time = 0;

add_action('newsletter_feed_job', 'newsletter_feed_job');
/*
 * Runs daily at hour specified by user and if feed by mail is active. When it
 * starts, it checks if there is new posts published after the last run and
 * build a message using the specified theme.
*/
function newsletter_feed_job() {
    global $newsletter_feed_last_time;

    newsletter_debug(__FUNCTION__, "Start feed task");

    $options = get_option('newsletter');
    $options_feed = get_option('newsletter_feed');

    // If not daily...
    if ($options_feed['day'] != 0) {
        if (date('N') != $options_feed['day']) {
            newsletter_debug(__FUNCTION__, 'Not the right day...');
            return;
        }
    }

    // Extract the saved batch to get the last post id, if the batch doesn't
    // exists, it will be created.
    $batch = get_option('newsletter_feed_batch');
    newsletter_debug(__FUNCTION__, "Previous batch:\n" . print_r($batch, true));

    if (is_array($batch)) {
        if (!$batch['completed']) {
            newsletter_info(__FUNCTION__, 'Batch not completed so cannot start a new one');
            return;
        }
    }

    $posts = get_posts('numberposts=1');
    if ($batch['last_time'] >= newsletter_m2t($posts[0]->post_date_gmt)) {
        newsletter_info(__FUNCTION__, 'Nothing to send');
        return;
    }

    $newsletter_feed_last_time = $batch['last_time'];

    // Setup a new batch
    $batch = array();
    $batch['subject'] = $posts[0]->post_title;
    $batch['last_time'] = time();
    $batch['list'] = 0;
    $batch['completed'] = false;
    $batch['name'] = 'feed-by-mail-' . date('d/m/Y');
    $batch['id'] = 0;

    $batch['body'] = newsletter_create_message('', 'feed');

    update_option('newsletter_feed_batch', $batch);

    newsletter_debug(__FUNCTION__, "New batch saved:\n" . print_r($batch, true));

    newsletter_feed_batch_job();
}

function newsletter_feed_is_old() {
    global $post, $newsletter_feed_last_time;
    return newsletter_m2t($post->post_date_gmt) <= (int)$newsletter_feed_last_time;
}

define('NEWSLETTER_FEED_BATCH_JOB_FREQUENCY', 300);
add_action('newsletter_feed_batch_job', 'newsletter_feed_batch_job');
function newsletter_feed_batch_job() {
    newsletter_debug(__FUNCTION__, 'Start');
    $res = newsletter_feed_send_batch();

    if ($res) {
        newsletter_debug(__FUNCTION__, 'Result true');
        $batch = get_option('newsletter_feed_batch');
        newsletter_debug(__FUNCTION__, "Batch:\n" . print_r($batch, true));
        // Batch may have been cancelled
        if (!is_array($batch) || $batch['completed']) {
            return;
        }
        // Schedule next event
        wp_schedule_single_event(time()+NEWSLETTER_FEED_BATCH_JOB_FREQUENCY, 'newsletter_feed_batch_job');
        newsletter_debug(__FUNCTION__, 'Next batch scheduled on ' . newsletter_date(wp_next_scheduled('newsletter_feed_batch_job')));
    }
    else {
        newsletter_debug(__FUNCTION__, 'Sending returned error');
    }
}

function newsletter_feed_set($id, $status) {
    global $wpdb;

    $a = array('feed'=>$status);
    $wpdb->update($wpdb->prefix . 'newsletter', $a, array('id'=>$id));
}

function newsletter_feed_set_all($status) {
    global $wpdb;

    $wpdb->query("update " . $wpdb->prefix . 'newsletter set feed=' . $status);
}

function newsletter_feed_subscribe($id, $token) {
    global $newsletter_subscriber;

    $newsletter_subscriber = newsletter_get_subscriber_strict($id, $token);
    newsletter_feed_set($newsletter_subscriber->id, 1);
}

function newsletter_feed_unsubscribe($id, $token) {
    global $newsletter_subscriber;

    $newsletter_subscriber = newsletter_get_subscriber_strict($id, $token);
    newsletter_feed_set($newsletter_subscriber->id, 0);
}

function newsletter_feed_send_batch() {
    global $wpdb, $newsletter_options_main;

    newsletter_info(__FUNCTION__, 'Start');

    $options_feed = get_option('newsletter_feed', array());
    $batch = get_option('newsletter_feed_batch', array());

    newsletter_debug(__FUNCTION__, "Batch:\n" . print_r($batch, true));

    // Batch have to contain 'id' which is the starting id, 'simulate' boolean
    // to indicate if is a simulation or not, 'scheduled' if it's a scheduled
    // sending process. 'list' is the list number, required.
    // If 'id' = 0 it's a new seding process.

    if (!isset($batch['id'])) {
        newsletter_error(__FUNCTION__, 'Batch "id" parameter not present');
        return false;
    }

    if (!isset($batch['list'])) {
        newsletter_error(__FUNCTION__, 'Batch "list" parameter not present');
        return false;
    }

    if (!isset($batch['name'])) {
        newsletter_error(__FUNCTION__, 'Batch "name" parameter not present');
        return false;
    }

    $id = (int)$batch['id'];
    $list = (int)$batch['list'];

    $max = $options_feed['scheduler_max'];
    if (!is_numeric($max)) $max = 200; // per hour
    $max = floor($max/(3600/NEWSLETTER_FEED_BATCH_JOB_FREQUENCY));
    if ($max == 0) $max = 1;

    $query = "select * from " . $wpdb->prefix . "newsletter where status='C' and feed=1 and " .
        " id>" . $id . " order by id limit " . $max;
    newsletter_debug(__FUNCTION__, $query);
    $recipients = $wpdb->get_results($query);
    newsletter_debug(__FUNCTION__, print_r($recipients, true));

    // For a new batch save some info
    if ($id == 0) {
        $batch['total'] = $wpdb->get_var("select count(*) from " . $wpdb->prefix . "newsletter where status='C' and feed=1");
        $batch['sent'] = 0;
        $batch['completed'] = false;
        $batch['message'] = '';
    }

    //
    // Not all hosting provider allow this...
    @set_time_limit(NEWSLETTER_TIME_LIMIT);
    @$wpdb->query("set session wait_timeout=" . NEWSLETTER_WAIT_TIMEOUT);

    $start_time = time();
    $max_time = (int)(ini_get('max_execution_time') * 0.8);
    $db_time = time();

    $message = $batch['body'];
    $subject = $batch['subject'];

    $idx = 0;

    add_action('phpmailer_init', 'newsletter_phpmailer_init');

    foreach ($recipients as $r) {

        $m = newsletter_replace_all_urls($message, $r);
        $m = newsletter_replace($m, $r);

        if ($options_feed['track'] == 1) $m = newsletter_relink($m, $r->id, $batch['name']);

        $s = $subject;
        $s = newsletter_replace($s, $r);

        $x = newsletter_mail($r->email, $s, $m, true);

        if ($x) {
            newsletter_debug(__FUNCTION__, 'Sent to ' . $r->id . ' success');
        } else {
            newsletter_debug(__FUNCTION__, 'Sent to ' . $r->id . ' failed');
        }

        $idx++;

        $batch['sent']++;
        $batch['id'] = $r->id;

        // Try to avoid database timeout
        if (time()-$db_time > 15) {
            newsletter_debug(__FUNCTION__, 'Batch saving to avoid database timeout');
            $db_time = time();
            $batch['message'] = 'Temporary saved batch to avoid database timeout';
            if (!update_option('newsletter_feed_batch', $batch)) {
                newsletter_error(__FUNCTION__, 'Unable to save to database, saving on file system');
                newsletter_error(__FUNCTION__, "Batch:\n" . print_r($last, true));

                //newsletter_save_batch_file($batch);
                remove_action('phpmailer_init','newsletter_phpmailer_init');

                return false;
            }
        }

        // Check for the max emails per batch
        if ($max != 0 && $idx >= $max) {
            newsletter_info(__FUNCTION__, 'Batch saving due to max emails limit reached');
            $batch['message'] = 'Batch max emails limit reached (it is ok)';
            if (!update_option('newsletter_feed_batch', $batch)) {
                newsletter_error(__FUNCTION__, 'Unable to save to database, saving on file system');
                newsletter_error(__FUNCTION__, "Batch:\n" . print_r($last, true));

                //newsletter_save_batch_file($batch);
                remove_action('phpmailer_init','newsletter_phpmailer_init');

                return false;
            }

            remove_action('phpmailer_init','newsletter_phpmailer_init');

            return true;
        }

        // Timeout check, max time is zero if set_time_limit works
        if (($max_time != 0 && (time()-$start_time) > $max_time)) {
            newsletter_info(__FUNCTION__, 'Batch saving due to max time limit reached');
            $batch['message'] = 'Batch max time limit reached (it is ok)';
            if (!update_option('newsletter_feed_batch', $batch)) {
                newsletter_error(__FUNCTION__, 'Unable to save to database, saving on file system');
                newsletter_error(__FUNCTION__, "Batch:\n" . print_r($last, true));

                //newsletter_save_batch_file($batch);
                remove_action('phpmailer_init','newsletter_phpmailer_init');

                return false;
            }

            remove_action('phpmailer_init','newsletter_phpmailer_init');

            return true;
        }
    }

    // All right (incredible!)
    newsletter_info(__FUNCTION__, 'Sending completed!');
    $batch['completed'] = true;
    $batch['message'] = '';
    if (!update_option('newsletter_feed_batch', $batch)) {
        newsletter_error(__FUNCTION__, 'Unable to save to database, saving on file system');
        newsletter_error(__FUNCTION__, "Batch:\n" . print_r($last, true));

        //newsletter_save_batch_file($batch);
        remove_action('phpmailer_init','newsletter_phpmailer_init');

        return false;
    }

    remove_action('phpmailer_init','newsletter_phpmailer_init');

    return true;
}


/**
 * Resets the batch status.
 */
function newsletter_reset_batch() {

}


/** 
 * Find an image for a post checking the media uploaded for the post and
 * choosing the first image found.
 */
function nt_post_image($post_id, $size='thumbnail', $alternative=null) {

    $attachments = get_children(array('post_parent'=>$post_id, 'post_status'=>'inherit', 'post_type'=>'attachment', 'post_mime_type'=>'image', 'order'=>'ASC', 'orderby'=>'menu_order ID' ) );

    if (empty($attachments)) {
        return $alternative;
    }

    foreach ($attachments as $id=>$attachment) {
        $image = wp_get_attachment_image_src($id, $size);
        return $image[0];
    }
    return null;
}

function nt_option($name, $def = null) {
//    if ($newsletter_is_feed && $name == 'posts') {
//        $options = get_option('newsletter_feed');
//        return $options['posts'];
//    }
    $options = get_option('newsletter_email');
    $option = $options['theme_' . $name];
    if (!isset($option)) return $def;
    else return $option;
}

/**
 * Retrieves the theme dir path.
 */
function newsletter_get_theme_dir($theme) {
    if ($theme[0] == '*') {
        return ABSPATH . '/wp-content/plugins/newsletter-custom/themes/' . substr($theme, 1);
    }
    else {
        return dirname(__FILE__) . '/themes/' . $theme;
    }
}

/**
 * Retrieves the theme URL (pointing to theme dir).
 */
function newsletter_get_theme_url($theme) {
    if ($theme[0] == '*') {
        return get_option('siteurl') . '/wp-content/plugins/newsletter-custom/themes/' . substr($theme, 1);
    }
    else {
        return get_option('siteurl') . '/wp-content/plugins/newsletter-pro/themes/' . $theme;
    }
}

/**
 * Creates the HTML of a message using the "messages" theme checking if there is one
 * in "newsletter-custom" directory
 */
function newsletter_create_message($message, $theme='messages') {

    $file = ABSPATH . 'wp-content/plugins/newsletter-custom/themes/' . $theme . '/theme.php';
    if (!is_file($file)) {
        $file = ABSPATH . 'wp-content/plugins/newsletter-pro/themes/' . $theme . '/theme.php';
    }
    ob_start();
    @include($file);
    $html = ob_get_contents();
    ob_end_clean();

    return str_replace('{message}', $message, $html);
}

/**
 * Loads the theme css content to be embedded in emails body.
 */
function newsletter_get_theme_css($theme) {
    if ($theme == 'blank') return '';
    return @file_get_contents(newsletter_get_theme_dir($theme) . '/style.css');
}

function newsletter_get_theme_html($theme) {
    if ($theme == 'blank') return '';
    $file = newsletter_get_theme_dir($theme) . '/theme.php';

    // Execute the theme file and get the content generated
    ob_start();
    @include($file);
    $html = ob_get_contents();
    ob_end_clean();
    return $html;
}

function newsletter_m2t($s) {
    $s = explode(' ', $s);
    $d = explode('-', $s[0]);
    $t = explode(':', $s[1]);
    return gmmktime((int)$t[0], (int)$t[1], (int)$t[2], (int)$d[1], (int)$d[2], (int)$d[0]);

}

function newsletter_date($time=null, $now=false, $left=false) {
    if (is_null($time)) $time = time();
    if ($time === false) $buffer = 'none';
    else $buffer = gmdate(get_option('date_format') . ' ' . get_option('time_format'), $time + get_option('gmt_offset')*3600);
    if ($now) {
        $buffer .= ' (now: ' . gmdate(get_option('date_format') . ' ' .
            get_option('time_format'), time() + get_option('gmt_offset')*3600);
        if ($left) {
            $buffer .= ', ' . gmdate('H:i:s', $time-time()) . ' left';
        }
        $buffer .= ')';
    }
    return $buffer;
}

function newsletter_die($message = '') {
    newsletter_debug(__FUNCTION__, 'Die');
    newsletter_shutdown();
    die($message);
}

add_action('shutdown', 'newsletter_shutdown');
function newsletter_shutdown() {
    newsletter_debug(__FUNCTION__, 'Shutdown');
    newsletter_mail_close();
}

/*******************************************************************************
 * MAILER
 ******************************************************************************/
$newsletter_mailer = null;
function newsletter_mail_init() {
    global $newsletter_mailer, $newsletter_options_main;

    newsletter_debug(__FUNCTION__, 'Start');

    if (!is_null($newsletter_mailer)) {
        newsletter_debug(__FUNCTION__, 'Mailer already initialized');
        return;
    }

    require_once ABSPATH . WPINC . '/class-phpmailer.php';
    require_once ABSPATH . WPINC . '/class-smtp.php';
    $newsletter_mailer = new PHPMailer();

    $options_smtp = get_option('newsletter_smtp', array());

    if ($options_smtp['enabled'] == 0) {
        newsletter_debug(__FUNCTION__, 'SMTP not enabled');
        $newsletter_mailer->IsMail();
    }
    else {
        $newsletter_mailer->IsSMTP();
        $newsletter_mailer->Host = $options_smtp['host_1'];
        if (isset($options_smtp['auth_1'])) {
            $newsletter_mailer->SMTPAuth = true;
            $newsletter_mailer->Username = $options_smtp['user_1'];
            $newsletter_mailer->Password = $options_smtp['pass_1'];
        }
        $newsletter_mailer->SMTPKeepAlive = true;

    }

    $newsletter_mailer->CharSet = 'UTF-8';
    $newsletter_mailer->From = $newsletter_options_main['sender_email'];
    if (!empty($newsletter_options_main['return_path'])) {
        $newsletter_mailer->Sender = $newsletter_options_main['return_path'];
    }

    $newsletter_mailer->FromName = $newsletter_options_main['sender_name'];
}

function newsletter_mail_close() {
    global $newsletter_mailer;

    if (is_null($newsletter_mailer)) return;
    $newsletter_mailer->SmtpClose();
    $newsletter_mailer = null;
}

/*******************************************************************************
 * FOLLOW UP
 ******************************************************************************/
define('NEWSLETTER_FOLLOWUP_MAX_EMAILS', 20); // max emails per run
define('NEWSLETTER_FOLLOWUP_JOB_FREQUENCY', 600); // seconds
define('NEWSLETTER_FOLLOWUP_MAX_STEPS', 10); // seconds
define('NEWSLETTER_FOLLOWUP_STATUS_SUBSCRIBED', 1);
define('NEWSLETTER_FOLLOWUP_STATUS_UNSUBSCRIBED', 0);

add_action('newsletter_followup_job', 'newsletter_followup_job');
function newsletter_followup_job() {
    global $wpdb, $newsletter_options_main;

    newsletter_info(__FUNCTION__, 'Follow up start');

    // Extracts all subscribers with followup field
    $query = "select * from " . $wpdb->prefix . "newsletter " .
        "where status='C' and " .
        "followup=1 and followup_time<" . time() . " order by id limit " . NEWSLETTER_FOLLOWUP_MAX_EMAILS;

    $subscribers = $wpdb->get_results($query);
    $options = get_option('newsletter_followup', array());

    newsletter_set_limits();

    foreach ($subscribers as $subscriber) {
        newsletter_info(__FUNCTION__, $subscriber->name);

        $subscriber->followup_step++; // Step to do
        newsletter_info(__FUNCTION__, $subscriber->name . ' step ' . $subscriber->followup_step);
        if ($subscriber->followup_step<=$options['steps']) {
            newsletter_info(__FUNCTION__, 'Going on with step');
            newsletter_followup_set($subscriber->id, 1, time()+$options['interval']*3600, $subscriber->followup_step);

            $message = $options['step_' . $subscriber->followup_step . '_message'];
            $message = newsletter_create_message($message, 'followup');
            $message = newsletter_replace($message, $subscriber);

            $message = newsletter_replace_url($message, 'FOLLOWUP_UNSUBSCRIPTION_URL',
                newsletter_add_qs($newsletter_options_main['url'],
                'na=fu&amp;ni=' . $subscriber->id . '&amp;nt=' . $subscriber->token));
            if ($options['track'] == 1) $message = newsletter_relink($message, $subscriber->id, 'followup');

            $subject = $options['step_' . $subscriber->followup_step . '_subject'];
            $subject = newsletter_replace($subject, $subscriber);

            newsletter_mail($subscriber->email, $subject, $message);
        }
        else {
            newsletter_info(__FUNCTION__, 'All steps done');
            newsletter_followup_set($subscriber->id, 2);
        }
    }

    wp_schedule_single_event(time()+NEWSLETTER_FOLLOWUP_JOB_FREQUENCY, 'newsletter_followup_job');

    newsletter_info(__FUNCTION__, 'Follow up end');
}

function newsletter_followup_set($id, $status, $time=null, $step=null) {
    global $wpdb;

    $a = array('followup'=>$status);
    if (!is_null($time)) $a['followup_time'] = $time;
    if (!is_null($step)) $a['followup_step'] = $step;
    $wpdb->update($wpdb->prefix . 'newsletter', $a, array('id'=>$id));
}

function newsletter_followup_subscribe($id, $token) {
    global $newsletter_subscriber;

    $newsletter_subscriber = newsletter_get_subscriber_strict($id, $token);
    newsletter_followup_set($newsletter_subscriber->id, 1, time(), 0);
}

function newsletter_followup_unsubscribe($id, $token) {
    global $newsletter_subscriber;

    $newsletter_subscriber = newsletter_get_subscriber_strict($id, $token);
    newsletter_followup_set($newsletter_subscriber->id, 3, 0, 0);
}


/*******************************************************************************
 * EMAIL ARCHIVE
 ******************************************************************************/
function newsletter_email_save() {
    global $wpdb;

    $options_email = get_option('newsletter_email');
    $wpdb->insert($wpdb->prefix . 'newsletter_email',
        array(
        'subject'=>$options_email['subject'],
        'message'=>$options_email['message'],
        'name'=>$options_email['name'],
        'subject2'=>$options_email['subject2'],
        'message2'=>$options_email['message2'],
        'name2'=>$options_email['name2'],
        'theme'=>$options_email['theme']
        )
    );
}

/*******************************************************************************
 * FORMS
 ******************************************************************************/
function newsletter_form($number, $label=null, $replace=false) {
    global $newsletter_labels, $newsletter_options_main;
    if (!empty($number)) {
        $options_forms = get_option('newsletter_forms');
        $buffer = $options_forms['form_' . $number];
        if ($replace) $buffer = newsletter_replace_labels($buffer);
    }
    else {
        $buffer = $newsletter_labels[$label];
        if ($replace) $buffer = newsletter_replace_labels($buffer);
    }
    return $buffer;
}

/*******************************************************************************
 * LOCKED CONTENT
 ******************************************************************************/
add_shortcode('newsletter_lock', 'newsletter_lock_call');
function newsletter_protect_call($attrs, $content=null)
{
    global $newsletter_subscriber;

    $cookie = $_COOKIE['newsletter'];
    
    if (isset($cookie)) {
        list ($id, $token) = explode('-', $cookie, 2);
        $newsletter_subscriber = newsletter_get_subscriber($id, $token, true);
        if ($newsletter_subscriber != null && $newsletter_subscriber->status == NEWSLETTER_STATUS_CONFIRMED) return do_shortcode($content);
    }
    $newsletter_protect_options = get_option('newsletter_protect');
    return $newsletter_protect_options['message'];
}
?>
