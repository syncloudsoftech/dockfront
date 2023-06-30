<?php

require_once __DIR__.'/vendor/autoload.php';

$whoops = new Whoops\Run();
$whoops->pushHandler(new Whoops\Handler\PlainTextHandler());
$whoops->register();

use Aws\Credentials\CredentialProvider;
use Aws\Result;
use Aws\S3\S3Client;
use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;

define('READ_BUFFER_SIZE', 1024);

$originType = getenv('ORIGIN_TYPE');

// s3
$s3AccessKeyId = getenv('S3_ACCESS_KEY_ID');
$s3Bucket = getenv('S3_BUCKET');
$s3Endpoint = getenv('S3_ENDPOINT');
$s3PathStyleEndpoint = getenv('S3_PATH_STYLE_ENDPOINT');
$s3Region = getenv('S3_REGION');
$s3SecretAccessKey = getenv('S3_SECRET_ACCESS_KEY');

// web
$webUrl = getenv('WEB_URL');
$webUserAgent = getenv('WEB_USER_AGENT');

$path = ltrim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');

switch ($originType) {
    case 's3':
        if ($path === '') {
            throw new Exception('S3 origin requires a path to be requested, empty or none provided.');
        }

        $client = new S3Client([
            'credentials' => [
                'key' => $s3AccessKeyId,
                'secret' => $s3SecretAccessKey,
            ],
            'endpoint' => $s3Endpoint ?: null,
            'region' => $s3Region,
            'signature_version' => 'v4',
            'use_path_style_endpoint' => $s3PathStyleEndpoint === 'true',
            'version' => 'latest',
        ]);
        $command = $client->getCommand('GetObject', [
            'Key' => $path,
            'Bucket' => $s3Bucket,
            '@http' => ['stream' => true],
        ]);
        $result = $client->execute($command);
        send_s3_result($result);
        break;
    case 'web':
        $response = (new Client())
            ->get(rtrim($webUrl, '/').'/'.$path, [
                'headers' => ['user-agent' => $webUserAgent],
                'stream' => true,
            ]);
        send_psr7_response($response);
        break;
    default:
        throw new Exception(sprintf('Configured origin type i.e., %s is invalid.', $path), 0, $e);
        break;
}

function send_psr7_response(ResponseInterface $response) {
    $status = sprintf(
        'HTTP/%s %s %s',
        $response->getProtocolVersion(),
        $response->getStatusCode(),
        $response->getReasonPhrase()
    );
    header($status, true);
    foreach ($response->getHeaders() as $name => $_) {
        $header = sprintf('%s: %s', $name, $response->getHeaderLine($name));
        header($header, false);
    }

    $body = $response->getBody();
    while (!$body->eof()) {
        echo $body->read(READ_BUFFER_SIZE);
    }
}

function send_s3_result(Result $result) {
    $headers = $result['@metadata']['headers'];
    foreach ($headers as $name => $value) {
        $header = sprintf('%s: %s', $name, $value);
        header($header, false);
    }

    $body = $result['Body'];
    while (!$body->eof()) {
        echo $body->read(READ_BUFFER_SIZE);
    }
}
