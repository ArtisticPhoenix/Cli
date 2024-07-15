Command line interface options parser

The purpose of this library is to make a more user friendly way of setting command line arguments for programs.


It's fairly strait forward so I'll jump right in with an example (will call it 'program'):

```php
    //command line call (typically for the help doc)
    > php {pathto}example.php -h
    
    > php {pathto}example.php -o
```

This is the form of a typical command line call, here we are assuming only that PHP is executable on calling `php`.  For windows users you may have to add the `php.exe` to the system environmental variables to call PHP this way.  It's not hard to do and there are plenty of tutorials on how to do this for your version of windows.  Otherwise you can always call PHP using the full path to the executable on your setup.


### Class refrence ###
```php
	//get an instance of Cli, this is a singleton class
	public static function getInstance() : self;
	//set arguments with a config array
	public function fromConfig(array $conf) : self;	
	//set an argument to accept
	public function setArgument($shortName, $longName=null, $doc='', array $options=[]) : self;
	//(changed in 2.0) get a list of the argument set with setArgument
	public function getArguments() : array;
	//(added in 2.0) get a single argument set 
	public function getArgument(string $which) : array;
	//(added in 2.0) convert an arguments name to the short version ( safe for leading hypen and short names)
	public function toShortName(string $long_name) : string|false;
	//(added in 2.0) convert an arguments name to the long version ( safe for leading hypens and long names)
	public function toLongName(string $short_name) : string|false;
	//set the allowed request types (for overriding)
	public function setAllowedRequestTypes($requestType) : self;	
	//set the current request types (for overriding auto detection)
	public function setCurrentRequestType($requestType) : self;	
	//get the current request type (as of version 1.0.2)
	public function getCurrentRequestType() : int;
	//set a request (for overriding)
	public function setRequest(array $request) : self;
	//(added in 2.0) get the value of an argument from the request or null to get the request as an array
	public function getRequest($which=null, $default=null) : array;
	//(added in 2.0) is an argument set in the request or null is the request itself set
	public function issetRequest($which=null) : bool
	//get a list of the allowed options (see options)
	public function getOptions() : array;
	//get the argument help doc as text
	public function getHelpDoc() : string;
	//output the argement help doc
	public function printHelpDoc($exit=true) : null;
``` 	


### Cli Class Constants ###
 Name                   |   Type    |   Since  | Description
 ---------------------- | --------- | -------- | ------------------------------------------------------
  VERSION               |  string   |   2.0.0  | the current version
  OPT_VALUE_EXPECTED    |  string   |   2.0.0  | __Option:__ see Value Expected
  OPT_MUST_VALIDATE     |  string   |   2.0.0  | __Option:__ see Must Validate
  OPT_MULTIPLE_EXPECTED |  string   |   2.0.0  | __Option:__ see Multiple Expected
  R_ALL                 |  int      |   1.0.0  | __Bitwise Flag__ Value subject to change (composite of all other flags)
  REQUEST_CLI           |  int      |   1.0.0  | __Bitwise Flag__ Command line request 
  REQUEST_POST          |  int      |   1.0.0  | __Bitwise Flag__ HTTP Post request
  REQUEST_GET           |  int      |   1.0.0  | __Bitwise Flag__ HTTP Get request
 

### General Argument Definitions ###
 Name           |  Type    |   Since  | Description
 -------------- | -------- | -------- | ------------------------------------------------------
 $conf          |  array   |   1.0.0  | An array of arguments to set (see below)
 $shortName     |  string  |   1.0.0  | An arguments short name max length of 1, a-z A-Z or 0-9
 $longName      |  string  |   1.0.0  | The long name for an argument or null, min length of 1, a-z A-Z or 0-9
 $which		 	|  string  |   1.0.0  | Argument's shortName or longName, get an arguments value from request, null for get all
 $default		|  mixed   |   1.0.0  | Default value to return when no value is set in the request ( can be a closure since 2.0.0 )
 $options       |  array   |   1.0.0  | An array of options for the argument (see below)
 $requestType   |  int     |   1.0.0  | One of the Cli::REQUEST_ constants or Cli::R_ALL (bitwise)
 $request       |  array   |   1.0.0  | The request (typically auto detected)

### Options (changed in 2.0.0) ###
 Name                  |   Type    |   Since  | Description
 --------------------- | --------- | -------- | ------------------------------------------------------
OPT_VALUE_EXPECTED     |   bool    |   2.0.0  | A value is expexed for this argument
OPT_MUST_VALIDATE      |   mixed   |   2.0.0  | If this argument is present then it's value must meet this condition
OPT_MULTIPLE_EXPECTED  |   mixed   |   2.0.0  | Multiple argements are exected, this argment value will always be an array

