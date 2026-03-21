// Game State Variables
let coins = 0;
let lives = 3;
let sessionCoins = 0;
let startTime = Date.now();
let isPaused = false;
let timerSeconds = 60;
let timerInterval = null;
let currentLevel = 1;

// Memory Game Variables
const cardValues = ['🍌', '🍌', '🍌🍌', '🍌🍌', '🍌🍌🍌', '🍌🍌🍌', '🍌🍌🍌🍌', '🍌🍌🍌🍌'];
let cards = [];
let hasFlippedCard = false;
let lockBoard = false;
let firstCard, secondCard;
let matchCount = 0;

// Initialize Game
async function initGame() {
    // Load state from server
    try {
        const statsResponse = await fetch('sync_stats.php');
        const statsData = await statsResponse.json();
        if (statsData.status === 'success') {
            lives = statsData.lives;
            coins = statsData.coins;
            currentLevel = statsData.level;

            // Apply Theme & Language
            if (statsData.theme) document.body.className = 'theme-' + statsData.theme;
            if (statsData.language) applyLanguage(statsData.language);
        }
    } catch (e) { console.error("Error syncing stats:", e); }

    updateUI();
    setupBoard();
    startTimer();
}

// Custom Toast Notification System
function showToast(message, type = 'info') {
    const container = document.getElementById('toastContainer');
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;

    let icon = 'ℹ️';
    if (type === 'success') icon = '✅';
    if (type === 'error') icon = '❌';

    toast.innerHTML = `<span>${icon}</span> <span>${message}</span>`;
    container.appendChild(toast);

    setTimeout(() => {
        toast.remove();
    }, 3000);
}

// Update DOM elements
function updateUI() {
    document.getElementById('coinCount').innerText = coins;
    document.getElementById('lives').innerText = lives;
    const levelEl = document.getElementById('currentLevel');
    if (levelEl) levelEl.innerText = currentLevel;
    syncStats();
}

function syncStats() {
    const formData = new FormData();
    formData.append('coins', coins);
    formData.append('lives', lives);
    formData.append('level', currentLevel);
    fetch('sync_stats.php', { method: 'POST', body: formData });
}

// Buy Life logic
function buyLife() {
    if (coins >= 100) {
        coins -= 100;
        lives++;
        updateUI();
        showToast("Purchased 1 ❤️ for 100 🪙!", "success");
    } else {
        const needed = 100 - coins;
        showToast(`Not enough coins! Need ${needed} more 🪙`, "error");
    }
}

// Shuffle array (Fisher-Yates)
function shuffle(array) {
    let currentIndex = array.length, randomIndex;
    while (currentIndex !== 0) {
        randomIndex = Math.floor(Math.random() * currentIndex);
        currentIndex--;
        [array[currentIndex], array[randomIndex]] = [array[randomIndex], array[currentIndex]];
    }
    return array;
}

// Setup Memory Board
function setupBoard() {
    const grid = document.getElementById('memoryGrid');
    grid.innerHTML = ''; // Clear board
    matchCount = 0;

    cards = shuffle([...cardValues]);

    cards.forEach((bananaVal, index) => {
        const cardElement = document.createElement('div');
        cardElement.classList.add('memory-card');
        cardElement.dataset.val = bananaVal;
        cardElement.dataset.index = index;

        cardElement.innerHTML = `
            <div class="memory-card-inner">
                <div class="memory-card-front">?</div>
                <div class="memory-card-back">${bananaVal}</div>
            </div>
        `;

        cardElement.addEventListener('click', flipCard);
        grid.appendChild(cardElement);
    });
}

// Card clicked
function flipCard(event) {
    if (lockBoard || isPaused) return; // Prevent clicking while paused
    const clickedCard = event.currentTarget;
    if (clickedCard === firstCard) return; // Prevent double click

    clickedCard.classList.add('flipped');

    if (!hasFlippedCard) {
        // First click
        hasFlippedCard = true;
        firstCard = clickedCard;
        return;
    }

    // Second click
    secondCard = clickedCard;
    checkForMatch();
}

// Check match logic
function checkForMatch() {
    let isMatch = firstCard.dataset.val === secondCard.dataset.val;

    if (isMatch) {
        disableCards();
        coins += 25; // Award coins
        sessionCoins += 25;
        updateUI();
        showToast("Match found! +25 🪙", "success");
        matchCount++;

        if (matchCount === 4) {
            setTimeout(() => {
                showToast("Board cleared! +50 🪙 bonus", "success");
                coins += 50;
                sessionCoins += 50;
                updateUI();
                timerSeconds += 10; // Extra time reward
                setTimeout(setupBoard, 1500);
            }, 1000);
        }

    } else {
        unflipCards();
    }
}

// If matched, disable clicks and highlight
function disableCards() {
    firstCard.removeEventListener('click', flipCard);
    secondCard.removeEventListener('click', flipCard);

    firstCard.classList.add('matched');
    secondCard.classList.add('matched');

    resetBoard();
}

// If no match, unflip after delay
function unflipCards() {
    lockBoard = true;

    setTimeout(() => {
        firstCard.classList.remove('flipped');
        secondCard.classList.remove('flipped');
        resetBoard();
    }, 1000);
}

function resetBoard() {
    [hasFlippedCard, lockBoard] = [false, false];
    [firstCard, secondCard] = [null, null];
}

// Save Session to Database
function saveSession() {
    const timeSpent = Math.floor((Date.now() - startTime) / 1000);
    const formData = new FormData();
    formData.append('game_type', 'minigame');
    formData.append('score', matchCount);
    formData.append('coins_earned', sessionCoins);
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

// Handle Page Visibility / Exit
window.addEventListener('beforeunload', () => {
    if (sessionCoins > 0 || matchCount > 0) {
        saveSession();
    }
});

// Timer Logic
function startTimer() {
    timerSeconds = 60;
    updateTimerUI();
    if (timerInterval) clearInterval(timerInterval);
    timerInterval = setInterval(() => {
        if (!isPaused) {
            timerSeconds--;
            updateTimerUI();
            if (timerSeconds <= 0) {
                stopTimer();
                showToast("Time's up! Session ended.", "error");
                saveSession();
                setTimeout(() => location.href = 'dashboard.php', 2000);
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
}

// Start game on load
window.onload = initGame;
