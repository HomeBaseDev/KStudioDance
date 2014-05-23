<?php
@include_once 'commons.php';

$options = get_option('newsletter_smtp');

if ($action == 'save' || $action == 'test') {
    $options = stripslashes_deep($_POST['options']);
    update_option('newsletter_smtp', $options);
}

$nc = new NewsletterControls($options);
$nc->errors($errors);
$nc->messages($messages);
?>

<div class="wrap">

    <h2><?php _e('Newsletter SMTP', 'newsletter'); ?></h2>

<script type="text/javascript">
    function newsletter_test(f, i)
    {
        f.elements['test'].value = i;
        f.submit();
    }
</script>

<form method="post" action="">
    <input type="hidden" name="test"/>
    <?php wp_nonce_field(); ?>

    <?php
    $test = $_REQUEST['test'];

    if ($test != '') {

        echo '<h3>Test report</h3>';

        $options_newsletter = get_option('newsletter');

        require_once ABSPATH . WPINC . '/class-phpmailer.php';
        require_once ABSPATH . WPINC . '/class-smtp.php';
        $mail = new PHPMailer();

        $mail->IsSMTP();
        $mail->SMTPDebug = true;

        $mail->CharSet = 'UTF-8';

        $message = 'This Email is sent by PHPMailer of WordPress';
        $mail->IsHTML(false);
        $mail->Body = $message;

        $mail->From = $newsletter_options_main['sender_email'];

        $mail->FromName = $newsletter_options_main['sender_name'];

        $mail->Subject = '[' . get_option('blogname') . '] SMTP test';

        $mail->Host = $options['host_' . $test];
        if (!empty($options['port_' . $test])) $mail->Port = (int)$options['port_' . $test];

        if (isset($options['auth_' . $test])) {
            $mail->SMTPAuth = true;
            $mail->Username = $options['user_' . $test];
            $mail->Password = $options['pass_' . $test];
        }

        $mail->SMTPKeepAlive = true;


        $mail->ClearAddresses();
        $mail->AddAddress($options['test_email']);
        echo '<textarea style="width:100%;height:250px;font-size:10px">';
        ob_start();
        $mail->Send();
        $mail->SmtpClose();
        echo htmlspecialchars(ob_get_clean());
        ob_end_clean();
        echo '</textarea>';

        if ($mail->IsError()) {
            echo $mail->ErrorInfo;
        }
        else {
            echo 'TEST OK';
        }
    }
    ?>

    <h3>General options</h3>
    <table class="form-table">
        <tr>
            <th>Enable external SMTP?</th>
            <td><?php $nc->yesno('enabled'); ?></td>
        </tr>

        <tr>
            <th>Test address</th>
            <td>
                <?php $nc->text('test_email', 30); ?>
                <br />
                Send SMTP test email to this address
            </td>
        </tr>
    </table>
    <p class="submit">
        <?php $nc->button('save', 'Save'); ?>
    </p>

    <?php for($i=1; $i<=1; $i++) { ?>

    <h3>SMTP <?php echo $i; ?></h3>
    <table class="form-table">
        <tr>
            <th>SMTP host/port</th>
            <td>
                host: <?php $nc->text('host_' . $i, 30); ?>
                port: <?php $nc->text('port_' . $i, 6); ?>
                <br />
                Leave port empty for default value. To use gmail try host "ssl://smtp.gmail.com" and port "465" (without quotes).
            </td>
        </tr>
        <tr>
            <th>Authentication</th>
            <td>
                <input type="checkbox" name="options[auth_<?php echo $i; ?>]" value="1" <?php echo $options['auth_' . $i] != null?'checked':''; ?> />
                SMTP requires authentication
                <br />
                user: <?php $nc->text('user_' . $i, 30); ?>
                password: <?php $nc->text('pass_' . $i, 30); ?>
            </td>
        </tr>
    </table>
    <p class="submit">
        <?php $nc->button('save', 'Save'); ?>
        <?php $nc->button('test', 'Test', 'newsletter_test(this.form, ' . $i . ')'); ?>
    </p>
    <?php } ?>

</form>


</div>