#### OPT_VALUE_EXPECTED ####
When this option is true a value is expected for this argument:  
- If _true_ and the argument is set without a value `prog.exe -a` then it is value "false", please note this is the opposite of below
- If _true_ and the argument is set with a value `prog.exe -a=1` then it's value is set
- If _false_ and the argument is set without a value `prog.exe -a` then it is value "true" 
- If _false_ and the argument is set with a value `prog.exe -a=1` then it is still "true" but the value is not given

#### OPT_MUST_VALIDATE ####
This option can be either a boolean value or a Closure (or any class that impliments the Callable interface):
- If it's set to _true_, the argement will always validate if it's in the request
- If it's set to _false_, the argement will never validate if it's in the request
- If it's set to a callback, then it's the callback's responsibillity to return true or false

#### OPT_MULTIPLE_EXPECTED ####
This option deturmines if an argument can have multiple values `prog.exe -a=1 -a=2 -a=3`
- If it's set to _true_, the argements value will always be represented by an array
- If it's set to _false_, the argements value never be represented by an array and only the last value is set

### Basic Usage ###
Usage is pretty simple, there are 3 main methods you will need and few others that are just nice to have.

```php
	//instanciate	
	$Cli = Cli::getInstance();
```

Cli is a "Singlton" which means you can only ever have one instance of the class.  Calling getInstance again will return the same instance.  This is fine because we can only handle one request at any give time.  The main function you will use is `$Cli->setArgument` (or `$Cli->fromConfig()`) which defines what arguments you will accept from the request.

```php
	//instanciate	
	$Cli = Cli::getInstance();
	
	//setup a basic argument  (called -h or --help)
	$Cli->setArgument('h', 'help', 'Show this help document');
```

Above we are setting up a very basic argument to show the argument help document. 

The first argument is shortName, `h` in this case, this is mainly what you use when referring to this argument.  All incoming request data will be "normalized" to use the short name.  Any items in the request that do not have a corresponding argument are simply ignored. In a command line call it will be referred to as `-h`, for a GET or POST request it will be referred to simply as `h`.

The second argument is the longName, `help` in this case.  Any incoming arguments using the optional longName will be converted to their short name equivalent. In a command line call it will be referred to as `--help`, for a GET or POST request it will be referred to simply as `help`.

The third argument is the Help Doc.  This string will be compiled with all the other arguments and retuned from `getHelpDoc` or output from `printHelpDoc`. It may also be appended to `InvalidArgument` exceptions thrown by the libary.

```php
	//instanciate	
	$Cli = Cli::getInstance();
	//setup a basic argument  (called -h or --help)
	$Cli->setArgument('h', 'help', 'Show this help document');
	//setup an argument that only accepts foo as the input
	$Cli->setArgument('f', 'foo', 'This is just foo, and must always be foo', [
		'accept' => function($shortName, $value){
			if($value == 'foo') return true;
			return false;
		}
	]);
	
	$Cli->setArgument('i', 'input', 'This is input that requires a value', [
		'requireValue' => true
	]);
```
The fourth argument is an array of options, currently only 2 options are supported

 - 'accept' This is a callback that takes the `$shortName` and the [request]`$value` as inputs and should return true to accept the value.  If false is returned the argument is removed from the request input.  It is up to the developer to throw exceptions for invalid inputs.  This can be easly done in the callback.  The `$value` can be modified by reference by adding `&$value` (by reference).
 - 'requiredValue' This is a boolen that makes a value required for the argument.  If the value is not included with the argument then an `evo\exception\InvalidArgument` is thrown.  This does not mean that the argument itself is required only that if the argument is present that an acceptable value is also included.
 
```php
	//instanciate	
	$Cli = Cli::getInstance();
	$Cli->fromConfig([
		[
			'shortName' => 'h',
			'longName' => 'help',
			'doc' => 'Show this help document'
		],[
			'shortName' => 'f',
			'longName' => 'foo',
			'doc' => 'This is just foo, and must always be foo',
			'options' => [
				$Cli::OPT_MUST_VALIDATE  => function($shortName, $value){
					if($value == 'foo') return true;
					return false;
				}
			]
		],[
			'shortName' => 'i',
			'longName' => 'input',
			'doc' => 'This is input that requires a value',
			$Cli::OPT_VALUE_EXPECTED ' => [
				'requireValue' => true
			]
		]
	]);
```

This is equivalent to the previous code block where each argument was provided individually.  It is up to the developer to decide how this is saved.  There are some limitation due to using a closure as the 'accept' option. However this could be saved in a PHP file as an array:

