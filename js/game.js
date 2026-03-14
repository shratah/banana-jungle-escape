let bananas = 0;
let lives = 3;
let coins = 0;
let correctAnswer = 0;
let isLoading = false;
let startTime = Date.now();
let isPaused = false;
let timerSeconds = 30;
let timerInterval = null;
let shieldActive = false;

// Custom Toast Notification System
function showToast(message, type = 'info') {
    const container = document.getElementById('toastContainer');
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;

    // Icon based on type
    let icon = 'ℹ️';
    if (type === 'success') icon = '✅';
    if (type === 'error') icon = '❌';

    toast.innerHTML = `<span>${icon}</span> <span>${message}</span>`;
    container.appendChild(toast);

    // Remove toast after animation completes (3s total)
    setTimeout(() => {
        toast.remove();
    }, 3000);
}

// Fetch a puzzle from the Banana API
async function loadPuzzle() {
    // Load persisted state from server instead of localStorage
    try {
        const statsResponse = await fetch('sync_stats.php');
        const statsData = await statsResponse.json();
        if (statsData.status === 'success') {
            lives = statsData.lives;
            coins = statsData.coins;
        }
    } catch (e) { console.error("Error syncing stats:", e); }

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
        const response = await fetch('proxy.php');
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
            startTimer();
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

    if (parseInt(userAnswer) === correctAnswer) {
        bananas++;
        showToast("Correct! You collected a banana! 🍌", "success");
        stopTimer();
        startFallingGame(); // Start the bonus game!
    } else {
        lives--;
        showToast(`Wrong! You lost a life ❤️`, "error");

        // Add shake animation
        const gameBox = document.getElementById('gameBox');
        gameBox.classList.add('shake');
        setTimeout(() => gameBox.classList.remove('shake'), 500);
    }

    // Update Stats UI & Sync with Server
    document.getElementById("bananaCount").innerText = bananas;
    document.getElementById("lives").innerText = lives;
    syncStats(); // Save to database instead of localStorage

    answerInput.value = "";

    // Win/Loss Condition Check
    if (lives <= 0) {
        saveSession('main', bananas, 0);
        setTimeout(() => {
            alert("Game Over 💀 The jungle got you!");
            location.href = 'dashboard.php';
        }, 500);
        return;
    }

    if (bananas >= 10) {
        saveSession('main', bananas, 0);
        setTimeout(() => {
            alert("You Escaped! 🏆 Great job solving the puzzles!");
            location.href = 'dashboard.php';
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

// Save Session to Database
function saveSession(type, score, coinsEarned) {
    stopTimer();
    const timeSpent = Math.floor((Date.now() - startTime) / 1000);
    const formData = new FormData();
    formData.append('game_type', type);
    formData.append('score', score);
    formData.append('coins_earned', coinsEarned);
    formData.append('time_spent', timeSpent);

    fetch('save_score.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success' && data.achievements.length > 0) {
                showToast(`Unlocked Achievements: ${data.achievements.join(', ')}! 🌟`, 'success');
            }
        })
        .catch(err => console.error('Error saving session:', err));
}

// Sync current stats to DB
function syncStats() {
    const formData = new FormData();
    formData.append('coins', coins);
    formData.append('lives', lives);
    fetch('sync_stats.php', { method: 'POST', body: formData });
}

// Timer Logic
function startTimer() {
    timerSeconds = 30;
    updateTimerUI();
    if (timerInterval) clearInterval(timerInterval);
    timerInterval = setInterval(() => {
        if (!isPaused) {
            timerSeconds--;
            updateTimerUI();
            if (timerSeconds <= 0) {
                stopTimer();
                lives--;
                showToast("Time's up! ❤️ lost", "error");
                syncStats();
                checkGameOver();
                loadPuzzle();
            }
        }
    }, 1000);
}

function stopTimer() {
    if (timerInterval) clearInterval(timerInterval);
}

function updateTimerUI() {
    const timerEl = document.getElementById('gameTimer');
    if (timerEl) timerEl.innerText = timerSeconds + "s";
}

// Pause Game
function togglePause() {
    isPaused = !isPaused;
    const pauseOverlay = document.getElementById('pauseOverlay');
    if (pauseOverlay) pauseOverlay.style.display = isPaused ? 'flex' : 'none';
    const inputField = document.getElementById("answer");
    if (inputField) inputField.disabled = isPaused;
}

function checkGameOver() {
    if (lives <= 0) {
        saveSession('main', bananas, 0);
        setTimeout(() => {
            alert("Game Over 💀 The jungle got you!");
            location.href = 'dashboard.php';
        }, 500);
        return true;
    }
    return false;
}

// Falling Game Bonus Mechanic
function startFallingGame() {
    const container = document.getElementById('fallingGameContainer');
    container.innerHTML = '';
    const objectsCount = 12;
    let fallbackCount = 0;

    const spawnInterval = setInterval(() => {
        if (isPaused) return;
        createFallingObject();
        fallbackCount++;
        if (fallbackCount >= objectsCount) {
            clearInterval(spawnInterval);
            setTimeout(() => {
                if (!checkGameOver()) loadPuzzle();
            }, 5000);
        }
    }, 400);
}

function createFallingObject() {
    const container = document.getElementById('fallingGameContainer');
    const obj = document.createElement('div');
    obj.className = 'falling-object';

    // Weighted random for types
    const rand = Math.random();
    let type = 'coin';
    if (rand > 0.95) type = 'heart';
    else if (rand > 0.85) type = 'shield';
    else if (rand > 0.75) type = 'boost';

    const symbols = {
        'coin': '🪙',
        'heart': '❤️',
        'shield': '🛡️',
        'boost': '🍌'
    };

    obj.innerText = symbols[type];
    obj.dataset.type = type;

    const posX = Math.random() * (window.innerWidth - 50);
    obj.style.left = posX + 'px';
    obj.style.top = '-50px';

    container.appendChild(obj);

    let posY = -50;
    const speed = 2 + Math.random() * 3;

    const fall = () => {
        if (!isPaused) {
            posY += speed;
            obj.style.top = posY + 'px';
        }

        if (posY < window.innerHeight) {
            requestAnimationFrame(fall);
        } else {
            obj.remove();
        }
    };

    obj.onmouseover = () => {
        catchObject(obj);
    };

    requestAnimationFrame(fall);
}

function catchObject(obj) {
    const type = obj.dataset.type;
    obj.remove();

    if (type === 'coin') {
        coins += 10;
        showToast("+10 🪙", "success");
    } else if (type === 'heart') {
        lives++;
        showToast("Extra Life! ❤️", "success");
    } else if (type === 'shield') {
        shieldActive = true;
        showToast("Shield Active! 🛡️", "info");
    } else if (type === 'boost') {
        bananas++;
        showToast("Banana Boost! 🍌", "success");
    }

    syncStats();
    document.getElementById("coinCount").innerText = coins;
    document.getElementById("lives").innerText = lives;
    document.getElementById("bananaCount").innerText = bananas;
}

// Start the first puzzle when script loads
window.onload = loadPuzzle;