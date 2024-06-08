<?php
namespace evo\cli;

use evo\pattern\singleton\SingletonInterface;
use evo\pattern\singleton\SingletonTrait;
use evo\exception as E;

/**
 *
 * (c) 2018 Hugh Durham III
 *
 * For license information please view the LICENSE file included with this source code.
 *
 * Cli arguments(options) manager
 *
 * @author HughDurham {ArtisticPhoenix}
 * @package Evo
 * @subpackage pattern
 * @version 2.0.0
 *
 */
final class Cli implements SingletonInterface
{
    use SingletonTrait;

    /**
     * the current version
     * added v2.0.0
     */
    const VERSION = '2.0.0';

    /**
     * Option Key for options that should have a value but that may not
     * this must be set in order to retrive the value from the request
     * $options = [class::OPT_HAS_VALUE => true]
     *  constant - since v2.0.0
     */
    const OPT_VALUE_EXPECTED                = 'VALUE_EXPECTED';

    /**
     * a callback function to validate the value from options that have OPT_HAS_VALUE => true
     * data will be ran aganst this function  "OPT_MUST_VALIDATE => fn($which,$request) strlen($which)"
     * added v2.0.0
     */
    const OPT_MUST_VALIDATE              = 'MUST_VALIDATE';

    /**
     * this will always be retuned as an indexed array
     * added v2.0.0
     */
    const OPT_MULTIPLE_EXPECTED          = 'MULTIPLE_EXPECTED';

    /**
     * bitwise flag for what request type to accept
     * @var integer
     */
    const REQUEST_CLI       = 1;

    /**
     * bitwise flag for what request type to accept
     * @var integer
     */
    const REQUEST_POST      = 2;

    /**
     * bitwise flag for what request type to accept
     * @var integer
     */
    const REQUEST_GET       = 4;

    /**
     * @todo
     * bitwise flag for what request type to accept
     * @var integer
     */
    /*const REQUEST_PUT       = 8;*/

    /**
     * @todo
     * bitwise flag for what request type to accept
     * @var integer
     */
    /* const REQUEST_DELETE   = 16;*/

    /**
     * @todo
     * bitwise flag for what request type to accept
     * @var integer
     */
    /* const REQUEST_PATCH    = 32;*/

    /**
     * Request types
     *
     * @var int
     */
    const R_ALL =
        self::REQUEST_CLI |
        self::REQUEST_POST |
        self::REQUEST_GET;
    /*|
    /*self::REQUEST_PUT |
    self::REQUEST_DELETE |
    self::REQUEST_PATCH;*/

    /**
     * storage for the arguments
     * @var array
     */
    protected $arguments = [];

    /**
     * storage for the options (internal only)
     *
     * @var array
     */
    protected $options = [];

    /**
     * storage for the REQUEST_* flags
     * @var integer
     */
    protected $allowedRequestTypes = self::REQUEST_CLI;

    /**
     * one of the REQUEST_* constants
     * @var int
     */
    protected $currentRequestType;

    /**
     * the request arguments
     */
    protected $request;

