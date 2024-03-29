<?php
// This file contains the default options values

// Subscription page introductory text (befor the subscription form)
$newsletter_default_options['subscription_text'] =
"<p>Inscreva-se na minha newsletter preenchendo os campos abaixo.
Tentarei lhe fazer feliz.</p>
<p>Um email de confirmação será enviado a sua caixa de entrada:
por favor leia as instruções e complete seu registro.</p>";

// Message show after a subbscription request has made.
$newsletter_default_options['subscribed_text'] =
"<p>Você foi inscrito corretamente na newsletter.
Em alguns minutos você receberá um email de confirmação. Siga o link para confirmar a inscrição.
Se o email demorar mais do que 15 minutos para chegar, cheque sua caixa de SPAM.</p>";

// Confirmation email subject (double opt-in)
$newsletter_default_options['confirmation_subject'] =
"{name}, confirme sua inscrição no site {blog_title}";

// Confirmation email body (double opt-in)
$newsletter_default_options['confirmation_message'] =
"<p>Oi {name},</p>
<p>Recebemos um pedido de inscrição nos nossos informativos deste email. Você pode confirmar
<a href=\"{subscription_confirm_url}\"><strong>clicando aqui</strong></a>.
Se você não puder seguir o link, acesse este endereço:</p>
<p>{subscription_confirm_url}</p>
<p>Se o pedido de inscrição não veio de você, apenas ignore esta mensagem.</p>
<p>Obrigado.</p>";


// Subscription confirmed text (after a user clicked the confirmation link
// on the email he received
$newsletter_default_options['confirmed_text'] =
"<p>Sua inscrição foi confirmada!
Obrigado {name}.</p>";

$newsletter_default_options['confirmed_subject'] =
"Bem vindo(a) a bordo, {name}";

$newsletter_default_options['confirmed_message'] =
"<p>A mensagem confirma a sua inscrição nos nossos informativos.</p>
<p>Obrigado.</p>";

// Unsubscription request introductory text
$newsletter_default_options['unsubscription_text'] =
"<p>Cancele a sua inscrição nos informativos
<a href=\"{unsubscription_confirm_url}\">clicando aqui</a>.";

// When you finally loosed your subscriber
$newsletter_default_options['unsubscribed_text'] =
"<p>Sua inscrição foi cancelada. Inscreva-se novamente quando quiser.</p>";

// Labels
$newsletter_default_options_i18n['form_name'] = 'Seu&nbsp;nome';
$newsletter_default_options_i18n['form_email'] = 'Seu&nbsp;email';
$newsletter_default_options_i18n['form_submit'] = 'Assinar';

$newsletter_default_options_i18n['widget_name'] = $newsletter_default_options_i18n['form_name'];
$newsletter_default_options_i18n['widget_email'] = $newsletter_default_options_i18n['form_email'];
$newsletter_default_options_i18n['widget_submit'] = $newsletter_default_options_i18n['form_submit'];

$newsletter_default_options_i18n['embedded_name'] = $newsletter_default_options_i18n['form_name'];
$newsletter_default_options_i18n['embedded_email'] = $newsletter_default_options_i18n['form_email'];
$newsletter_default_options_i18n['embedded_submit'] = $newsletter_default_options_i18n['form_submit'];
?>