
"use strict";

// Toggle password visibility on the login form.
document.addEventListener("DOMContentLoaded", () => {
    const passwordInput = document.getElementById("password");
    const toggleButton = document.getElementById("togglePassword");

    if (!passwordInput || !toggleButton) {
        return;
    }

    toggleButton.addEventListener("click", () => {
        const isHidden = passwordInput.type === "password";

        passwordInput.type = isHidden ? "text" : "password";

        toggleButton.setAttribute(
            "aria-label",
            isHidden ? "Hide password" : "Show password"
        );

        toggleButton.setAttribute(
            "aria-pressed",
            String(isHidden)
        );
    });
});
