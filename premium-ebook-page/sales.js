document.addEventListener('DOMContentLoaded', () => {
    // Reveal Animations on Scroll
    const revealElements = document.querySelectorAll('.reveal');
    
    const revealOnScroll = () => {
        const windowHeight = window.innerHeight;
        const elementVisible = 100;
        
        revealElements.forEach((el) => {
            const elementTop = el.getBoundingClientRect().top;
            if (elementTop < windowHeight - elementVisible) {
                el.classList.add('active');
            }
        });
    };
    
    window.addEventListener('scroll', revealOnScroll);
    revealOnScroll(); // Trigger once on load
    // Countdown removed for Sales Page

    // Form Handling with Partial Capture (Retargeting feature adapted for generic hosting)
    const form = document.getElementById('preorder-form');
    const successMsg = document.getElementById('success-message');
    const submitBtn = document.getElementById('submit-btn');
    const emailInput = document.getElementById('email');
    const whatsappInput = document.getElementById('whatsapp');
    
    // Session management
    let sessionId = localStorage.getItem('premium_ebook_session');
    if (!sessionId) {
        sessionId = Math.random().toString(36).substring(2, 15) + Math.random().toString(36).substring(2, 15);
        localStorage.setItem('premium_ebook_session', sessionId);
    }
    
    // Backend API URL pointing to the local PHP backend
    const API_BASE = 'api'; 
    
    // Track page view
    try {
        fetch(`${API_BASE}/track.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ sessionId, referrer: document.referrer })
        }).catch(() => console.log('Tracking endpoint unavailable'));
    } catch(e) {}

    // Track partial form fill
    const trackPartial = () => {
        const email = emailInput.value;
        const whatsapp = whatsappInput.value;
        if (email || whatsapp) {
            try {
                fetch(`${API_BASE}/track.php`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ sessionId, email, whatsapp })
                }).catch(() => {});
            } catch(e) {}
        }
    };

    emailInput.addEventListener('blur', trackPartial);
    whatsappInput.addEventListener('blur', trackPartial);
    
    // --- Coupon Logic ---
    const applyCouponBtn = document.getElementById('apply-coupon-btn');
    const couponInput = document.getElementById('couponCode');
    const couponMessage = document.getElementById('coupon-message');
    let currentDiscount = 0;
    let hasUpsell = false;

    const updateDisplayPrice = () => {
        const base = 10000;
        const upsellCost = hasUpsell ? 3000 : 0;
        const newPrice = Math.max(0, base - currentDiscount) + upsellCost;
        
        const btnPriceEl = document.getElementById('btn-price');
        if (btnPriceEl) btnPriceEl.textContent = newPrice.toLocaleString();
        
        const heroPriceEl = document.getElementById('hero-price');
        if (heroPriceEl) heroPriceEl.textContent = newPrice.toLocaleString() + ' XAF';
    };

    const upsellCb = document.getElementById('upsellCv');
    if (upsellCb) {
        upsellCb.addEventListener('change', (e) => {
            hasUpsell = e.target.checked;
            updateDisplayPrice();
        });
    }
    
    if (applyCouponBtn) {
        applyCouponBtn.addEventListener('click', async () => {
            const code = couponInput.value.trim();
            if (!code) {
                couponMessage.style.color = '#ef4444'; // red
                couponMessage.textContent = 'Veuillez entrer un code promo.';
                return;
            }
            
            applyCouponBtn.disabled = true;
            applyCouponBtn.textContent = '...';
            
            try {
                const res = await fetch(`${API_BASE}/validate_coupon.php`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ code })
                });
                const data = await res.json();
                
                if (res.ok && data.success) {
                    couponMessage.style.color = '#10b981'; // green
                    couponMessage.textContent = `${data.message} - Réduction de ${data.discount} XAF appliquée !`;
                    currentDiscount = data.discount;
                    updateDisplayPrice();
                    
                    if (document.getElementById('hero-discount')) {
                        document.getElementById('hero-discount').textContent = 'Code Promo!';
                    }
                } else {
                    couponMessage.style.color = '#ef4444';
                    couponMessage.textContent = data.error || 'Code invalide.';
                    currentDiscount = 0;
                    updateDisplayPrice();
                }
            } catch (err) {
                couponMessage.style.color = '#ef4444';
                couponMessage.textContent = 'Erreur de connexion.';
            } finally {
                applyCouponBtn.disabled = false;
                applyCouponBtn.textContent = 'Appliquer';
            }
        });
    }

    // Check for default coupon or URL coupon on load
    const checkDefaultCoupon = async () => {
        try {
            const urlParams = new URLSearchParams(window.location.search);
            const urlCoupon = urlParams.get('coupon');
            
            if (urlCoupon) {
                // Apply coupon from Magic Link
                couponInput.value = urlCoupon;
                applyCouponBtn.click();
                return;
            }

            const res = await fetch(`${API_BASE}/get_default_coupon.php`);
            const data = await res.json();
            if (data.success && data.code) {
                couponInput.value = data.code;
                couponMessage.style.color = '#10b981';
                couponMessage.textContent = `Code promo par défaut "${data.code}" appliqué - Réduction de ${data.discount} XAF !`;
                currentDiscount = data.discount;
                updateDisplayPrice();
                
                if (document.getElementById('hero-discount')) {
                    document.getElementById('hero-discount').textContent = 'Code Promo!';
                }
            }
        } catch (e) {
            console.log('No default coupon or error fetching.');
        }
    };
    checkDefaultCoupon();

    // Handle Form Submission
    if (form) {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            submitBtn.disabled = true;
            submitBtn.innerHTML = 'Réservation en cours...';
            
            const formData = {
                fullName: document.getElementById('fullName').value,
                email: emailInput.value,
                whatsapp: whatsappInput.value,
                city: document.getElementById('city').value,
                profession: document.getElementById('profession').value,
                sessionId: sessionId,
                coupon: document.getElementById('couponCode').value,
                upsell_cv: hasUpsell
            };
            
            try {
                const res = await fetch(`${API_BASE}/purchase.php`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(formData)
                });
                
                if (res.ok) {
                    const data = await res.json();
                    if (data.success) {
                        const finalPrice = data.finalPrice || 10000;
                        const purchaseId = data.purchaseId || '';
                        const redirectUrl = `paiement.html?amount=${finalPrice}&name=${encodeURIComponent(formData.fullName)}&email=${encodeURIComponent(formData.email)}&phone=${encodeURIComponent(formData.whatsapp)}&purchase_id=${purchaseId}`;
                        window.location.href = redirectUrl;
                    } else {
                        throw new Error(data.error || 'Erreur inconnue');
                    }
                } else {
                    const data = await res.json();
                    alert(data.error || 'Une erreur s\'est produite. Veuillez réessayer.');
                    submitBtn.disabled = false;
                    updateDisplayPrice();
                    submitBtn.innerHTML = `Valider ma commande (<span id="btn-price">${document.getElementById('btn-price').textContent}</span> XAF)`;
                }
            } catch (error) {
                console.log('Error:', error);
                alert(error.message || 'Une erreur de connexion est survenue.');
                submitBtn.disabled = false;
                submitBtn.innerHTML = `Valider ma commande (<span id="btn-price">${document.getElementById('btn-price').textContent}</span> XAF)`;
            }
        });
    }

    // --- FAQ Accordion Logic ---
    const faqItems = document.querySelectorAll('.faq-item');
    faqItems.forEach(item => {
        const question = item.querySelector('.faq-question');
        question.addEventListener('click', () => {
            const isActive = item.classList.contains('active');
            // Close all
            faqItems.forEach(faq => faq.classList.remove('active'));
            // Open clicked if it wasn't active
            if (!isActive) item.classList.add('active');
        });
    });

    // --- Exit Intent Popup Logic ---
    let exitPopupShown = false;
    document.addEventListener('mouseleave', (e) => {
        if (e.clientY < 0 && !exitPopupShown) {
            const popup = document.getElementById('exit-popup');
            if (popup) {
                popup.classList.add('active');
                exitPopupShown = true;
            }
        }
    });

    // --- Live Sales Notification Toasts ---
    const names = ["Marc", "Jeanne", "Paul", "Aline", "Cédric", "Sophie", "Alain", "Marie", "Hervé", "Nathalie"];
    const cities = ["Douala", "Yaoundé", "Bafoussam", "Garoua", "Kribi", "Limbe", "Bamenda"];
    const toastContainer = document.getElementById('toast-container');
    
    // Fetch Settings for toasts
    let appSettings = {
        toast_enabled: "1",
        toast_frequency_seconds: "12"
    };
    fetch(`${API_BASE}/get_settings.php?t=${new Date().getTime()}`)
        .then(res => res.json())
        .then(data => { appSettings = { ...appSettings, ...data }; })
        .catch(e => console.log('Could not load settings for toasts'));

    function createToast() {
        if (!toastContainer) return;
        
        const name = names[Math.floor(Math.random() * names.length)];
        const city = cities[Math.floor(Math.random() * cities.length)];
        const timeAgo = Math.floor(Math.random() * 15) + 1; // 1 to 15 mins ago

        const toast = document.createElement('div');
        toast.className = 'toast';
        toast.innerHTML = `
            <img src="actual_ebook_cover.jpg" alt="Guide" class="toast-img">
            <div class="toast-content">
                <p><strong>${name}</strong> de ${city} a précommandé</p>
                <span>Il y a ${timeAgo} min</span>
            </div>
        `;

        toastContainer.appendChild(toast);
        
        // Trigger reflow for animation
        setTimeout(() => toast.classList.add('show'), 100);

        // Remove after 5 seconds
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 500);
        }, 5000);
    }

    // Start showing toasts randomly after a delay
    setTimeout(() => {
        if (appSettings.toast_enabled !== '1') return;
        
        createToast();
        
        const frequencyMs = parseInt(appSettings.toast_frequency_seconds || '12') * 1000;
        
        setInterval(() => {
            if (appSettings.toast_enabled === '1' && Math.random() > 0.3) { 
                createToast();
            }
        }, frequencyMs);
    }, 5000); // Initial delay of 5 seconds

    // --- Floating CVs Logic ---
    const cvContainer = document.getElementById('floating-cv-container');
    if (cvContainer) {
        fetch(`${API_BASE}/get_cvs.php?t=${new Date().getTime()}`)
            .then(res => res.json())
            .then(cvs => {
                if (cvs && cvs.length > 0) {
                    cvContainer.innerHTML = ''; // clear default
                    cvs.forEach((cv, idx) => {
                        const img = document.createElement('img');
                        img.src = `uploads/cvs/${encodeURIComponent(cv)}`;
                        img.alt = 'Modèle de CV Professionnel';
                        img.className = 'cv-template-img';
                        if (idx === 0) img.classList.add('active');
                        cvContainer.appendChild(img);
                    });
                    
                    if (cvs.length > 1) {
                        let currentIdx = 0;
                        setInterval(() => {
                            const images = cvContainer.querySelectorAll('.cv-template-img');
                            images[currentIdx].classList.remove('active');
                            currentIdx = (currentIdx + 1) % images.length;
                            images[currentIdx].classList.add('active');
                        }, 3500); // Change image every 3.5 seconds
                    }
                }
            })
            .catch(() => console.log('Could not load dynamic CVs'));
    }
});
