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
let nextPuzzleData = null;
let isPreFetching = false;
let currentLevel = 1;
let puzzlesInLevel = 0;
let heartsLostInLevel = false;
let powerups = { magnet: 0, freeze: 0, rainbow: 0, lucky: 0 };
let activePowerups = { magnet: false, freeze: false, rainbow: false, lucky: false };

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

// Prefetch the next puzzle in the background
async function preFetchNextPuzzle() {
    if (isPreFetching || nextPuzzleData) return;
    isPreFetching = true;
    try {
        const response = await fetch('proxy.php');
        const data = await response.json();
        if (data.question && data.solution !== undefined) {
            // Preload the base64 image immediately
            const img = new Image();
            img.onload = () => {
                nextPuzzleData = data;
                isPreFetching = false;
            };
            img.src = data.question; // Data is already base64 data URL
        }
    } catch (e) {
        console.error("Prefetch error:", e);
        isPreFetching = false;
    }
}

// Display the puzzle (uses pre-fetched data if available)
async function loadPuzzle() {
    // Update UI elements
    const coinCounter = document.getElementById("coinCount");
    if (coinCounter) coinCounter.innerText = coins;
    const livesEl = document.getElementById("lives");
    if (livesEl) livesEl.innerText = lives;
    const levelEl = document.getElementById("currentLevel");
    if (levelEl) levelEl.innerText = currentLevel;
    const progressEl = document.getElementById("puzzleProgress");
    if (progressEl) progressEl.innerText = puzzlesInLevel + 1;

    if (isLoading) return;
    isLoading = true;

    const imgElement = document.getElementById("bananaImage");
    const loader = document.getElementById("loader");
    const inputField = document.getElementById("answer");

    // UI Loading state
    imgElement.style.display = 'none';
    loader.style.display = 'inline-block';
    inputField.disabled = true;

    // Use pre-fetched data if ready, otherwise fetch immediately
    let data = nextPuzzleData;
    nextPuzzleData = null; // Consume the cache

    if (!data) {
        try {
            const response = await fetch('proxy.php');
            data = await response.json();
        } catch (error) {
            console.error("Error fetching puzzle:", error);
            showToast("Failed to load puzzle. Retrying...", "error");
            isLoading = false;
            setTimeout(loadPuzzle, 2000);
            return;
        }
    }

    // Display the puzzle
    correctAnswer = data.solution;
    imgElement.src = data.question;
    imgElement.style.display = 'block';
    loader.style.display = 'none';
    inputField.disabled = false;
    inputField.focus();
    isLoading = false;
    startTimer();

    // Start fetching the NEXT one immediately in identifying background
    preFetchNextPuzzle();
}

// Initial stats load (one time only)
async function initialStatsLoad() {
    try {
        const statsResponse = await fetch('sync_stats.php');
        const statsData = await statsResponse.json();
        if (statsData.status === 'success') {
            coins = statsData.coins;
            lives = statsData.lives;
            currentLevel = statsData.level;
            powerups = {
                magnet: statsData.magnet || 0,
                freeze: statsData.freeze || 0,
                rainbow: statsData.rainbow || 0,
                lucky: statsData.lucky || 0
            };
            
            // Apply Theme & Language
            if (statsData.theme) document.body.className = 'theme-' + statsData.theme;
            if (statsData.language) applyLanguage(statsData.language);

            updatePowerupUI();
            document.getElementById("lives").innerText = lives;
            const coinCounter = document.getElementById("coinCount");
            if (coinCounter) coinCounter.innerText = coins;
        }
    } catch (e) { console.error("Error loading stats:", e); }
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
        puzzlesInLevel++;
        showToast("Correct! 🧩 Progress: " + puzzlesInLevel + "/3", "success");
        stopTimer();
        
        // Check if level is completed (after bonus game)
        if (puzzlesInLevel >= 3) {
            handleLevelCompletion();
        } else {
            startFallingGame(); // Start the bonus game!
        }
    } else {
        lives--;
        heartsLostInLevel = true;
        showToast(`Wrong! You lost a life ❤️`, "error");

        // Add shake animation
        const gameBox = document.getElementById('gameBox');
        gameBox.classList.add('shake');
        setTimeout(() => gameBox.classList.remove('shake'), 500);
    }

    // Update Stats UI & Sync with Server
    document.getElementById("bananaCount").innerText = bananas;
    document.getElementById("lives").innerText = lives;
    syncStats();

    answerInput.value = "";

    // Win/Loss Condition Check
    if (checkGameOver()) return;

    if (currentLevel > 10) {
        saveSession('main', bananas, 0);
        setTimeout(() => {
            alert("CONGRATULATIONS! 🏆 You've cleared all 10 levels of the Jungle!");
            location.href = 'dashboard.php';
        }, 500);
        return;
    }

    // Load next puzzle if game continues and not waiting for level animation
    if (puzzlesInLevel < 3) {
        loadPuzzle();
    }
}