    /**
     * called when the first instance is created (after construct)
     */
    protected function init()
    {
        if (php_sapi_name() == 'cli') {
            $this->currentRequestType = self::REQUEST_CLI;
            $this->streamOutput();
        } else {
            switch (strtoupper($_SERVER['REQUEST_METHOD'])) {
                case 'POST':
                    $this->currentRequestType = self::REQUEST_POST;
                    break;
                /*
                case 'PUT':
                    $this->currentRequestType = self::REQUEST_PUT;
                break;
                case 'DELETE':
                    $this->currentRequestType = self::REQUEST_DELETE;
                break;
                case 'PATCH':
                    $this->currentRequestType = self::REQUEST_PATCH;
                break;
                 */
                default:
                    $this->currentRequestType = self::REQUEST_GET;
            }
        }

        //================ OPTIONS
        $this->options[self::OPT_MUST_VALIDATE] = [
            'doc'       => 'Option must be a Closure,
which must return true to accept a given value for argument, 
eg. [class::OPT_VALIDATE => fn($opt,$request)return $$opt;]',
            'accept'    => function ($value) {
                //validation for this option
                if (is_a($value, \Closure::class) || is_bool($value)) {
                    return true;
                }
                return false;
            }
        ];

        $this->options[self::OPT_VALUE_EXPECTED] = [
            'doc'       => 'Option must be a boolean value,
when true a value is expected for this argument ( default=false if present without a value ),
when false a value is not expected for this argument ( default=true if paresent without a value)',
            'accept'    => function ($value) {
                //validation for this option
                return is_bool($value);
            }
        ];

        $this->options[self::OPT_MULTIPLE_EXPECTED] = [
            'doc'       => 'Option must be a boolean value,
when true a the attribue will always be an array
when false only the last value is used for the arguement',
            'accept'    => function ($value) {
                //validation for this option
                return is_bool($value);
            }
        ];
    }

    /**
     * Returns the names of the allowed options
     *
     * @return array
     */
    public function getOptions()
    {
        return array_combine(array_keys($this->options), array_column($this->options, 'doc'));
    }

    /**
     * check an arguments option against a value
     *
     * @param string $key
     * @param mixed $value
     * @return array
     * @throws E\InvalidArgument
     */
    protected function ckOption($key, $value)
    {
        if (!isset($this->options[$key])) {
            throw new E\InvalidArgument("Unknown option $key");
        }


        if (!$this->options[$key]['accept']->__invoke($value)) {
            throw new E\InvalidArgument("Invalid value for option $key");
        }

        return $value;
    }

    /**
     * One of the REQUEST_* type constants.
     *
     * Auto detection of the request type is suggested,
     * but this method is provided if you need it
     *
     * @param int $requestType
     * @return self
     */
    public function setCurrentRequestType($requestType)
    {
        $this->currentRequestType = $requestType;
        return $this;
    }

    /**
     *
     * @return number - one of the self::REQUEST_* constants
     */
    public function getCurrentRequestType()
    {
        return $this->currentRequestType;
    }

    /**
     * Set which types of requests are allowed
     *
     * One of the Reqest_* constants, or R_ALL for all types
     *
     * @param int $requestType
     * @return self
     */
    public function setAllowedRequestTypes($requestType)
    {
        $this->allowedRequestTypes = $requestType;
        return $this;
    }

    /**
     *
     * @param array $conf
     * @return self
     * @example <pre>
     * [
     *     [
     *        'shortName' => 'h'
     *        'longName'  => 'help'
     *         'doc'      => 'Show this help text'
     *         'options'  => []
     *     ],
     *     [...]
     * ]
     */
    public function fromConfig(array $conf)
    {
        foreach ($conf as $argument) {
            $this->setArgument(
                $argument['shortName'],
                isset($argument['longName'])?$argument['longName']:null,
                isset($argument['doc'])?$argument['doc']:'',
                isset($argument['options'])?$argument['options']:[]
            );
        }
        return $this;
    }

    /**
     *
     * @param string $shortName - the short name of the argument (len 1)
     * @param string|null $longName - long name of the argument (len > 1) this is linked to the short name and vis-virsa
     * @param string $doc - help documentation
     * @param array $options = [self::OPT_* => "setting"]
     * @return self
     * @throws E\InvalidArgument
     *
     * @example
     * [
    $Cli::OPT_VALUE_EXPECTED        => true,
    $Cli::OPT_MULTIPLE_EXPECTED     => true,
    $Cli::OPT_MUST_VALIDATE         => fn($k,$v)=>strlen($v),
     * ]
     *
     */
    public function setArgument($shortName, $longName=null, $doc='', array $options=[])
    {
        if (!preg_match('/^[a-z0-9]$/i', $shortName)) {
            throw new E\InvalidArgument("Invalid shortName '{$shortName}', can only be 1 length and only 'a-z','A-Z' and '0-9' are accepted.");
        }

        if ($longName && !preg_match('/^[a-z0-9]{2,}/i', $longName)) {
            throw new E\InvalidArgument("Invalid longName '{$longName}', must be 2 or more length and only 'a-z','A-Z' and '0-9' are accepted.");
        }

        $this->arguments[$shortName] = [
            'shortName'         => $shortName,
            'longName'          => $longName ?? $shortName,
            'longNameLength'    => strlen($longName), //important for helpdoc spacing
            'doc'               => $doc,
            'options'           => []
        ];

        if (!empty($options)) {
            foreach ($options as $key=>$value) {
                //validate each option
                $this->arguments[$shortName]['options'][$key] = $this->ckOption($key, $value);
            }
        }
        return $this;
    }

