// Floating Banana Background Effect and form validation
document.addEventListener("DOMContentLoaded", () => {
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