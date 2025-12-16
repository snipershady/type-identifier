<?php

header('Content-type:application/json');

$epti = new TypeIdentifier\Service\EffectivePrimitiveTypeIdentifierService();
$inputServer = $epti->getTypedValueFromServer('HTTP_USER_AGENT');

$requestMethod = filter_var($_SERVER['REQUEST_METHOD'], FILTER_UNSAFE_RAW);

if ('GET' === $requestMethod) {
    $inputParameter = $epti->getTypedValueFromGet('param');
    exit(json_encode(['is_valid' => true, 'value' => $inputParameter, 'agent' => $inputServer]));
}

if ('POST' === $requestMethod) {
    $inputParameter = $epti->getTypedValueFromPost('param');
    exit(json_encode(['is_valid' => true, 'value' => $inputParameter, 'agent' => $inputServer]));
}
