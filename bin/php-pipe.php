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
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Formatter\LineFormatter;

try {
    $logger = new Logger('standard');
    $logger->pushHandler(
        (new StreamHandler('php://stderr'))->setFormatter(new LineFormatter("[%datetime%] %level_name%: %message%\n"))
    );

    $parser = new Optparse\Parser();

    function usage() {
        global $parser;
        fwrite(STDERR, "{$parser->usage()}\n");
        exit(1);
    }

    $parser->setExamples([
        sprintf("%s --script ./script.php", $argv[0]),
        sprintf("cat ./data.csv | %s --script ./script.php", $argv[0]),
    ]);

    $script = null;

    $parser->addFlag('help', [ 'alias' => '-h' ], 'usage');
    $parser->addFlag('verbose', [ 'alias' => '-v' ]);

    $parser->addFlagVar('script', $script, [ 'alias' => '-s', 'required' => true, 'has_value' => true ]);

    $parser->parse();

    if (!$script) {
        usage();
    }

    if (($fp = fopen('php://stdin', 'r')) === false) {
        usage();
    }
    // No input from pipe of stdin
    if (stream_get_meta_data($fp)['seekable'] == true) {
        $logger->error("No input from pipe of stdin");
        usage();
    }

    if (file_exists($script))
        require_once $script;

    if (!function_exists('pipe')) {
        $logger->error(sprintf("Undefined function pipe() in script: %s", $script));
        function pipe($line) {
            return $line . "\n";
        }
    }

    while (!feof($fp)) {
        $line = trim(fgets($fp));

        echo pipe($line);
    }
    fclose($fp);

} catch (\Exception $e) {

    $logger->error($e->getMessage());

    exit(255);
}
