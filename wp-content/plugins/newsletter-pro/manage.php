<?php

@include_once 'commons.php';

$options = stripslashes_deep($_POST['options']);
$options_lists = get_option('newsletter_lists');

if ($action == 'save')
{
    $subscriber = array();
    $subscriber['email'] = $options['subscriber_email'];
    $subscriber['name'] = $options['subscriber_name'];
    if (!empty($options['subscriber_id'])) $subscriber['id'] = $options['subscriber_id'];
    for ($i=1; $i<=9; $i++)
    {
        $list = 'list_' . $i;
        if (isset($options['subscriber_list_' . $i])) $subscriber[$list] = 1;
        else $subscriber[$list] = 0;
    }

    newsletter_save($subscriber);
}

if ($action == 'add')
{
    $subscriber = array();
    $subscriber['name'] = $options['subscriber_name'];
    $subscriber['email'] = $options['subscriber_email'];
    for ($i=1; $i<=9; $i++)
    {
        $list = 'list_' . $i;
        if (isset($options['subscriber_list_' . $i])) $subscriber[$list] = 1;
        else $subscriber[$list] = 0;
    }
    newsletter_save($subscriber);
    unset($options['subscriber_id']);
}

if ($action == 'edit')
{
    $subscriber = newsletter_get_subscriber($options['subscriber_id']);
    $options['subscriber_name'] = $subscriber->name;
    $options['subscriber_email'] = $subscriber->email;
    for ($i=1; $i<=9; $i++)
    {
        // To avoid stale data from other editing
        unset($options['subscriber_list_' . $i]);
        $list = 'list_' . $i;
        if ($subscriber->$list == 1) {
            $options['subscriber_list_' . $i] = 1;
        }
    }
}

if ($action == 'resend') {
    newsletter_send_confirmation(newsletter_get_subscriber($options['subscriber_id']));
}

if ($action == 'resend_welcome') {
    newsletter_send_welcome($newsletter_get_subscriber($options['subscriber_id']));
}

if ($action == 'remove') {
    newsletter_delete($options['subscriber_id']);
    unset($options['subscriber_id']);
}

if ($action == 'remove_unconfirmed') {
    newsletter_delete_all(NEWSLETTER_STATUS_UNCONFIRMED);
}

if ($action == 'confirm_all') {
    newsletter_set_status_all(NEWSLETTER_STATUS_CONFIRMED);
}

if ($action == 'remove_all') {
    newsletter_delete_all();
}

if ($action == 'status') {
    newsletter_set_status($options['subscriber_id'], $options['subscriber_status']);
}

if ($action == 'feed') {
    $subscriber = newsletter_get_subscriber($options['subscriber_id']);
    if ($subscriber->feed == NEWSLETTER_FEED_STATUS_SUBSCRIBED) 
        newsletter_feed_set($options['subscriber_id'], NEWSLETTER_FEED_STATUS_UNSUBSCRIBED);
    else
        newsletter_feed_set($options['subscriber_id'], NEWSLETTER_FEED_STATUS_SUBSCRIBED);
}

if ($action == 'feed_all') {
    newsletter_feed_set_all(NEWSLETTER_FEED_STATUS_SUBSCRIBED);
}

if ($action == 'followup') {
    $subscriber = newsletter_get_subscriber($options['subscriber_id']);
    if ($subscriber->followup == NEWSLETTER_FOLLOWUP_STATUS_SUBSCRIBED)
        newsletter_followup_set($options['subscriber_id'], NEWSLETTER_FOLLOWUP_STATUS_UNSUBSCRIBED);
    else
        newsletter_followup_set($options['subscriber_id'], NEWSLETTER_FOLLOWUP_STATUS_SUBSCRIBED);
}


$list = newsletter_search($options['search_text'], $options['search_status'], $options['search_order']);

$nc = new NewsletterControls($options);
$nc->errors($errors);
$nc->messages($messages);

?>
<script type="text/javascript">
    function newsletter_edit(f, id)
    {
        f.elements["options[subscriber_id]"].value = id;
        f.submit();
    }
    
    function newsletter_remove(f, id)
    {
        f.elements["options[subscriber_id]"].value = id;
        f.submit();
    }

    function newsletter_feed(f, id)
    {
        f.elements["options[subscriber_id]"].value = id;
        f.submit();
    }

    function newsletter_followup(f, id)
    {
        f.elements["options[subscriber_id]"].value = id;
        f.submit();
    }

    function newsletter_set_status(f, id, status)
    {
        f.elements["options[subscriber_id]"].value = id;
        f.elements["options[subscriber_status]"].value = status;
        f.submit();
    }

    function newsletter_resend(f, id)
    {
        if (!confirm("<?php _e('Resend the subscription confirmation email?', 'newsletter'); ?>")) return;
        f.elements["options[subscriber_id]"].value = id;
        f.submit();
    }

    function newsletter_resend_welcome(f, id)
    {
        if (!confirm("<?php _e('Resend the welcome email?', 'newsletter'); ?>")) return;
        f.elements["options[subscriber_id]"].value = id;
        f.submit();
    }
</script>

