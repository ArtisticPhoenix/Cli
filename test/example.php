<?php
require_once __DIR__.'/../vendor/autoload.php';

$Cli = \evo\cli\Cli::I();

/**
 *  php C:/UniserverZ/www/evo/Cli/test/example.php -h
 */
//Register command line arguments [$Cli::OPT_VALUE_EXPECTED=>true]
$Cli->setArgument('h', 'help', 'Show this help document');

$Cli->setArgument('b', 'beta', 'a simple option');

$Cli->setArgument('d', 'delta', 'a generic option', [
    $Cli::OPT_VALUE_EXPECTED => true,
    $Cli::OPT_MULTIPLE_EXPECTED => true,
]);
$Cli->setArgument('g', 'gamma', 'a generic option', [
    $Cli::OPT_VALUE_EXPECTED => true,
    //   $Cli::OPT_MULTIPLE_EXPECTED => true,
    $Cli::OPT_MUST_VALIDATE => fn($k,$v)=>strlen($v)
]);


if($Cli->getRequest('h')){
    $Cli->printHelpDoc();
}
