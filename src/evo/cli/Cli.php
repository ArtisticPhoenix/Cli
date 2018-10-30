<?php
namespace evo\cli;

use evo\pattern\singleton\SingletonInterface;
use evo\pattern\singleton\SingletonTrait;
use evo\exception\InvalidArgument;

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
 *
 */
final class Cli implements SingletonInterface
{
    use SingletonTrait;
    
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
        $this->options['accept'] = [
            'doc'       => 'Option must be a Closure, which must return true to accept a given value for argument',
            'accept'    => function($value){
                if(is_a($value, \Closure::class)) return true;
                return false;
             }
        ];
        
        $this->options['requireValue'] = [
            'doc'       => 'Option must be a boolean value, a value is required for this argument',
            'accept'    => function($value){
                return is_bool($value);
             }
        ];
    }
    
    /**
     * Returns the names of the allowed options
     * 
     * @return array
     */
    public function getOptions(){
       return array_combine(array_keys($this->options), array_column($this->options, 'doc'));
    }
    
    /**
     * check a arguments option
     * 
     * @param string $key
     * @param mixed $value
     * @return array
     * @throws InvalidArgument
     */
    protected function ckOption($key, $value){
        if(!isset($this->options[$key])) throw new InvalidArgument("Unknown option $key");
        
        if(!$this->options[$key]['accept']->__invoke($value)) throw new InvalidArgument("Invalid value for option $key");
        
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
     * @param string $shortName - the short name of the argument (len 1)
     * @param string|null $longName - long name of the argument (len > 1) this is linked to the short name and vis-virsa
     * @param string $doc - help documentation
     * @param array $options = ['requireValue'=>true, accept=>function($k,$v){ return true;}, 'required' => true]
     * @return self
     * @throws InvalidArgument
     */
    public function setArgument($shortName, $longName=null, $doc='', array $options=[])
    {

        if(!preg_match('/^[a-z0-9]$/i', $shortName)) throw new InvalidArgument("Invalid shortName '{$shortName}', can only be 1 lenght and only 'a-z','A-Z' and '0-9' are accepted.");  
        
        if($longName && !preg_match('/^[a-z0-9]{2,}/i', $longName)) throw new InvalidArgument("Invalid longName '{$longName}', must be 2 or more lenght and only 'a-z','A-Z' and '0-9' are accepted.");

        $this->arguments[$shortName] = [
            'shortName'         => $shortName,
            'longName'          => $longName,
            'longNameLength'    => strlen($longName), //important for helpdoc spacing
            'doc'               => $doc,
            'options'           => []
        ];

        if(!empty($options)){
            foreach ($options as $key=>$value){  
                $this->arguments[$shortName]['options'][$key] = $this->ckOption($key, $value);
            }
        }
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
        foreach ($conf as $argument)
            $this->setArgument(
                $argument['shortName'],
                isset($conf['longName'])?$conf['longName']:null,
                isset($conf['doc'])?$conf['doc']:'',
                isset($conf['options'])?$conf['options']:''
           );
        return $this;
    }
 
    /**
     *
     * @param string $which
     * @param mixed $default
     */
    public function getArguments($which=null, $default=null)
    {
        if(!$this->request) $this->setRequest($this->fetchRequest());
        
        if(empty($which)) return $this->request;
        
        if(strlen($which) > 1 && $which = @array_column($this->arguments, 'shortName', 'longName')[$which]);
      
        return isset($this->request[$which]) ? $this->request[$which] : $default;
    }
    
    /**
     *
     */
    public function getHelpDoc()
    {
        $maxLen = max(array_column($this->arguments, 'longNameLength'))+8; // + ', --' + name +'    '
        $doc = "Usage: php <file> [--] [args...]\n";
        foreach ($this->arguments as $settings){
            $longName = empty($settings['longName']) ? str_repeat(" ", $maxLen) : str_pad(', --'.$settings['longName'],$maxLen, " ");
            
            $doc .= "    -{$settings['shortName']}{$longName} {$settings['doc']}\n";
        }
        return $doc;
    }
    
    /**
     * 
     * @param string $exit
     */
    public function printHelpDoc($exit=true){
        echo $this->getHelpDoc();
        if($exit) exit();
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
     * 
     * @param array $request
     * @throws InvalidArgument
     * @return array
     */
    protected function normalizeRequest(array $request){
        $argNames = array_column($this->arguments, 'longName', 'shortName');      
        $validArgs = [];
        foreach ($request as $arg => $value){
            $realname = $arg;
            
            if(!strlen($arg)) continue;
            
            if(!isset($argNames[$arg]) && false === ($arg = array_search($arg, $argNames))) continue;  

            if(isset($this->arguments[$arg]['options']['accept']) && !$this->arguments[$arg]['options']['accept']->__invoke($arg,$value)){
                if(isset($this->arguments[$arg]['options']['requireValue'])) throw new InvalidArgument("A value is required for argument '{$realname}'. ".$this->arguments[$arg]['doc']);
                continue;
            }
            
            if(isset($this->arguments[$arg]['options']['requireValue']) && !strlen($value)) throw new InvalidArgument("A value is required for argument '{$realname}'. ".$this->arguments[$arg]['doc']);

            if(!strlen($value)) $value = true;
            
            $validArgs[$arg] = $value;
        }
        return $validArgs;   
    }
    
    /**
     *
     */
    protected function fetchRequest()
    {
        $request = [];
        if($this->currentRequestType & $this->allowedRequestTypes){
            if ($this->currentRequestType == self::REQUEST_CLI) {
                $shortOpts = [];
                $longOpts = [];
                
                foreach ($this->arguments as $arg=>$settings){     
                    $t= isset($settings['options']['accept']) || isset($settings['options']['requireValue']) ? '::':'';      
                    $shortOpts[] = $settings['shortName'].$t;
                    $longOpts[] = $settings['longName'].$t; 
                }
                
                $request = getopt(implode($shortOpts), $longOpts);
            }else {
                switch (strtoupper($_SERVER['REQUEST_METHOD'])) {
                    case 'POST':
                        $request = $_POST;
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
        if (!headers_sent()) {
            if ($this->currentRequestType == self::REQUEST_CLI) {
                header("Content-type: text/plain");
            }
            // recommended to prevent caching of even
            header('Cache-Control: no-cache');
        }
    }
}
