<?php

require_once __DIR__ . '/support/env.php';
require_once __DIR__ . '/support/http.php';

load_env_file(__DIR__ . '/../.env');

error_reporting(E_ALL);
ini_set('display_errors', env_bool('APP_DEBUG', false) ? '1' : '0');
ini_set('log_errors', '1');
