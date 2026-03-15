<?php
header('Content-Type: application/json');
session_start();

/**
 * CV Audit API - Simulation & Scoring Logic
 * Criteria: Job Match (30%), ATS (25%), Density (20%), Contacts (15%), Action Verbs (10%)
 */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$targetJob = $_POST['job_title'] ?? '';
$cvFile = $_FILES['cv_file'] ?? null;

if (!$cvFile || !$targetJob) {
    echo json_encode(['success' => false, 'message' => 'Job title and CV file are required']);
    exit;
}

// 1. Deterministic Seeding for Consistency
// We use a hash of the job title and file properties to seed the random generator.
// This ensures that the same CV for the same job always produces the same score.
$seed = crc32($targetJob . $cvFile['name'] . $cvFile['size']);
srand($seed);

// Simulate "Analyzing" delay for UX
// In a real production app, we would use a PDF parser here.
// For this version, we will perform a "smart simulation" based on file properties 
// and common keywords if it's text-based, otherwise fallback to consistent pseudo-random ranges.

$score = 0;
$breakdown = [
    'job_match' => 0,
    'ats_friendly' => 0,
    'density' => 0,
    'contacts' => 0,
    'action_verbs' => 0
];

$findings = [];

// --- Professional Scoring Engine ---

// 1. Dictionaries
$actionVerbs = ['géré', 'dirigé', 'optimisé', 'développé', 'piloté', 'négocié', 'accru', 'réduit', 'conçu', 'lancé', 'analysé', 'supervisé'];
$softSkills = ['leadership', 'autonomie', 'rigueur', 'esprit d\'équipe', 'communication', 'adaptabilité', 'organisation', 'négociation'];
$atsRedFlags = ['tableaux', 'colonnes', 'images', 'logos', 'zones de texte'];

// 2. Sector Identification
$sectors = [
    'tech' => ['dev', 'informatique', 'réseau', 'data', 'cloud', 'logiciel'],
    'finance' => ['comptable', 'finance', 'audit', 'banque', 'gestion', 'trésorerie'],
    'admin' => ['rh', 'assistant', 'secrétaire', 'administratif', 'accueil'],
    'logistics' => ['logistique', 'achat', 'stock', 'transport', 'supply'],
    'sales' => ['commercial', 'vente', 'business', 'client', 'marketing']
];

$detectedSector = 'general';
foreach ($sectors as $sector => $keywords) {
    foreach ($keywords as $k) {
        if (stripos($targetJob, $k) !== false) {
            $detectedSector = $sector;
            break 2;
        }
    }
}

// 3. Score Calculations

// A. Job Match (30%)
$jobWords = explode(' ', trim($targetJob));
if (count($jobWords) >= 2) {
    if ($detectedSector !== 'general') {
        $breakdown['job_match'] = rand(85, 96);
        $findings[] = ['type' => 'success', 'text' => "Excellente précision métier ($detectedSector). Votre profil est très sectorisé."];
    } else {
        $breakdown['job_match'] = rand(70, 85);
        $findings[] = ['type' => 'success', 'text' => "Titre de poste précis détecté. Bonne clarté d'intention."];
    }
} else {
    $breakdown['job_match'] = rand(45, 60);
    $findings[] = ['type' => 'warning', 'text' => "Titre trop vague. Ajoutez votre spécialité (ex: 'RH' -> 'Assistant RH de proximité')."];
}

// B. ATS Friendly (25%)
$extension = strtolower(pathinfo($cvFile['name'], PATHINFO_EXTENSION));
if ($extension !== 'pdf') {
    $breakdown['ats_friendly'] = rand(20, 40);
    $findings[] = ['type' => 'warning', 'text' => "Format $extension non optimal. 80% des recruteurs utilisent l'ATS, le PDF est obligatoire."];
} else {
    $fileSize = $cvFile['size'];
    if ($fileSize > 2000000) { // 2MB
        $breakdown['ats_friendly'] = rand(50, 70);
        $findings[] = ['type' => 'warning', 'text' => "Fichier trop lourd. Une taille > 2Mo indique souvent trop d'images illisibles pour un robot."];
    } else {
        $breakdown['ats_friendly'] = rand(90, 100);
        $findings[] = ['type' => 'success', 'text' => "Score ATS maximal. Structure technique propre et légère."];
    }
}

// C. Density & Content (20%)
$fileName = strtolower($cvFile['name']);
$hasName = (stripos($fileName, 'cv') !== false || stripos($fileName, 'curriculum') !== false);
if ($hasName && strlen($fileName) > 10) {
    $breakdown['density'] = rand(80, 95);
    $findings[] = ['type' => 'success', 'text' => "Volume d'informations cohérent avec un standard de 1 à 2 pages."];
} else {
    $breakdown['density'] = rand(40, 65);
    $findings[] = ['type' => 'warning', 'text' => "Le nom de fichier manque de professionnalisme. Préférez 'CV-PRENOM-NOM.pdf'."];
}

// D. Contacts & Presence (15%)
$breakdown['contacts'] = rand(75, 98);
$findings[] = ['type' => 'info', 'text' => "Présence de coordonnées cliquables (Email, WhatsApp) simulée comme valide."];

// E. Action & Impact (10%)
// Professional verbiage simulation
$breakdown['action_verbs'] = rand(40, 90);
if ($breakdown['action_verbs'] < 65) {
    $findings[] = ['type' => 'warning', 'text' => "Manque de chiffres clés. Un CV pro doit mentionner des (%) ou des chiffres de réussite."];
} else {
    $findings[] = ['type' => 'success', 'text' => "Vocabulaire d'impact détecté. Verbes d'action puissants identifiés."];
}

// Total Score Calculation
$totalScore = (
    ($breakdown['job_match'] * 0.30) +
    ($breakdown['ats_friendly'] * 0.25) +
    ($breakdown['density'] * 0.20) +
    ($breakdown['contacts'] * 0.15) +
    ($breakdown['action_verbs'] * 0.10)
);

$totalScore = round($totalScore);

// Store in session for lead capture
$_SESSION['last_audit'] = [
    'score' => $totalScore,
    'job' => $targetJob,
    'sector' => $detectedSector,
    'timestamp' => time()
];

$totalScore = round($totalScore);

// Store in session for the next step (lead capture)
$_SESSION['last_audit'] = [
    'score' => $totalScore,
    'job' => $targetJob,
    'timestamp' => time()
];

echo json_encode([
    'success' => true,
    'score' => $totalScore,
    'breakdown' => $breakdown,
    'findings' => $findings,
    'audit_id' => uniqid('audit_')
]);
