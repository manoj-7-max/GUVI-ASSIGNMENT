$(document).ready(function() {
    // If user is already authenticated, redirect to profile
    if (localStorage.getItem('auth_token')) {
        window.location.href = 'profile.html';
        return;
    }

    // Password visibility toggles
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

    $('#toggle-confirm-password').on('click', function() {
        const confirmField = $('#confirm_password');
        const icon = $(this).find('i');
        if (confirmField.attr('type') === 'password') {
            confirmField.attr('type', 'text');
            icon.removeClass('bi-eye').addClass('bi-eye-slash');
        } else {
            confirmField.attr('type', 'password');
            icon.removeClass('bi-eye-slash').addClass('bi-eye');
        }
    });

    // Form Submission & AJAX
    $('#registration-form').on('submit', function(e) {
        e.preventDefault();
        
        const alertContainer = $('#alert-container');
        alertContainer.addClass('d-none').removeClass('alert-custom-success alert-custom-error');
        
        let isValid = true;
        const form = this;
        
        // Form field values
        const fullName = $('#full_name').val().trim();
        const email = $('#email').val().trim();
        const mobile = $('#mobile').val().trim();
        const password = $('#password').val();
        const confirmPassword = $('#confirm_password').val();
        
        // Remove existing error classes
        $('.form-control-custom').removeClass('is-invalid');
        
        // Validation checks
        if (fullName === '') {
            $('#full_name').addClass('is-invalid');
            isValid = false;
        }
        
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (email === '' || !emailRegex.test(email)) {
            $('#email').addClass('is-invalid');
            isValid = false;
        }
        
        const mobileRegex = /^[0-9]{10,15}$/;
        if (mobile === '' || !mobileRegex.test(mobile)) {
            $('#mobile').addClass('is-invalid');
            isValid = false;
        }
        
        if (password.length < 6) {
            $('#password').addClass('is-invalid');
            isValid = false;
        }
        
        if (confirmPassword === '' || confirmPassword !== password) {
            $('#confirm_password').addClass('is-invalid');
            isValid = false;
        }
        
        if (!isValid) {
            return;
        }
        
        // Show spinner
        $('#spinner').css('display', 'flex');
        
        $.ajax({
            url: 'php/register.php',
            type: 'POST',
            dataType: 'json',
            data: {
                full_name: fullName,
                email: email,
                mobile: mobile,
                password: password,
                confirm_password: confirmPassword
            },
            success: function(response) {
                $('#spinner').css('display', 'none');
                
                alertContainer.text(response.message)
                    .removeClass('d-none')
                    .addClass('alert-custom-success');
                
                form.reset();
                
                // Redirect after 2 seconds
                setTimeout(function() {
                    window.location.href = 'login.html';
                }, 2000);
            },
            error: function(xhr) {
                $('#spinner').css('display', 'none');
                
                let errorMessage = 'An error occurred during registration. Please try again.';
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
