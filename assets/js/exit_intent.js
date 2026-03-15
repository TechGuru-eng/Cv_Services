// assets/js/exit_intent.js

(function() {
    let popupShown = false;
    const COOKIE_NAME = 'hireme_exit_popup_shown_v1';
    const TEST_MODE = false; // Set to true to force show everywhere (for dev)

    // Check if recently shown
    if (!TEST_MODE && localStorage.getItem(COOKIE_NAME)) {
        console.log('Exit popup already showed recently.');
        return;
    }

    async function fetchPopupData() {
        try {
            const response = await fetch('api/get_active_popup.php');
            const data = await response.json();
            if (data.success && data.popup) {
                createPopup(data.popup);
            }
        } catch (e) {
            console.error('Failed to fetch exit popup:', e);
        }
    }

    function createPopup(data) {
        // Create Overlay
        const overlay = document.createElement('div');
        overlay.id = 'exit-popup-overlay';
        overlay.style.cssText = `
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.6); z-index: 9999; display: none;
            justify-content: center; align-items: center; opacity: 0; transition: opacity 0.3s ease;
            backdrop-filter: blur(4px);
        `;

        // Create Modal
        const modal = document.createElement('div');
        modal.className = 'exit-popup-modal';
        modal.style.cssText = `
            background: white; padding: 2rem; border-radius: 1rem; width: 90%; max-width: 500px;
            text-align: center; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            transform: scale(0.9); transition: transform 0.3s ease; position: relative;
        `;

        // Close Button
        const closeBtn = document.createElement('button');
        closeBtn.innerHTML = '&times;';
        closeBtn.style.cssText = `
            position: absolute; top: 10px; right: 15px; font-size: 1.5rem; color: #9ca3af;
            background: none; border: none; cursor: pointer; padding: 5px;
        `;
        closeBtn.onclick = closePopup;

        // Content
        const content = `
            <div style="margin-bottom: 1.5rem;">
                <span style="display:inline-block; background-color: #fee2e2; color: #ef4444; padding: 0.5rem; border-radius: 9999px; margin-bottom: 1rem;">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7" />
                    </svg>
                </span>
                <h2 style="font-size: 1.5rem; font-weight: 800; color: #1f2937; line-height: 1.2; margin-bottom: 0.5rem;">
                    ${escapeHtml(data.title)}
                </h2>
                <p style="color: #4b5563; font-size: 1rem; line-height: 1.5;">
                    ${escapeHtml(data.message).replace(/\n/g, '<br>')}
                </p>
            </div>
            
            ${data.promo_code ? `
                <div style="background-color: #f3f4f6; border: 2px dashed #d1d5db; padding: 1rem; border-radius: 0.75rem; margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center;">
                    <span style="font-weight: 700; font-family: monospace; font-size: 1.25rem; color: #374151; letter-spacing: 0.05em;">
                        ${escapeHtml(data.promo_code)}
                    </span>
                    <button id="copy-btn-${data.id}" onclick="copyValidCode('${escapeHtml(data.promo_code)}', this)" 
                        style="background-color: #2563eb; color: white; border: none; padding: 0.5rem 1rem; border-radius: 0.5rem; font-weight: 600; cursor: pointer; transition: background-color 0.2s;">
                        ${escapeHtml(data.coupon_label)}
                    </button>
                </div>
            ` : ''}

            <button onclick="closePopup()" style="width: 100%; color: #6b7280; font-size: 0.875rem; text-decoration: underline; background: none; border: none; cursor: pointer;">
                Non merci, je ne veux pas d'offre
            </button>
        `;

        modal.innerHTML = content;
        modal.appendChild(closeBtn);
        overlay.appendChild(modal);
        document.body.appendChild(overlay);

        // Bind Mouse Leave Event
        document.addEventListener('mouseleave', (e) => {
            if (e.clientY <= 0 && !popupShown) {
                showPopup();
            }
        });

        // Bind Mobile Scroll/Back Event (Simple Heuristic for mobile)
        // Detecting "exit" on mobile is hard. We can use a timer or scroll speed, but for now relies on desktop exit intent mostly.
        // Or specific logic: if user scrolls up fast?
    }

    function showPopup() {
        const overlay = document.getElementById('exit-popup-overlay');
        const modal = overlay.querySelector('.exit-popup-modal');
        
        overlay.style.display = 'flex';
        // Force reflow
        overlay.offsetHeight; 
        
        overlay.style.opacity = '1';
        modal.style.transform = 'scale(1)';
        
        popupShown = true;
        if (!TEST_MODE) {
            localStorage.setItem(COOKIE_NAME, 'true');
        }
    }

    window.closePopup = function() {
        const overlay = document.getElementById('exit-popup-overlay');
        overlay.style.opacity = '0';
        setTimeout(() => {
            overlay.style.display = 'none';
        }, 300);
    };

    window.copyValidCode = function(code, btn) {
        navigator.clipboard.writeText(code).then(() => {
            const originalText = btn.innerText;
            btn.innerText = 'Copié !';
            btn.style.backgroundColor = '#059669';
            setTimeout(() => {
                btn.innerText = originalText;
                btn.style.backgroundColor = '#2563eb';
            }, 2000);
        });
    };

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.innerText = text;
        return div.innerHTML;
    }

    // Initialize
    fetchPopupData();

})();
