/**
 * Pet Haven - Main JavaScript
 */

document.addEventListener('DOMContentLoaded', () => {
    // Auto-dismiss or allow manual closing of flash alerts
    document.querySelectorAll('.alert').forEach(alert => {
        const closeBtn = alert.querySelector('button');
        if (closeBtn) {
            closeBtn.addEventListener('click', () => alert.remove());
        }
    });

    // Confirmation on critical admin actions
    document.querySelectorAll('[data-confirm]').forEach(el => {
        el.addEventListener('click', (e) => {
            const msg = el.getAttribute('data-confirm') || 'Are you sure you want to proceed?';
            if (!confirm(msg)) {
                e.preventDefault();
            }
        });
    });

    // Image preview for pet uploads
    const imgInput = document.getElementById('pet_image_input');
    const previewContainer = document.getElementById('pet_image_preview');
    if (imgInput && previewContainer) {
        imgInput.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewContainer.src = e.target.result;
                    previewContainer.style.display = 'block';
                };
                reader.readAsDataURL(file);
            }
        });
    }
});
