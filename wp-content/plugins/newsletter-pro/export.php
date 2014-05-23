<?php
@include_once 'commons.php';

$keys = $wpdb->get_results("select distinct name from " . $wpdb->prefix . "newsletter_profiles order by name");

// CSV header
$header = 'Email;Name;Status;Token;';
foreach ($keys as $key) {
    // Remove some keys?
    $header .= $key->name . ';';
}
$header .= "\n";
?>

<div class="wrap">
    <h2><?php _e('Newsletter Export', 'newsletter'); ?> <a target="_blank" alt="help" href="http://www.satollo.net/plugins/newsletter#export"><img src="<?php echo get_option('siteurl'); ?>/wp-content/plugins/newsletter-pro/help.png"/></a></h2>

<textarea wrap="off" style="width: 100%; height: 400px; font-size: 11px; font-family: monospace">
<?php
echo $header;

$query = "select * from " . $wpdb->prefix . "newsletter";
$recipients = $wpdb->get_results($query . " order by email");
for ($i=0; $i<count($recipients); $i++) {
    echo '"' . $recipients[$i]->email . '";"' . $recipients[$i]->name .
        '";"' . $recipients[$i]->status . '";"' . $recipients[$i]->token . '";';

    $profile = $wpdb->get_results("select name,value from " . $wpdb->prefix . "newsletter_profiles where newsletter_id=" . $recipients[$i]->id . " order by name");
    $map = array();
    foreach ($profile as $field) {
        $map[$field->name] = $field->value;
        //echo htmlspecialchars($field->name) . ': ' . htmlspecialchars($field->value) . '<br />';
    }

    foreach ($keys as $key) {
        if (isset($map[$key->name])) {
        $s = str_replace('"', "'", $map[$key->name]);
        $s = str_replace("\n", '', $s);
        $s = str_replace("\r", '', $s);
        }
        else $s = '';
        echo '"' . $s . '";';
    }
    echo "\n";
}
?></textarea>
</div>
