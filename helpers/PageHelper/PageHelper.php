<?php
namespace AndyKirk\PHPHelpers\PageHelper;
/**
 * PageHelper
 *
 * ...
 *
 * @package PageHelper
 * @author Andy Kirk
 * @copyright Copyright (c) 2015
 * @version 0.1
 **/


/*
    This is NOT an attempt at creating a PHP templating 'language', or even an attempt at providing
    generic templating 'shortcuts'.
    See [Fabien Potencier](http://fabien.potencier.org/templating-engines-in-php.html)'s blog post
    about why PHP isn't really great at being a 'proper' templating language.
    This class is merely an aide to writing a PHP/HTML page _structure_, providing shortcut methods
    and common data and markup patterns representing _my_ preferred way of doing things
    (though the point is that it's flexible enough that the structure can be adapted to any similar
    use and the class can remain untouched).
    It assumes that the data would come from elsewhere and can be reorganised into a format suitable
    for the class to use before it's passed to the class.

    For example, 'index.php' could be the main entry point (after redirection) and code could be
    provided at the top of the script that processes the 'route' and gathers the relevant page data,
    perhaps from a database or flat file-system. This allows the structure and the data to be in
    close proximity, making it easier to understand what's going on.
    It could also be used to help constructing a template or theme for a CMS. In Joomla, for
    example, though Joomla uses output buffering and placeholder 'tags' to render a template, it's
    still convenient to extract the data provided by Joomla (and manipulate if necessary) and use
    this class to help construct the page 'template' as it makes things easier to understand and
    exert control over.


    There are only a few things to learn:

    1. The `show' syntax. This can be used without arguments to compile a html template between the
       `show` and `endshow` tags, replacing any strings prefixed with `$` with variables in the
       objects `data` that match the keys specified by those strings.
       It can also be passed an argument that must match a key in the objects data that is an array.
       Furthermore the `orshow` tag may be used to display alternative content is that array is
       empty or doesn't exist. Use `check('key_name')` to check for a non-empty array without 
       entering the loop.

    2. There is a magic __get method that will return any variable in the objects `data`.
       E.g. `echo $_->page_id`

    3. There is a magic __call method that, aside from proving shortcuts to the `show` methods,
       outputs an HTML attribute with a name matching the method name and a value that matches a
       `data` key specified by the argument, so longs as it's a simple string, otherwise nothing is
       shown.

    4. You can also use `extract()` on the object to allow you to echo out or loop over the
       variables in the objects `data` manually, though you need to make sure (or check) that the
       variable exists, or a NOTICE will be shown.




    The idea is that this class provides a data container and a variety of methods that allows you
    to write up a page template in a number of different ways, depending on needs/preferences etc.
    It SHOULD NOT arbitrarily dictate things - MY preferences can be represented as an example
    implementation.
    Vars should be position-agnostic, e.g. don't have $body_id, $html_class etc as it's possible
    that these vars may used elsewhere instead (if that's the preference) or even in multiple
    locations.
    E.g. $page_id could appear in the <html> or <body> or even a container <div>, or even all 3
    if they're prefixed (e.g. <html id="$page_id">, <body id="p-$page_id">, <div id="c-$page_id">

    It should be possible, however, to allow simlar data to be collected into different containers
    that can be used to represent the different locations required by the implemenatation, e.g.
    script tags in the <head> AND at the bottom on the <body>. Therefore would probably be best
    to group data by type, then (user-defined) locations.
    E.g.:
    data['script_tags']['head']
    data['script_tags']['foot']
    data['script']['head']
    data['script']['foot']


    It should also be possoble to simply extract() the data and us in the HTML directly, allowing
    for varying levels of verbosity, e.g.

    <?php if ($script_tags['head']): ?>
    <!-- Dynamic head JS tags -->
    <?php foreach ($script_tags['head'] as $script_tag): ?>
    <script <?php echo $_->attribs($script_tag); ?>></script>
    <?php endforeach; ?>
    <?php endif; ?>

    OR
    to call a helper method to do the looping itself:

    <?php tags($script_tags['head']); ?>
    (having extracted the data)

    OR even:

    <?php tags('script.head'); ?>


    DILEMMA: To echo or not to echo?

    <?php echo ...; ?> best practice.
    can be shorted to <?= ...; ?> if dev wants to

    <?php x(); ?> omit echo is shorter but means the echo must happen inside a method and I'm sure
    there are a million reasons this is a bad idea.

    I guess so long as they're considered MAGIC methods and don't to the logic or looping themselves
    it may be ok. I'd think testability would be the worst affected.
    Maybe it should be optional?

*/
class PageHelper {


