// Authentication JavaScript

document.addEventListener('DOMContentLoaded', function() {
    // Password validation for registration
    const passwordInput = document.getElementById('password');
    const confirmPasswordInput = document.getElementById('confirm_password');
    
    if (passwordInput && confirmPasswordInput) {
        confirmPasswordInput.addEventListener('input', function() {
            if (passwordInput.value !== confirmPasswordInput.value) {
                confirmPasswordInput.setCustomValidity('Passwords do not match');
            } else {
                confirmPasswordInput.setCustomValidity('');
            }
        });
    }
    
    // Form validation with reCAPTCHA check
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            // Check if reCAPTCHA is present on login form
            const recaptchaContainer = this.querySelector('.g-recaptcha');
            if (recaptchaContainer) {
                // Verify reCAPTCHA response exists
                const recaptchaResponse = grecaptcha.getResponse();
                if (!recaptchaResponse) {
                    e.preventDefault();
                    e.stopPropagation();
                    alert('Please complete the reCAPTCHA verification before logging in.');
                    return;
                }
            }
            
            if (!this.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
            }
            this.classList.add('was-validated');
        });
    });
    
    // Log reCAPTCHA status for debugging
    console.log('reCAPTCHA API loaded:', typeof grecaptcha !== 'undefined');
});


