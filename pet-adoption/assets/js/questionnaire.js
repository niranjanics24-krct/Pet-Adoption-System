/**
 * Multi-Step Extended Questionnaire Wizard
 */

document.addEventListener('DOMContentLoaded', () => {
    const wizard = document.getElementById('quiz-wizard');
    if (!wizard) return;

    let currentStep = 1;
    const totalSteps = 5; // Steps 1-4 are sections, Step 5 is Review & Confirm

    const stepElements = document.querySelectorAll('.wizard-step');
    const stepperItems = document.querySelectorAll('.step-item');
    const prevBtn = document.getElementById('wizard-prev-btn');
    const nextBtn = document.getElementById('wizard-next-btn');
    const submitBtn = document.getElementById('wizard-submit-btn');
    const stepCounter = document.getElementById('wizard-step-counter');
    const progressBar = document.getElementById('wizard-progress-bar');
    const errorNotice = document.getElementById('wizard-step-error');

    // Option card click handling
    document.querySelectorAll('.option-card').forEach(card => {
        card.addEventListener('click', function(e) {
            const radio = this.querySelector('input[type="radio"]');
            if (radio) {
                radio.checked = true;
                
                // Remove selected class from siblings in same question
                const groupName = radio.getAttribute('name');
                document.querySelectorAll(`input[name="${groupName}"]`).forEach(r => {
                    const parentCard = r.closest('.option-card');
                    if (parentCard) parentCard.classList.remove('selected');
                });

                this.classList.add('selected');
                if (errorNotice) errorNotice.style.display = 'none';
            }
        });
    });

    // Mark pre-checked options
    document.querySelectorAll('.option-card input[type="radio"]:checked').forEach(radio => {
        const parentCard = radio.closest('.option-card');
        if (parentCard) parentCard.classList.add('selected');
    });

    function showStep(step) {
        stepElements.forEach((el, index) => {
            el.style.display = (index + 1 === step) ? 'block' : 'none';
        });

        // Update stepper items
        stepperItems.forEach((item, index) => {
            const stepNum = index + 1;
            item.classList.remove('active', 'completed');
            if (stepNum === step) {
                item.classList.add('active');
            } else if (stepNum < step) {
                item.classList.add('completed');
            }
        });

        // Update step counter & progress bar
        const percent = Math.round((step / totalSteps) * 100);
        if (progressBar) progressBar.style.width = percent + '%';
        if (stepCounter) {
            if (step <= 4) {
                stepCounter.innerText = `Step ${step} of ${totalSteps} (Questions ${(step - 1) * 5 + 1} - ${Math.min(step * 5, 20)})`;
            } else {
                stepCounter.innerText = `Step 5 of 5: Review & Submit`;
            }
        }

        // Button visibility
        if (prevBtn) prevBtn.style.display = (step === 1) ? 'none' : 'inline-flex';
        
        if (step === totalSteps) {
            if (nextBtn) nextBtn.style.display = 'none';
            if (submitBtn) submitBtn.style.display = 'inline-flex';
            buildReviewSummary();
        } else {
            if (nextBtn) nextBtn.style.display = 'inline-flex';
            if (submitBtn) submitBtn.style.display = 'none';
        }

        if (errorNotice) errorNotice.style.display = 'none';
        window.scrollTo({ top: wizard.offsetTop - 70, behavior: 'smooth' });
    }

    function validateCurrentStep() {
        if (currentStep === 5) return true; // Review step

        const currentStepEl = document.querySelector(`.wizard-step[data-step="${currentStep}"]`);
        if (!currentStepEl) return true;

        const questionBoxes = currentStepEl.querySelectorAll('.quiz-question-box');
        let isValid = true;
        let firstMissing = null;

        questionBoxes.forEach(box => {
            const isRequired = box.getAttribute('data-required') === '1';
            if (!isRequired) return;

            const radioChecked = box.querySelector('input[type="radio"]:checked');
            const select = box.querySelector('select');

            let answered = false;
            if (radioChecked) {
                answered = true;
            } else if (select && select.value !== '') {
                answered = true;
            }

            if (!answered) {
                isValid = false;
                box.style.borderColor = '#C53030';
                if (!firstMissing) firstMissing = box;
            } else {
                box.style.borderColor = 'var(--border-color)';
            }
        });

        if (!isValid) {
            if (errorNotice) {
                errorNotice.innerText = 'Please answer all required questions in this section before continuing.';
                errorNotice.style.display = 'block';
            }
            if (firstMissing) {
                firstMissing.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }

        return isValid;
    }

    function buildReviewSummary() {
        const reviewContainer = document.getElementById('quiz-review-container');
        if (!reviewContainer) return;

        let html = '<div style="display:flex; flex-direction:column; gap:16px;">';
        
        const allBoxes = document.querySelectorAll('.quiz-question-box');
        allBoxes.forEach(box => {
            const num = box.querySelector('.quiz-q-num')?.innerText || '';
            const title = box.querySelector('.quiz-q-title')?.innerText || '';
            const radioChecked = box.querySelector('input[type="radio"]:checked');
            const select = box.querySelector('select');
            const textarea = box.querySelector('textarea');

            let answerText = '<em>Not answered</em>';
            if (radioChecked) {
                const label = radioChecked.closest('.option-card')?.querySelector('.option-text')?.innerText;
                answerText = label ? label : radioChecked.value;
            } else if (select && select.value) {
                answerText = select.options[select.selectedIndex].text;
            } else if (textarea && textarea.value.trim()) {
                answerText = textarea.value.trim();
            }

            html += `
                <div style="background:var(--bg-warm-tint); padding:14px 18px; border-radius:8px; border-left:4px solid var(--primary-forest);">
                    <div style="font-size:12px; font-weight:700; color:var(--primary-forest); margin-bottom:4px;">${num}</div>
                    <div style="font-weight:600; font-size:14px; margin-bottom:6px;">${title}</div>
                    <div style="font-size:14px; color:var(--text-dark); background:#FFFFFF; padding:8px 12px; border-radius:6px; border:1px solid var(--border-color);">
                        ${answerText}
                    </div>
                </div>
            `;
        });

        html += '</div>';
        reviewContainer.innerHTML = html;
    }

    if (nextBtn) {
        nextBtn.addEventListener('click', () => {
            if (validateCurrentStep()) {
                currentStep = Math.min(totalSteps, currentStep + 1);
                showStep(currentStep);
            }
        });
    }

    if (prevBtn) {
        prevBtn.addEventListener('click', () => {
            currentStep = Math.max(1, currentStep - 1);
            showStep(currentStep);
        });
    }

    // Step item navigation (allows clicking previous steps)
    stepperItems.forEach((item, index) => {
        item.addEventListener('click', () => {
            const targetStep = index + 1;
            if (targetStep < currentStep) {
                currentStep = targetStep;
                showStep(currentStep);
            } else if (targetStep === currentStep + 1) {
                if (validateCurrentStep()) {
                    currentStep = targetStep;
                    showStep(currentStep);
                }
            }
        });
    });

    // Confirmation on submit
    if (submitBtn) {
        submitBtn.addEventListener('click', (e) => {
            const confirmed = confirm('Are you sure you are ready to submit this adoption request? Your eligibility score will be calculated and your application will be forwarded to our team.');
            if (!confirmed) {
                e.preventDefault();
            }
        });
    }

    // Initialize first step
    showStep(1);
});
