<?php

function validate_password_policy($password) {
    $errors = [];

    if (strlen($password) < 10) {
        $errors[] = "mínimo 10 caracteres";
    }

    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = "una minúscula";
    }

    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = "una mayúscula";
    }

    if (!preg_match('/\d/', $password)) {
        $errors[] = "un número";
    }

    if (!preg_match('/[^a-zA-Z\d]/', $password)) {
        $errors[] = "un símbolo";
    }

    return $errors;
}

function password_policy_message() {
    return "La contraseña debe tener mínimo 10 caracteres e incluir mayúscula, minúscula, número y símbolo.";
}
