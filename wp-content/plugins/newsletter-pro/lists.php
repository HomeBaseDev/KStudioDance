<?php
@include_once 'commons.php';

$options = get_option('newsletter_lists');

if ($action == 'save') {
    $options = stripslashes_deep($_POST['options']);
    update_option('newsletter_lists', $options);
}

$nc = new NewsletterControls($options);
$nc->errors($errors);
$nc->messages($messages);
?>

<div class="wrap">

<h2>Newsletter Lists</h2>

    <form action="" method="post">
        <?php wp_nonce_field(); ?>

        <h3>Configuration</h3>
        <table class="form-table">
            <tr>
                <th>Enabled?</th>
                <td>
                    <?php $nc->yesno('enabled'); ?>
                </td>
            </tr>
        </table>
        <p class="submit"><?php $nc->button('save', 'Save'); ?></p>
        
        <?php for ($i=1; $i<=9; $i++) { ?>
        <h3>List <?php echo $i; ?></h3>
        <table class="form-table">
            <tr>
                <th>Name</th>
                <td>
                    <?php $nc->text('name_' . $i); ?>
                </td>
            </tr>
            <tr>
                <th>Type</th>
                <td>
                    <?php $nc->select('type_' . $i, array('public'=>'Public', 'private'=>'Private')); ?>
                </td>
            </tr>
        </table>
        <p class="submit"><?php $nc->button('save', 'Save'); ?></p>
        <?php } ?>
    </form>
</div>