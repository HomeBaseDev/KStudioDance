<?php

// Suscripcion pagina de introduccion de texto (formulario de suscripcion)
$newsletter_default_options['subscription_text'] =
"<p>Suscribirse a mi boletín llenando el formulario a continuacion.
Voy a tratar de hacerte feliz.</p>
<p>Un correo de confirmacion será enviado a su buzon de correo:
por favor, lea las instrucciones en su interior para completar la suscripcion.</p>";

// Mostrar mensaje despues de una solicitud de suscripcion hecha.
$newsletter_default_options['subscribed_text'] =
"<p>Con exito suscrito a mi boletín informativo.
Usted recibirá en pocos minutos un email de confirmacion. Siga el enlace
en el para confirmar la suscripcion. Si el correo tarda mas de 15
minutos en aparecer en su buzon de correo, revise la carpeta de spam.</p>";

// Tema de correo electronico de confirmacion (double opt-in)
$newsletter_default_options['confirmation_subject'] =
"{name}, confirmar su suscripcion a {blog_title}";

// Cuerpo confirmacion por correo electronico (double opt-in)
$newsletter_default_options['confirmation_message'] =
"<p>Hola {name},</p>
<p>He recibido una solicitud de suscripcion para esta direccion de correo electronico. Usted puede confirmar:
<a href=\"{subscription_confirm_url}\"><strong>click aquí</strong></a>.
Si usted no puede hacer click en el enlace, utilice el siguiente enlace:</p>
<p>{subscription_confirm_url}</p>
<p>Si esta solicitud de suscripcion no se ha hecho de usted, simplemente ignore este mensaje.</p>
<p>Gracias.</p>";


// Suscripcion confirmacion texto (despues de que un usuario hace clic en el enlace de confirmacion
// en el correo electronico que recibira
$newsletter_default_options['confirmed_text'] =
"<p>Su suscripcion se ha confirmado!
Gracias {name}!</p>";

$newsletter_default_options['confirmed_subject'] =
"Bienvenido a bordo, {name}";

$newsletter_default_options['confirmed_message'] =
"<p>El mensaje de confirmar su suscripcion a {blog_title} newsletter.</p>
<p>Gracias!</p>";

// Darse de baja de la solicitud introduccion de texto
$newsletter_default_options['unsubscription_text'] =
"<p>Por favor, confirme que desea darse de baja mi boletín de noticias
<a href=\"{unsubscription_confirm_url}\">click aquí</a>.";


$newsletter_default_options['unsubscribed_text'] =
"<p>Me hace llorar, pero he quitado su suscripcion ...</p>";

$newsletter_default_options['unsubscribed_subject'] =
"Adios, {name}";

$newsletter_default_options['unsubscribed_message'] =
"<p>Mensaje de confirmacion de su baja en {blog_title} Boletín de noticias.</p>
<p>Adios!</p>";

$newsletter_default_options['subscription_form'] =
'<form method="post" action="" style="text-align: center">
<input type="hidden" name="na" value="s"/>
<table cellspacing="3" cellpadding="3" border="0" width="50%">
<tr><td>Su&nbsp;Nombre</td><td><input type="text" name="nn" size="30"/></td></tr>
<tr><td>Su&nbsp;e-Mail</td><td><input type="text" name="ne" size="30"/></td></tr>
<tr><td colspan="2" style="text-align: center"><input type="submit" value="Suscribirme"/></td></tr>
</table>
</form>';

// Labels
$newsletter_default_options_i18n['form_name'] = 'Su&nbsp;Nombre';
$newsletter_default_options_i18n['form_email'] = 'Su&nbsp;e-Mail';
$newsletter_default_options_i18n['form_submit'] = 'Suscribirme';

$newsletter_default_options_i18n['widget_name'] = $newsletter_default_options_i18n['form_name'];
$newsletter_default_options_i18n['widget_email'] = $newsletter_default_options_i18n['form_email'];
$newsletter_default_options_i18n['widget_submit'] = $newsletter_default_options_i18n['form_submit'];

$newsletter_default_options_i18n['embedded_name'] = $newsletter_default_options_i18n['form_name'];
$newsletter_default_options_i18n['embedded_email'] = $newsletter_default_options_i18n['form_email'];
$newsletter_default_options_i18n['embedded_submit'] = $newsletter_default_options_i18n['form_submit'];
?>
