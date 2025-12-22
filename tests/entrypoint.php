<?php

require_once __DIR__.'/../vendor/autoload.php';
header('Content-type:application/json');

$epti = new TypeIdentifier\Service\EffectivePrimitiveTypeIdentifierService();
$inputServer = $epti->getTypedValueFromServer('HTTP_USER_AGENT');

$requestMethod = $epti->getTypedValueFromServer('REQUEST_METHOD');
if ('GET' === $requestMethod) {
    $inputParameter = $epti->getTypedValueFromGet('param');
    exit(json_encode(['is_valid' => true, 'value' => $inputParameter, 'agent' => $inputServer]));
}

if ('POST' === $requestMethod) {
    $inputParameter = $epti->getTypedValueFromPost('param');
    exit(json_encode(['is_valid' => true, 'value' => $inputParameter, 'agent' => $inputServer]));
}