    /**
     * Stores the page data
     * @var array
     **/
    public $data;

    public $html_class_separator = ' ';

    protected $tmp_args;


    /*
     * Set key/flag relative to ob level:
    */
    protected $ob_store = array();
    #protected $show_key = array();
    #protected $orshow   = array();
    #protected $check    = array();

    /**
     * Stores array of valid attributes useable via magic attr method, and if they can contain
     * multiple space-separated values (like classes).
     * @var array
     **/
    public $attributes = array(
        'class' => true,
        'lang'  => false,
        'id'    => false,
        'title' => false
    );

    /**
     * PageHelper::__construct()
     *
     * @param string $config_path path to config file
     */
    public function __construct($config_path = '') {
        $this->setDefaultData();
    }

    /**
     * PageHelper::setDefaultData();
     */
    protected function setDefaultData() {
        $this->data = array(
            'author'       => '',
            'description'  => '',
            'encoding'     => 'utf-8',
            'fonts'        => array(),
            'foot_script'  => '',
            'foot_scripts' => '',
            'head_script'  => '',
            'head_scripts' => array(),
            'html_classes' => 'no-js',
            'keywords'     => '',
            'language'     => 'en-gb',
            'linktags'     => array(),
            'metatags'     => array(),
            'page_id'      => '',
            'page_title'   => '',
            'stylesheets'  => array(),
            'title'        => '',
            'sitename'     => ''
        );
    }


    /**
     * PageHelper:: ();
     *
     * @param string $key
     * @param string|array $items
     */
    public function addClass($key, $items) {
        #var_dump($key); var_dump($items);
        if (!isset($this->data[$key])) {
            return;
        }

        if (is_string($items)) {
            $items = preg_replace('#\s{2,}#', ' ', $items);
            $items = explode(' ', $items);
        }
        #var_dump($items);
        $data = $this->data[$key];

        if (!is_array($data)) {
            $data = preg_replace('#\s{2,}#', ' ', $data);
            $data = explode(' ', $data);
        }

        $new_data = array_unique(array_merge($data, $items));

        if (is_array($this->data[$key])) {
            $this->data[$key] = $new_data;
        } else {
            $this->data[$key] = implode($this->html_class_separator, $new_data);
        }
    }

    /**
     * PageHelper:: ();
     *
     * @param string $key
     * @param string|array $items
     */
    public function removeClass($key, $items) {
        if (!isset($this->data[$key])) {
            return;
        }

        if (is_string($items)) {
            $items = preg_replace('#\s{2,}#', ' ', $items);
            $items = explode(' ', $items);
        }
        #var_dump($items);
        $data = $this->data[$key];

        if (!is_array($data)) {
            $data = preg_replace('#\s{2,}#', ' ', $data);
            $data = explode(' ', $data);
        }

        $new_data = array_diff($data, $items);

        if (is_array($this->data[$key])) {
            $this->data[$key] = $new_data;
        } else {
            $this->data[$key] = implode($this->html_class_separator, $new_data);
        }
    }

    /**
     * PageHelper::escape();
     */
    public function escape($string) {
        $string = mb_convert_encoding($string, 'UTF-8', 'UTF-8');
        $string = htmlentities($string, ENT_QUOTES, 'UTF-8', true);
        
        return $string;
    }
    
