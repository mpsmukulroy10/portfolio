// =============================================
// CONTACT FORM - FIXED VERSION
// =============================================
function initForm() {
    const form = document.getElementById('contactForm');
    if (!form) {
        console.error('❌ Contact form not found!');
        return;
    }
    
    console.log('✅ Contact form found');
    const msg = document.getElementById('formMsg');
    const btn = document.getElementById('submitBtn');
    const btnTxt = btn ? btn.querySelector('.btn-text') : null;

    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        console.log('📤 Form submitted');
        
        const t = translations[lang] || translations.en;
        const formData = new FormData(form);
        
        // Log form data
        for (let [key, value] of formData.entries()) {
            console.log(`🔹 ${key}: ${value}`);
        }

        // Validation
        const name = formData.get('name')?.trim();
        const email = formData.get('email')?.trim();
        const message = formData.get('message')?.trim();

        if (!name || !email || !message) {
            showMsg('Please fill all fields correctly.', 'error');
            return;
        }

        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            showMsg('Please enter a valid email address.', 'error');
            return;
        }

        // Disable button
        btn.disabled = true;
        if (btnTxt) btnTxt.textContent = 'Sending...';
        showMsg('⏳ Sending...', 'info');

        try {
            const url = form.action || '<?= SITE_URL ?>/includes/contact.php';
            console.log('📡 Sending to:', url);
            
            const response = await fetch(url, {
                method: 'POST',
                body: formData,
                headers: {
                    'Accept': 'application/json'
                }
            });
            
            console.log('📥 Response status:', response.status);
            const text = await response.text();
            console.log('📄 Raw response:', text);
            
            let data;
            try {
                data = JSON.parse(text);
            } catch(e) {
                console.error('❌ JSON parse error:', e);
                showMsg('❌ Server returned invalid response', 'error');
                return;
            }
            
            if (data.success) {
                showMsg(data.message || '✅ Message sent successfully!', 'success');
                form.reset();
            } else {
                showMsg(data.message || '❌ Failed to send message', 'error');
            }
        } catch (error) {
            console.error('❌ Network error:', error);
            showMsg('❌ Network error. Please try again.', 'error');
        }

        btn.disabled = false;
        if (btnTxt) btnTxt.textContent = translations[lang]?.send_message || 'Send Message';
    });

    function showMsg(text, type) {
        if (!msg) {
            console.warn('⚠️ Message element not found');
            return;
        }
        msg.textContent = text;
        msg.className = 'form-msg ' + (type || '');
        msg.style.display = 'block';
        console.log(`📨 Message: ${type} - ${text}`);
        
        clearTimeout(msg._timeout);
        if (type !== 'error') {
            msg._timeout = setTimeout(() => {
                msg.style.opacity = '0';
                setTimeout(() => {
                    msg.style.display = 'none';
                    msg.style.opacity = '1';
                }, 500);
            }, 6000);
        }
    }
}

// Make sure it runs on page load
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 DOM loaded, initializing form...');
    initForm();
});