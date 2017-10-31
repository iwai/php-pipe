#!/usr/bin/env php
<?php
/**
 * Created by PhpStorm.
 * User: iwai
 * Date: 2017/02/03
 * Time: 13:05
 */

ini_set('date.timezone', 'Asia/Tokyo');

if (PHP_SAPI !== 'cli') {
    echo sprintf('Warning: %s should be invoked via the CLI version of PHP, not the %s SAPI'.PHP_EOL, $argv[0], PHP_SAPI);
    exit(1);
}

require_once __DIR__.'/../vendor/autoload.php';

use CHH\Optparse;

$parser = new Optparse\Parser();

function usage() {
    global $parser;
    fwrite(STDERR, "{$parser->usage()}\n");
    exit(1);
}

$parser->setExamples([
    sprintf("%s --script ./script.php", $argv[0]),
]);

$script = null;

$parser->addFlag('help', [ 'alias' => '-h' ], 'usage');
$parser->addFlag('verbose', [ 'alias' => '-v' ]);

$parser->addFlagVar('script', $script, [ 'required' => true, 'has_value' => true ]);

try {
    $parser->parse();
} catch (\Exception $e) {
    usage();
}

try {
    if (!$script) {
        usage();
    }

    if (($fp = fopen('php://stdin', 'r')) === false) {
        usage();
    }
    $read = [$fp];
    $w = $e = null;
    $num_changed_streams = stream_select($read, $w, $e, 1);

    if (!$num_changed_streams) {
        usage();
    }

    require_once $script;

    while (!feof($fp)) {
        $line = trim(fgets($fp));

        echo pipe($line);
    }
    fclose($fp);

} catch (\Exception $e) {
    throw $e;
}
