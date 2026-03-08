// JavaScript for reset-password.php

function togglePass(inputId, btn) {
        const input = document.getElementById(inputId);
        if (input.type === 'password') {
            input.type = 'text';
            btn.textContent = '🙈';
            btn.title = 'Hide password';
        } else {
            input.type = 'password';
            btn.textContent = '👁';
            btn.title = 'Show password';
        }
    }
