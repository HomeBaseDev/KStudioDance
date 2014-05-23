<?php
@include_once 'commons.php';
$list = $wpdb->get_results("select id,date,subject,name from " . $wpdb->prefix .
    "newsletter_emails order by date desc");

$options = stripslashes_deep($_POST['options']);

if ($action == 'restore') {
    $tmp = $wpdb->get_results($wpdb->prepare("select subject,message from " . $wpdb->prefix .
    "newsletter_emails where id=%d", $options['id']));
    $newsletter_email = get_option('newsletter_email');
    $newsletter_email['subject'] = $tmp[0]->subject;
    $newsletter_email['message'] = $tmp[0]->message;
    update_option('newsletter_email', $newsletter_email);
    $messages = 'Newsletter restored. Go to Composer panel to edit it.';
}

$nc = new NewsletterControls($options);
$nc->errors($errors);
$nc->messages($messages);
?>

<script type="text/javascript" src="<?php echo get_option('siteurl'); ?>/wp-content/plugins/newsletter-pro/tiny_mce/tiny_mce.js"></script>
<script type="text/javascript">
    tinyMCE.init({
        mode : "textareas",
        theme : "simple"
        });
    function newsletter_view(f, id)
    {
        f.elements['options[id]'].value = id;
        f.submit();
    }
</script>

<div class="wrap">

<h2>Email archive</h2>

<form action="" method="post">
    <?php wp_nonce_field(); ?>
    <?php $nc->hidden('id'); ?>

    <?php if ($action == 'view') { ?>

    <?php 
    $tmp = $wpdb->get_results($wpdb->prepare("select subject,message from " . $wpdb->prefix .
    "newsletter_emails where id=%d", (int)$options['id']));
    ?>
    <strong><?php echo htmlspecialchars($tmp[0]->subject); ?></strong><br />
    <textarea name="message" cols="" rows="15" style="width: 100%"><?php echo htmlspecialchars($tmp[0]->message); ?></textarea>
    <br />
    <?php $nc->button('restore', 'Restore in composer'); ?>
    <?php $nc->button('back', 'Back'); ?>
    
    <?php } else { ?>

    <table class="clicks" cellspacing="0">
        <tr>
            <th>Date</th>
            <th>Subject</th>
            <th>Tracking name</th>
            <th>&nbsp;</th>
        </tr>

        <?php

        if ($list) {
            for ($i=0; $i<count($list); $i++) {
        ?>
        <tr>
        <td><?php echo $list[$i]->date; ?></td>
        <td><?php echo htmlspecialchars($list[$i]->subject); ?></td>
        <td><?php echo $list[$i]->name; ?></td>
        <td><?php $nc->button('view', 'View', 'newsletter_view(this.form,' . $list[$i]->id . ')'); ?></td>
        </tr>
        <?php
            }
        }
        ?>
    </table>
    <?php } ?>
</form>

</div>
