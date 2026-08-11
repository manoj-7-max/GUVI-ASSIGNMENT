$(document).ready(function() {
    const token = localStorage.getItem('auth_token');
    if (token) {
        $('#nav-profile').removeClass('d-none');
        $('#nav-logout').removeClass('d-none');
        $('#nav-login').addClass('d-none');
        $('#nav-register').addClass('d-none');
    }

    $('#btn-nav-logout').on('click', function() {
        $.ajax({
            url: 'php/logout.php',
            type: 'POST',
            data: { token: token },
            dataType: 'json',
            success: function() {
                localStorage.removeItem('auth_token');
                window.location.href = 'login.html';
            },
            error: function() {
                localStorage.removeItem('auth_token');
                window.location.href = 'login.html';
            }
        });
    });
});