    /**
     * Normalize -Added 2.0.0 - return the short name of an argument ( short name and leading - safe )
     * @param string $longName
     * @return false|string - false argument does not exist or the short name of the argument
     */
    public function toShortName($longName)
    {
        $longName = preg_replace(['/^-(\w)$/','/^--(\w{2,})$/'], '\1', $longName); //remove leading -
        //convert to array  ['shortName' => 'longName', ...]
        $a = array_column($this->arguments, 'longName', 'shortName');
        if(isset($a[$longName])) return $longName; //this is actually  the short name, so just return it

        return array_search($longName, $a); //return the key or false
    }

    /**
     * Normalize -Added 2.0.0 - return the long name of an argument ( long name and leading - safe )
     * @param string $shortName
     * @return false|string - false argument does not exist or the long name of the argument
     */
    public function toLongName($shortName)
    {
        $shortName = preg_replace(['/^-(\w)$/','/^--(\w{2,})$/'], '\1', $shortName); //remove leading -
        //convert to array  ['longName' => 'shortName', ...]
        $a = array_column($this->arguments,'shortName', 'longName');

        if(isset($a[$shortName])) return $shortName; //this is actually  the long name, so just return it

        return array_search($shortName, $a);
    }

    /**
     * -Modified functionallity 2.0.0
     * Return the arguments set with setArgument
     *
     * @return array
     */
    public function getArguments() : array
    {
        return $this->arguments;
    }

    /**
     * -Added functionallity 2.0.0
     *  Return a single argument set with setArgument
     * @param string $which
     * @return array
     * @throws E\InvalidArgument
     */
    public function getArgument(string $which) : array
    {
        if(false === ($w = $this->toShortName($which))) throw new E\InvalidArgument('Unknown argument '.$which);
        return $this->arguments[$w];
    }


    /**
     * Added in 2.0.0
     * Get the request value for an argument or a default value
     * @param null|string $which - leave null to the entire request
     * @param null|mixed $default - can be a closure (added in 2.0))
     * @return mixed
     * @throws E\InvalidArgument
     */
    public function getRequest($which=null, $default=null)
    {
        //make sure the request is set
        if (!$this->request)  $this->setRequest($this->fetchRequest());

        if (empty($which))  return $this->request; //return all

        if(false === ($w = $this->toShortName($which))) throw new E\InvalidArgument('Unknown argument '.$which);

        if(!isset($this->request[$w]) && $default){
            if(is_callable($default) && is_object($default)){
                return $default($which, $this->request);
            }else{
                return $default;
            }
        }

        return $this->request[$w];
    }

    /**
     * Get the help document as a string
     * Enter which argument to get only that arguments help doc - leave null for all
     *
     * @param null|string $which
     *
     * @return string
     */
    public function getHelpDoc($which=null)
    {
        $doc = "";

        if($which) {
            $argument = $this->getArgument($which);
            return $argument['doc'] ?? '';
        }else{
            $doc .= "Usage: php <file> [--] [args...]\n";
            $arguments = $this->arguments;
            $maxLen = max(array_column($arguments, 'longNameLength')) + 8; // + ', --' + name +'    '

            foreach ($arguments as $settings) {
                $longName = empty($settings['longName']) ? str_repeat(" ", $maxLen) : str_pad(', --' . $settings['longName'], $maxLen, " ");

                $doc .= "    -{$settings['shortName']}{$longName} {$settings['doc']}\n";
            }
        }
        return $doc;
    }

