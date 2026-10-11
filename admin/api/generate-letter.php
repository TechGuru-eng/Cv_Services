<?php
// admin/api/generate-letter.php
header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['admin'])) {
    echo json_encode(['success' => false, 'message' => 'Non autorisé. Veuillez vous connecter.']);
    exit;
}

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

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    echo json_encode(['success' => false, 'message' => 'Données invalides.']);
    exit;
}

$targetPosition = trim($input['target_position'] ?? '');
$targetCompany = trim($input['target_company'] ?? '');
$targetLocation = trim($input['target_location'] ?? '');
$tone = trim($input['tone'] ?? 'Professionnel');
$language = trim($input['language'] ?? 'Français');
$clientData = trim($input['client_data'] ?? '');
$apiKey = getGeminiApiKey();

if ($apiKey === '') {
    echo json_encode([
        'success' => false,
        'message' => 'La clé API Gemini n\'est pas configurée sur ce serveur.'
    ]);
    exit;
}

if (empty($targetPosition)) {
    echo json_encode(['success' => false, 'message' => 'Veuillez renseigner le poste ciblé.']);
    exit;
}

// Build Prompt
$prompt = "Rédige une lettre de motivation professionnelle de haut niveau.\n";
$prompt .= "Langue de rédaction : " . ($language === 'Anglais' ? 'English' : 'Français') . "\n";
$prompt .= "Ton de la lettre : " . ($tone === 'Dynamique' ? 'Dynamique et Moderne' : 'Professionnel et Convaincant') . "\n";
$prompt .= "Poste ciblé : " . $targetPosition . "\n";
if (!empty($targetCompany)) {
    $prompt .= "Entreprise ciblée : " . $targetCompany . "\n";
}
if (!empty($targetLocation)) {
    $prompt .= "Localisation de l'entreprise : " . $targetLocation . "\n";
}
$prompt .= "\nDonnées sur le profil du candidat :\n" . $clientData . "\n\n";
$prompt .= "Règles strictes de rédaction :\n";
$prompt .= "1. Format standard et élégant avec des paragraphes bien espacés.\n";
$prompt .= "2. Salutation formelle adaptée (ex: 'Madame, Monsieur,' ou 'Monsieur le Directeur,').\n";
$prompt .= "3. Pas de placeholders génériques comme '[Votre Nom]', laisse des espaces vides ou utilise les données du candidat si fournies.\n";
$prompt .= "4. Si l'entreprise est mentionnée, explique pourquoi le candidat souhaite la rejoindre spécifiquement.\n";
$prompt .= "5. Termine par une formule de politesse chaleureuse et professionnelle.\n";

// Execute Gemini API call
$apiUrl = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=" . $apiKey;

$postData = [
    'contents' => [
        [
            'parts' => [
                ['text' => $prompt]
            ]
        ]
    ]
];

$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($response === false) {
    echo json_encode(['success' => false, 'message' => 'Erreur cURL : ' . $error]);
    exit;
}

if ($httpCode !== 200) {
    $respDecoded = json_decode($response, true);
    $errMsg = $respDecoded['error']['message'] ?? 'Erreur inconnue de l\'API Gemini.';
    echo json_encode(['success' => false, 'message' => 'Erreur API (' . $httpCode . ') : ' . $errMsg]);
    exit;
}

$respDecoded = json_decode($response, true);
$generatedText = $respDecoded['candidates'][0]['content']['parts'][0]['text'] ?? '';

if (empty($generatedText)) {
    echo json_encode(['success' => false, 'message' => 'L\'API n\'a renvoyé aucun texte.']);
    exit;
}

// Clean markdown formatting if Gemini wrapped it in markdown code blocks
$generatedText = preg_replace('/^```[a-zA-Z]*\n/', '', $generatedText);
$generatedText = preg_replace('/\\n```$/', '', $generatedText);
$generatedText = trim($generatedText);

echo json_encode([
    'success' => true,
    'letter' => $generatedText
]);
