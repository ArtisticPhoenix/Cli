<?php
namespace evo\cli;

use evo\pattern\singleton\SingletonInterface;
use evo\pattern\singleton\SingletonTrait;
use evo\exception\InvalidArgument;

/**
 *
 * (c) 2016 Hugh Durham III
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
     * bitwise flag for what request type to accept
     * @var integer
     */
    const REQUEST_PUT       = 8;
    
    /**
     * bitwise flag for what request type to accept
     * @var integer
     */
    const REQUEST_DELETE   = 16;
    
    /**
     * bitwise flag for what request type to accept
     * @var integer
     */
    const REQUEST_PATCH    = 32;
    
    /**
     *
     *
     * @var int
     */
    const R_ALL =
        self::REQUEST_CLI |
        self::REQUEST_POST |
        self::REQUEST_GET |
        self::REQUEST_PUT |
        self::REQUEST_DELETE |
        self::REQUEST_PATCH;
    
    /**
     * storage for the arguments
     * @var array
     */
    protected $arguments = [];
    
    
    /**
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
                default:
                    $this->currentRequestType = self::REQUEST_GET;
            }
        }
        
        
        //================ OPTIONS
        $this->options['accept'] = [
            'doc'       => '[bool|string(regex)|closure] must return true to accept a given value for argument',
            'accept'    => function($value){
                if(is_bool($value)) return 'Bool';
                if(is_a($value, \Closure::class)) return 'Closure';
                if(is_string($value)) return 'Pattern';
                
                return false;
             }
        ];
        
        $this->options['required'] = [
            'doc'       => '[bool] is a value required, to accept a value the accept option must be set',
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
        $opts = array_combine(array_keys($this->options),array_column($this->options, 'doc'));

        debug_dump($opts);
    }
    
    /**
     * check a arguments option
     * 
     * @param string $key
     * @param mixed $value
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
     * @param int $type
     */
    public function setCurrentRequestType($requestType)
    {
        $this->currentRequestType = $requestType;
    }
    
    /**
     * Set which types of requests are allowed
     * 
     * One of the Reqest_* constants, or R_ALL for all types
     *
     * @param int $flags
     */
    public function setAllowedRequestTypes($request_type)
    {
        $this->allowedRequestTypes = $request_type;
    }
    
    /**
     *
     * @param string $shortName - the short name of the argument (len 1)
     * @param string|null $longName - long name of the argument (len > 1) this is linked to the short name and vis-virsa
     * @param string $doc - help documentation
     * @param array $options = ['required'=>true, accept=>function($k,$v){ return true;}]
     * @throws InvalidArgument
     */
    public function setArgument($shortName, $longName=null, $doc='', array $options=[])
    {
        
        
        if(!preg_match('/^[a-z0-9]$/i', $shortName)) throw new InvalidArgument("Invalid shortName, can only be 1 lenght and only 'a-z','A-Z' and '0-9' are accepted.");  
        
        if($longName) $longName = ltrim($longName,'-');
        
        $this->arguments[$shortName] = [
            'shortName' => $shortName,
            'longName'  => $longName,
            'doc'       => $doc,
            'options'   => []
        ];

        if(!empty($options)){
            foreach ($options as $key=>$value){  
                $this->arguments[$shortName]['options'][$key] = $this->ckOption($key, $value);
            }
        } 
    }
    
    
    public function loadConfig(array $conf)
    {
    }
    
    
    
    /**
     *
     * @param string $which
     * @param mixed $default
     */
    public function getArguments($which=null, $default=null)
    {
        debug_dump($this->arguments);
        
        if(!$this->request) $this->setRequest($this->fetchRequest());
        
       
  
    }
    
    /**
     *
     */
    public function getHelpDoc()
    {
        //php <file> [args...]
        //$head = '-t <docroot>     Specify document root <docroot> for built-in web server.';
    }
    
    /**
     *
     * @param array $request
     */
    public function setRequest(array $request)
    {
        debug_dump($request);
        $this->request = $request;
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
                
                foreach ($this->arguments as $arg=>$data){
                    debug_dump($data);
                    
                    $t=isset($data['options']['accept'])?empty($data['options']['required'])?'::':':':'';
                    
                    $shortOpts[] = $data['shortName'].$t;
                    $longOpts[] = $data['longName'].$t; 
                }
                
                debug_dump(implode($shortOpts));
                
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
