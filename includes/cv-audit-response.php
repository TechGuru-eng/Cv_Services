<?php
function cv_audit_schema(): array {
    $scores = [];
    foreach (['job_match', 'ats_friendly', 'density', 'contacts', 'action_verbs'] as $key) {
        $scores[$key] = ['type' => 'integer', 'minimum' => 0, 'maximum' => 100];
    }
    return ['type' => 'object', 'properties' => [
        'readable' => ['type' => 'boolean'], 'sector' => ['type' => 'string'],
        'breakdown' => ['type' => 'object', 'properties' => $scores, 'required' => array_keys($scores)],
        'findings' => ['type' => 'array', 'items' => ['type' => 'object', 'properties' => [
            'type' => ['type' => 'string', 'enum' => ['success', 'warning', 'info']], 'text' => ['type' => 'string']], 'required' => ['type', 'text']]],
    ], 'required' => ['readable', 'sector', 'breakdown', 'findings']];
}
function cv_audit_result(?array $data): array {
    if (!$data || ($data['readable'] ?? false) !== true || !is_string($data['sector'] ?? null) ||
        !is_array($data['findings'] ?? null) || !$data['findings']) throw new UnexpectedValueException('Unreadable CV');
    $weights = ['job_match' => 0.30, 'ats_friendly' => 0.25, 'density' => 0.20, 'contacts' => 0.15, 'action_verbs' => 0.10];
    $total = 0;
    $breakdown = [];
    foreach ($weights as $key => $weight) {
        $value = $data['breakdown'][$key] ?? null;
        if (!is_int($value) || $value < 0 || $value > 100) throw new UnexpectedValueException('Invalid score');
        $breakdown[$key] = $value;
        $total += $value * $weight;
    }
    $findings = [];
    foreach (array_slice($data['findings'], 0, 12) as $finding) {
        if (!in_array($finding['type'] ?? '', ['success', 'warning', 'info'], true) || !is_string($finding['text'] ?? null)) {
            throw new UnexpectedValueException('Invalid finding');
        }
        $findings[] = ['type' => $finding['type'], 'text' => mb_substr($finding['text'], 0, 500)];
    }
    return ['score' => (int)round($total), 'breakdown' => $breakdown, 'findings' => $findings, 'sector' => mb_substr($data['sector'], 0, 80)];
}
