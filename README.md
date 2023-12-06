# utils

2023-11-25  初始化

2023-12-6  发布相应工具接口



**使用方法如下：**

```php
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


```

