<?php
require_once __DIR__ . '/../app/bootstrap.php';

define("GEMINI_API_KEY", env_value("GEMINI_API_KEY", ""));
define("GEMINI_MODEL", env_value("GEMINI_MODEL", "gemini-2.5-flash"));
