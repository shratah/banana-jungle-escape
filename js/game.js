let bananas = 0;
let lives = 3;
let coins = 0;
let correctAnswer = 0;
let isLoading = false;

// Custom Toast Notification System
function showToast(message, type = 'info') {
    const container = document.getElementById('toastContainer');
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    
    // Icon based on type
    let icon = 'ℹ️';
    if(type === 'success') icon = '✅';
    if(type === 'error') icon = '❌';

    toast.innerHTML = `<span>${icon}</span> <span>${message}</span>`;
    container.appendChild(toast);

    // Remove toast after animation completes (3s total)
    setTimeout(() => {
        toast.remove();
    }, 3000);
}

// Fetch a puzzle from the Banana API
async function loadPuzzle() {
    // Load persisted state
    const savedLives = localStorage.getItem('jungleLives');
    const savedCoins = localStorage.getItem('jungleCoins');
    if (savedLives !== null) lives = parseInt(savedLives);
    if (savedCoins !== null) coins = parseInt(savedCoins);
    
    // Update newly added UI elements
    const coinCounter = document.getElementById("coinCount");
    if (coinCounter) coinCounter.innerText = coins;
    document.getElementById("lives").innerText = lives;

    if (isLoading) return;
    isLoading = true;
    
    const imgElement = document.getElementById("bananaImage");
    const loader = document.getElementById("loader");
    const inputField = document.getElementById("answer");
    
    // UI Loading state
    imgElement.style.display = 'none';
    loader.style.display = 'inline-block';
    inputField.disabled = true;

    try {
        const response = await fetch('http://marcconrad.com/uob/banana/api.php?out=json');
        const data = await response.json();
        
        // The API returns { question: "url", solution: number }
        correctAnswer = data.solution;
        
        // Preload image to avoid flicker
        const img = new Image();
        img.onload = () => {
            imgElement.src = data.question;
            imgElement.style.display = 'block';
            loader.style.display = 'none';
            inputField.disabled = false;
            inputField.focus();
            isLoading = false;
        };
        img.src = data.question;

    } catch (error) {
        console.error("Error fetching puzzle:", error);
        showToast("Failed to load puzzle. Retrying...", "error");
        isLoading = false;
        setTimeout(loadPuzzle, 2000); // Retry after 2 seconds
    }
}

// Check user answer
function checkAnswer() {
    if (isLoading) return;
    
    let answerInput = document.getElementById("answer");
    let userAnswer = answerInput.value.trim();

    if (userAnswer === "") {
        showToast("Please enter an answer!", "info");
        return;
    }

    if(parseInt(userAnswer) === correctAnswer) {
        bananas++;
        showToast("Correct! You collected a banana! 🍌", "success");
    } else {
        lives--;
        showToast(`Wrong! You lost a life ❤️`, "error");
        
        // Add shake animation
        const gameBox = document.getElementById('gameBox');
        gameBox.classList.add('shake');
        setTimeout(() => gameBox.classList.remove('shake'), 500);
    }

    // Update Stats UI & Local Storage
    document.getElementById("bananaCount").innerText = bananas;
    document.getElementById("lives").innerText = lives;
    localStorage.setItem('jungleLives', lives);

    answerInput.value = ""; 

    // Win/Loss Condition Check
    if(lives <= 0) {
        setTimeout(() => {
            alert("Game Over 💀 The jungle got you!");
            location.reload();
        }, 500);
        return;
    }

    if(bananas >= 10) {
        setTimeout(() => {
            alert("You Escaped! 🏆 Great job solving the puzzles!");
            location.reload();
        }, 500);
        return;
    }

    // Load next puzzle if game continues
    loadPuzzle();
}

// Allow pressing Enter to submit
function handleKeyPress(event) {
    if (event.key === "Enter") {
        checkAnswer();
    }
}

// Start the first puzzle when script loads
window.onload = loadPuzzle;