// Form Validation and Submission Handler
document.addEventListener('DOMContentLoaded', function() {
    console.log('✓ Form handler loaded');
    
    // Get all forms
    const forms = document.querySelectorAll('form');
    console.log(`Found ${forms.length} forms`);
    
    forms.forEach(form => {
        // Add submit event listener
        form.addEventListener('submit', function(e) {
            console.log('Form submitted:', form.action);
            
            // Check if form is valid
            if (!form.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
                console.log('Form validation failed');
            } else {
                console.log('Form is valid, submitting...');
            }
            
            form.classList.add('was-validated');
        });
        
        // Debug submit button
        const submitBtn = form.querySelector('button[type="submit"]');
        if (submitBtn) {
            console.log('Submit button found:', submitBtn.textContent.trim());
            
            submitBtn.addEventListener('click', function(e) {
                console.log('Submit button clicked');
            });
        }
    });
    
    // Check for validation errors in page
    const errors = document.querySelectorAll('.invalid-feedback, .alert-danger');
    if (errors.length > 0) {
        console.log('Validation errors found:', errors.length);
        errors.forEach(error => console.log('Error:', error.textContent.trim()));
    }
});