function handleLevelCompletion() {
    const isPerfect = !heartsLostInLevel;
    showToast(`Level ${currentLevel} COMPLETED! 🏆`, "success");
    
    // Save session with level completion info
    saveSession('main', bananas, 0, currentLevel, isPerfect);

    // If perfect, award bonus coins
    if (isPerfect) {
        coins += 300;
        showToast("PERFECTIONIST! +300 🪙 Bonus!", "success");
        document.getElementById("coinCount").innerText = coins;
    }

    currentLevel++;
    puzzlesInLevel = 0;
    heartsLostInLevel = false;

    // Small delay before starting next level
    setTimeout(() => {
        if (!checkGameOver() && currentLevel <= 10) {
            loadPuzzle();
        }
    }, 2000);
}

// Allow pressing Enter to submit
function handleKeyPress(event) {
    if (event.key === "Enter") {
        checkAnswer();
    }
}

// Save Session to Database
function saveSession(type, score, coinsEarned, levelCompleted = 0, isPerfect = false) {
    stopTimer();
    const timeSpent = Math.floor((Date.now() - startTime) / 1000);
    const formData = new FormData();
    formData.append('game_type', type);
    formData.append('score', score);
    formData.append('coins_earned', coinsEarned);
    formData.append('time_spent', timeSpent);
    if (levelCompleted > 0) {
        formData.append('level_completed', levelCompleted);
        formData.append('perfect_level', isPerfect ? 'true' : 'false');
    }

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
    formData.append('level', currentLevel);
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
    
    // Disable/Enable input based on pause state
    const inputField = document.getElementById("answer");
    if (inputField) inputField.disabled = isPaused;
}

// Power-up Activation
function usePowerup(type) {
    if (powerups[type] > 0 && !activePowerups[type]) {
        powerups[type]--;
        activePowerups[type] = true;
        showToast(`ACTIVATED: ${type.toUpperCase()}! ⚡`, "success");
        updatePowerupUI();
        
        // Duration logic
        let duration = 5000; // 5 seconds
        if (type === 'freeze') duration = 5000;
        if (type === 'rainbow') duration = 8000;
        if (type === 'magnet') duration = 10000;

        setTimeout(() => {
            activePowerups[type] = false;
            showToast(`${type.toUpperCase()} expired.`, "info");
        }, duration);

        // Sync count to server
        const formData = new FormData();
        formData.append(type, powerups[type]);
        fetch('sync_stats.php', { method: 'POST', body: formData });
    } else {
        showToast("Don't have this power-up! Visit Shop.", "error");
    }
}

function updatePowerupUI() {
    const container = document.getElementById('powerupInventory');
    if (!container) return;
    container.innerHTML = '';
    
    for (const [type, count] of Object.entries(powerups)) {
        if (count > 0) {
            const btn = document.createElement('button');
            const icons = { magnet: '🧲', freeze: '⏸️', rainbow: '🌈', lucky: '🍀' };
            btn.innerHTML = `${icons[type]} ${count}`;
            btn.className = 'pwr-btn';
            btn.onclick = () => usePowerup(type);
            container.appendChild(btn);
        }
    }
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

    // Weighted random for types (Improved by Lucky Charm)
    const rand = Math.random();
    let type = 'coin';
    const luck = activePowerups.lucky ? 0.15 : 0; // Increase special item chance by 15%

    if (rand > 0.95 - luck) type = 'heart';
    else if (rand > 0.85 - luck) type = 'shield';
    else if (rand > 0.75 - luck) type = 'boost';

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

    // Physics & Interaction
    const gameLoop = setInterval(() => {
        if (isPaused) return;
        
        // Time Freeze Check
        if (!activePowerups.freeze) {
            posY += speed;
            obj.style.top = posY + 'px';
        }

        // Magnet Logic: Auto-collect if sufficiently close (within 100px)
        if (activePowerups.magnet) {
            // Check distance to cursor or just auto-pick if in lower half? 
            // Better: Auto-collect if posY is near the "catch" zone
            if (posY > window.innerHeight - 200) {
                catchObject(obj);
                clearInterval(gameLoop);
            }
        }

        // Cleanup if missed
        if (posY > window.innerHeight) {
            obj.remove();
            clearInterval(gameLoop);
        }
    }, 1000 / 60);

    obj.onmouseover = () => {
        catchObject(obj);
        clearInterval(gameLoop);
    };
}

function catchObject(obj) {
    const type = obj.dataset.type;
    obj.remove();

    if (type === 'coin') {
        let val = 10;
        if (activePowerups.rainbow) val = 20;
        coins += val;
        showToast(`+${val} 🪙`, "success");
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
window.onload = async () => {
    await initialStatsLoad();
    loadPuzzle();
};