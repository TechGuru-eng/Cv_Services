class SpinWheel {
    constructor() {
        this.slices = [];
        this.ctx = null;
        this.canvas = null;
        this.currentRotation = 0;
        this.isSpinning = false;
        this.winner = null;
        this.popupData = null;

        this.browser = localStorage.getItem('hireme_wheel_browser');
        if (!this.browser) {
            const bytes = new Uint8Array(16);
            crypto.getRandomValues(bytes);
            this.browser = Array.from(bytes, n => n.toString(16).padStart(2, '0')).join('');
            localStorage.setItem('hireme_wheel_browser', this.browser);
        }
        this.ensureModalExists();
    }

    async init() {
        console.log("SpinWheel: Initializing...");
        // Prevent showing if already interacted or recently shown
        if (localStorage.getItem('hireme_spin_played') || sessionStorage.getItem('hireme_spin_session_dismissed')) {
            console.log("SpinWheel: Already played or dismissed.");
            return;
        }

        try {
            const response = await fetch('api/get_exit_popup.php?browser=' + encodeURIComponent(this.browser));
            const data = await response.json();

            if (data.success && data.data && data.data.popup_type === 'spin_wheel') {
                this.popupData = data.data;
                // Parse slices if string, or use default
                if (typeof this.popupData.slices === 'string') {
                    try {
                        this.slices = JSON.parse(this.popupData.slices);
                    } catch (e) {
                        this.slices = [];
                    }
                } else if (Array.isArray(this.popupData.slices)) {
                    this.slices = this.popupData.slices;
                } else {
                    this.slices = [];
                }

                if (this.slices.length !== 5 || this.slices.some(s => ![0,10,20,30,40].includes(Number(s.value)))) return;
                this.setupDOM();
                this.modal.querySelector('.choice-title').textContent = this.popupData.title;
                this.modal.querySelector('.choice-text').textContent = this.popupData.message + ' Tentez de gagner 10%, 20%, 30% ou 40% de réduction sur les packs Étudiant, Pro et Senior.';
                this.modal.querySelector('.result-view .btn-play').textContent = this.popupData.cta_text;
                this.modal.querySelector('.choice-text').textContent += ' Une seule participation par navigateur et adresse IP. Vous pouvez aussi ne rien gagner.';
                this.modal.setAttribute('role', 'dialog');
                this.modal.setAttribute('aria-modal', 'true');
                this.drawWheel();
                this.bindEvents(); // Bind exit intent here if not global
            }
        } catch (e) {
            console.error("SpinWheel init error:", e);
        }
    }

    ensureModalExists() {
        if (!document.getElementById('spinWheelModal')) {
            const modalHTML = `
            <div id="spinWheelModal">
                <div class="spin-modal-content">
                    <!-- Step 1: Choice -->
                    <div class="choice-view" id="choiceView">
                        <div class="choice-icon">🎰</div>
                        <h2 class="choice-title">Tentez votre chance !</h2>
                        <p class="choice-text">Tentez de gagner une réduction sur votre CV.</p>
                        <div class="choice-buttons">
                            <button id="btnSpinAccept" class="btn-play">OUI, JE VEUX JOUER</button>
                            <button id="btnSpinDecline" class="btn-decline">Non merci, je paie plein tarif</button>
                        </div>
                    </div>

                    <!-- Step 2: Wheel -->
                    <div class="wheel-view" id="wheelView">
                         <div style="font-size: 24px; font-weight: bold; color: white; margin-bottom: 20px;">Bonne Chance ! 🍀</div>
                         <div class="wheel-container">
                            <canvas id="wheelCanvas" width="350" height="350"></canvas>
                            <div class="wheel-marker"></div>
                            <button type="button" class="spin-btn-center" id="spinTrigger">TOURNER</button>
                        </div>
                    </div>

                    <!-- Step 3: Result -->
                    <div class="result-view" id="resultView">
                        <div class="result-title">FÉLICITATIONS !</div>
                        <p>Vous avez gagné :</p>
                        <div class="result-value" id="resultValue"></div>
                        <p>Utilisez ce code maintenant :</p>
                        <div class="coupon-box" id="couponCode"></div>
                         <p style="margin-bottom: 20px; font-size: 14px;"><span id="spinTimer"></span></p>
                        <a href="#pricing" class="btn-play" onclick="document.getElementById('spinWheelModal').classList.remove('active');">PROFITER DE L'OFFRE</a>
                    </div>
                </div>
            </div>`;
            document.body.insertAdjacentHTML('beforeend', modalHTML);
        }
    }

    setupDOM() {
        this.canvas = document.getElementById('wheelCanvas');
        this.ctx = this.canvas.getContext('2d');
        this.modal = document.getElementById('spinWheelModal');
        this.choiceView = document.getElementById('choiceView');
        this.wheelView = document.getElementById('wheelView');
        this.resultView = document.getElementById('resultView');
    }

    drawWheel() {
        const numSlices = this.slices.length;
        const arc = Math.PI * 2 / numSlices;
        const cx = this.canvas.width / 2;
        const cy = this.canvas.height / 2;
        const radius = this.canvas.width / 2;

        this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);

        for (let i = 0; i < numSlices; i++) {
            const angle = i * arc;

            // Slice
            this.ctx.beginPath();
            this.ctx.fillStyle = this.slices[i].color; // Red and Gold/Yellow
            this.ctx.moveTo(cx, cy);
            this.ctx.arc(cx, cy, radius, angle, angle + arc);
            this.ctx.lineTo(cx, cy);
            this.ctx.fill();

            // Text
            this.ctx.save();
            this.ctx.translate(cx, cy);
            this.ctx.rotate(angle + arc / 2);
            this.ctx.textAlign = "right";
            this.ctx.fillStyle = "#fff";
            this.ctx.font = "bold 16px Arial";
            this.ctx.fillText(this.slices[i].text, radius - 20, 5);
            this.ctx.restore();
        }
    }

    show() {
        if (!localStorage.getItem('hireme_spin_played') && !sessionStorage.getItem('hireme_spin_session_dismissed') && !sessionStorage.getItem('hireme_order_placed')) {
            this.modal.classList.add('active');
        }
    }

    bindEvents() {
        // Choice Events
        document.getElementById('btnSpinAccept').onclick = () => {
            this.choiceView.style.display = 'none';
            this.wheelView.style.display = 'flex';
            this.drawWheel(); // Redraw to be sure
        };

        document.getElementById('btnSpinDecline').onclick = () => {
            this.modal.classList.remove('active');
            sessionStorage.setItem('hireme_spin_session_dismissed', 'true');
        };

        // Spin Logic
        document.getElementById('spinTrigger').onclick = () => {
            if (this.isSpinning) return;
            this.spin();
        };

        const claim = this.resultView.querySelector('.btn-play');
        claim.href = '#order';
        claim.removeAttribute('onclick');
        claim.onclick = async (event) => {
            event.preventDefault();
            const response = await fetch('api/check_promo.php?code=' + encodeURIComponent(this.winner.code));
            const reward = await response.json();
            if (!reward.valid) {
                document.getElementById('couponCode').textContent = reward.message || 'Code indisponible';
                return;
            }
            const input = document.getElementById('promoInput');
            if (input) input.value = reward.code;
            if (typeof applyDiscount === 'function') applyDiscount(reward, true);
            this.modal.classList.remove('active');
            document.getElementById('order')?.scrollIntoView({behavior: 'smooth'});
        };
        this.modal.addEventListener('click', e => {
            if (e.target === this.modal && !this.isSpinning) {
                this.modal.classList.remove('active');
                sessionStorage.setItem('hireme_spin_session_dismissed', 'true');
            }
        });
        document.addEventListener('keydown', e => {
            if (e.key === 'Escape' && !this.isSpinning) {
                this.modal.classList.remove('active');
                sessionStorage.setItem('hireme_spin_session_dismissed', 'true');
            }
        });
        const eligibleAfter = Date.now() + 5000;
        // Mobile exit proxy: rapid upward scrolling after engagement.
        let lastY = window.scrollY, lastTime = Date.now();
        window.addEventListener('scroll', () => {
            const now = Date.now(), y = window.scrollY;
            if (window.matchMedia('(pointer: coarse)').matches && now >= eligibleAfter && y > 100 && (lastY - y) / Math.max(1, now - lastTime) > 2) this.show();
            lastY = y; lastTime = now;
        }, {passive: true});
        // Global Exit Intent Trigger (Desktop)
        let exitIntentTriggered = false;
        document.addEventListener('mouseleave', (e) => {
            console.log("SpinWheel: Mouse leave detected. Y:", e.clientY);
            // Strict check: User must be exiting from the TOP of the viewport
            if (e.clientY <= 0 && Date.now() >= eligibleAfter && !exitIntentTriggered) {
                console.log("SpinWheel: Triggering Popup!");
                exitIntentTriggered = true;
                this.show();
            }
        });

    }

    async spin() {
        if (this.isSpinning || localStorage.getItem('hireme_spin_played')) return;
        this.isSpinning = true;
        document.getElementById('spinTrigger').disabled = true;

        // The server selects and records the one permitted result.
        let reward;
        try {
            const response = await fetch('api/spin_exit_wheel.php', {
                method: 'POST', headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({browser: this.browser, token: this.popupData.token})
            });
            const result = await response.json();
            if (!result.success) {
                this.isSpinning = false;
                if (result.played) localStorage.setItem('hireme_spin_played', 'true');
                this.wheelView.style.display = 'none';
                this.resultView.style.display = 'block';
                this.resultView.querySelector('.result-title').textContent = 'PARTICIPATION';
                document.getElementById('resultValue').textContent = result.message;
                document.getElementById('couponCode').style.display = 'none';
                document.getElementById('spinTimer').parentElement.style.display = 'none';
                this.resultView.querySelector('.btn-play').style.display = 'none';
                return;
            }
            reward = result.reward;
            localStorage.setItem('hireme_spin_played', 'true');
        } catch (error) {
            this.isSpinning = false;
            this.wheelView.querySelector('div').textContent = 'Connexion interrompue. Rechargez la page.';
            document.getElementById('spinTrigger').onclick = null;
            return;
        }
        const winnerIndex = this.slices.findIndex(s => Number(s.value) === Number(reward.value));

        // Calculate rotation
        // We need to rotate so the winner slice aligns with the marker (top, -90deg or 270deg)
        // Canvas 0 is 3 o'clock aka 0deg. Marker is at 12 o'clock (-90deg).

        const numSlices = this.slices.length;
        const sliceAngle = 360 / numSlices;

        // The angle where the winner starts
        const winnerStartAngle = (winnerIndex * sliceAngle);
        // Center of the winner slice
        const winnerCenterAngle = winnerStartAngle + (sliceAngle / 2);

        // To bring winnerCenterAngle to -90 (Top), we need to rotate:
        // Current 0 is at 3 o'clock. 
        // We want winnerCenter to be at -90.
        // Rotation = -90 - winnerCenterAngle.
        // Add extra spins (360 * 5)

        const extraSpins = 360 * 8; // 8 full spins
        // Adjust for canvas coordinate system where 0 is right.
        // We need to rotate the canvas COUNTER CLOCKWISE to bring the target to the top?
        // Actually CSS rotate is CLOCKWISE.
        // If target is at 45deg (example), we need to rotate -135deg (counter-clockwise) to bring it to -90.
        // Or +225deg.

        // Let's use a simpler logic:
        // Randomize landing within the slice for realism? No, center is fine.

        const stopAngle = 270 - winnerCenterAngle; // 270 is same as -90.
        const totalRotation = extraSpins + stopAngle;

        const canvas = document.getElementById('wheelCanvas');
        canvas.style.transition = 'transform 5s cubic-bezier(0.15, 0, 0.15, 1)'; // Ease-out-quart ish
        canvas.style.transform = `rotate(${totalRotation}deg)`;

        // Wait for animation
        setTimeout(() => {
            this.showResult(reward);
        }, 5000);
    }

    showResult(winnerSlice) {
        this.isSpinning = false;
        this.winner = winnerSlice;
        localStorage.setItem('hireme_spin_played', 'true');

        this.wheelView.style.display = 'none';
        this.resultView.style.display = 'block';

        // Confetti
        if (Number(winnerSlice.value) > 0) this.fireConfetti();
        else {
            this.resultView.querySelector('.result-title').textContent = 'MERCI D’AVOIR JOUÉ';
            this.resultView.querySelector('p').textContent = 'Pas de réduction cette fois-ci.';
            document.getElementById('couponCode').style.display = 'none';
            this.resultView.querySelectorAll('p')[1].style.display = 'none';
            const action = this.resultView.querySelector('.btn-play');
            action.textContent = 'DÉCOUVRIR LES PACKS';
            action.onclick = () => this.modal.classList.remove('active');
        }

        // Update Text
        document.getElementById('resultValue').textContent = winnerSlice.text;
        document.getElementById('couponCode').textContent = winnerSlice.code;

        // Coupon validity is enforced by the promo system.
        document.getElementById('spinTimer').parentElement.textContent = Number(winnerSlice.value) > 0 ? 'Validité et disponibilité selon les conditions du code promo.' : 'Une seule participation. Aucun nouvel essai.';
    }

    fireConfetti() {
        // Simple manual confetti or use library if available. 
        // Since no library, I'll cheat with a simple "Yay" animation on the modal or just the color flash.
        // Or minimal DOM confetti.
        const colors = ['#ef4444', '#3b82f6', '#22c55e', '#fbbf24'];

        for (let i = 0; i < 50; i++) {
            const conf = document.createElement('div');
            conf.style.position = 'fixed';
            conf.style.left = Math.random() * 100 + 'vw';
            conf.style.top = '-10px';
            conf.style.width = '10px';
            conf.style.height = '10px';
            conf.style.backgroundColor = colors[Math.floor(Math.random() * colors.length)];
            conf.style.zIndex = 10001;
            conf.style.animation = `fall ${Math.random() * 3 + 2}s linear forwards`;
            document.body.appendChild(conf);

            // Remove after animation
            setTimeout(() => conf.remove(), 5000);
        }

        // Add styles dynamically for falling
        const style = document.createElement('style');
        style.innerHTML = `
            @keyframes fall {
                to { transform: translateY(100vh) rotate(720deg); }
            }
        `;
        document.head.appendChild(style);
    }
}

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    const wheel = new SpinWheel();
    wheel.init();
});
