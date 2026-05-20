<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_GET["lang"])) {
    $selectedLang = $_GET["lang"];

    if (in_array($selectedLang, ["es", "en"])) {
        $_SESSION["lang"] = $selectedLang;
    }
}

$langCode = $_SESSION["lang"] ?? "es";

$langFile = __DIR__ . "/" . $langCode . ".php";

if (!file_exists($langFile)) {
    $langFile = __DIR__ . "/es.php";
}

$translations = require $langFile;

function t($key) {
    global $translations;
    return $translations[$key] ?? $key;
}

function current_lang() {
    return $_SESSION["lang"] ?? "es";
}