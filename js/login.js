$(document).ready(function() {
    // If user is already authenticated, redirect to profile
    if (localStorage.getItem('auth_token')) {
        window.location.href = 'profile.html';
        return;
    }

    // Password visibility toggle
    $('#toggle-password').on('click', function() {
        const passwordField = $('#password');
        const svg = $(this).find('svg');
        if (passwordField.attr('type') === 'password') {
            passwordField.attr('type', 'text');
            svg.html('<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line>');
        } else {
            passwordField.attr('type', 'password');
            svg.html('<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle>');
        }
    });

    // Form Submission & AJAX
    $('#login-form').on('submit', function(e) {
        e.preventDefault();
        
        const alertContainer = $('#alert-container');
        alertContainer.addClass('d-none').removeClass('alert-custom-success alert-custom-error');
        
        let isValid = true;
        
        const email = $('#email').val().trim();
        const password = $('#password').val();
        
        // Remove existing error classes
        $('.form-control-custom').removeClass('is-invalid');
        
        // Validation checks
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (email === '' || !emailRegex.test(email)) {
            $('#email').addClass('is-invalid');
            isValid = false;
        }
        
        if (password === '') {
            $('#password').addClass('is-invalid');
            isValid = false;
        }
        
        if (!isValid) {
            return;
        }
        
        // Show spinner
        $('#spinner').css('display', 'flex');
        
        $.ajax({
            url: 'php/login.php',
            type: 'POST',
            dataType: 'json',
            data: {
                email: email,
                password: password
            },
            success: function(response) {
                $('#spinner').css('display', 'none');
                
                alertContainer.text(response.message)
                    .removeClass('d-none')
                    .addClass('alert-custom-success');
                
                // Save session token to LocalStorage
                localStorage.setItem('auth_token', response.token);
                
                // Redirect after 1 second
                setTimeout(function() {
                    window.location.href = 'profile.html';
                }, 1000);
            },
            error: function(xhr) {
                $('#spinner').css('display', 'none');
                
                let errorMessage = 'Authentication failed. Please verify your email and password.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                
                alertContainer.text(errorMessage)
                    .removeClass('d-none')
                    .addClass('alert-custom-error');
            }
        });
    });
});
