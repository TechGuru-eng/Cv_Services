<?php
session_start();
require_once 'admin/config/db.php';

$stmt = $pdo->query("SELECT id, name, email, whatsapp FROM orders WHERE LOWER(payment_status) = 'verified' ORDER BY id DESC");
$verifiedOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);
$totalNames = count($verifiedOrders);
?>
<!DOCTYPE html>
<html lang="fr" class="scroll-smooth">
<head>
<meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Suivi & Analyse CV Premium | MonCV</title>
    <meta name="description" content="Notre engagement envers votre réussite. Demandez une analyse gratuite de votre CV si vous n'obtenez pas d'entretiens. Service exclusif aux clients MonCV.">
    <meta name="keywords" content="analyse cv, refonte cv, suivi cv, optimisation cv, garantie cv, MonCV">
    <meta name="author" content="MonCV">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="https://moncvpro.hiremeguide.com/cv-care.php" />
    
    <!-- Open Graph / Social Media -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://moncvpro.hiremeguide.com/cv-care.php">
    <meta property="og:title" content="Suivi & Analyse CV Premium | MonCV">
    <meta property="og:description" content="Notre engagement envers votre réussite. Demandez une analyse de votre CV après 3 mois sans entretiens.">
    <meta property="og:image" content="https://moncvpro.hiremeguide.com/assets/images/cv_care_flyer.png">
    
    <!-- Favicons -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Plus Jakarta Sans', 'system-ui', 'sans-serif'],
                        title: ['Outfit', 'sans-serif']
                    },
                    colors: {
                        primary: '#0ea5e9',
                        dark: '#0f172a',
                        cardBg: 'rgba(255, 255, 255, 0.85)'
                    },
                    animation: {
                        'float': 'float 6s ease-in-out infinite',
                        'fade-in-up': 'fadeInUp 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards',
                        'pulse-slow': 'pulse 4s cubic-bezier(0.4, 0, 0.6, 1) infinite',
                    },
                    keyframes: {
                        float: {
                            '0%, 100%': { transform: 'translateY(0)' },
                            '50%': { transform: 'translateY(-20px)' },
                        },
                        fadeInUp: {
                            '0%': { opacity: '0', transform: 'translateY(40px)' },
                            '100%': { opacity: '1', transform: 'translateY(0)' },
                        }
                    }
                }
            }
        }
    </script>
    <style>
        body {
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 50%, #bae6fd 100%);
            color: #0f172a;
            overflow-x: hidden;
        }

        .glass-panel {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border: 1px solid rgba(255, 255, 255, 0.6);
            box-shadow: 0 20px 40px -15px rgba(14, 165, 233, 0.15);
        }

        /* Form Inputs */
        select, input[type="text"], input[type="email"] {
            background: #ffffff;
            border: 2px solid #e2e8f0;
            color: #0f172a;
        }
        select:focus, input:focus {
            border-color: #0ea5e9;
            box-shadow: 0 0 0 4px rgba(14, 165, 233, 0.1);
        }

        /* Upload Zones */
        .upload-zone {
            background: #f8fafc;
            border: 2px dashed #cbd5e1;
            transition: all 0.4s ease;
        }
        .upload-zone:hover, .upload-zone.dragover {
            border-color: #0ea5e9;
            background: #f0f9ff;
            transform: translateY(-2px);
        }

        /* Checkbox */
        .custom-checkbox input:checked + div {
            background: #0ea5e9;
            border-color: #0ea5e9;
        }
        .custom-checkbox input:checked + div svg {
            opacity: 1;
            transform: scale(1);
        }

        /* Custom Scrollbar */
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: #f1f5f9; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

        .spinner {
            border: 3px solid rgba(255,255,255,0.3);
            border-radius: 50%;
            border-top: 3px solid white;
            width: 20px;
            height: 20px;
            animation: spin 1s linear infinite;
        }

        .delay-100 { animation-delay: 0.1s; }
        .delay-200 { animation-delay: 0.2s; }
        .delay-300 { animation-delay: 0.3s; }
    </style>
    <script src="assets/js/site-language.js?v=4" defer></script>

    <!-- Shared HireMe favicon -->
    <link rel="icon" type="image/png" sizes="48x48" href="assets/favicons/favicon-48.png?v=20261011">
    <link rel="icon" type="image/png" sizes="32x32" href="assets/favicons/favicon-32.png?v=20261011">
    <link rel="icon" type="image/png" sizes="16x16" href="assets/favicons/favicon-16.png?v=20261011">
    <link rel="apple-touch-icon" sizes="180x180" href="assets/favicons/apple-touch-icon-180.png?v=20261011">
