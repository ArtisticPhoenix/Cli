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
	//get an arguments value from request
	public function getArguments($which=null, $default=null) : mixed;
	//set the allowed request types (for overriding)
	public function setAllowedRequestTypes($requestType) : self;	
	//set the current request types (for overriding auto detection)
	public function setCurrentRequestType($requestType) : self;	
	//set a request (for overriding)
	public function setRequest(array $request) : self;
	//get a list of the allowed options (see options)
	public function getOptions() : array;
	//get the argument help doc as text
	public function getHelpDoc() : string;
	//output the argement help doc
	public function printHelpDoc($exit=true) : null;
```
### Arguments ###
 Name              |   Type   |   Required  | Description
 ----------------- | -------- | ----------- | ------------------------------------------------------
 $conf             |  array   |      yes    | An array of arguments to set (see below)
 $shortName        |  string  |      yes    | An arguments short name max length of 1, a-z A-Z or 0-9
 $longName         |  string  |      no     | The long name for an argument or null, min length of 1, a-z A-Z or 0-9
 $which			   |  string  |      no     | Argument's shortName or longName, get an arguments value from request, null for get all
 $default		   |  mixed   |      no     | Default value to return when no value is set in the request
 $options          |  array   |      no     | An array of options for the argument (see below)
 $requestType      |  int     |      yes    | One of the Cli::REQUEST_ constants or Cli::R_ALL (bitwise)
 $request          |  array   |      yes    | The request (typically auto detected)
 $exit             |  boolean |      no     | to exit or not

### Options ###
 Name              |   Type    |   Required  | Description
 ----------------- | --------- | ----------- | ------------------------------------------------------
 'accept'          |  Closure  |     no      | A callback function to run against the incoming request value
 'requireValue'    |    bool   |     no      | If this argument is present in the request then a value is required for it
 

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
				'accept' => function($shortName, $value){
					if($value == 'foo') return true;
					return false;
				}
			]
		],[
			'shortName' => 'i',
			'longName' => 'input',
			'doc' => 'This is input that requires a value',
			'options' => [
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
    //get all arguments (get all will return the shortName as the keys)
    $args = $Cli->getArguments();
    //get a single argument (using the shortName)
    $help = $Cli->getArguments('h');
    //get a single argument (using the longName)
    $help = $Cli->getArguments('help');
    //get a single argument (using the shortName) with a custom default return value (returned if the argument was not set)
    $foo = $Cli->getArguments('foo', 'Hello World');
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
    
    
