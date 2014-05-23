<?php

// Subscription form (traslate "your name", "your email" and the button "subscribe me")
$newsletter_labels['subscription_form'] =
'<div class="newsletter-form"><form method="post" action="" style="text-align: center">
<input type="hidden" name="na" value="s"/>
<table cellspacing="3" cellpadding="3" border="0" width="50%">
<!--name--><tr><td>{label_form_name}</td><td><input type="text" value="{nn}" name="nn" size="30"/></td></tr><!--/name-->
<tr><td>{label_form_email}</td><td align="left"><input type="text" name="ne" value="{ne}" size="30"/></td></tr>
<!--lists--><tr><td>{label_form_lists}</td><td align="left">{lists}</td></tr><!--/lists-->
<tr><td colspan="2" style="text-align: center"><input type="submit" value="{label_form_submit}"/></td></tr>
</table>
</form></div>';

$newsletter_labels['profile_form'] =
'<div class="newsletter-form"><form method="post" action="" style="text-align: center">
<input type="hidden" name="na" value="ps"/>
<table cellspacing="3" cellpadding="3" border="0" width="50%">
<tr><td>{label_form_name}</td><td><input type="text" value="{nn}" name="nn" size="30"/></td></tr>
<tr><td>{label_form_email}</td><td><input type="text" name="ne" value="{ne}" size="30"/></td></tr>
<!--lists--><tr><td>{label_form_lists}</td><td align="left">{lists}</td></tr><!--/lists-->
<tr><td colspan="2" style="text-align: center"><input type="submit" value="{label_profile_submit}"/></td></tr>
</table>
</form></div>';

$newsletter_labels['widget_form'] =
'<form action="{newsletter_url}" method="post">
{text}
<!--name--><p><input type="text" name="nn" value="{label_widget_name}" onclick="if (this.defaultValue==this.value) this.value=\'\'" onblur="if (this.value==\'\') this.value=this.defaultValue"/></p><!--/name-->
<p><input type="text" name="ne" value="{label_widget_email}" onclick="if (this.defaultValue==this.value) this.value=\'\'" onblur="if (this.value==\'\') this.value=this.defaultValue"/></p>
<!--lists--><p>{lists}</p><!--/lists-->
<p><input type="submit" value="{label_widget_submit}"/></p>
<input type="hidden" name="na" value="s"/>
</form>';

$newsletter_labels['embedded_form'] =
'<div class="newsletter-embed-form"><form action="{newsletter_url}" method="post">
<p><input type="text" name="ne" value="{label_embedded_email}" onclick="if (this.defaultValue==this.value) this.value=\'\'" onblur="if (this.value==\'\') this.value=this.defaultValue"/>
<!--name-->&nbsp;<input type="text" name="nn" value="{label_embedded_name}" onclick="if (this.defaultValue==this.value) this.value=\'\'" onblur="if (this.value==\'\') this.value=this.defaultValue"/><!--/name-->
<input type="submit" value="{label_embedded_submit}"/>
<input type="hidden" name="na" value="s"/></p>
</form></div>';

// Example of embedded form without name

$newsletter_labels['embedded_form_noname'] =
'<form action="{newsletter_url}" method="post">
<p><input type="text" name="ne" value="Your email" onclick="if (this.defaultValue==this.value) this.value=\'\'" onblur="if (this.value==\'\') this.value=this.defaultValue"/>
<input type="submit" value="Subscribe"/>
<input type="hidden" name="na" value="s"/></p>
</form>';


// Errors on subscription
$newsletter_labels['error_email'] = 'Wrong email address. <a href="javascript:history.back()">Go back</a>.';
$newsletter_labels['error_name'] = 'The name cannot be empty. <a href="javascript:history.back()">Go back</a>.';

?>
