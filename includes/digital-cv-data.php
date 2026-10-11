<?php
function digital_cv_data(string $json): array {
    $source = json_decode($json, true);
    if (!is_array($source)) throw new InvalidArgumentException('Invalid CV data');
    $safe = [];
    foreach (['name', 'title', 'summary', 'location', 'email', 'phone', 'photo', 'template', 'theme', 'spacing', 'font'] as $key) {
        $safe[$key] = htmlspecialchars(is_string($source[$key] ?? null) ? $source[$key] : '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
    foreach (['experiences' => ['title', 'years', 'company', 'desc'], 'education' => ['degree', 'years', 'school']] as $key => $fields) {
        $safe[$key] = [];
        foreach (is_array($source[$key] ?? null) ? $source[$key] : [] as $row) {
            if (!is_array($row)) continue;
            $item = [];
            foreach ($fields as $field) $item[$field] = htmlspecialchars(is_string($row[$field] ?? null) ? $row[$field] : '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $safe[$key][] = $item;
        }
    }
    foreach (['skills', 'languages'] as $key) {
        $safe[$key] = [];
        foreach (is_array($source[$key] ?? null) ? $source[$key] : [] as $value) {
            if (is_string($value)) $safe[$key][] = htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        }
    }
    $photo = is_string($source['photo'] ?? null) ? $source['photo'] : '';
    if ($photo !== '' && !preg_match('~^data:image/(?:png|jpeg|webp);base64,[A-Za-z0-9+/=]+$~', $photo) &&
        !preg_match('~^https?://~i', $photo)) $safe['photo'] = '';
    return $safe;
}
