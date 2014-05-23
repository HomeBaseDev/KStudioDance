<?php

if (!isset($newsletter_options_main['no_translation'])) {
    $plugin_dir = basename(dirname(__FILE__));
    load_plugin_textdomain('newsletter', 'wp-content/plugins/' . $plugin_dir . '/languages/');
}

$action = $_REQUEST['act'];
$step = null;
if (isset($action) && !check_admin_referer()) die('Invalid call');
$errors = null;
$messages = null;

/**
 * Utility class to generate HTML form fields.
 */
class NewsletterControls {

    var $data;
    var $action = false;
    
    function errors($errors) {
        if (is_null($errors)) return;
        echo '<script type="text/javascript">';
        echo 'alert("' . addslashes($errors) . '");';
        echo '</script>';
    }

    function messages($messages) {
        if (is_null($messages)) return;
        echo '<script type="text/javascript">';
        echo 'alert("' . addslashes($messages) . '");';
        echo '</script>';
    }

    function NewsletterControls($options=null) {
        $this->data = $options;
    }

    function yesno($name) {
        $value = isset($this->data[$name])?(int)$this->data[$name]:0;

        echo '<select style="width: 60px" name="options[' . $name . ']">';
        echo '<option value="0"';
        if ($value == 0) echo ' selected';
        echo '>No</option>';
        echo '<option value="1"';
        if ($value == 1) echo ' selected';
        echo '>Yes</option>';
        echo '</select>';
    }

    function select($name, $options) {
        $value = $this->data[$name];

        echo '<select name="options[' . $name . ']">';
        foreach($options as $key=>$label) {
            echo '<option value="' . $key . '"';
            if ($value == $key) echo ' selected';
            echo '>' . htmlspecialchars($label) . '</option>';
        }
        echo '</select>';
    }

    function select_grouped($name, $groups) {
        $value = $this->data[$name];

        echo '<select name="options[' . $name . ']">';

        foreach($groups as $group) {
            echo '<optgroup label="' . htmlspecialchars($group['']) . '">';
            foreach($group as $key=>$label) {
                if ($key == '') continue;
                echo '<option value="' . $key . '"';
                if ($value == $key) echo ' selected';
                echo '>' . htmlspecialchars($label) . '</option>';
            }
            echo '</optgroup>';
        }
        echo '</select>';
    }

    function value($name) {
        echo htmlspecialchars($this->data[$name]);
    }

    function text($name, $size=20) {
        echo '<input name="options[' . $name . ']" type="text" size="' . $size . '" value="';
        echo htmlspecialchars($this->data[$name]);
        echo '"/>';
    }

    function hidden($name) {
        echo '<input name="options[' . $name . ']" type="hidden" value="';
        echo htmlspecialchars($this->data[$name]);
        echo '"/>';
    }

    function button($action, $label, $function=null) {
        if (!$this->action) echo '<input name="act" type="hidden" value=""/>';
        $this->action = true;
        if ($function != null) {
            echo '<input class="newsletter-button" type="button" value="' . $label . '" onclick="this.form.act.value=\'' . $action . '\';' . htmlspecialchars($function) . '"/>';
        }
        else {
            echo '<input class="newsletter-button" type="button" value="' . $label . '" onclick="this.form.act.value=\'' . $action . '\';this.form.submit()"/>';
        }
    }

    function button_confirm($action, $label, $message) {
        if (!$this->action) echo '<input name="act" type="hidden" value=""/>';
        $this->action = true;
        echo '<input class="newsletter-button" type="button" value="' . $label . '" onclick="this.form.act.value=\'' . $action . '\';if (confirm(\'' .
            htmlspecialchars($message) . '\')) this.form.submit()"/>';
    }

    function editor($name, $rows=5, $cols=75) {
        echo '<textarea class="visual" name="options[' . $name . ']" wrap="off" rows="' . $rows . '" cols="' . $cols . '">';
        echo htmlspecialchars($this->data[$name]);
        echo '</textarea>';
    }

    function textarea($name, $rows=5, $cols=75) {
        echo '<textarea name="options[' . $name . ']" wrap="off" rows="' . $rows . '" cols="' . $cols . '">';
        echo htmlspecialchars($this->data[$name]);
        echo '</textarea>';
    }

    function email($prefix) {
        echo 'Subject:<br />';
        $this->text($prefix . '_subject', 70);
        echo '<br />Message:<br />';
        $this->editor($prefix . '_message');
    }

    function checkbox($name, $label='') {
        echo '<input type="checkbox" id="' . $name . '" name="options[' . $name . ']" value="1"';
        if (isset($this->data[$name])) echo ' checked="checked"';
        echo '/>';
        if ($label != '') echo ' <label for="' . $name . '">' . $label . '</label>';
    }

    function hours($name) {
        $hours = array();
        for($i=0; $i<24; $i++) {
            $hours[''.$i] = ''.$i;
        }
        $this->select($name, $hours);
    }

    function days($name) {
        $days = array(0=>'Every day', 1=>'Monday', 2=>'Tuesday', 3=>'Wednesday', 4=>'Thursday', 5=>'Friday', 6=>'Saturday', 7=>'Sunday');
        $this->select($name, $days);
    }
}

?>
