$(document).ready(function() {
    // If user is already authenticated, redirect to profile
    if (localStorage.getItem('auth_token')) {
        window.location.href = 'profile.html';
        return;
    }

    // Password visibility toggle
    $('#toggle-password').on('click', function() {
        const passwordField = $('#password');
        const icon = $(this).find('i');
        if (passwordField.attr('type') === 'password') {
            passwordField.attr('type', 'text');
            icon.removeClass('bi-eye').addClass('bi-eye-slash');
        } else {
            passwordField.attr('type', 'password');
            icon.removeClass('bi-eye-slash').addClass('bi-eye');
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
