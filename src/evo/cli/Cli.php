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
 * @subpackage CLI
 * @version 2.2.0
 *
 */
final class Cli implements SingletonInterface
{
    use SingletonTrait;

    /**
     * the current version
     * added v2.0.0
     */
    const string VERSION = '2.2.0';

    /**
     * Option Key for options that should have a value but that may not eg. -x=something
     * this MUST be true in order to retrieve the value 'something' from the request
     *     when set as (-x) request value is boolean false
     *     when set as (-x=) request value is an empty string
     *     when set as (-x=foo) request value is 'foo'
     * otherwise, when this is false
     *     when set as (-x,-x=, -x=foo) request value will always equal boolean true
     * when not set the default request value will always be null
     *
     *  $options = [class::OPT_HAS_VALUE => true]
     *  constant - since v2.0.0
     */
    const string OPT_VALUE_EXPECTED = 'VALUE_EXPECTED';

    /**
     * a callback function to validate the value from options that have OPT_HAS_VALUE => true
     * data will be run against this function  "OPT_MUST_VALIDATE => fn($which,$request) strlen($which)"
     * added v2.0.0
     */
    const string OPT_MUST_VALIDATE = 'MUST_VALIDATE';

    /**
     * this will nat always be returned as an array
     * in some cases it will be null or boolean false.
     * null = indicates it's not set
     * boolean false = indicates its set as a single -m (falsy)
     * "" empty string = indicates its set as a single -m= (falsy)
     * array = the above values can also be included in the array -m=x, truthy
     * ['1', '', false] ~ -m=1 -m= -m
     * it is the developers[your] responsibility to handle these cases.
     * added v2.0.0
     */
    const string OPT_MULTIPLE_EXPECTED = 'MULTIPLE_EXPECTED';

    /**
     * bitwise flag for what request type to accept
     * @var integer
     */
    const int REQUEST_CLI = 1;

    /**
     * bitwise flag for what request type to accept
     * @var integer
     */
    const int REQUEST_POST = 2;

    /**
     * bitwise flag for what request type to accept
     * @var integer
     */
    const int REQUEST_GET = 4;

    /**
     *
     * bitwise flag for what request type to accept
     * @var integer
     */
    const int REQUEST_PUT = 8;

    /**
     *
     * bitwise flag for what request type to accept
     * @var integer
     */
    const int REQUEST_DELETE = 16;

    /**
     *
     * bitwise flag for what request type to accept
     * @var integer
     */
    const REQUEST_PATCH = 32;

    /**
     * Request types
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
    protected array $arguments = [];

    /**
     * storage for the options (internal only)
     *
     * @var array
     */
    protected array $options = [];

    /**
     * storage for the REQUEST_* flags
     * @var int
     */
    protected int $allowedRequestTypes = self::REQUEST_CLI;

    /**
     * one of the REQUEST_* constants
     * @var int
     */
    protected int $currentRequestType;

    /**
     * the request arguments
     */
    protected ?array $request = null;

    /**
     * called when the first instance is created (after construct)
     */
    protected function init(): void
    {
        if (php_sapi_name() == 'cli') {
            $this->currentRequestType = self::REQUEST_CLI;
        } else {
            //http methods are a bit more tricky to accomplish (4 vs 1)
            $request_method = $_SERVER['REQUEST_METHOD'] ?? 'get';
            $this->currentRequestType = match (strtoupper($request_method)) {
                'POST' => self::REQUEST_POST,
                'PUT' => self::REQUEST_PUT,
                'DELETE' => self::REQUEST_DELETE,
                default => self::REQUEST_GET
            };
        }

        //================ OPTIONS
        $this->options[self::OPT_MUST_VALIDATE] = [
            'doc' => 'Option must be a Closure,
which must return true to accept a given value for argument, 
eg. [class::OPT_VALIDATE => fn($opt,$request)return $$opt;]',
            'accept' => function ($value) {
                //validation for this option
                if (is_a($value, \Closure::class) || is_bool($value)) {
                    return true;
                }
                return false;
            }
        ];

        $this->options[self::OPT_VALUE_EXPECTED] = [
            'doc' => 'Option must be a boolean value,
when true a value is expected for this argument ( default=false if present without a value ),
when false a value is not expected for this argument ( default=true if present without a value)',
            'accept' => function ($value) {
                //validation for this option
                return is_bool($value);
            }
        ];

        $this->options[self::OPT_MULTIPLE_EXPECTED] = [
            'doc' => 'Option must be a boolean value,
when true the attribute will always be an array
when false only the last value is used for the attribute',
            'accept' => function ($value) {
                //validation for this option
                return is_bool($value);
            }
        ];
    }

