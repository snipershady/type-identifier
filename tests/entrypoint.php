<?php

declare(strict_types=1);

use TypeIdentifier\Service\EffectivePrimitiveTypeIdentifierService;

require_once __DIR__ . '/../vendor/autoload.php';

/*
 * Test endpoint driven by EffectivePrimitiveTypeRequestTest over real HTTP.
 *
 * Its whole reason to exist is the filter_input() branch of
 * EffectivePrimitiveTypeIdentifierService::readFromInput(): under CLI
 * filter_input() always returns null and only the direct $_* fallback runs, so
 * that branch — the one that actually executes in production — can only be
 * covered by a request served by a real SAPI.
 *
 * Reads every typed accessor for INPUT_GET, INPUT_POST, INPUT_SERVER and
 * INPUT_COOKIE and echoes the results as JSON for the test to assert on.
 */

header('Content-type: application/json');

$epti = new EffectivePrimitiveTypeIdentifierService();

$requestMethod = $epti->getStringValueFromServer('REQUEST_METHOD');
$isGet = 'GET' === $requestMethod;
$isPost = 'POST' === $requestMethod;

if (!$isGet && !$isPost) {
    http_response_code(405);

    exit(json_encode(['is_valid' => false, 'method' => $requestMethod], JSON_THROW_ON_ERROR));
}

/*
 * "SAPI only" mode, requested with the X-Test-Sapi-Only header.
 *
 * Emptying the superglobal entries disarms the fallback half of
 * readFromInput(): from here on the $_* arrays hold nothing for these keys, so
 * any value still coming back can only have been read by filter_input() from
 * the SAPI input stream. It turns "the filter_input() branch is covered" from
 * an assumption into an assertion the test suite can actually make — the branch
 * runs in this server process, which the CLI coverage driver cannot observe.
 */
$sapiOnly = '' !== $epti->getStringValueFromServer('HTTP_X_TEST_SAPI_ONLY', trim: true);
if ($sapiOnly) {
    unset(
        $_GET['param'],
        $_POST['param'],
        $_SERVER['HTTP_USER_AGENT'],
        $_SERVER['HTTP_X_TEST_INT'],
        $_SERVER['HTTP_X_TEST_FLOAT'],
        $_SERVER['HTTP_X_TEST_BOOL'],
        $_SERVER['HTTP_X_TEST_STRING'],
        $_COOKIE['test_int'],
        $_COOKIE['test_float'],
        $_COOKIE['test_bool'],
        $_COOKIE['test_string'],
    );
}

$response = [
    'is_valid' => true,
    'method' => $requestMethod,
    'sapi_only' => $sapiOnly,

    // getTypedValueFrom{Get,Post}() — the untyped resolution
    'value' => $isGet
        ? $epti->getTypedValueFromGet('param')
        : $epti->getTypedValueFromPost('param'),

    // getTypedValueFromServer()
    'agent' => $epti->getTypedValueFromServer('HTTP_USER_AGENT'),

    // the four typed accessors of the request source
    'typed' => [
        'int' => $isGet ? $epti->getIntValueFromGet('param') : $epti->getIntValueFromPost('param'),
        'float' => $isGet ? $epti->getFloatValueFromGet('param') : $epti->getFloatValueFromPost('param'),
        'bool' => $isGet ? $epti->getBoolValueFromGet('param') : $epti->getBoolValueFromPost('param'),
        'string' => $isGet ? $epti->getStringValueFromGet('param') : $epti->getStringValueFromPost('param'),
    ],

    // the four typed accessors of $_SERVER, fed by X-Test-* request headers
    'server' => [
        'int' => $epti->getIntValueFromServer('HTTP_X_TEST_INT'),
        'float' => $epti->getFloatValueFromServer('HTTP_X_TEST_FLOAT'),
        'bool' => $epti->getBoolValueFromServer('HTTP_X_TEST_BOOL'),
        'string' => $epti->getStringValueFromServer('HTTP_X_TEST_STRING', trim: true),
    ],

    // the four typed accessors of $_COOKIE, fed by request cookies
    'cookie' => [
        'int' => $epti->getIntValueFromCookie('test_int'),
        'float' => $epti->getFloatValueFromCookie('test_float'),
        'bool' => $epti->getBoolValueFromCookie('test_bool'),
        'string' => $epti->getStringValueFromCookie('test_string', trim: true),
    ],

    // getTypedValueFromCookie(): the untyped resolution of the same source
    'cookie_value' => $epti->getTypedValueFromCookie('test_int'),
];

exit(json_encode($response, JSON_THROW_ON_ERROR));
