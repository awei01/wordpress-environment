<?php

require(__DIR__ . '/autoload.php');

$configs = require(__DIR__ . '/configs.php');

$env = new Environment($configs);

$env->boot();

return $env;