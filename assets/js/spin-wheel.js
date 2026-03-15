class SpinWheel {
    constructor() {
        this.slices = [];
        this.ctx = null;
        this.canvas = null;
        this.currentRotation = 0;
        this.isSpinning = false;
        this.winner = null;
        this.popupData = null;

        this.ensureModalExists();
    }

    async init() {
        console.log("SpinWheel: Initializing...");
        // Prevent showing if already interacted or recently shown
        if (localStorage.getItem('hireme_spin_played') || sessionStorage.getItem('hireme_spin_session_dismissed')) {
            console.log("SpinWheel: Blocked by storage (played or dismissed). IGNORING for debug.");
            // return; // Commented out for testing purposes if needed
        }

        try {
            const response = await fetch('api/get_exit_popup.php');
            const data = await response.json();

            if (data.success && data.data && data.data.popup_type === 'spin_wheel') {
                this.popupData = data.data;
                // Parse slices if string, or use default
                if (typeof this.popupData.slices === 'string') {
                    try {
                        this.slices = JSON.parse(this.popupData.slices);
                    } catch (e) {
                        this.slices = this.getDefaultSlices();
                    }
                } else if (Array.isArray(this.popupData.slices)) {
                    this.slices = this.popupData.slices;
                } else {
                    this.slices = this.getDefaultSlices();
                }

                this.setupDOM();
                this.drawWheel();
                this.bindEvents(); // Bind exit intent here if not global
            }
        } catch (e) {
            console.error("SpinWheel init error:", e);
        }
    }

    getDefaultSlices() {
        return [
            { text: '10% OFF', color: '#fbbf24', value: '10' },
            { text: '50% OFF', color: '#ef4444', value: '50' }, // Targeted winner usually
            { text: '20% OFF', color: '#fbbf24', value: '20' },
            { text: '30% OFF', color: '#ef4444', value: '30' },
            { text: '5% OFF', color: '#fbbf24', value: '5' },
            { text: '40% OFF', color: '#ef4444', value: '40' }
        ];
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
                        <p class="choice-text">Ne partez pas sans rien. Tournez la roue pour gagner jusqu'à <strong>-60%</strong> sur votre CV !</p>
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
                            <div class="spin-btn-center" id="spinTrigger">SPIN</div>
                        </div>
                    </div>

                    <!-- Step 3: Result -->
                    <div class="result-view" id="resultView">
                        <div class="result-title">FÉLICITATIONS !</div>
                        <p>Vous avez gagné :</p>
                        <div class="result-value" id="resultValue">50% OFF</div>
                        <p>Utilisez ce code maintenant :</p>
                        <div class="coupon-box" id="couponCode">HIREME50</div>
                         <p style="margin-bottom: 20px; font-size: 14px;">Expire dans <span id="spinTimer" style="color:red; font-weight:bold">15:00</span></p>
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
            this.ctx.fillStyle = i % 2 === 0 ? '#ef4444' : '#fbbf24'; // Red and Gold/Yellow
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
        if (!localStorage.getItem('hireme_spin_played')) {
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

        // Global Exit Intent Trigger (Desktop)
        let exitIntentTriggered = false;
        document.addEventListener('mouseleave', (e) => {
            console.log("SpinWheel: Mouse leave detected. Y:", e.clientY);
            // Strict check: User must be exiting from the TOP of the viewport
            if (e.clientY < 50 && !exitIntentTriggered) { // Increased to 50 for easier testing
                console.log("SpinWheel: Triggering Popup!");
                exitIntentTriggered = true;
                this.show();
            }
        });

        // Mobile Trigger (Idle or Scroll Up fast - Simplified to just timer or scroll for now, keeping simple mouseleave as requested)
    }

    spin() {
        this.isSpinning = true;

        // Determine winner
        // Logic: Check DB discount_percent. Find the slice that matches.
        // If not found, default to 50% or random.
        const targetPercent = this.popupData.discount_percent || 50;
        let winnerIndex = this.slices.findIndex(s => s.value == targetPercent);

        if (winnerIndex === -1) {
            winnerIndex = Math.floor(Math.random() * this.slices.length); // Fallback
        }

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
            this.showResult(this.slices[winnerIndex]);
        }, 5000);
    }

    showResult(winnerSlice) {
        this.isSpinning = false;
        localStorage.setItem('hireme_spin_played', 'true');

        this.wheelView.style.display = 'none';
        this.resultView.style.display = 'block';

        // Confetti
        this.fireConfetti();

        // Update Text
        document.getElementById('resultValue').textContent = winnerSlice.text;
        document.getElementById('couponCode').textContent = this.popupData.discount_code || 'HIREME50';

        // Start countdown
        this.startTimer(15 * 60, document.getElementById('spinTimer'));
    }

    startTimer(duration, display) {
        let timer = duration, minutes, seconds;
        setInterval(function () {
            minutes = parseInt(timer / 60, 10);
            seconds = parseInt(timer % 60, 10);

            minutes = minutes < 10 ? "0" + minutes : minutes;
            seconds = seconds < 10 ? "0" + seconds : seconds;

            display.textContent = minutes + ":" + seconds;

            if (--timer < 0) {
                timer = duration;
            }
        }, 1000);
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
