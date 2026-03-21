const translations = {
    en: {
        game_title: "🍌 Banana Escape",
        coins: "Coins",
        lives: "Lives",
        bananas: "Bananas",
        level: "Level",
        puzzle: "Puzzle",
        timer: "Timer",
        minigame: "Mini-Game",
        dashboard: "Dashboard",
        logout: "Logout",
        submit: "Submit",
        pause: "Pause",
        resume: "Resume",
        escape: "⬅️ Escape",
        dash: "📊 Dash",
        jungle_match: "🃏 Jungle Match",
        buy_life: "Buy ❤️ (100🪙)",
        match_instruction: "Find matching pairs to earn coins!",
        welcome: "Welcome back",
        summary: "Summary",
        history: "Recent History",
        leaderboard: "Global Top 10",
        achievements: "Achievements",
        shop: "Jungle Shop",
        total_score: "Total Score",
        total_coins: "Total Coins",
        games_played: "Games Played",
        time_spent: "Time Spent",
        buy_powerup: "Buy Power-up",
        unlock_now: "Unlock Now",
        correct: "Correct!",
        wrong: "Wrong!",
        game_over: "Game Over 💀",
        escaped: "You Escaped! 🏆",
        level_completed: "Level Completed! 🏆",
        game_paused: "Game Paused ⏸️"
    },
    ta: {
        game_title: "🍌 வாழைப்பழ் எஸ்கேப்",
        coins: "நாணயங்கள்",
        lives: "உயிர்கள்",
        bananas: "வாழைப்பழங்கள்",
        level: "நிலை",
        puzzle: "புதிர்",
        timer: "நேரம்",
        minigame: "மினி-கேம்",
        dashboard: "டாஷ்போர்டு",
        logout: "வெளியேறு",
        submit: "சமர்ப்பி",
        pause: "நிறுத்து",
        resume: "தொடர்",
        escape: "⬅️ தப்பி",
        dash: "📊 டேஷ்",
        jungle_match: "🃏 ஜங்கிள் மேட்ச்",
        buy_life: "உயிர் வாங்கு ❤️ (100🪙)",
        match_instruction: "நாணயங்களை சம்பாதிக்க பொருந்தும் ஜோடிகளை கண்டுபிடிக்கவும்!",
        welcome: "நல்வரவு",
        summary: "சுருக்கம்",
        history: "சமீபத்திய வரலாறு",
        leaderboard: "உலகளாவிய டாப் 10",
        achievements: "சாதனைகள்",
        shop: "காட்டு கடை",
        total_score: "மொத்த மதிப்பெண்",
        total_coins: "மொத்த நாணயங்கள்",
        games_played: "விளையாடிய விளையாட்டுகள்",
        time_spent: "செலவழித்த நேரம்",
        buy_powerup: "சக்தி-அப் வாங்கு",
        unlock_now: "இப்போது திற",
        correct: "சரி! ✅",
        wrong: "தவறு! ❌",
        game_over: "விளையாட்டு முடிந்தது 💀",
        escaped: "நீங்கள் தப்பித்துவிட்டீர்கள்! 🏆",
        level_completed: "நிலை முடிந்தது! 🏆",
        game_paused: "விளையாட்டு நிறுத்தப்பட்டது ⏸️"
    }
};

function applyLanguage(lang) {
    const t = translations[lang] || translations.en;
    
    // Select elements by data-t attribute or common IDs
    document.querySelectorAll('[data-t]').forEach(el => {
        const key = el.getAttribute('data-t');
        if (t[key]) el.innerText = t[key];
    });

    // Handle placeholders
    document.querySelectorAll('[data-t-placeholder]').forEach(el => {
        const key = el.getAttribute('data-t-placeholder');
        if (t[key]) el.placeholder = t[key];
    });
}
