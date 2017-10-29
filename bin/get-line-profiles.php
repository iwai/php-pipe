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
    sprintf("%s --token XXX ./id_list.txt", $argv[0]),
]);

$token = null;

$parser->addFlag('help', [ 'alias' => '-h' ], 'usage');
$parser->addFlag('verbose', [ 'alias' => '-v' ]);

$parser->addFlagVar('token', $token, [ 'required' => true, 'has_value' => true ]);
$parser->addArgument('file', [ 'required' => false ]);

try {
    $parser->parse();
} catch (\Exception $e) {
    usage();
}

$file_path = $parser['file'];

try {
    if (!$token) {
        usage();
    }

    if ($file_path) {
        if (($fp = fopen($file_path, 'r')) === false) {
            die('Could not open '.$file_path);
        }
    } else {
        if (($fp = fopen('php://stdin', 'r')) === false) {
            usage();
        }
        $read = [$fp];
        $w = $e = null;
        $num_changed_streams = stream_select($read, $w, $e, 1);

        if (!$num_changed_streams) {
            usage();
        }
    }

    $client = null;

    # https://api.line.me/v2/bot/profile/

    $client = new GuzzleHttp\Client([
        'base_uri' => 'https://api.line.me/',
        'timeout'  => 3.0,
    ]);

    while (!feof($fp)) {
        $line = trim(fgets($fp));

        if (empty($line)) {
            continue;
        }

        if ($parser->flag('verbose')) {
        }

        try {
            $response = $client->get(
                sprintf('/v2/bot/profile/%s', $line),
                ['headers' => [ 'Authorization' => 'Bearer ' . $token ]]
            );

            echo $response->getBody(), PHP_EOL;

        } catch (\GuzzleHttp\Exception\ClientException $e) {

            fwrite(STDERR, $token . PHP_EOL);
            fwrite(STDERR, sprintf('%s: %s %s', $e->getResponse()->getStatusCode(), $e->getResponse()->getBody(), $line) . PHP_EOL);
        }
    }
    fclose($fp);

} catch (\Exception $e) {
    throw $e;
}