    /**
     * PageHelper::replaceVars();
     *
     * @param string $string
     * @param array $data
     */
    public function replaceVars($string, $data, $indent = '') {
        // Parse string for variable names:
        if (preg_match_all('#\$(([a-z][a-z0-9_-]*)(\|[a-z]+[|a-z]*)*)#', $string, $matches, PREG_SET_ORDER)) {
            #echo '<pre>'; var_dump($matches); echo '</pre>';
            foreach($matches as $match) {
                $key = $match[2];
                if (!empty($data[$key])) {
                    $val = $data[$key];
                    if (!is_array($val) && !is_object($val)) {
                    
                        $val = (string) $val;
                        
                        $raw = false;
                        #echo '<pre>'; var_dump($match); echo '</pre>'; //return;
                        // Check for flags/modifiers:
                        if (isset($match[3])) {
                            $pipes = explode('|', trim($match[3], '|'));
                            foreach ($pipes as $pipe) {
                                // Check for 'raw':
                                if ($pipe == 'raw') {
                                    $raw = true;
                                    continue;
                                }
                                // May have a prefix here, or have some other container to make it 
                                // easy to add custom flags/filters.
                                if (method_exists($this, '' . $pipe)) {
                                    if ($pipe == 'indent') {
                                        $val = $this->$pipe($val, $indent);
                                    } else {
                                        $val = $this->$pipe($val);
                                    }
                                }
                            }
                        }
                        
                        if (!$raw) {
                            $val = $this->escape($val);
                        }
                    
                        $string = str_replace($match[0], $val, $string);
                    } else {
                        //trigger_error('Can\'t echo var');
                        return '';
                    }
                } else {
                    //trigger_error('Var `' . $key . '` not found');
                    return '';
                }
            }
        }
        return $string;
    }
    
    /**
     * PageHelper::parseTag();
     *
     * @param string $tring
     * @param array $data
     * @param array $attribs
     */
    public function parseTag($string, $data, $attribs = array()) {
        
        $string = $this->replaceVars($string, $data);
        
        if ($string == '') {
            return '';
        }

        // Append any attributes:
        if (!empty($attribs)) {
            $attrib_string = $this->tagAttribs($attribs);
            $string        = preg_replace('#(\s?/?>)#', ' ' . $attrib_string . '$1', $string, 1);
        }

        return $string;
    }
    
    /**
     * PageHelper::parseBlock();
     *
     * @param string $tring
     * @param array $data
     */
    public function parseBlock($string, $data, $indent = '') {
        
        $string = $this->replaceVars($string, $data, $indent);
        
        return $string;
    }

    /**
     * PageHelper::isTag();
     *
     * @param string $string
     */
    public function isTag($string) {
        return (preg_match('#<[a-z][a-z0-9]*\s?[^>]*>#', $string) === 1);
    }

    /**
     * PageHelper::tagAttribs();
     *
     * @param array $attribs
     */
    public function tagAttribs($attribs)
    {
        $return = array();
        foreach ($attribs as $key=>$value) {
            if (is_numeric($key)) {
                $return[] = $value;
            } else {
                if (!$value) {
                    continue;
                }
                $return[] = $key . '="' . $value . '"';
            }
        }
        return implode(' ', $return);
    }


    /*public function _($tag_or_data_array_or_attr_name = '', $tag_or_attr_data_key = false) {

        if ($tag_or_attr_data_key == false) {
            // Passed only one arg, check it's a valid tag template:
            if ($this->isTag($tag_or_data_array_or_attr_name)) {
                // Parse string for variable names:
                $tag_string = $this->parseTag($tag_or_data_array_or_attr_name, $this->data);
                // Output the tag:
                $this->output_tag($tag_string);
                return;
            } else {
                trigger_error('Only one arg passed but isn\'t a valid tag');
                return;
            }
        }

        // Check if first arg is an attribute and the second arg is a data key:
        if (
            array_key_exists($tag_or_data_array_or_attr_name, $this->attributes)
         && isset($this->data[$tag_or_attr_data_key])
        ) {
            $this->output_attribute($tag_or_data_array_or_attr_name, $this->data[$tag_or_attr_data_key]);
            return;
        }

        // Check if the first arg is a data key of an array and the second arg is a valid tag template:
        if (
            array_key_exists($tag_or_data_array_or_attr_name, $this->data)
         && is_array($this->data[$tag_or_data_array_or_attr_name])
         && $this->isTag($tag_or_attr_data_key)
        ) {
            // Remove the 's' at the end of the key name:
            $singlular = rtrim($tag_or_data_array_or_attr_name, 's');
            if ($singlular == $tag_or_data_array_or_attr_name) {
                trigger_error('Could not singularise the key name');
                return;
            }


            foreach ($this->data[$tag_or_data_array_or_attr_name] as $val) {
                $tag_string = $this->parseTag($tag_or_attr_data_key, array($singlular => $val));
                $this->output_tag($tag_string);
            }
            return;
        }
    }*/

    public function output_attribute($name, $key) {
        if (isset($this->data[$key]) && is_string($this->data[$key])) {
            echo ' ' . $name . '="' . $this->data[$key] . '"';
        }
        echo '';
    }

