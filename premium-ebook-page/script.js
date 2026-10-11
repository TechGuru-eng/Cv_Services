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

    // Backend API URL pointing to the local PHP backend
    const API_BASE = 'api'; 

    // Fetch Settings
    let appSettings = {
        scarcity_end_date: "2026-07-30T00:00:00",
        toast_enabled: "1",
        toast_frequency_seconds: "12"
    };

    const loadSettings = async () => {
        try {
            const res = await fetch(`${API_BASE}/get_settings.php?t=${new Date().getTime()}`);
            if (res.ok) {
                const data = await res.json();
                appSettings = { ...appSettings, ...data };
                
                // Initialize Countdown
                if (appSettings.scarcity_end_date) {
                    const targetDate = new Date(appSettings.scarcity_end_date).getTime();
                    
                    const updateCountdown = () => {
                        const now = new Date().getTime();
                        const difference = targetDate - now;
                        
                        if (difference > 0) {
                            const days = Math.floor(difference / (1000 * 60 * 60 * 24));
                            const hours = Math.floor((difference % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                            const minutes = Math.floor((difference % (1000 * 60 * 60)) / (1000 * 60));
                            const seconds = Math.floor((difference % (1000 * 60)) / 1000);
                            
                            document.getElementById('cd-days').textContent = days.toString().padStart(2, '0');
                            document.getElementById('cd-hours').textContent = hours.toString().padStart(2, '0');
                            document.getElementById('cd-minutes').textContent = minutes.toString().padStart(2, '0');
                            document.getElementById('cd-seconds').textContent = seconds.toString().padStart(2, '0');
                        }
                    };
                    
                    updateCountdown();
                    setInterval(updateCountdown, 1000);
                }
            }
        } catch(e) { console.log('Could not load settings'); }
    };
    
    loadSettings();

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
                sessionId: sessionId
            };
            
            try {
                const res = await fetch(`${API_BASE}/subscribe.php`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(formData)
                });
                
                if (res.ok) {
                    form.classList.add('hidden');
                    successMsg.classList.remove('hidden');
                } else {
                    alert('Une erreur s\'est produite. Veuillez réessayer.');
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = 'Je réserve ma copie (5 000 XAF)';
                }
            } catch (error) {
                console.log('API not reachable, simulating success for demo purposes');
                setTimeout(() => {
                    form.classList.add('hidden');
                    successMsg.classList.remove('hidden');
                }, 1000);
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
    }, 5000);

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
