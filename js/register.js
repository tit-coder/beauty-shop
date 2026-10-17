<script>
    const password = document.getElementById("password");
    const eyeIcon = document.getElementById("eyeIcon");

    document.getElementById("togglePass").onclick = function () {
        if (password.type === "password") {
            password.type = "text";

            // đổi icon thành mắt mở
            eyeIcon.innerHTML = `
                <path d="M1 12C2.73 8.11 7 5 12 5s9.27 3.11 11 7c-1.73 3.89-6 7-11 7S2.73 15.89 1 12z" 
                      stroke="#666" stroke-width="1.5" fill="none"/>
                <circle cx="12" cy="12" r="5" stroke="#666" stroke-width="1.5" fill="none"/>
            `;
        } else {
            password.type = "password";

            // đổi icon lại mắt bạn đầu
            eyeIcon.innerHTML = `
                <path d="M12 5C7 5 2.73 8.11 1 12c1.73 3.89 6 7 11 7s9.27-3.11 11-7c-1.73-3.89-6-7-11-7zm0 12a5 5 0 110-10 5 5 0 010 10z" 
                    stroke="#666" stroke-width="1.5" fill="none"/>
                <circle cx="12" cy="12" r="2.5" fill="#666"/>
            `;
        }
    };
</script>