    /**
     * Returns the names of the allowed options and their help doc
     *
     * @return array
     */
    public function getOptions(): array
    {
        return array_combine(array_keys($this->options), array_column($this->options, 'doc'));
    }

    /**
     * check an arguments option against a value
     *
     * @param string $key
     * @param mixed $value
     * @return void
     * @throws E\OutOfBoundsException - unknown option
     * @throws E\ValueError - invalid option value
     */
    protected function ckOption($key, $value): void
    {
        if (!isset($this->options[$key])) {
            throw new E\OutOfBoundsException("Unknown option $key");
        }

        if (!$this->options[$key]['accept']->__invoke($value)) {
            throw new E\ValueError("Invalid value for option $key");
        }
    }

    /**
     *
     *
     * Auto-detection of the request type is suggested,
     * but this method is provided if you need/want it
     *
     * @param int $requestType - One of the REQUEST_* type constants.
     * @return self
     */
    public function setCurrentRequestType(int $requestType): self
    {
        if (!$requestType & self::R_ALL) {
            throw new E\OutOfBoundsException('Unknown request type');
        }

        $this->currentRequestType = $requestType;
        return $this;
    }

    /**
     *
     * @return int - one of the self::REQUEST_* constants
     */
    public function getCurrentRequestType(): int
    {
        return $this->currentRequestType;
    }

