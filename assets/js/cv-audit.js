/**
 * CV Audit Modal Logic
 */

const cvAudit = {
    modal: null,
    form: null,
    fileInput: null,
    jobInput: null,
    resultArea: null,
    step1: null,
    step2: null,
    step3: null,
    step4: null,

    init() {
        this.injectStyles();
        this.injectModal();
        this.bindEvents();
    },

    injectStyles() {
        const style = document.createElement('style');
        style.textContent = `
            .audit-modal-overlay {
                position: fixed; top: 0; left: 0; right: 0; bottom: 0;
                background: rgba(17, 24, 39, 0.82); backdrop-filter: blur(8px);
                z-index: 1000; display: none; align-items: center; justify-content: center;
                padding: 1rem;
            }
            .audit-modal-content {
                background: white; width: 100%; max-width: 520px;
                max-height: 92vh; overflow-y: auto;
                border-radius: 2.5rem; position: relative;
                box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
                animation: modalIn 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
                scrollbar-width: thin;
                scrollbar-color: #e5e7eb transparent;
            }
            .audit-modal-content::-webkit-scrollbar { width: 6px; }
            .audit-modal-content::-webkit-scrollbar-thumb { background-color: #e5e7eb; border-radius: 10px; }
            
            @keyframes modalIn {
                from { opacity: 0; transform: translateY(40px) scale(0.9); }
                to { opacity: 1; transform: translateY(0) scale(1); }
            }
            .audit-close {
                position: sticky; top: 1.5rem; float: right; margin-right: 1.5rem;
                width: 2.5rem; height: 2.5rem; border-radius: 50%;
                display: flex; align-items: center; justify-content: center;
                background: white; cursor: pointer; border: 1px solid #f3f4f6; z-index: 50;
                box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
                color: #9ca3af; font-size: 1.25rem;
            }
            .audit-step { display: none; padding: 2.5rem; clear: both; }
            .audit-step.active { display: block; }
            
            .verdict-badge {
                display: inline-block; padding: 0.25rem 1rem; border-radius: 99px;
                font-size: 0.75rem; font-weight: 800; text-transform: uppercase;
                margin-bottom: 1rem;
            }
            
            .finding-item {
                display: flex; gap: 0.75rem; font-size: 0.875rem; margin-bottom: 0.75rem;
                padding: 1rem; border-radius: 1rem; border: 1px solid #f3f4f6;
                transition: transform 0.2s ease;
            }
            .finding-item:hover { transform: translateX(4px); }
            .finding-item.warning { border-color: #fef3c7; background: #fffbeb; }
            .finding-item.success { border-color: #dcfce7; background: #f0fdf4; }
            .finding-item.info { border-color: #e0f2fe; background: #f0f9ff; }
            
            .pro-tip {
                background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
                border: 1px solid #e2e8f0; padding: 1.25rem; border-radius: 1.25rem;
                margin-top: 1.5rem; position: relative;
            }
            .pro-tip::before {
                content: '💡 PRO TIP'; position: absolute; top: -10px; left: 1.25rem;
                background: #0ea5e9; color: white; padding: 2px 10px; border-radius: 4px;
                font-size: 10px; font-weight: 900;
            }
        `;
        document.head.appendChild(style);
    },

    injectModal() {
        const modalHtml = `
            <div class="audit-modal-overlay" id="auditModal">
                <div class="audit-modal-content">
                    <button class="audit-close" onclick="cvAudit.close()">×</button>
                    
                    <!-- Step 1: Upload -->
                    <div class="audit-step active" id="auditStep1">
                        <div class="text-center mb-6">
                            <span class="text-4xl">🔍</span>
                            <h2 class="text-2xl font-black text-gray-900 mt-4">Scannez votre CV</h2>
                            <p class="text-gray-500 mt-2">Découvrez votre score de recrutement en 10 secondes.</p>
                        </div>
                        
                        <div class="space-y-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-400 uppercase tracking-widest mb-2">Quel job visez-vous ?</label>
                                <input type="text" id="auditJobTitle" placeholder="Ex: Chef de Projet, Assistant RH..." 
                                    class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-primary outline-none">
                            </div>
                            
                            <div class="border-2 border-dashed border-gray-200 rounded-2xl p-8 text-center cursor-pointer hover:border-primary transition-colors bg-gray-50"
                                onclick="document.getElementById('auditFileInput').click()">
                                <input type="file" id="auditFileInput" class="hidden" accept=".pdf,.png,.jpg,.jpeg">
                                <span class="text-3xl">📄</span>
                                <p class="text-sm font-bold text-gray-700 mt-3" id="auditFileName">Glissez votre CV ou cliquez ici</p>
                                <p class="text-xs text-gray-400 mt-1">PDF, PNG (Max 5MB)</p>
                            </div>
                            
                            <button onclick="cvAudit.startAnalysis()" id="auditStartBtn"
                                class="w-full bg-primary text-white font-black py-4 rounded-xl shadow-lg shadow-primary/20 hover:bg-neutral transition-all disabled:opacity-50">
                                Analyser mon CV gratuitement
                            </button>
                        </div>
                    </div>

                    <!-- Step 2: Analyzing -->
                    <div class="audit-step" id="auditStep2">
                        <div class="text-center">
                            <div class="inline-block relative">
                                <div class="w-20 h-20 border-4 border-gray-100 border-t-primary rounded-full animate-spin"></div>
                                <span class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 text-2xl">🤖</span>
                            </div>
                            <h2 class="text-2xl font-bold text-gray-900 mt-8" id="analysisStatus">Analyse en cours...</h2>
                            <p class="text-gray-500 mt-2">Comparaison avec les critères ATS de votre domaine.</p>
                            
                            <div class="mt-8 space-y-4 text-left max-w-xs mx-auto">
                                <div class="flex items-center gap-3 text-sm text-gray-400" id="step_v1">
                                    <span class="w-5 h-5 flex items-center justify-center rounded-full bg-gray-100 text-[10px]">1</span>
                                    <span>Extraction du texte...</span>
                                </div>
                                <div class="flex items-center gap-3 text-sm text-gray-400" id="step_v2">
                                    <span class="w-5 h-5 flex items-center justify-center rounded-full bg-gray-100 text-[10px]">2</span>
                                    <span>Recherche de mots-clés...</span>
                                </div>
                                <div class="flex items-center gap-3 text-sm text-gray-400" id="step_v3">
                                    <span class="w-5 h-5 flex items-center justify-center rounded-full bg-gray-100 text-[10px]">3</span>
                                    <span>Calcul de la lisibilité...</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Step 3: Results -->
                    <div class="audit-step" id="auditStep3">
                        <div class="text-center mb-6">
                            <div id="resultVerdictBadge" class="verdict-badge">BILAN</div>
                            <div class="relative w-32 h-32 mx-auto mb-4">
                                <svg class="w-full h-full" viewBox="0 0 36 36">
                                    <path class="text-gray-100" stroke-width="3" stroke="currentColor" fill="none"
                                        d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                                    <path id="auditScorePath" class="text-primary transition-all duration-1000" stroke-width="3" 
                                        stroke-dasharray="0, 100" stroke-linecap="round" stroke="currentColor" fill="none"
                                        d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                                </svg>
                                <div class="absolute inset-0 flex items-center justify-center">
                                    <span class="text-3xl font-black text-gray-900" id="resultScoreText">0%</span>
                                </div>
                            </div>
                            <h2 class="text-2xl font-black text-gray-900" id="resultTitle">Analyse Terminée</h2>
                            <p class="text-gray-500 text-sm" id="resultSub">Voici les points critiques de votre candidature.</p>
                        </div>

                        <div id="resultBreakdown" class="grid grid-cols-2 gap-4 mb-6"></div>
                        <div id="resultFindings" class="mb-6 max-h-48 overflow-y-auto pr-2"></div>
                        <div id="resultProTip" class="pro-tip mb-8 text-xs text-gray-600 leading-relaxed italic"></div>

                        <!-- CTA Section -->
                        <div class="bg-gray-900 rounded-2xl p-6 text-white text-center relative overflow-hidden group">
                            <div class="relative z-10">
                                <h3 class="text-lg font-bold mb-2">🚀 Obtenir votre Rapport d'Audit Complet ?</h3>
                                <p class="text-xs text-gray-400 mb-6">Recevez par email votre analyse détaillée par secteur et un plan d'action immédiat pour booster vos entretiens.</p>
                                
                                <div id="auditLeadForm">
                                    <div class="flex flex-col sm:flex-row gap-3 mb-3">
                                        <input type="email" id="auditEmailInput" placeholder="Votre email pour le rapport" 
                                            class="w-full sm:flex-1 px-4 py-3 rounded-xl bg-white/10 border border-white/20 text-white text-sm outline-none focus:border-primary">
                                        <button onclick="cvAudit.saveLead()" id="saveLeadBtn"
                                            class="w-full sm:w-auto bg-primary text-white px-6 py-3 rounded-xl text-sm font-bold hover:bg-white hover:text-primary transition-all shadow-lg shadow-black/20">Recevoir & Booster</button>
                                    </div>
                                    <p class="text-[10px] text-gray-500 italic">Envoi immédiat du rapport détaillé + Coupon -15%</p>
                                </div>

                                <div id="auditSuccessCTA" class="hidden">
                                    <a href="#pricing" onclick="cvAudit.close()" 
                                        class="block w-full bg-primary text-white font-bold py-3 rounded-xl hover:scale-105 transition-transform">
                                        Voir les Tarifs (Spécial -60%)
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Step 4: Final Success -->
                    <div class="audit-step" id="auditStep4">
                        <div class="text-center py-8">
                            <span class="text-6xl">🎉</span>
                            <h2 class="text-2xl font-black text-gray-900 mt-6">C'est envoyé !</h2>
                            <p class="text-gray-500 mt-2">Consultez votre boîte mail. Votre coupon de -15% est inclus.</p>
                            <button onclick="cvAudit.close()" class="mt-8 bg-neutral text-white px-8 py-3 rounded-xl font-bold">Fermer</button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        document.body.insertAdjacentHTML('beforeend', modalHtml);

        this.modal = document.getElementById('auditModal');
        this.fileInput = document.getElementById('auditFileInput');
        this.jobInput = document.getElementById('auditJobTitle');
        this.step1 = document.getElementById('auditStep1');
        this.step2 = document.getElementById('auditStep2');
        this.step3 = document.getElementById('auditStep3');
        this.step4 = document.getElementById('auditStep4');
    },

    bindEvents() {
        this.fileInput.addEventListener('change', (e) => {
            const fileName = e.target.files[0]?.name || "Fichier sélectionné";
            document.getElementById('auditFileName').textContent = fileName;
        });

        document.querySelectorAll('.cv-audit-trigger').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                this.open();
            });
        });
    },

    open() {
        this.modal.style.display = 'flex';
        this.showStep(1);
    },

    close() {
        this.modal.style.display = 'none';
    },

    showStep(n) {
        [this.step1, this.step2, this.step3, this.step4].forEach((s, idx) => {
            if (s) s.classList.toggle('active', (idx + 1) === n);
        });
    },

    async startAnalysis() {
        const file = this.fileInput.files[0];
        const job = this.jobInput.value;

        if (!file || !job) {
            alert("Veuillez entrer un métier et sélectionner votre CV.");
            return;
        }

        this.showStep(2);

        setTimeout(() => { document.getElementById('step_v1').style.color = '#0ea5e9'; }, 800);
        setTimeout(() => { document.getElementById('step_v2').style.color = '#0ea5e9'; }, 1800);
        setTimeout(() => { document.getElementById('step_v3').style.color = '#0ea5e9'; }, 2800);

        const formData = new FormData();
        formData.append('cv_file', file);
        formData.append('job_title', job);

        try {
            const response = await fetch('api/analyze_cv.php', {
                method: 'POST',
                body: formData
            });
            const data = await response.json();

            if (data.success) {
                setTimeout(() => { this.showResults(data); }, 3500);
            } else {
                alert(data.message);
                this.showStep(1);
            }
        } catch (error) {
            console.error(error);
            this.showStep(1);
        }
    },

    showResults(data) {
        this.showStep(3);

        const path = document.getElementById('auditScorePath');
        const scoreText = document.getElementById('resultScoreText');
        const verdictBadge = document.getElementById('resultVerdictBadge');
        const proTip = document.getElementById('resultProTip');

        let color = '#ef4444';
        let title = "Analyse Critique";
        let badgeClass = "bg-red-100 text-red-600";
        let tipText = "Votre CV présente des lacunes majeures qui bloquent votre recrutement. Une refonte structurelle est urgente.";

        if (data.score >= 40) {
            color = '#f97316';
            title = "Potentiel à Développer";
            badgeClass = "bg-orange-100 text-orange-600";
            tipText = "La base est là, mais le manque de chiffres clés et de précision métier réduit vos chances de 50%.";
        }
        if (data.score >= 70) {
            color = '#eab308';
            title = "Profil Compétitif";
            badgeClass = "bg-yellow-100 text-yellow-600";
            tipText = "Bon CV ! Pour passer au niveau 'Expert', optimisez vos verbes d'action et votre structure ATS.";
        }
        if (data.score >= 85) {
            color = '#22c55e';
            title = "Excellent Profil";
            badgeClass = "bg-green-100 text-green-600";
            tipText = "CV de très haute qualité. Assurez-vous que vos réseaux sociaux pro sont aussi impeccables que ce document.";
        }

        verdictBadge.textContent = title.toUpperCase();
        verdictBadge.className = `verdict-badge ${badgeClass}`;
        proTip.textContent = tipText;

        path.style.stroke = color;
        scoreText.textContent = data.score + '%';
        scoreText.style.color = color;

        setTimeout(() => {
            path.style.strokeDasharray = `${data.score}, 100`;
        }, 100);

        document.getElementById('resultTitle').textContent = "Bilan Professionnel";
        document.getElementById('resultSub').textContent = `Secteur détecté : ${data.sector || 'Général'}`;

        const breakdown = document.getElementById('resultBreakdown');
        breakdown.innerHTML = '';

        const labels = {
            'job_match': 'Précision Métier',
            'ats_friendly': 'Compatibilité ATS',
            'density': 'Structure & Design',
            'contacts': 'Impact Réseaux'
        };

        Object.entries(labels).forEach(([key, label]) => {
            const val = data.breakdown[key] || 0;
            const bColor = val < 50 ? 'text-red-500' : (val < 75 ? 'text-orange-500' : 'text-green-500');
            breakdown.innerHTML += `
                <div class="bg-gray-50 p-4 rounded-2xl border border-gray-100 transition-all hover:bg-white hover:shadow-md">
                    <div class="text-[9px] text-gray-400 uppercase font-black mb-1 tracking-wider">${label}</div>
                    <div class="text-xl font-black ${bColor}">${val}%</div>
                </div>
            `;
        });

        const findingsDiv = document.getElementById('resultFindings');
        findingsDiv.innerHTML = '';
        data.findings.forEach(f => {
            const icon = f.type === 'warning' ? '⚠️' : (f.type === 'success' ? '✅' : 'ℹ️');
            findingsDiv.innerHTML += `
                <div class="finding-item ${f.type}">
                    <span class="mt-0.5">${icon}</span>
                    <span>${f.text}</span>
                </div>
            `;
        });
    },

    async saveLead() {
        const email = document.getElementById('auditEmailInput').value;
        const btn = document.getElementById('saveLeadBtn');

        if (!email || !email.includes('@')) {
            alert("Email valide requis pour recevoir le rapport.");
            return;
        }

        btn.disabled = true;
        btn.innerHTML = `<svg class="animate-spin h-4 w-4 text-white mx-auto" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>`;

        const formData = new FormData();
        formData.append('email', email);

        try {
            const response = await fetch('api/save_audit_lead.php', {
                method: 'POST',
                body: formData
            });
            const data = await response.json();
            if (data.success) {
                document.getElementById('auditLeadForm').classList.add('hidden');
                document.getElementById('auditSuccessCTA').classList.remove('hidden');
                if (typeof trackEvent === 'function') trackEvent('audit_lead_captured');
            } else {
                alert(data.message);
                btn.disabled = false;
                btn.textContent = "Recevoir & Booster";
            }
        } catch (error) {
            console.error(error);
            btn.disabled = false;
            btn.textContent = "Recevoir & Booster";
        }
    }
};

document.addEventListener('DOMContentLoaded', () => cvAudit.init());
