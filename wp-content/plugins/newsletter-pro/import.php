<?php

@include_once 'commons.php';

$options = stripslashes_deep($_POST['options']);

$options_lists = get_option('newsletter_lists');

if ($action == 'import') {
    @set_time_limit(100000);
    $csv = stripslashes($_POST['csv']);
    $lines = explode("\n", $csv);

    $error = array();

    foreach ($lines as $line) {
        $subscriber = array();
        $line = trim($line);
        if ($line == '') continue;
        if ($line[0] == '#') continue;
        $separator = $options['separator'];
        if ($separator == 'tab') $separator = "\t";
        $data = explode($separator, $line);
        $subscriber['email'] = newsletter_normalize_email($data[0]);
        if (!newsletter_is_email($subscriber['email']))
        {
            $error[] = $line;
            continue;
        }
        $subscriber['name'] = newsletter_normalize_name($data[1]);
        for ($i=1; $i<=9; $i++)
        {
            $list = 'list_' . $i;
            if (isset($options['list_' . $i])) $subscriber[$list] = 1;
            else $subscriber[$list] = 0;
        }

        newsletter_save($subscriber);
    }
}

$nc = new NewsletterControls();
$nc->errors($errors);
$nc->messages($messages);
?>

<div class="wrap">

    <h2><?php _e('Newsletter Import', 'newsletter'); ?> <a alt="help" target="_blank" href="http://www.satollo.net/plugins/newsletter#import"><img src="<?php echo get_option('siteurl'); ?>/wp-content/plugins/newsletter-pro/help.png"/></a></h2>

    <?php require_once 'header.php'; ?>

    <?php if (!empty($error)) { ?>

    <h3><?php _e('Rows with errors', 'newsletter'); ?></h3>

    <textarea wrap="off" style="width: 100%; height: 150px; font-size: 11px; font-family: monospace"><?php echo htmlspecialchars(implode("\n", $error))?></textarea>

    <?php } ?>

    <form method="post" action="">
        <?php wp_nonce_field(); ?>

        <h3><?php _e('CSV text with subscribers', 'newsletter'); ?></h3>
         <table class="form-table">
            <tr valign="top">
                <th>Associated lists</th>
                <td>
                <?php
                for ($i=1; $i<=9; $i++)
                {
                    if (empty($options_lists['name_' . $i])) continue;
                ?>
                    <?php $nc->checkbox('list_' . $i, htmlspecialchars($options_lists['name_' . $i])); ?><br />
                <?php
                }
                ?>
                </td>
            </tr>
            <tr valign="top">
                <th>Separator</th>
                <td>
                    <?php $nc->select('separator', array(';'=>'Semicolon', ','=>'Comma', 'tab'=>'Tabulation')); ?>
                </td>
            </tr>
                        <tr valign="top">
                <th>CSV text</th>
                <td>
        <textarea name="csv" wrap="off" style="width: 100%; height: 300px; font-size: 11px; font-family: monospace"></textarea>
                </td>
        </table>

        <p class="submit">
            <?php $nc->button('import', __('Import', 'newsletter')); ?>
        </p>
    </form>

</div>
