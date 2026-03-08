// Floating Leaf Background Effect and form validation
document.addEventListener("DOMContentLoaded", () => {
    // Check for messages from PHP redirect
    const urlParams = new URLSearchParams(window.location.search);
    const container = document.querySelector(".register-container");
    if (urlParams.has('error')) {
        const error = urlParams.get('error');
        let msg = "Registration failed.";
        if (error === 'exists') msg = "Username or Email already exists.";
        else if (error === 'db') msg = "Database connection error.";
        
        const errorMsg = document.createElement("p");
        errorMsg.style.color = "#ff6b6b";
        errorMsg.style.fontWeight = "bold";
        errorMsg.style.marginBottom = "15px";
        errorMsg.innerText = msg;
        container.insertBefore(errorMsg, container.querySelector("form"));
    }

    const bgAnimation = document.getElementById("bg-animation");
    const numLeaves = 20;

    // Generate floating leaves dynamically
    for (let i = 0; i < numLeaves; i++) {
        createLeaf(bgAnimation);
    }

    // Form Validation Logic
    const registerForm = document.getElementById("registerForm");
    
    registerForm.addEventListener("submit", function(e) {
        let hasError = false;
        const passwordInput = document.getElementById("password");
        const password = passwordInput.value;
        const usernameInput = document.getElementById("username");
        const username = usernameInput.value;
        const container = document.querySelector(".register-container");

        // Clear existing error messages
        const existingErrors = document.querySelectorAll(".error-msg");
        existingErrors.forEach(msg => msg.remove());

        // Validate Username length
        if (username.length < 3) {
            hasError = true;
            showError(usernameInput, "Username must be at least 3 characters.");
        }

        // Validate Password length
        if (password.length < 6) {
            hasError = true;
            showError(passwordInput, "Password must be at least 6 characters.");
        }

        if (hasError) {
            e.preventDefault();
            
            // Add shake effect to container
            container.classList.remove("shake");
            void container.offsetWidth; // Trigger reflow to restart animation
            container.classList.add("shake");
        }
    });

    function showError(inputElement, message) {
        const errorMsg = document.createElement("p");
        errorMsg.className = "error-msg";
        errorMsg.style.color = "#ff6b6b";
        errorMsg.style.fontSize = "0.85rem";
        errorMsg.style.marginTop = "-20px";
        errorMsg.style.marginBottom = "20px";
        errorMsg.style.fontWeight = "600";
        errorMsg.style.textAlign = "left";
        errorMsg.innerText = message;
        
        const inputGroup = inputElement.parentElement;
        inputGroup.insertAdjacentElement("afterend", errorMsg);
    }
});

function createLeaf(container) {
    const leaf = document.createElement("div");
    leaf.className = "floating-leaf";
    
    // Choose randomly between a leaf or a banana for variety
    const isBanana = Math.random() > 0.7;
    leaf.innerText = isBanana ? "🍌" : "🌿";
    
    // Randomize starting position, size, and animation duration
    const leftPos = Math.random() * 100;
    const animDuration = 12 + Math.random() * 20; 
    const animDelay = Math.random() * 15;
    const size = 1 + Math.random() * 1.5; 
    
    leaf.style.left = `${leftPos}%`;
    leaf.style.animationDuration = `${animDuration}s`;
    leaf.style.animationDelay = `${animDelay}s`;
    leaf.style.fontSize = `${size}rem`;
    
    // Randomize horizontal drift
    if(Math.random() > 0.5) {
        leaf.style.animationName = "floatUpLeft";
    }
    
    container.appendChild(leaf);
}