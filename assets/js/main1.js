document.addEventListener("DOMContentLoaded", () => {
    const loginForm = document.getElementById("loginForm");
    const loginMessage = document.getElementById("loginMessage");
    const loginBtn = document.getElementById("loginBtn");

    if (!loginForm) return;

    loginForm.addEventListener("submit", async (e) => {
        e.preventDefault();

        loginMessage.textContent = "";
        loginMessage.className = "form-message";

        const email = document.getElementById("email").value.trim();
        const password = document.getElementById("password").value.trim();

        if (!email || !password) {
            loginMessage.textContent = "Email i lozinka su obavezni.";
            loginMessage.classList.add("error");
            return;
        }

        loginBtn.disabled = true;
        loginBtn.textContent = "Prijava u tijeku...";

        try {
            const response = await fetch("api/login.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({
                    email: email,
                    password: password
                })
            });

            const result = await response.json();

            if (result.success) {
                loginMessage.textContent = result.message;
                loginMessage.classList.add("success");

                setTimeout(() => {
                    window.location.href = result.redirect;
                }, 800);
            } else {
                loginMessage.textContent = result.message || "Prijava nije uspjela.";
                loginMessage.classList.add("error");
            }

        } catch (error) {
            loginMessage.textContent = "Greška pri komunikaciji sa serverom.";
            loginMessage.classList.add("error");
            console.error("Login error:", error);
        } finally {
            loginBtn.disabled = false;
            loginBtn.textContent = "Prijava";
        }
    });
});