    /**
     * Set which types of requests are allowed
     *
     * @param int $requestType - One of the Reqest_* constants, or R_ALL for all types
     * @return self
     */
    public function setAllowedRequestTypes(int $requestType): self
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
    public function fromConfig(array $conf): self
    {
        foreach ($conf as $argument) {
            $this->setArgument(
                $argument['shortName'],
                $argument['longName'] ?? null,
                $argument['doc'] ?? '',
                $argument['options'] ?? []
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
     * @throws E\ValueError - invalid short or long name
     *
     * @example
     * [
     * $Cli::OPT_VALUE_EXPECTED        => true,
     * $Cli::OPT_MULTIPLE_EXPECTED     => true,
     * $Cli::OPT_MUST_VALIDATE         => fn($k,$v)=>strlen($v),
     * ]
     *
     */
    public function setArgument(string $shortName, ?string $longName = null, string $doc = '', array $options = []): self
    {
        if (!preg_match('/^[a-z0-9]$/i', $shortName))
            throw new E\ValueError("Invalid shortName '{$shortName}', can only be 1 length and only 'a-z','A-Z' and '0-9' are accepted.");

        if ($longName && !preg_match('/^[a-z0-9]{2,}/i', $longName))
            throw new E\ValueError("Invalid longName '{$longName}', must be 2 or more length and only 'a-z','A-Z' and '0-9' are accepted.");

        $this->arguments[$shortName] = [
            'shortName' => $shortName,
            'longName' => $longName ?? $shortName,
            'longNameLength' => strlen($longName), //important for help doc proper spacing
            'doc' => $doc,
            'options' => []
        ];

        if (!empty($options)) {
            //Arguments with OPT_MULTIPLE_EXPECTED set, Must also have OPT_VALUE_EXPECTED set to work correctly
            if(!empty($options[self::OPT_MULTIPLE_EXPECTED]) && empty($options[self::OPT_VALUE_EXPECTED])){
                $options[self::OPT_VALUE_EXPECTED] = true;
            }

            foreach ($options as $key => $value) {
                //validate each option & value
                $this->ckOption($key, $value);
                $this->arguments[$shortName]['options'][$key] = $value;
            }
        }
        return $this;
    }

    /**
     * Normalize -Added 2.0.0 - return the short name of an argument ( short name and leading - safe )
     * @param string $longName
     * @return false|string - false argument does not exist or the short name of the argument
     */
    public function toShortName(string $longName): string|false
    {
        $longName = preg_replace(['/^-(\w)$/', '/^--(\w{2,})$/'], '\1', $longName); //remove leading -
        //convert to array  ['shortName' => 'longName', ...] this gives us an easier format to work with
        $a = array_column($this->arguments, 'longName', 'shortName');
        if (isset($a[$longName])) return $longName; //this is actually  the short name, so just return it
        //otherwise we can simply search for the long name by using the shortname and return the index or false
        return array_search($longName, $a); //return the key or false
    }

    /**
     * Normalize -Added 2.0.0 - return the long name of an argument ( long name and leading - safe )
     * @param string $shortName
     * @return false|string - false argument does not exist or the long name of the argument
     */
    public function toLongName(string $shortName): string|false
    {
        $shortName = preg_replace(['/^-(\w)$/', '/^--(\w{2,})$/'], '\1', $shortName); //remove leading -
        //convert to array  ['longName' => 'shortName', ...] this gives us an easier format to work with
        $a = array_column($this->arguments, 'shortName', 'longName');
        if (isset($a[$shortName])) return $shortName; //this is actually the long name, so just return it
        //otherwise we can simply search for the long name by using the shortname and return the index or false
        return array_search($shortName, $a);
    }

    /**
     * -Modified functionality 2.0.0
     * Return the arguments set with setArgument
     *
     * @return array
     */
    public function getArguments(): array
    {
        return $this->arguments;
    }

    /**
     * -Added functionality 2.0.0
     *  Return a single argument set with setArgument
     * @param string $which
     * @return array
     * @throws E\OutOfBoundsException
     */
    public function getArgument(string $which): array
    {
        if (false === ($w = $this->toShortName($which))) throw new E\OutOfBoundsException('Unknown argument ' . $which);
        return $this->arguments[$w];
    }

    /**
     * Added in 2.0.0
     * Get the request value for an argument or a default value
     * @param null|string $which - leave null to the entire request
     * @param null|mixed $default - can be a closure (added in 2.0))
     * @return mixed
     * @throws E\OutOfBoundsException
     */
    public function getRequest(?string $which = null, mixed $default = null): mixed
    {
        //make sure the request is set
        if (!$this->request) $this->setRequest($this->fetchRequest());

        if (empty($which)) return $this->request; //return all

        if (false === ($w = $this->toLongName($which))) throw new E\OutOfBoundsException('Unknown argument ' . $which);

        if (!isset($this->request[$w]) && is_object($default) && is_callable($default)) {
            return $default($which, $this->request);
        }

        return $this->request[$w] ?? $default;
    }

    /**
     * You can use this callback method as the default when you wish to throw an evo\OutOfBoundsException for an unknown request key
     *
     * @return callable
     */
    public static function throwUnknownRequestKey(): callable{
        return static function($key) {
            throw new E\OutOfBoundsException("Unknown request key [".$key."]");
        };
    }

    /**
     * @param string|null $which - which argument / null for the request in general
     * @return bool
     *
     * @throws E\OutOfBoundsException
     */
    public function issetRequest(?string $which = null): bool
    {
        if (null === $which) {
            if (!$this->request) {
                return false;
            } else {
                return true;
            }
        }

        if (!$this->request) $this->setRequest($this->fetchRequest());

        if (false === ($w = $this->toLongName($which)))
            throw new E\OutOfBoundsException('Unknown argument ' . $which);

        return isset($this->request[$w]);
    }

    /**
     * Check if the request is empty or not
     * -- this is useful for calling printHelpDoc on an empty request
     * @param string|null $which - which argument / null for the request in general
     *
     * @return bool
     */
    public function isEmptyRequest(?string $which = null): bool
    {
        if (null === $which) return empty($this->request);

        return empty($this->request[$which]);
    }

    /**
     * Get the help document as a string
     * Enter which argument to get only that arguments help doc - leave null for all
     *
     * @param null|string $which
     *
     * @return string
     */
    public function getHelpDoc(?string $which = null): string
    {
        $doc = "";

        if ($which) {
            $argument = $this->getArgument($which);
            return $argument['doc'] ?? '';
        } else {
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
     * @param bool $exit - it's common/preferred to terminate execution after this
     */
    public function printHelpDoc(bool $exit = true): void
    {
        echo $this->getHelpDoc();
        if ($exit) {
            exit();
        }
    }

    /**
     * manually set the request - this can be useful for testing etc.
     * @param array $request
     * @return self
     */
    public function setRequest(array $request): self
    {
        $this->request = $this->normalizeRequest($request);
        return $this;
    }

    /**
     * normalize the request arguments, and filter for accepeted and required arguments
     *
     * @param array $request
     * @return array
     * @throws E\ValueError
     */
    protected function normalizeRequest(array $request): array
    {
        $validArgs = [];
        foreach ($request as $shortName => $value) {
            if (false === ($longName = $this->toLongName($shortName))) continue;

            $settings = $this->getArgument($shortName);
            $options = $settings['options'] ?? [];

            if (empty($settings['options'][self::OPT_VALUE_EXPECTED])) {
                //if this is false presence will indicate a true value/// -a (=true) or -a=100 (=true)
                //if this is true presence will indicate false for no value or the value /// -a (=false) or -a=100 (=100)
                $value = is_array($value) ? array_fill(0, count($value), true) : true;
                //if it's not here we're not having this conversation
            }

            //multiple inputs
            if (is_array($value) && empty($options[self::OPT_MULTIPLE_EXPECTED])) {
                $value = end($value);
            } else if (null !== $value && false !== $value && !is_array($value) && !empty($options[self::OPT_MULTIPLE_EXPECTED])) {
                //when the value is null or false just return it instead of doing this [null],[false]
                $value = [$value];
            }

            if (false !== ($validator = $options[self::OPT_MUST_VALIDATE] ?? false)) {
                if (is_callable($validator) && !$validator($shortName, $value)) {
                    throw new E\ValueError("An invalid value was given for  argument '{$longName}'. " . $settings['doc']);
                } else if (is_bool($validator) && !$validator) {
                    throw new E\ValueError("An invalid value was given for  argument '{$longName}'. " . $settings['doc']);
                }
            }
//@todo: use long names
            $validArgs[$longName] = $value;
        }

        return $validArgs;
    }

    /**
     * Fetch the request based on the current Request type and allowed request type
     *
     * @return array
     */
    protected function fetchRequest(): array
    {
        $request = [];
        if ($this->currentRequestType & $this->allowedRequestTypes) {
            if ($this->currentRequestType == self::REQUEST_CLI) {
                $shortOpts = [];
                $longOpts = [];

                foreach ($this->arguments as $arg => $settings) {
                    $t = isset($settings['options'][self::OPT_MUST_VALIDATE]) || isset($settings['options'][self::OPT_VALUE_EXPECTED]) ? '::' : '';
                    $shortOpts[] = $settings['shortName'] . $t;
                    $longOpts[] = $settings['longName'] . $t;
                }

                $request = getopt(implode($shortOpts), $longOpts);
            } else {
                $request_method = $_SERVER['REQUEST_METHOD'] ?? 'get';
                $request = match (strtoupper($request_method)) {
                    'POST', 'PUT', 'DELETE' => $_POST,
                    default => $_GET
                };
            }
        }
        return $request;
    }

    /**
     * changed to public v2.2
     * Stream output instead of buffering it over HTTP
     *
     * call early in the stack
     * don't forget to flush() your output
     */
    public static function streamOutput(): void
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
            if (!headers_sent()) {
                header("Content-type: text/plain");
                // recommended to prevent caching of even
                header('Cache-Control: no-cache');
            }
        }
    }
}