```php
<?php
//example config.php
    return [['shortName' => 'h','longName' => 'help','doc' => 'Show this help document'],[...]];
```
And then included ( or required) as follows:

 ```php
 $config = requre 'config.php';
 Cli::getInstance()->fromConfig($config);
```
The flexabillity of a callback simply outweighs difficulty in saving a config in other format.

After all your arguments are defined you can access the values they hold in the request by using `$Cli->getArguments()`, Like this:

```php
	$Cli = Cli::getInstance();
	//setup a basic argument  (called -h or --help)
	$Cli->setArgument('h', 'help', 'Show this help document');
    // ... other arguments ...
    // get an array all arguments (shortName as the keys), changed in 2.0.0,
    $args = $Cli->getArguments();
    //get a single argument (using the shortName), added in 2.0.0,
    $help = $Cli->getArgument('h');
    //get a single argument (using the longName), added in 2.0.0,
    $help = $Cli->getArgument('help');
    //get the request (this will contain only valid arguments)
    $foo = $Cli->geRequest();
	//get the request value of a single argement (default null)
    $foo = $Cli->geRequest('h');
	//get the request value of a single argement (default string foo)
    $foo = $Cli->geRequest('h', 'foo');
```

### Other Methods ###

**setAllowedRequestTypes**

The first other method is `$Cli->setAllowedRequestTypes($requestType)`. This sets which type of requests are allowed and is one of the `Cli::REQUEST_*` bitwise constants. Currently supported values are `Cli::REQUEST_CLI`, `Cli::REQUEST_POST` and `Cli::REQUEST_GET`.  Multiple types can be set by seperating them with a single pipe `|`, like a typical PHP flag. The default is `Cli::REQUEST_CLI`.

```php
	$Cli = Cli::getInstance();
    //allow only $_GET
    $Cli->setAllowedRequestTypes(Cli::REQUEST_GET); 
     //allow only $_POST
    $Cli->setAllowedRequestTypes(Cli::REQUEST_POST);
     //allow only Command line (Default)
    $Cli->setAllowedRequestTypes(Cli::REQUEST_CLI);
    //allow both $_GET & $_POST
    $Cli->setAllowedRequestTypes(Cli::REQUEST_GET|Cli::REQUEST_POST);
```

The `Cli::R_ALL` is included for allow all, this value is subject to change if additonal request types are added.  Such as those for a full REST framework.

**setCurrentRequestTypes**
This method is provided to override the autodetection.  This may be nessacary in the future when implimenting things like `PUT` and `DELETE` as no all servers support these HTTP Verbs.  This accepts a single `Cli::REQUEST_*` constant.  Currently it's of limited use.

**setRequest**
This method allows you to inject a request array such as would come from the Command line, `$_GET` or `$_POST` Supper Globals.  This is mostly for testing purposes.  Where you can use a canned request array to run in something like a UnitTest.

**getOptions**
This method returns the help documents for the currently supported options. It's mainly for ease of use by Developers using this library:

```php
	print_r(Cli::getInstance()->getOptions());
    //outputs
    Array (
        [accept] => Option must be a Closure, which must return true to accept a given value for argument
        [requireValue] => Option must be a boolean value, a value is requred for this argument
    )
```

**getHelpDoc**
This method returns the help document as a string, this is compiled from the `$doc` args of each argument that was set.  This is mainly geared towards the command line and it is up to the developer to decide how to handle this for normal HTTP requests.

**printHelpDoc**
This is simular to the above method except that it directly outputs the help document.  You can supply an optional argument to call exit (which is typical when displaying help).  The default is `true` set to false to continue script exection.  For flexabillity it is up to the developer to decide what argument is used for help, but typically it is simply `h` and `help`.  An example implimentation:

```php
    $Cli = Cli::getInstance();
    $Cli->setArgument('h', 'help', 'Show this help document');
    //... other arguments 
    if($Cli->getArgument('h')) $Cli->printHelpDoc(); //exits
```

**Install**

You can get it from composer, by requiring it.

    "require" : {
        "evo/cli" : "~1.0"
	}
    
It has 2 dependancies (which are included in the `composer.json` file.

    "require" : {
        "evo/cli" : "~1.0"
        "evo/patterns" : "~1.0",
		"evo/exception" : "dev-master"
	}
	
**Changelog**

1.0.0 - initial commits

1.0.1 - minor bug fix - for required args (were not acutally requiring)

1.0.2 - added method `getCurrentRequestType()`
  - minor bug fix - When passing a config array to fromConfig() that does not have any options an empty string was sent to the 4th argument (options) of `setArgument($shortName, $longName=null, $doc='', array $options=[])`
  - minor bug fix - not properly regestering some config settings

    
And that is pretty much it, Enjoy!

