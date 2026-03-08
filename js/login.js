// Floating Banana Background Effect and form validation
document.addEventListener("DOMContentLoaded", () => {
    // Check for messages from PHP redirect
    const urlParams = new URLSearchParams(window.location.search);
    const container = document.querySelector(".login-container");
    if (urlParams.has('error')) {
        const error = urlParams.get('error');
        let msg = "Login failed.";
        if (error === 'incorrect') msg = "Incorrect password!";
        else if (error === 'notfound') msg = "User not found!";
        else if (error === 'db') msg = "Database connection error.";
        
        const errorMsg = document.createElement("p");
        errorMsg.style.color = "#ff6b6b";
        errorMsg.style.fontWeight = "bold";
        errorMsg.style.marginBottom = "15px";
        errorMsg.innerText = msg;
        container.insertBefore(errorMsg, container.querySelector("form"));
    } else if (urlParams.has('msg')) {
        const msgStr = urlParams.get('msg');
        let msg = "Success!";
        if (msgStr === 'registered') msg = "Registration successful! Please login.";
        
        const successMsg = document.createElement("p");
        successMsg.style.color = "#4CAF50";
        successMsg.style.fontWeight = "bold";
        successMsg.style.marginBottom = "15px";
        successMsg.innerText = msg;
        container.insertBefore(successMsg, container.querySelector("form"));
    }

    const bgAnimation = document.getElementById("bg-animation");
    const numBananas = 15;

    // Generate floating bananas dynamically
    for (let i = 0; i < numBananas; i++) {
        createBanana(bgAnimation);
    }

    // Form Validation Logic
    const loginForm = document.getElementById("loginForm");
    
    loginForm.addEventListener("submit", function(e) {
        const passwordInput = document.getElementById("password");
        const password = passwordInput.value;
        const container = document.querySelector(".login-container");

        if (password.length < 6) {
            e.preventDefault();
            
            // Add custom error message if it doesn't exist
            let errorMsg = document.getElementById("error-msg");
            if (!errorMsg) {
                errorMsg = document.createElement("p");
                errorMsg.id = "error-msg";
                errorMsg.style.color = "#ff6b6b";
                errorMsg.style.fontSize = "0.85rem";
                errorMsg.style.marginTop = "-15px";
                errorMsg.style.marginBottom = "15px";
                errorMsg.style.fontWeight = "600";
                errorMsg.innerText = "Password must be at least 6 characters long.";
                
                const passwordGroup = passwordInput.parentElement;
                passwordGroup.insertAdjacentElement("afterend", errorMsg);
            }
            
            // Add shake effect to container
            container.classList.remove("shake");
            void container.offsetWidth; // Trigger reflow to restart animation
            container.classList.add("shake");
            
            passwordInput.focus();
        }
    });
});

function createBanana(container) {
    const banana = document.createElement("div");
    banana.className = "floating-banana";
    banana.innerText = "🍌";
    
    // Randomize starting position, size, and animation duration
    const leftPos = Math.random() * 100;
    const animDuration = 10 + Math.random() * 15; // Between 10s and 25s
    const animDelay = Math.random() * 10;
    const size = 1 + Math.random() * 1.5; // Between 1rem and 2.5rem
    
    banana.style.left = `${leftPos}%`;
    banana.style.animationDuration = `${animDuration}s`;
    banana.style.animationDelay = `${animDelay}s`;
    banana.style.fontSize = `${size}rem`;
    
    container.appendChild(banana);
}