    public function output_tag($tag_string) {
        echo $tag_string;
        //echo $tag_string;
    }


    public function __set($name, $value)
    {
        #echo "Setting '$name' to '$value'\n";
        $this->data[$name] = $value;
    }

    public function __get($name)
    {
        /*if ($name == 'endtags') {
            $this->endtags();
            return;
        }

        if ($name == 'tag') {
            $this->tag();
            return;
        }

        if ($name == 'endtag') {
            $this->endtag();
            return;
        }*/

        if ($name == 'show') {
            $this->show();
            return;
        }

        if ($name == 'orshow') {
            $this->orshow();
            return;
        }

        if ($name == 'endshow' || $name == 'endcheck') {
            $this->endshow();
            return;
        }




        #echo "Getting '$name'\n";
        if (array_key_exists($name, $this->data)) {
            return $this->data[$name];
        }
        return null;

        $trace = debug_backtrace();
        trigger_error(
            'Undefined property via __get(): ' . $name .
            ' in ' . $trace[0]['file'] .
            ' on line ' . $trace[0]['line'],
            E_USER_NOTICE);
        return null;
    }

    public function __call($name, $arguments)
    {
        // Note: value of $name is case sensitive.
        #echo "Calling object method '$name' " . implode(', ', $arguments). "\n";
        #var_dump($name);
        #var_dump($arguments);
        #var_dump(method_exists($this, $name));
        
        if ($name == 'check') {
            $this->show($arguments[0], true);
        }

        // Check if the $name is an attribute:
        if (array_key_exists($name, $this->attributes)) {
            $this->output_attribute($name, $arguments[0]);
            return;
        }
    }

    /*
    public function tags($key)
    {
        $this->tmp_args = $key;
        ob_start();
    }

    public function endtags()
    {
        $ob = ob_get_clean();

        $key = $this->tmp_args;
        $this->tmp_args = null;

        if (
            !array_key_exists($key, $this->data)
         || !is_array($this->data[$key])
        ) {
            echo "\n";
            return;
        }

        $indent = '';
        if (preg_match('#^(\s)*#', $ob, $matches)) {
            //echo '<pre>'; var_dump($matches); echo '</pre>'; return;
            $indent = $matches[0];
        }
        $tag_template = trim($ob);
        //echo '<pre>'; var_dump($ob); echo '</pre>'; return;

        // Remove the 's' at the end of the key name:
        $singlular = rtrim($key, 's');
        if ($singlular == $key) {
            trigger_error('Could not singularise the key name');
            return;
        }

        $i = 0;
        foreach ($this->data[$key] as $val) {
            $tag_string = $this->parseTag($tag_template, array($singlular => $val));
            if ($i > 0) {
                echo $indent;
            }
            $this->output_tag($tag_string . "\n");
            $i++;
        }

    }

    public function tag()
    {
        ob_start();
    }

    public function endtag()
    {
        $ob = ob_get_contents();
        ob_end_clean();
        #$tag = trim($this->parseTag($ob, $this->data));
        $tag = $this->parseTag($ob, $this->data);
        $this->output_tag($tag . "\n");
    }
    */


    public function show($key = false, $check = false)
    {
        // Record the data key (is present) and start buffering:
        ob_start();
        $ob_level = ob_get_level();
        $this->ob_store[$ob_level] = array();
        #echo '<pre>OBL: (' . $key . ', ' . $check . ') :'; var_dump($ob_level); echo '</pre>';# return;
        if (is_string($key)) {
            #$this->show_key[ob_get_level()] = $key;
            $this->ob_store[$ob_level]['show_key'] = $key;
            if ($check) {
                #$this->check[ob_get_level()] = true;
                $this->ob_store[$ob_level]['check_only'] = true;
            }
        }
        
    }

    public function orshow()
    {
        // Set the start a nested buffer and orshow flag:
        ob_start();
        $ob_level = ob_get_level();
        #echo '<pre>OBL: '; var_dump(ob_get_level()); echo '</pre>';# return;
        #$this->orshow[ob_get_level()] = true;
        $this->ob_store[$ob_level]['or_show'] = true;
    }

