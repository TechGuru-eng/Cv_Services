<?php
/**
 * API for Gemini AI: Generate Professional Cover Letter
 */

header('Content-Type: application/json; charset=utf-8');

function getGeminiApiKey(): string {
    foreach (['GEMINI_API_KEY', 'GOOGLE_API_KEY'] as $key) {
        $value = getenv($key);
        if (is_string($value) && trim($value) !== '') {
            return trim($value);
        }

        if (isset($_ENV[$key]) && is_string($_ENV[$key]) && trim($_ENV[$key]) !== '') {
            return trim($_ENV[$key]);
        }
    }

    return '';
}

$apiKey = getGeminiApiKey();
if ($apiKey === '') {
    echo json_encode(['success' => false, 'error' => 'La clé API Gemini n\'est pas configurée sur ce serveur.']);
    exit;
}

$apiUrl = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=" . $apiKey;

$data = json_decode(file_get_contents('php://input'), true);
if (!is_array($data)) {
    $data = [];
}

$jobTitle = trim($data['job_title'] ?? '');
$company = trim($data['company'] ?? 'votre entreprise');
$name = trim($data['name'] ?? 'le candidat');

if (empty($jobTitle)) {
    echo json_encode(['success' => false, 'error' => 'Veuillez renseigner le "Poste Visé" avant de générer la lettre.']);
    exit;
}

// Prepare the Prompt for Gemini
$prompt = "Agis comme un candidat expert rédigeant une lettre de motivation en français. "
        . "Tu t'appelles {$name} et tu postules au poste de '{$jobTitle}' chez '{$company}'. "
        . "Rédige uniquement le CORPS de la lettre de motivation (sans les adresses en haut, ni l'objet). "
        . "La lettre doit comporter 3 à 4 paragraphes courts et percutants (introduction, ce que le candidat apporte, conclusion avec appel à l'action pour un entretien). "
        . "Adopte un ton professionnel, confiant et persuasif. "
        . "Ne mets pas de balises markdown, ni de salutations de début (comme Madame, Monsieur, je m'en occuperai), ni de signature finale. Retourne juste le texte principal, prêt à être inséré.";

$payload = [
    "contents" => [
        [
            "parts" => [
                ["text" => $prompt]
            ]
        ]
    ],
    "generationConfig" => [
        "temperature" => 0.7,
        "maxOutputTokens" => 600
    ]
];

$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200 || !$response) {
    echo json_encode(['success' => false, 'error' => 'L\'API IA est temporairement indisponible. Veuillez réessayer plus tard.']);
    exit;
}

$result = json_decode($response, true);
$aiText = '';

if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
    $aiText = trim($result['candidates'][0]['content']['parts'][0]['text']);
    // Clean potential markdown formatting
    $aiText = str_replace(['*', '`'], '', $aiText);
    
    echo json_encode([
        'success' => true,
        'text' => $aiText
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'Erreur lors de la génération du contenu.']);
}
