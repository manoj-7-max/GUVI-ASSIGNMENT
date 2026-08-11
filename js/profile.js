$(document).ready(function() {
    const token = localStorage.getItem('auth_token');

    // 1. Session check: If no token exists, redirect to login
    if (!token) {
        window.location.href = 'login.html';
        return;
    }

    // Load Profile Data
    loadProfile();

    function loadProfile() {
        $('#spinner').css('display', 'flex');
        
        $.ajax({
            url: 'php/profile.php',
            method: 'POST',
            dataType: 'json',
            data: {
                action: 'get_profile',
                token: token
            },
            success: function(response) {
                $('#spinner').css('display', 'none');
                if (response.success && response.data) {
                    const user = response.data;
                    $('#full_name').val(user.full_name || '');
                    $('#email').val(user.email || '');
                    $('#mobile').val(user.mobile || '');
                    $('#age').val(user.age !== null ? user.age : '');
                    $('#date_of_birth').val(user.date_of_birth || '');
                    $('#address').val(user.address || '');
                } else {
                    handleAuthFailure();
                }
            },
            error: function(xhr) {
                $('#spinner').css('display', 'none');
                handleAuthFailure();
            }
        });
    }

    function handleAuthFailure() {
        localStorage.removeItem('auth_token');
        window.location.href = 'login.html';
    }

    // 2. Profile Update Submission
    $('#profile-form').on('submit', function(e) {
        e.preventDefault();
        
        const alertContainer = $('#alert-container');
        alertContainer.addClass('d-none').removeClass('alert-custom-success alert-custom-error');
        
        let isValid = true;
        
        const fullName = $('#full_name').val().trim();
        const mobile = $('#mobile').val().trim();
        const age = $('#age').val();
        const dob = $('#date_of_birth').val();
        const address = $('#address').val().trim();
        
        $('.form-control-custom').removeClass('is-invalid');
        
        if (fullName === '') {
            $('#full_name').addClass('is-invalid');
            isValid = false;
        }
        
        const mobileRegex = /^[0-9]{10,15}$/;
        if (mobile === '' || !mobileRegex.test(mobile)) {
            $('#mobile').addClass('is-invalid');
            isValid = false;
        }
        
        if (age !== '') {
            const ageInt = parseInt(age, 10);
            if (isNaN(ageInt) || ageInt < 0 || ageInt > 120) {
                $('#age').addClass('is-invalid');
                isValid = false;
            }
        }
        
        if (!isValid) {
            return;
        }
        
        $('#spinner').css('display', 'flex');
        
        $.ajax({
            url: 'php/profile.php',
            method: 'POST',
            dataType: 'json',
            data: {
                action: 'update_profile',
                token: token,
                full_name: fullName,
                mobile: mobile,
                age: age,
                date_of_birth: dob,
                address: address
            },
            success: function(response) {
                $('#spinner').css('display', 'none');
                
                alertContainer.text(response.message)
                    .removeClass('d-none')
                    .addClass('alert-custom-success');
                
                // Refresh data to ensure interface matches database
                loadProfile();
                
                // Automatically hide alert after 3 seconds
                setTimeout(function() {
                    alertContainer.addClass('d-none');
                }, 3000);
            },
            error: function(xhr) {
                $('#spinner').css('display', 'none');
                
                let errorMessage = 'Failed to update profile. Please try again.';
                if (xhr.status === 401) {
                    handleAuthFailure();
                    return;
                }
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                
                alertContainer.text(errorMessage)
                    .removeClass('d-none')
                    .addClass('alert-custom-error');
            }
        });
    });

    // 3. Logout action
    $('#btn-logout').on('click', function() {
        $('#spinner').css('display', 'flex');
        
        $.ajax({
            url: 'php/logout.php',
            method: 'POST',
            dataType: 'json',
            data: {
                token: token
            },
            success: function() {
                $('#spinner').css('display', 'none');
                localStorage.removeItem('auth_token');
                window.location.href = 'login.html';
            },
            error: function() {
                $('#spinner').css('display', 'none');
                localStorage.removeItem('auth_token');
                window.location.href = 'login.html';
            }
        });
    });
});