</head>
<body class="antialiased">

    <!-- Logo linking to Home -->
    <a href="index.php" class="absolute top-6 left-6 md:top-8 md:left-8 z-50 flex items-center gap-3 group">
        <div class="bg-gradient-to-br from-primary to-blue-600 text-white p-2.5 rounded-xl shadow-lg shadow-sky-500/30 group-hover:scale-105 transition-transform duration-300">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
        </div>
        <span class="text-2xl font-black text-dark font-title tracking-tight group-hover:text-primary transition-colors duration-300">
            HireMe<span class="text-primary">CV</span>
        </span>
    </a>

    <div class="min-h-screen flex flex-col md:flex-row relative pt-16 md:pt-0">
        
        <!-- Left Side: The "Why" & Philosophy -->
        <div class="w-full md:w-5/12 lg:w-1/2 p-8 md:p-16 lg:p-24 flex flex-col justify-center relative overflow-hidden">
            <!-- Decorative elements -->
            <div class="absolute top-10 left-10 w-32 h-32 bg-sky-400/30 rounded-full blur-3xl animate-pulse-slow"></div>
            <div class="absolute bottom-10 right-10 w-32 h-32 bg-indigo-400/20 rounded-full blur-3xl animate-pulse-slow"></div>
            
            <div class="relative z-10 space-y-8 animate-fade-in-up">
                <span class="inline-block bg-primary text-white text-xs font-black px-3 py-1.5 rounded-full uppercase tracking-widest font-title shadow-sm shadow-primary/20">
                    Notre Engagement
                </span>
                
                <h1 class="text-4xl md:text-5xl lg:text-6xl font-black text-dark tracking-tight font-title leading-tight">
                    Votre Réussite, <br>
                    <span class="text-transparent bg-clip-text bg-gradient-to-r from-primary to-indigo-600">
                        Notre Priorité.
                    </span>
                </h1>
                
                <p class="text-slate-600 text-base md:text-lg leading-relaxed font-medium">
                    Chez <strong class="text-dark">MonCV</strong>, notre mission ne s'arrête pas à la livraison. Si notre travail ne porte pas ses fruits, nous prenons nos responsabilités.
                </p>

                <div class="space-y-6 pt-6">
                    <div class="flex gap-4 items-start animate-fade-in-up delay-100">
                        <div class="w-12 h-12 rounded-full bg-sky-100 text-primary flex items-center justify-center shrink-0 border border-sky-200">
                            <span class="text-xl">🎯</span>
                        </div>
                        <div>
                            <h3 class="text-dark font-bold text-lg mb-1 font-title">Analyse Experte</h3>
                            <p class="text-slate-600 text-sm leading-relaxed font-medium">Si votre CV ne génère pas d'entretiens après 3 mois, nous l'analysons pour comprendre ce qui freine les recruteurs.</p>
                        </div>
                    </div>
                    
                    <div class="flex gap-4 items-start animate-fade-in-up delay-200">
                        <div class="w-12 h-12 rounded-full bg-amber-100 text-amber-600 flex items-center justify-center shrink-0 border border-amber-200">
                            <span class="text-xl">⚠️</span>
                        </div>
                        <div>
                            <h3 class="text-dark font-bold text-lg mb-1 font-title">Règle de Transparence</h3>
                            <p class="text-slate-600 text-sm leading-relaxed font-medium">Ce suivi optimise <strong class="text-amber-600">notre document initial</strong>. C'est un audit de structure, et non une mise à jour pour ajouter de nouveaux diplômes ou emplois.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Side: The Interactive Form -->
        <div class="w-full md:w-7/12 lg:w-1/2 p-4 sm:p-8 flex items-center justify-center relative">
            <div class="w-full max-w-xl glass-panel rounded-3xl p-6 sm:p-10 animate-fade-in-up delay-300">
                
                <!-- Step 1: Select Customer -->
                <div id="step-1" class="space-y-6 transition-all duration-500">
                    <div>
                        <div class="flex justify-between items-end mb-4">
                            <h2 class="text-2xl font-black text-dark font-title">Identifiez-vous</h2>
                            <span class="text-xs font-bold text-primary bg-sky-50 px-3 py-1 rounded-full border border-sky-100">
                                <?= $totalNames ?> éligibles
                            </span>
                        </div>
                        
                        <div class="relative group">
                            <select id="customerSelect" class="w-full px-5 py-4 rounded-xl outline-none transition font-medium appearance-none cursor-pointer hover:border-slate-300 focus:border-primary shadow-sm" onchange="handleCustomerSelect(this)">
                                <option value="" disabled selected>Recherchez votre nom dans la liste déroulante</option>
                                <?php foreach($verifiedOrders as $order): ?>
                                    <option value="<?= htmlspecialchars(json_encode($order)) ?>">
                                        <?= htmlspecialchars($order['name']) ?> 
                                        <?php if(!empty($order['email'])) echo " - " . htmlspecialchars($order['email']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-5 text-slate-400 group-hover:text-primary transition">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                            </div>
                        </div>
                    </div>
                    
                    <div class="text-center pt-4">
                        <button type="button" onclick="handleNotFound()" class="text-sm font-bold text-slate-400 hover:text-primary transition duration-300 underline underline-offset-4 decoration-slate-200 hover:decoration-primary">
                            Saisie manuelle si introuvable
                        </button>
                    </div>
                </div>

                <!-- Step 2: Form Details -->
                <div id="step-2" class="hidden space-y-8 transition-all duration-500 opacity-0 transform translate-y-4">
                    
                    <div id="customerInfoAlert" class="hidden bg-emerald-50 border border-emerald-100 p-4 rounded-2xl flex items-center gap-4 shadow-sm">
                        <div class="bg-emerald-100 p-2 rounded-full">
                            <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        </div>
                        <div>
                            <p class="font-bold text-sm text-emerald-800">Client identifié</p>
                            <p id="verifiedCustomerName" class="text-emerald-600 font-medium text-xs mt-0.5"></p>
                        </div>
                    </div>

                    <form id="redesignForm" onsubmit="submitForm(event)" class="space-y-6">
                        <input type="hidden" id="orderId" name="order_id" value="">
                        
                        <div id="manualFields" class="space-y-6 hidden bg-slate-50 p-6 rounded-2xl border border-slate-100">
                            <div>
                                <label class="block text-xs font-bold text-slate-500 mb-2 uppercase tracking-wider">Nom complet *</label>
                                <input type="text" id="customerName" name="customer_name" class="w-full px-4 py-3.5 rounded-xl outline-none transition text-sm">
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 mb-2 uppercase tracking-wider">Adresse Email</label>
                                    <input type="email" id="customerEmail" name="customer_email" class="w-full px-4 py-3.5 rounded-xl outline-none transition text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 mb-2 uppercase tracking-wider">Numéro WhatsApp</label>
                                    <input type="text" id="customerWhatsapp" name="customer_whatsapp" class="w-full px-4 py-3.5 rounded-xl outline-none transition text-sm">
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-500 mb-2 uppercase tracking-wider">Raison de l'Analyse *</label>
                            <div class="relative group">
                                <select id="reason" name="reason" required class="w-full px-4 py-4 rounded-xl outline-none transition text-sm appearance-none cursor-pointer hover:border-slate-300 focus:border-primary">
                                    <option value="" disabled selected>-- Pourquoi demandez-vous cette analyse ? --</option>
                                    <option value="no_success_3_months">Plus de 3 mois sans entretien d'embauche</option>
                                    <option value="recruiter_criticism">Un recruteur a critiqué la forme/structure du CV</option>
                                </select>
                                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4">
                                    <svg class="h-4 w-4 text-slate-400 group-hover:text-primary transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                </div>
                            </div>
                        </div>
                        
                        <div id="uploadSection" class="hidden space-y-5">
                            <div>
                                <label class="block text-xs font-bold text-slate-500 mb-2 uppercase tracking-wider">Preuve de paiement *</label>
                                <div class="upload-zone rounded-2xl p-6 text-center cursor-pointer relative group">
                                    <input type="file" id="paymentProof" name="payment_proof" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" onchange="updateFileName(this, 'proofName')">
                                    <svg class="mx-auto h-8 w-8 text-slate-300 group-hover:text-primary transition mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                                    <span id="proofName" class="text-sm font-bold text-slate-500 group-hover:text-primary transition block">Cliquez ou glissez votre reçu ici</span>
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-500 mb-2 uppercase tracking-wider">Le CV Réalisé par nos soins *</label>
                                <div class="upload-zone rounded-2xl p-6 text-center cursor-pointer relative group">
                                    <input type="file" id="oldCv" name="old_cv" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" onchange="updateFileName(this, 'cvName')">
                                    <svg class="mx-auto h-8 w-8 text-slate-300 group-hover:text-primary transition mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                    <span id="cvName" class="text-sm font-bold text-slate-500 group-hover:text-primary transition block">Document PDF ou Word</span>
                                </div>
                            </div>
                        </div>

                        <!-- Strict Rules Acknowledgement -->
                        <div class="bg-red-50 p-5 rounded-2xl border border-red-100">
                            <label class="custom-checkbox flex items-start gap-4 cursor-pointer group">
                                <input type="checkbox" required class="sr-only">
                                <div class="w-6 h-6 rounded bg-white border-2 border-slate-300 flex items-center justify-center transition-all shrink-0 mt-0.5 group-hover:border-primary">
                                    <svg class="w-3.5 h-3.5 text-white opacity-0 transform scale-50 transition-all" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path></svg>
                                </div>
                                <span class="text-sm text-slate-600 font-medium leading-relaxed">
                                    J'accepte qu'il s'agisse d'un audit de forme. <strong class="text-red-500 font-bold">Je certifie ne pas utiliser ce service pour ajouter de nouvelles expériences, compétences ou diplômes.</strong>
                                </span>
                            </label>
                        </div>

                        <div class="pt-6 border-t border-slate-100 flex flex-col-reverse sm:flex-row justify-between items-center gap-4">
                            <button type="button" onclick="resetForm()" class="text-sm font-bold text-slate-400 hover:text-slate-700 transition w-full sm:w-auto text-center">
                                Annuler
                            </button>
                            <button type="submit" id="submitBtn" class="w-full sm:w-auto bg-primary hover:bg-sky-600 text-white font-black py-4 px-8 rounded-xl shadow-lg shadow-sky-500/30 transition-all transform hover:-translate-y-1 text-sm tracking-wide">
                                Soumettre l'Analyse
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Step 3: Success -->
                <div id="step-3" class="hidden text-center space-y-6 transition-all duration-500 opacity-0 transform translate-y-4 py-10">
                    <div class="w-24 h-24 bg-emerald-100 text-emerald-500 rounded-full flex items-center justify-center mx-auto mb-6 shadow-xl shadow-emerald-500/20 animate-float border-[8px] border-emerald-50">
                        <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path></svg>
                    </div>
                    <h2 class="text-3xl font-black text-dark font-title tracking-tight">Dossier Transmis</h2>
                    <p class="text-base text-slate-500 font-medium max-w-sm mx-auto leading-relaxed">
                        Nos experts vont auditer votre document selon nos critères stricts. Nous vous contacterons par email ou WhatsApp.
                    </p>
                    <div class="pt-8">
                        <button type="button" onclick="location.reload()" class="bg-white border-2 border-slate-200 hover:border-slate-400 text-slate-700 font-bold py-3.5 px-8 rounded-xl transition text-sm">
                            Faire une autre demande
                        </button>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <script>
        let currentRequestId = null;

        function updateFileName(input, textId) {
            const span = document.getElementById(textId);
            if (input.files && input.files[0]) {
                span.textContent = input.files[0].name;
                span.classList.add('text-primary', 'font-bold');
                span.classList.remove('text-slate-500');
            } else {
                span.textContent = textId === 'cvName' ? 'Document PDF ou Word' : 'Cliquez ou glissez votre reçu ici';
                span.classList.remove('text-primary', 'font-bold');
                span.classList.add('text-slate-500');
            }
        }

        // Drag and drop visual feedback
        document.querySelectorAll('.upload-zone input[type="file"]').forEach(input => {
            input.addEventListener('dragover', (e) => input.parentElement.classList.add('dragover'));
            input.addEventListener('dragleave', (e) => input.parentElement.classList.remove('dragover'));
            input.addEventListener('drop', (e) => input.parentElement.classList.remove('dragover'));
        });
        
        function handleCustomerSelect(select) {
            if (!select.value) return;
            const customer = JSON.parse(select.value);
            selectCustomer(customer);
        }

        function showStep2() {
            document.getElementById('step-1').classList.add('hidden');
            const step2 = document.getElementById('step-2');
            step2.classList.remove('hidden');
            setTimeout(() => {
                step2.classList.remove('opacity-0', 'translate-y-4');
            }, 50);
        }

        async function createStartRecord(customerData) {
            const formData = new FormData();
            formData.append('action', 'start');
            formData.append('order_id', customerData.order_id || '');
            formData.append('customer_name', customerData.name || '');
            formData.append('customer_email', customerData.email || '');
            formData.append('customer_whatsapp', customerData.whatsapp || '');

            try {
                const response = await fetch('api/submit_redesign.php', { method: 'POST', body: formData });
                const result = await response.json();
                if (result.success) currentRequestId = result.request_id;
            } catch (error) {
                console.error("Tracking start failed", error);
            }
        }

        function selectCustomer(customer) {
            document.getElementById('orderId').value = customer.id;
            document.getElementById('customerName').value = customer.name;
            document.getElementById('customerEmail').value = customer.email;
            document.getElementById('customerWhatsapp').value = customer.whatsapp;
            
            document.getElementById('customerInfoAlert').classList.remove('hidden');
            document.getElementById('verifiedCustomerName').textContent = `${customer.name} (ID: ${customer.id})`;
            
            document.getElementById('manualFields').classList.add('hidden');
            document.getElementById('uploadSection').classList.add('hidden');
            
            document.getElementById('paymentProof').required = false;
            document.getElementById('oldCv').required = false;

            createStartRecord({ order_id: customer.id, name: customer.name, email: customer.email, whatsapp: customer.whatsapp });
            showStep2();
        }

        function handleNotFound() {
            document.getElementById('orderId').value = '';
            document.getElementById('customerInfoAlert').classList.add('hidden');
            
            document.getElementById('manualFields').classList.remove('hidden');
            document.getElementById('uploadSection').classList.remove('hidden');
            
            document.getElementById('customerName').required = true;
            document.getElementById('paymentProof').required = true;
            document.getElementById('oldCv').required = true;

            document.getElementById('customerName').addEventListener('blur', function() {
                if(this.value.trim().length > 2 && !currentRequestId) {
                    createStartRecord({
                        name: this.value.trim(),
                        email: document.getElementById('customerEmail').value.trim(),
                        whatsapp: document.getElementById('customerWhatsapp').value.trim()
                    });
                }
            });

            showStep2();
        }

        function resetForm() {
            document.getElementById('step-2').classList.add('opacity-0', 'translate-y-4');
            setTimeout(() => {
                document.getElementById('step-2').classList.add('hidden');
                document.getElementById('step-1').classList.remove('hidden');
                document.getElementById('customerSelect').value = '';
                currentRequestId = null;
            }, 300);
        }

        async function submitForm(e) {
            e.preventDefault();
            
            const btn = document.getElementById('submitBtn');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<div class="spinner mx-auto"></div>';
            btn.disabled = true;

            const form = document.getElementById('redesignForm');
            const formData = new FormData(form);
            formData.append('action', 'complete');
            
            if (!currentRequestId) {
                const startData = new FormData();
                startData.append('action', 'start');
                startData.append('customer_name', document.getElementById('customerName').value);
                startData.append('customer_email', document.getElementById('customerEmail').value);
                startData.append('customer_whatsapp', document.getElementById('customerWhatsapp').value);
                
                try {
                    const startRes = await fetch('api/submit_redesign.php', { method: 'POST', body: startData });
                    const startJson = await startRes.json();
                    if (startJson.success) currentRequestId = startJson.request_id;
                } catch(e) {}
            }

            if (!currentRequestId) {
                alert("Erreur de connexion. Veuillez réessayer.");
                btn.innerHTML = originalText;
                btn.disabled = false;
                return;
            }

            formData.append('request_id', currentRequestId);

            try {
                const response = await fetch('api/submit_redesign.php', { method: 'POST', body: formData });
                const result = await response.json();
                
                if (result.success) {
                    document.getElementById('step-2').classList.add('opacity-0', 'translate-y-4');
                    setTimeout(() => {
                        document.getElementById('step-2').classList.add('hidden');
                        const step3 = document.getElementById('step-3');
                        step3.classList.remove('hidden');
                        setTimeout(() => step3.classList.remove('opacity-0', 'translate-y-4'), 50);
                    }, 300);
                } else {
                    alert("Erreur: " + result.error);
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                }
            } catch (error) {
                alert("Une erreur de réseau s'est produite.");
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
        }
    </script>
</body>
</html>