    /**
     * Output the help doc
     *
     * @param bool $exit
     */
    public function printHelpDoc($exit=true)
    {
        echo $this->getHelpDoc();
        if ($exit) {
            exit();
        }
    }

    /**
     *
     * @param array $request
     * @return self
     */
    public function setRequest(array $request)
    {
        $this->request = $this->normalizeRequest($request);
        return $this;
    }

    /**
     * normalize the request arguments, and filter for accepeted and required arguments
     *
     * @param array $request
     * @throws E\InvalidArgument
     * @return array
     */
    protected function normalizeRequest(array $request)
    {
        $validArgs = [];
        foreach ($request as $shortName => $value) {
            if(false === ($longName = $this->toLongName($shortName))) continue;

            $settings = $this->getArgument($shortName);
            $options = $settings['options'] ?? [];

            if(empty($settings['options'][self::OPT_VALUE_EXPECTED])){
                //if this is false presence will indicte a true value/// -a (=true) or -a=100 (=true)
                //if this is true presence will indicte false for no value or the value /// -a (=false) or -a=100 (=100)
                $value = is_array($value) ? array_fill(0, count($value), true): true;
                //if it's not here we're not having this conversation
            }

            //multiple inputs
            if(is_array($value) && empty($options[self::OPT_MULTIPLE_EXPECTED])){
                $value = end($value);
            }else if(!is_array($value) && !empty($options[self::OPT_MULTIPLE_EXPECTED])){
                $value = [$value];
            }

            if(false !== ($validator = $options[self::OPT_MUST_VALIDATE] ?? false)){
                if (is_callable($validator) && !$validator($shortName, $value)) {
                    throw new E\InvalidArgument("An invalid value was given for  argument '{$longName}'. ".$settings['doc']);
                }else if(is_bool($validator) && !$validator){
                    throw new E\InvalidArgument("An invalid value was given for  argument '{$longName}'. ".$settings['doc']);
                }
            }

            $validArgs[$shortName] = $value;
        }

        return $validArgs;
    }

    /**
     * Fetch the request based on the current Request type and allowed request type
     *
     * @return array
     */
    protected function fetchRequest()
    {
        $request = [];
        if ($this->currentRequestType & $this->allowedRequestTypes) {
            if ($this->currentRequestType == self::REQUEST_CLI) {
                $shortOpts = [];
                $longOpts = [];

                foreach ($this->arguments as $arg=>$settings) {
                    $t= isset($settings['options'][self::OPT_MUST_VALIDATE]) || isset($settings['options'][self::OPT_VALUE_EXPECTED]) ? '::':'';
                    $shortOpts[] = $settings['shortName'].$t;
                    $longOpts[] = $settings['longName'].$t;
                }

                $request = getopt(implode($shortOpts), $longOpts);
            } else {
                switch (strtoupper($_SERVER['REQUEST_METHOD'])) {
                    case 'POST':
                        $request = $_POST;
                    // no break
                    default:
                        $request = $_GET;
                }
            }
        }
        return $request;
    }

    /**
     * Stream output instead of buffering it
     */
    protected function streamOutput()
    {
        if (!headers_sent()) {
            // Turn off output buffering
            ini_set('output_buffering', 'off');
            // Turn off PHP output compression
            ini_set('zlib.output_compression', false);

            //Flush (send) the output buffer and turn off output buffering
            //ob_end_flush();
            while (ob_get_level()) {
                ob_end_flush();
            }

            // Implicitly flush the buffer(s)
            ini_set('implicit_flush', true);
            ob_implicit_flush(true);

            //prevent apache from buffering it for deflate/gzip

            if ($this->currentRequestType == self::REQUEST_CLI) {
                header("Content-type: text/plain");
            }
            // recommended to prevent caching of even
            header('Cache-Control: no-cache');
        }

    }
}
