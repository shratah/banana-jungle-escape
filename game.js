let bananas = 0;
let lives = 3;
let correctAnswer = 0;

// Generate a random "puzzle" (number 1-10)
function loadPuzzle() {
    correctAnswer = Math.floor(Math.random() * 10) + 1;
    document.getElementById("bananaImage").src = 'https://via.placeholder.com/150?text=${correctAnswer}?';
}

function checkAnswer() {
    let userAnswer = document.getElementById("answer").value;

    if(userAnswer == correctAnswer) {
        bananas++;
        alert("You collected a banana! 🍌");
    } else {
        lives--;
        alert("Wrong! You lost a life ❤️");
    }

    document.getElementById("bananaCount").innerText = bananas;
    document.getElementById("lives").innerText = lives;

    if(lives <= 0) {
        alert("Game Over 💀");
        location.reload();
    }

    if(bananas >= 10) {
        alert("You Escaped! 🏆");
        location.reload();
    }

    document.getElementById("answer").value = ""; // Clear input
    loadPuzzle();
}

// Start the first puzzle
loadPuzzle();