<?php
/** Server-only settings. Environment takes priority; local Laragon secrets stay outside DocumentRoot. */
function moncv_ai_setting(string $name): string {
    $value = getenv($name);
    if (is_string($value) && $value !== '') return $value;
    $localConfig = dirname(__DIR__, 3) . '/etc/moncv-ai.json';
    if (!is_file($localConfig) || !is_readable($localConfig)) return '';
    $settings = json_decode(file_get_contents($localConfig), true);
    return is_array($settings) && is_string($settings[$name] ?? null) ? trim($settings[$name]) : '';
}