<div class="wrap">
    <h2><?php _e('Newsletter Subscribers', 'newsletter'); ?></h2>

    <form id="channel" method="post" action="">
        <?php wp_nonce_field(); ?>

        <h3>Create/Edit</h3>
        <?php $nc->hidden('subscriber_id'); ?>
        <?php $nc->hidden('subscriber_status'); ?>
        <table class="form-table">
            <tr valign="top">
                <th>Name and email</th>
                <td>
                    name: <?php $nc->text('subscriber_name', 40); ?>
                    email: <?php $nc->text('subscriber_email', 40); ?>
                </td>
            </tr>
            <tr valign="top">
                <th>Lists</th>
                <td>
                <?php
                for ($i=1; $i<=9; $i++)
                {
                    if (empty($options_lists['name_' . $i])) continue;
                ?>
                    <?php $nc->checkbox('subscriber_list_' . $i, htmlspecialchars($options_lists['name_' . $i])); ?><br />
                <?php
                }
                ?>
                </td>
            </tr>
        </table>
        <p class="submit">
            <?php if (isset($options['subscriber_id'])) { ?>
                <?php $nc->button('save', 'Save'); ?>
            <?php } ?>
            <?php $nc->button('add', 'Add new'); ?>
        </p>


        <h3>Search</h3>
            <table class="form-table">
                <tr valign="top">
                    <th><?php _e('Search', 'newsletter'); ?></th>
                    <td>
                        <?php $nc->text('search_text', 40); ?>
                        <?php _e('status', 'newsletter'); ?>:&nbsp;<?php $nc->select('search_status', array(''=>'All', 'C'=>'Confirmed', 'S'=>'Not confirmed')); ?>
                        <?php _e('order', 'newsletter'); ?>:&nbsp;<?php $nc->select('search_order', array('id'=>'Id', 'email'=>'Email', 'name'=>'Name')); ?>
                        <?php $nc->button('search', 'Search'); ?>
                        <br />
                        <?php _e('Press without filter to show all. Max 100 results will be shown. Use export panel to get all subscribers.', 'newsletter'); ?>
                    </td>
                </tr>
            </table>
            <p class="submit">
                <?php $nc->button('remove_unconfirmed', 'Remove all not confirmed'); ?>
                <?php $nc->button_confirm('remove_all', 'Remove all', 'Are you sure you want to remove ALL subscribers?'); ?>
                <?php $nc->button_confirm('confirm_all', 'Confirm all', 'Are you sure you want to mark ALL subscribers as confirmed?'); ?>
                <?php $nc->button_confirm('feed_all', 'Feed by mail for all', 'Are you sure you want to mark ALL subscribers to receive feeds?'); ?>
            </p>



<h3><?php _e('Statistics', 'newsletter'); ?></h3>
<?php _e('Confirmed subscriber', 'newsletter'); ?>: <?php echo $wpdb->get_var("select count(*) from " . $wpdb->prefix . "newsletter where status='C'"); ?>
<br />
<?php _e('Unconfirmed subscriber', 'newsletter'); ?>: <?php echo $wpdb->get_var("select count(*) from " . $wpdb->prefix . "newsletter where status='S'"); ?>

<h3><?php _e('Search results', 'newsletter'); ?></h3>

<?php if ($list) { ?>

<table class="bordered-table" border="1" cellspacing="5">
<tr>
    <th>Id</th><th><?php _e('Email', 'newsletter'); ?></th>
    <th><?php _e('Name', 'newsletter'); ?></th>
    <th><?php _e('Status', 'newsletter'); ?></th>
    <th><?php _e('Actions', 'newsletter'); ?></th>
    <th><?php _e('Profile', 'newsletter'); ?></th>
</tr>
    <?php foreach($list as $s) { ?>
<tr>
<td><?php echo $s->id; ?></td>
<td><?php echo $s->email; ?></td>
<td><?php echo $s->name; ?></td>
<td><small>
Confirmed:&nbsp;<?php echo ($s->status=='S'?'NO':'YES'); ?><br />
Feed: <?php echo ($s->feed!=1?'NO':'YES'); ?><br />
Follow Up: <?php echo ($s->followup!=1?'NO':'YES'); ?> (<?php echo $s->followup_step; ?>)
</small></td>
<td>
    <?php $nc->button('edit', 'Edit', 'newsletter_edit(this.form,' . $s->id . ')'); ?>
    <?php $nc->button('remove', 'Remove', 'newsletter_remove(this.form,' . $s->id . ')'); ?>
    <?php $nc->button('status', 'Confirm', 'newsletter_set_status(this.form,' . $s->id . ',\'C\')'); ?>
    <?php $nc->button('status', 'Unconfirm', 'newsletter_set_status(this.form,' . $s->id . ',\'S\')'); ?>
    <?php $nc->button('resend', 'Resend confirmation', 'newsletter_resend(this.form,' . $s->id . ')'); ?>
    <?php $nc->button('resend_welcome', 'Resend welcome', 'newsletter_resend_welcome(this.form,' . $s->id . ')'); ?>
    <?php $nc->button('feed', 'Switch feed', 'newsletter_feed(this.form,' . $s->id . ')'); ?>
    <?php $nc->button('followup', 'Switch follow up', 'newsletter_followup(this.form,' . $s->id . ')'); ?>
</td>
<td><small>
        <?php
        $query = $wpdb->prepare("select name,value from " . $wpdb->prefix . "newsletter_profiles where newsletter_id=%d", $s->id);
        $profile = $wpdb->get_results($query);
        foreach ($profile as $field) {
            echo htmlspecialchars($field->name) . ': ' . htmlspecialchars($field->value) . '<br />';
        }
        echo 'Token: ' . $s->token;
?>
</small></td>
</tr>
<?php } ?>
</table>
<?php } ?>
    </form>
</div>
