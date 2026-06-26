<?php
function app_countries(): array {
    return [
        "AR" => ["flag" => "", "es" => "Argentina", "en" => "Argentina"],
        "BO" => ["flag" => "", "es" => "Bolivia", "en" => "Bolivia"],
        "BR" => ["flag" => "", "es" => "Brasil", "en" => "Brazil"],
        "CA" => ["flag" => "", "es" => "Canada", "en" => "Canada"],
        "CL" => ["flag" => "", "es" => "Chile", "en" => "Chile"],
        "CO" => ["flag" => "", "es" => "Colombia", "en" => "Colombia"],
        "CR" => ["flag" => "", "es" => "Costa Rica", "en" => "Costa Rica"],
        "CU" => ["flag" => "", "es" => "Cuba", "en" => "Cuba"],
        "DO" => ["flag" => "", "es" => "Republica Dominicana", "en" => "Dominican Republic"],
        "EC" => ["flag" => "", "es" => "Ecuador", "en" => "Ecuador"],
        "ES" => ["flag" => "", "es" => "Espana", "en" => "Spain"],
        "GT" => ["flag" => "", "es" => "Guatemala", "en" => "Guatemala"],
        "HN" => ["flag" => "", "es" => "Honduras", "en" => "Honduras"],
        "MX" => ["flag" => "", "es" => "Mexico", "en" => "Mexico"],
        "NI" => ["flag" => "", "es" => "Nicaragua", "en" => "Nicaragua"],
        "PA" => ["flag" => "", "es" => "Panama", "en" => "Panama"],
        "PE" => ["flag" => "", "es" => "Peru", "en" => "Peru"],
        "PR" => ["flag" => "", "es" => "Puerto Rico", "en" => "Puerto Rico"],
        "PY" => ["flag" => "", "es" => "Paraguay", "en" => "Paraguay"],
        "SV" => ["flag" => "", "es" => "El Salvador", "en" => "El Salvador"],
        "US" => ["flag" => "", "es" => "Estados Unidos", "en" => "United States"],
        "UY" => ["flag" => "", "es" => "Uruguay", "en" => "Uruguay"],
        "VE" => ["flag" => "", "es" => "Venezuela", "en" => "Venezuela"]
    ];
}

function normalize_country_code(?string $country): string {
    $country = strtoupper(trim((string)$country));

    if (array_key_exists($country, app_countries())) {
        return $country;
    }

    return "";
}

function country_display(?string $country, string $language = "es"): array {
    $country = normalize_country_code($country);

    if ($country === "") {
        return ["code" => "", "flag" => "", "name" => ""];
    }

    $countries = app_countries();
    $labelKey = $language === "en" ? "en" : "es";

    return [
        "code" => $country,
        "flag" => "",
        "name" => $countries[$country][$labelKey]
    ];
}
