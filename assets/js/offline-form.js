document.addEventListener('DOMContentLoaded', () => {
    const FORM_KEY = 'hireme_form_backup';

    // 1. Restore Data
    const savedData = localStorage.getItem(FORM_KEY);
    if (savedData) {
        try {
            const data = JSON.parse(savedData);

            // Text Inputs
            setVal('name', data.name);
            setVal('email', data.email);
            setVal('phone', data.phone);
            setVal('city', data.city);
            setVal('promoInput', data.promo);

            // Selects
            if (data.pack) {
                const packSelect = document.getElementById('packSelect');
                if (packSelect) packSelect.value = data.pack;
            }

            // Checkboxes (Upsells)
            setCheck('upsellLetter', data.letter);
            setCheck('upsellRecruiter', data.recruiter);

            console.log("Offline Data Restored 📂");
        } catch (e) {
            console.error("Error restoring offline data", e);
        }
    }

    // 2. Save Data Live
    const inputs = ['name', 'email', 'phone', 'city', 'promoInput', 'packSelect', 'upsellLetter', 'upsellRecruiter'];
    inputs.forEach(id => {
        const el = document.getElementById(id);
        if (el) {
            el.addEventListener('input', saveData);
            el.addEventListener('change', saveData);
        }
    });

    function saveData() {
        const data = {
            name: getVal('name'),
            email: getVal('email'),
            phone: getVal('phone'),
            city: getVal('city'),
            promo: getVal('promoInput'),
            pack: getVal('packSelect'),
            letter: getCheck('upsellLetter'),
            recruiter: getCheck('upsellRecruiter'),
            timestamp: new Date().getTime()
        };
        localStorage.setItem(FORM_KEY, JSON.stringify(data));
    }

    // 3. Offline Submit Interception
    const form = document.getElementById('orderForm');
    if (form) {
        form.addEventListener('submit', (e) => {
            if (!navigator.onLine) {
                e.preventDefault();

                // Show Offline Message
                showToast("Pas de connexion Internet 🚫. Vos données sont sauvegardées. Réessayez une fois connecté.", "error");

                // Optional: visual cue
                const btn = form.querySelector('button[type="submit"]');
                const originalText = btn.innerHTML;
                btn.innerHTML = "🚫 En attente de connexion...";
                btn.classList.add('bg-gray-500', 'cursor-not-allowed');

                // Polling for connection
                const checkInterval = setInterval(() => {
                    if (navigator.onLine) {
                        btn.innerHTML = "✅ Connexion rétablie !";
                        btn.classList.remove('bg-gray-500', 'cursor-not-allowed');
                        setTimeout(() => {
                            btn.innerHTML = originalText;
                        }, 2000);
                        clearInterval(checkInterval);
                    }
                }, 2000);
            }
        });
    }

    // Helpers
    function getVal(id) { return document.getElementById(id) ? document.getElementById(id).value : ''; }
    function setVal(id, val) { if (document.getElementById(id) && val) document.getElementById(id).value = val; }
    function getCheck(id) { return document.getElementById(id) ? document.getElementById(id).checked : false; }
    function setCheck(id, val) { if (document.getElementById(id)) document.getElementById(id).checked = val; }
});
