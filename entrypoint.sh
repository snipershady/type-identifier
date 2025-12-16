#!/bin/bash


composer install

php vendor/bin/phpunit tests/EffectivePrimitiveTypeTest.php
php vendor/bin/phpunit tests/EffectivePrimitiveTypeRequestTest.php 
 