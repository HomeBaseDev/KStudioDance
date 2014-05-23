<?php
@include_once 'commons.php';

$options = get_option('newsletter_stats');
if ($action == 'save') {
    $options = stripslashes_deep($_POST['options']);
    update_option('newsletter_stats', $options);
}

$nc = new NewsletterControls($options);
$nc->errors($errors);
$nc->messages($messages);
?>

<div class="wrap">

    <h2>Newsletter Statistics</h2>
    <ul>
        <li><a href="?page=newsletter-pro/statistics-clicks.php">Clicks</a></li>
        <li><a href="?page=newsletter-pro/statistics-profiles.php">Profiles</a></li>
        <li><a href="?page=newsletter-pro/statistics-users.php">Users</a></li>
    </ul>
    <!--
    <form action="" method="post">
        <?php wp_nonce_field(); ?>

        <h3>Configuration</h3>
        <table class="form-table">
            <tr>
                <th>Tracking alternative URL<br /><small>to be used for sites with tracking issues</small></th>
                <td>
                    <?php $nc->yesno('alternative'); ?>
                </td>
            </tr>
        </table>

    </form>
    -->

</div>
