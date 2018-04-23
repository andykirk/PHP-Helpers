<?php
namespace AndyKirk\PHPHelpers\HTMLHelpers;

class formHelperValidator
{
    public function validate($el, $type, $default_val, $sumbitted_val) {
        $required = isset($el['required'])
                  ? (bool) $el['required']
                  : false;
        #var_dump($required);
        switch ($type) {
            case 'email':
                return $this->email($sumbitted_val, $default_val, $required);
                break;
            default:
                return false;
        }

    }

    protected function email($value, $default, $required) {
        #var_dump((empty($value) || $value == $default) && !$required);
        if ((empty($value) || $value == $default) && !$required) {
            return true;
        }
        $result = filter_var($value, FILTER_VALIDATE_EMAIL);
        return $result;
    }
}

class FormHelper {

    public $form_html;
    public $html;
    public $id;
    public $input;
    public $output;

    protected $validator = false;

    public function __construct ($id, $input, array $options = array()) {
        $this->id     = $id;
        $this->input  = $input;

    }

    public function process(array $data, array $defaults = array()) {
        if (!preg_match('#.*(<form.*id="' . $this->id . '".*</form>).*#s', $this->input, $matches)) {
            return;
        }
        #echo "<pre>\n";var_dump($matches[1]);echo "</pre>\n";exit;
        $orig_form = $matches[1];
        $form = $matches[1];

        // xhtmlify the form:
        // boolean attributes:
        $boolean_attibutes = array(
            'checked',
            'disabled',
            'required',
            'novalidate');
        $temp_form = preg_replace('#(' . implode('|', $boolean_attibutes) .')([^=])#', '$1="$1"$2', $form);
        #...

        #echo "<pre>\n";var_dump($form);echo "</pre>\n";exit;

        $xml_form = '<?xml version="1.0" encoding="utf-8"?>' . $temp_form;
        $xml = new SimpleXMLElement($xml_form);

        $els = $xml->xpath('//input[@value and @id]|//select|//textarea');

        #echo "<pre>\n";var_dump($els);echo "</pre>\n";exit;

        $form_values  = array();
        $form_errors  = array();
        $clean_values = array();
        $input_types  = array();
        foreach ($els as $key => $el) {
            $id    = (string) $el['id'];
            $value = isset($el['value'])
                   ? (string) $el['value']
                   : false;
            #echo "<pre>\n";var_dump($value);echo "</pre>\n";
            $type = isset($el['type'])
                  ? (string) $el['type']
                  : false;
            // textbox:
            if ($value === false && isset($el[0])) {
                $value = $el[0];
                $type = 'textarea';
            }
            // select:
            #...
            $input_types[$id] = $type;
            // override if passed via defaults:
            if (isset($defaults[$id])) {
                $value = $defaults[$id];
            }
            if (!isset($data[$id])) {
                $form_values[$id] = $value;
                continue;
            }
            if (!$this->validator) {
                $this->setValidator();
            }
            $valid = $this->validator->validate($el, $input_types[$id], $value, $data[$id]);
            #$valid = true;
            if ($valid) {
                #$clean_values[$id] = $this->validator->clean($el, $value, $data[$id]);
            } else {
                $form_errors[] = $id;
            }
            $form_values[$id] = $data[$id];
        }
        if (!empty($form_errors)) {
            #echo "<pre>\n";var_dump($form_errors);echo "</pre>\n";
            foreach ($form_errors as $id) {
                $form = preg_replace('/<!--\s?#' . $id . '\.error:\s(.*)-->/', '$1', $form);
            }
        }
        if (!empty($form_values)) {
            foreach ($form_values as $id => $value) {
                if ($input_types[$id] == 'email' ||
                    $input_types[$id] == 'text') {
                    if (preg_match('#<[^>]+id="' . $id . '"[^>]+>#', $form, $matches)) {
                        #echo "<pre>\n";var_dump($matches);echo "</pre>\n";
                        if ($input_types[$id] == 'input') {
                            $el = preg_replace('#value="[^"]*"#', 'value="' . $value . '"', $matches[0]);
                            $form = str_replace($matches[0], $el, $form);
                        }

                    }
                }
            }
        }
        #echo "<pre>\n";var_dump($form);echo "</pre>\n";exit;
        #echo "<pre>\n";var_dump($form_errors);echo "</pre>\n";
        #echo "<pre>\n";var_dump($form_values);echo "</pre>\n";
        #echo "<pre>\n";var_dump($clean_values);echo "</pre>\n";exit;
        $this->output = str_replace($orig_form, $form, $this->input);
        return;
    }

