<?php

require "../vendor/autoload.php";


use Xiajin2015\Utils\Date;
use Xiajin2015\Utils\Random;

$result = Date::span('1698811683', time());
echo '<pre>';
var_dump($result);
echo '<hr/>';
$result = Random::alnum(6);
echo '<pre>';
var_dump($result);

