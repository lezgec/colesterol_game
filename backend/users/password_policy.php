<?php

function validate_password_policy($password) {
    $errors = [];

    if (strlen($password) < 10) {
        $errors[] = "minimo 10 caracteres";
    }

    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = "una minuscula";
    }

    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = "una mayuscula";
    }

    if (!preg_match('/\d/', $password)) {
        $errors[] = "un numero";
    }

    if (!preg_match('/[^a-zA-Z\d]/', $password)) {
        $errors[] = "un simbolo";
    }

    return $errors;
}

function password_policy_message() {
    return "La contrasena debe tener minimo 10 caracteres e incluir mayuscula, minuscula, numero y simbolo.";
}
