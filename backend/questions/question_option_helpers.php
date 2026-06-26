<?php

function question_option_letters() {
    return ["A", "B", "C", "D"];
}

function normalize_correct_option_position(array $question, $targetCorrectOption = null) {
    $letters = question_option_letters();
    $correctOption = strtoupper(trim($question["correct_option"] ?? ""));

    if (!in_array($correctOption, $letters, true)) {
        return $question;
    }

    if ($targetCorrectOption === null) {
        $targetCorrectOption = $letters[random_int(0, count($letters) - 1)];
    }

    $targetCorrectOption = strtoupper(trim($targetCorrectOption));

    if (!in_array($targetCorrectOption, $letters, true) || $targetCorrectOption === $correctOption) {
        $question["correct_option"] = $correctOption;
        return $question;
    }

    $currentKey = "option_" . strtolower($correctOption);
    $targetKey = "option_" . strtolower($targetCorrectOption);
    $correctText = $question[$currentKey] ?? "";

    $question[$currentKey] = $question[$targetKey] ?? "";
    $question[$targetKey] = $correctText;
    $question["correct_option"] = $targetCorrectOption;

    return $question;
}

function build_shuffled_question_payload(array $row) {
    $letters = question_option_letters();
    $correctOption = strtoupper(trim($row["correct_option"] ?? ""));
    $optionItems = [];

    foreach ($letters as $letter) {
        $optionItems[] = [
            "letter" => $letter,
            "text" => $row["option_" . strtolower($letter)] ?? ""
        ];
    }

    shuffle($optionItems);

    $options = [];
    $optionLetters = [];
    $correctIndex = 0;

    foreach ($optionItems as $index => $item) {
        $options[] = $item["text"];
        $optionLetters[] = $item["letter"];

        if ($item["letter"] === $correctOption) {
            $correctIndex = $index;
        }
    }

    return [
        "options" => $options,
        "option_letters" => $optionLetters,
        "correct" => $correctIndex,
        "correct_option" => $correctOption,
        "display_correct_option" => $letters[$correctIndex]
    ];
}
