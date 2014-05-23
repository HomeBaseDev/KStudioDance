<?php

@include_once 'commons.php';

$options = get_option('newsletter_protect');

if ($action == 'save') {
    $options = stripslashes_deep($_POST['options']);

    if ($errors == null) {
        update_option('newsletter_protect', $options);
    }

    if (empty($options['url'])) {
        $messages = 'A readirect URL should be provided';
    }
}


$nc = new NewsletterControls($options);

$nc->errors($errors);
$nc->messages($messages);
?>
<div class="wrap">

    <h2>Content locking</h2>
    <p>
        To lock a piece of content in a post just surround it with [newsletter_lock]...[/newsletter_lock]. The message configured
        below will be shown instead of the content. When a subscriber uses the unlocking link (that you can provide in newsletter or
        welcome email with the placeholder {unlock_url}) any locked content will be shown (anywhere in the blog).
    </p>
    <p>
        When a subscriber clicks the unlocking link he will be redirected to the url configured below. See the usage ideas below to better
        understand
    </p>
    <?php require_once 'header.php'; ?>

    <form method="post" action="">
    <?php wp_nonce_field(); ?>

        <h3>Configuration</h3>
        <table class="form-table">
            <tr valign="top">
                <th>Destination URL after content unlocking</th>
                <td>
                    <?php $nc->text('url', 60); ?>
                </td>
            </tr>
            <tr valign="top">
                <th>Denied content message</th>
                <td>
                    <?php $nc->textarea('message'); ?>
                    <br />
                    Use HTML to format the message. WordPress shortcode provided by other plugin will be
                    "executed".
                </td>
            </tr>
        </table>
        <p class="submit">
            <?php $nc->button('save', __('Save', 'newsletter')); ?>
        </p>

        <h3>Usage ideas</h3>
        <p>
            The best example is to tell you how I'm using it on my blog. On www.satollo.com I have some premium contents, a number of
            posts with information, pictures and videos that I make available only to newsletter subscribers.
        </p>
        <p>
            Those premium posts are broken up in pieces where some are readable and some are hidden. The open parts are useful to stimulate the reader curiosity.
            The hidden parts are surrounded by newsletter locking shortcode ([newsletter_lock] some content [/newsletter_lock]).
        </p>
        <p>
            The message shown in place of the hidden parts is something like: "Premium content! To access it just subscribe the free newsletter and you'll
            suddenly receive a link to see this content".
        </p>
        <p>
            On welcome email I added the {unlock_url} which is the link that unlock all the protected content and I configured the redirection URL
            to a blog tag page (eg. http://www.satollo.com/tag/reserved) that lists all premium posts (I tag all premium posts with "reserved" but you can use
            a category to group them as well you can have an "index" posts with links to all protected posts or even have a single premium post where to
            redirect the user).
        </p>
        <p>
            Usually I add the unlocking URL on any newsletter I send out just as reminder.
        </p>
    </form>
</div>