    public function endshow()
    {
        $ob_level = ob_get_level();
        $orshow = false;
        // Check the orshow flag and if set, get and end the nested buffer:
        if (isset($this->ob_store[$ob_level]['or_show'])) {
            $orshow_block = ob_get_clean();
            $ob_level = ob_get_level();
            $orshow = true;
        }
        
        // Get the flags locally and reset class members:
        $key = false;
        
        #echo '<pre>OBL: (' . $key . ') :'; var_dump($ob_level); echo '</pre>';# return;
        #echo '<pre>'; var_dump($ob_level); echo '</pre>';# return;
        #echo '<pre>'; var_dump($this->show_key); echo '</pre>';# return;
        if (isset($this->ob_store[$ob_level]['show_key'])) {
            $key = $this->ob_store[$ob_level]['show_key'];
        }
        
        /*$orshow = false;
        if (isset($this->ob_store[$ob_level]['or_show'])) {
            $orshow = $this->ob_store[$ob_level]['or_show'];
        }*/
        
        $check = false;
        if (isset($this->ob_store[$ob_level]['check_only'])) {
            $check = $this->ob_store[$ob_level]['check_only'];
        }
        #echo '<pre>Just checking: '; var_dump($check); echo '</pre>';# return;
        
        unset($this->ob_store[$ob_level]);
        
        
        
        // Get the loop buffer:
        $ob = ob_get_clean();
        
        
        
        
        #echo '<pre>'; var_dump($ob); echo '</pre>';# return;
        
        $indent = '';
        if (preg_match('#^\s*#', $ob, $matches)) {
            #echo '<pre>'; var_dump($matches); echo '</pre>'; //return;
            $indent = $matches[0];
        }
        
        
        // Check the data exists and determine if it's an array or not:
        // (if not show the 'orshow' block if it exists, or a line-break if not)
        $data_ok  = false;
        if (
            $key
         && array_key_exists($key, $this->data)
         && !empty($this->data[$key])
         && (is_array($this->data[$key]) || is_string($this->data[$key]))
        ) {
            $data_ok  = true;
        }

        
        
        // If there was no key or the key was a valid string, we're not looping, so just do a simple
        // parse:
        if (!$key || (is_string($this->data[$key]) && $data_ok)) {
            if (strpos($ob, PHP_EOL) === false) {
                $output = $this->parseTag($ob, $this->data);
            } else {
                $output = PHP_EOL . $this->parseBlock($ob, $this->data, $indent);
            }
            
            if (!empty($output)) {
                $this->output_tag($output . PHP_EOL);
            }
            return;
        }

        // If the data wasn't valid, check for the `orshow` block:
        if (!$data_ok) {
            if ($orshow) {
                echo PHP_EOL . $orshow_block . PHP_EOL;
                return;
            } else {
                echo PHP_EOL;
                return;
            }
        }
        
        #echo '<pre>'; var_dump($data_ok); echo '</pre>';
        #echo '<pre>'; var_dump($orshow); echo '</pre>';
        
        // We're only checking the data, so no further processing required:
        if ($check) {
            echo PHP_EOL . $ob;
            return;
        }
        
        // Data is ok and must be an array, so start looping:

        // Determine the indentation from the buffer template:
       
        $tag_template = trim($ob);

        // Remove the 's' at the end of the key name:
        $singlular = rtrim($key, 's');
        if ($singlular == $key) {
            trigger_error('Could not singularise the key name');
            return;
        }
        
        


        // Do the loop (with indentation):
        $i = 0;
        foreach ($this->data[$key] as $k => $val) {
            // Add any attributes:
            $attribs = array();
            if (is_array($val)) {
                $attribs = $val;
                $val     = $k;
            }

            $tag_string = $this->parseTag($tag_template, array($singlular => $val), $attribs);
            if ($i > 0) {
                echo $indent;
            }
            $this->output_tag($tag_string . PHP_EOL);
            $i++;
        }
    }


    public function indent($string, $indent = '') {
    
        /*$indent = '';
        if (preg_match('#^(\s)*#', $string, $matches)) {
            //echo '<pre>'; var_dump($matches); echo '</pre>'; return;
            $indent = $matches[0];
        }*/
    
        if ($indent != '') {
            $indented_string = '';
            $lines = explode(PHP_EOL, $string);
            
            #if (!preg_match('#^(\s)*#', $lines[1], $matches)) {
                $i = 0;
                foreach ($lines as $line) {
                    if ($i > 0) {
                        $indented_string .= $indent;
                    }
                    $indented_string .= $line . PHP_EOL;
                    $i++;
                }
                $string = $indented_string;
            #}
        }
        
        return $string;
    }
    

}
?>