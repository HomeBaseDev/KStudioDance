<?php

@include_once 'commons.php';

$options = get_option('newsletter_i18n', array());

if ($action == 'save') {
    $options = stripslashes_deep($_POST['options']);
    update_option('newsletter_i18n', $options);
}

$nc = new NewsletterControls($options);

$nc->errors($errors);
$nc->messages($messages);
?>

<div class="wrap">

    <h2><?php _e('Newsletter Texts', 'newsletter'); ?> <!--<a target="_blank" href="http://www.satollo.net/plugins/newsletter#main"><img src="<?php echo get_option('siteurl'); ?>/wp-content/plugins/newsletter-pro/help.png"/></a>--></h2>

    <?php require_once 'header.php'; ?>

    <form method="post" action="">
        <?php wp_nonce_field(); ?>

        <h3>Subscription form labels</h3>

        <table class="form-table">
            <tr valign="top">
                <th><?php _e('Name field', 'newsletter'); ?></th>
                <td>
                    <?php $nc->text('form_name', 40); ?>
                </td>
            </tr>
            <tr valign="top">
                <th><?php _e('Email field', 'newsletter'); ?></th>
                <td>
                    <?php $nc->text('form_email', 40); ?>
                </td>
            </tr>
            <tr valign="top">
                <th><?php _e('List field', 'newsletter'); ?></th>
                <td>
                    <?php $nc->text('form_lists', 40); ?>
                </td>
            </tr>
            <tr valign="top">
                <th><?php _e('Submit button', 'newsletter'); ?></th>
                <td>
                    <?php $nc->text('form_submit', 40); ?>
                </td>
            </tr>
        </table>
        <p class="submit">
            <?php $nc->button('save', __('Save', 'newsletter')); ?>
        </p>

        <h3>Widget labels</h3>
        <table class="form-table">
            <tr>
                <th><?php _e('Name field', 'newsletter'); ?></th>
                <td>
                    <?php $nc->text('widget_name', 40); ?>
                </td>
            </tr>
            <tr>
                <th><?php _e('Email field', 'newsletter'); ?></th>
                <td>
                    <?php $nc->text('widget_email', 40); ?>
                </td>
            </tr>
            <tr valign="top">
                <th><?php _e('List field', 'newsletter'); ?></th>
                <td>
                    <?php $nc->text('widget_lists', 40); ?>
                </td>
            </tr>
            <tr>
                <th><?php _e('Submit button', 'newsletter'); ?></th>
                <td>
                    <?php $nc->text('widget_submit', 40); ?>
                </td>
            </tr>
        </table>
        <p class="submit">
            <?php $nc->button('save', __('Save', 'newsletter')); ?>
        </p>

        <h3>Embedded form labels</h3>
        <table class="form-table">
            <tr>
                <th><?php _e('Name field', 'newsletter'); ?></th>
                <td>
                    <?php $nc->text('embedded_name', 40); ?>
                </td>
            </tr>
            <tr>
                <th><?php _e('Email field', 'newsletter'); ?></th>
                <td>
                    <?php $nc->text('embedded_email', 40); ?>
                </td>
            </tr>
            <tr valign="top">
                <th><?php _e('List field', 'newsletter'); ?></th>
                <td>
                    <?php $nc->text('embedded_lists', 40); ?>
                </td>
            </tr>
            <tr>
                <th><?php _e('Submit button', 'newsletter'); ?></th>
                <td>
                    <?php $nc->text('embedded_submit', 40); ?>
                </td>
            </tr>
        </table>
        <p class="submit">
            <?php $nc->button('save', __('Save', 'newsletter')); ?>
        </p>

 <h3>Profile form labels</h3>
        <table class="form-table">
            <tr>
                <th><?php _e('Submit button', 'newsletter'); ?></th>
                <td>
                    <?php $nc->text('profile_submit', 40); ?>
                </td>
            </tr>
        </table>
        <p class="submit">
            <?php $nc->button('save', __('Save', 'newsletter')); ?>
        </p>
    </form>
</div>