    public function setValidator($validator = false) {
        if (!$validator) {
            $validator = new formHelperValidator();
        }
        $this->validator = $validator;
    }

    /*public function getOutput() {
        $return = $this->html;

        $return = str_replace('</body>', '<p>Test</p></body>', $return);
        return $return;
    }*/
}
ob_start();
?><!doctype html>
<!-- paulirish.com/2008/conditional-stylesheets-vs-css-hacks-answer-neither/ -->
<!--[if lt IE 7]> <html class="no-js lt-ie9 lt-ie8 lt-ie7" lang="en"> <![endif]-->
<!--[if IE 7]>    <html class="no-js lt-ie9 lt-ie8" lang="en"> <![endif]-->
<!--[if IE 8]>    <html class="no-js lt-ie9" lang="en"> <![endif]-->
<!-- Consider adding a manifest.appcache: h5bp.com/d/Offline -->
<!--[if gt IE 8]><!--> <html class="no-js" lang="en"> <!--<![endif]-->
<head>
<meta charset="utf-8">

<title>Form helper</title>
<meta name="description" content="">

<!-- Mobile viewport optimized: h5bp.com/viewport -->
<meta name="viewport" content="width=device-width">

<!-- -->
<link rel="stylesheet" href="css/inuit.css">
<!-- -->
</head>
<body>
<h1>Form helper</h1>

<form id="contactform" action="/experiments/form-helper.php" method="post" novalidate>
<!-- form.success: <div class="success">
<p>Success! Submitted values were:</p>
<ul id="values">
<li class="value"><li>
</ul>
</div> -->
<fieldset id="contactformInputs">
<legend>To contact me, please fill out this short form:</legend>
<input type="hidden" id="token" name="token" class="hidden" value="" />
<ol>
<li>
    <label for="name">
        Your name:
        <!-- #name.error: <br /><strong class="errorText">You did not enter you name.</strong> -->
    </label>
    <input type="text" name="name" id="name" value="" required />
</li>
<li>
    <label for="email">
        Your email address:
        <!-- #email.error: <br /><strong class="errorText">You must enter a valid email address or I will not be able to respond to your query.</strong> -->
    </label>
    <input type="email" name="email" id="email" value="andy.kirk@npeu.ox.ac.uk" required />
</li>
<li>
    <label for="message">Your message:
        <!-- #message.error: <br /><strong class="errorText">You did not enter any message.</strong> -->
    </label>
    <textarea name="message" id="message" rows="10" cols="50" required>Sample text</textarea>
</li>
</ol>
</fieldset>
<fieldset id="contactformControls" class="controls">
<legend class="hidden"></legend>
<ol>
<li>
    <!-- #human.error: <strong class="errorText">You must check the 'anti-spam' box:</strong><br /> -->
    <input type="hidden" class="hidden" name="human" value="0" />
    <input type="checkbox" name="human" id="human" tabindex="1" value="1" class="checkradio" required />
    <label for="human" class="checkradioLabel">
         To help reduce spam, please tick this box before sending your message.
    </label>
</li>
<li>
    <input type="submit" name="send" id="send" class="button" value="Send Message" tabindex="1" />
</li>
</ol>
</fieldset>
</form>
</body>
</html>
<?php
$output = ob_get_contents();
ob_end_clean();


$token                  = md5(uniqid(rand(), true));
$_SESSION['token']      = $token;
$_SESSION['token_time'] = time();
$form_vals = array(
    'token' => $token,
    'name'  => 'test'
);

#echo $output; exit;

#require_once 'libs/functions.php';
#require_once 'libs/html_parser.php';
$options = array(
    'show_form_on_success' => false
);
$form = new formHelper('contactform', $output, $options);
$form->process($_POST, $form_vals);
echo $form->output;
/*
if($form->is_valid) {
    $values = $form->values:
    // do something with the values like save to a database
    // or pass to an email handler.
}
// If the form isn't valid, the output will already contain uncommented
// messages and sticky values.
*/
#echo $form->getOutput();

?>