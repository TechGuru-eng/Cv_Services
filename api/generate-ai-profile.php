<?php
/**
 * API for Gemini AI: Generate Professional Profile Summary
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

if (empty($jobTitle)) {
    echo json_encode(['success' => false, 'error' => 'Veuillez renseigner le "Titre du Poste" avant de générer le profil avec l\'IA.']);
    exit;
}

// Prepare the Prompt for Gemini
$prompt = "Agis comme un expert RH et recruteur francophone senior. "
        . "Le candidat postule pour le poste de : '{$jobTitle}'. "
        . "Rédige une 'Accroche Professionnelle' (Profil) de CV, percutante, dynamique, de 3 à 4 phrases maximum. "
        . "Mets en avant la détermination, l'orientation résultat et la volonté d'apporter de la valeur à l'entreprise. "
        . "N'utilise ni le pronom 'je' au début de chaque phrase, ni d'introduction de chatbot (comme 'Voici votre accroche'). "
        . "Retourne UNIQUEMENT le texte de l'accroche, prêt à être copié-collé sur le CV.";

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
        "maxOutputTokens" => 250
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
    // Clean potential markdown quotes
    $aiText = str_replace(['"', '`'], '', $aiText);
    
    echo json_encode([
        'success' => true,
        'text' => $aiText
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'Erreur lors de la génération du contenu.']);
}
