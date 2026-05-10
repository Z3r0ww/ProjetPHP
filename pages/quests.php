<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_class_chosen();

$pdo = get_pdo();
$uid = $_SESSION['user_id'];
$today = date('Y-m-d');

// --- SYSTÈME DE PÉNALITÉ ANTI-FRAUDE ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['fraud'])) {
    $penalty_gold = 150;
    $pdo->prepare("UPDATE users SET gold = GREATEST(0, gold - ?) WHERE id = ?")
        ->execute([$penalty_gold, $uid]);
    header("Location: quests.php?error=fraud"); 
    exit;
}

// --- VALIDATION DE LA QUÊTE ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['quest_id'])) {
    $qid = (int)$_POST['quest_id'];
    $pdo->prepare("INSERT INTO user_quests (user_id, quest_id, completed_at) VALUES (?, ?, ?)")
        ->execute([$uid, $qid, $today]);
    add_xp($pdo, $uid, 300);
    header("Location: quests.php?msg=success"); 
    exit;
}

$stmt = $pdo->prepare("
    SELECT q.*, (SELECT 1 FROM user_quests uq WHERE uq.quest_id = q.id AND uq.user_id = ? AND uq.completed_at = ?) as done
    FROM quests q WHERE q.class = ? OR q.class = 'all' LIMIT 4
");
$stmt->execute([$uid, $today, $_SESSION['class']]);
$quests = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Missions — Sport RPG</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@700&family=Rajdhani:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <style>
        .progress-ring__circle { transition: stroke-dashoffset 0.3s; transform: rotate(-90deg); transform-origin: 50% 50%; }
        .dashboard-page { background: #04060e; min-height: 100vh; color: white; font-family: 'Rajdhani', sans-serif; }
    </style>
</head>
<body class="dashboard-page">
    <?php include __DIR__ . '/../includes/background.php'; ?>
    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <main class="relative z-10 max-w-6xl mx-auto pt-32 px-6 pb-20">
        <?php if(isset($_GET['error']) && $_GET['error'] === 'fraud'): ?>
            <div class="bg-red-500/20 border border-red-500 text-red-500 p-4 rounded-xl mb-8 text-center font-bold animate-pulse uppercase tracking-widest">
                ⚠️ ALERTE : TENTATIVE DE FRAUDE DÉTECTÉE.
            </div>
        <?php endif; ?>

        <div class="text-center mb-16">
            <h1 class="font-['Cinzel'] text-4xl text-white tracking-[0.3em] uppercase italic">Missions <span class="text-blue-500">Journalières</span></h1>
        </div>

        <div class="grid md:grid-cols-2 gap-8">
            <?php foreach($quests as $i => $q): 
                // Temps dynamique selon la quête (plus longue si c'est la dernière)
                $timeNeeded = ($i === 3) ? 120 : (($i === 2) ? 60 : 30);
            ?>
                <div class="relative group">
                    <div class="absolute -inset-0.5 bg-gradient-to-r from-blue-600 to-cyan-600 rounded-[2rem] opacity-20 group-hover:opacity-40 blur transition duration-500"></div>
                    <div class="relative bg-[#0d1526]/80 backdrop-blur-xl border border-white/10 p-8 rounded-[2rem] h-full flex flex-col justify-between <?= $q['done'] ? 'grayscale opacity-30 pointer-events-none' : '' ?>">
                        <div>
                            <h2 class="font-['Cinzel'] text-xl text-white uppercase italic tracking-wider"><?= htmlspecialchars($q['name']) ?></h2>
                            <p class="text-slate-400 text-sm italic my-6 leading-relaxed">"<?= htmlspecialchars($q['description']) ?>"</p>
                        </div>
                        
                        <div class="flex justify-between items-center pt-6 border-t border-white/5">
                            <span class="text-[10px] text-blue-400 font-bold uppercase tracking-widest">⏱ <?= $timeNeeded ?>s Effort</span>
                            <?php if(!$q['done']): ?>
                                <button onclick="startMission(<?= $q['id'] ?>, <?= $timeNeeded ?>, '<?= addslashes($q['name']) ?>')" 
                                        class="bg-blue-600 hover:bg-blue-500 text-white px-8 py-2 rounded-xl text-[10px] font-bold uppercase tracking-widest shadow-lg transition-all">
                                    DÉMARRER
                                </button>
                            <?php else: ?>
                                <span class="text-green-500 font-bold text-[10px] uppercase tracking-widest italic">Accompli ✓</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </main>

    <div id="vModal" class="fixed inset-0 z-[5000] hidden items-center justify-center bg-black/95 backdrop-blur-2xl p-4">
        <div class="bg-[#0d1526] border border-white/10 p-10 rounded-[3rem] text-center max-w-sm w-full relative shadow-[0_0_50px_rgba(59,130,246,0.2)]">
            <h3 id="vTitle" class="font-['Cinzel'] text-xl text-white mb-8 italic uppercase tracking-widest"></h3>
            
            <div class="relative inline-block mb-8">
                <svg width="140" height="140">
                    <circle class="text-white/5" stroke-width="6" stroke="currentColor" fill="transparent" r="60" cx="70" cy="70" />
                    <circle id="vCircle" class="text-blue-500 progress-ring__circle" stroke-width="6" stroke-dasharray="377" stroke-dashoffset="377" stroke-linecap="round" stroke="currentColor" fill="transparent" r="60" cx="70" cy="70" />
                </svg>
                <div id="vTimer" class="absolute inset-0 flex items-center justify-center text-3xl font-['Cinzel'] text-white">0s</div>
            </div>

            <div class="space-y-4">
                <button id="vBtn" disabled class="w-full py-4 bg-white/5 text-white/20 rounded-2xl font-bold uppercase text-[10px] tracking-widest transition-all italic">Concentration...</button>
                <button onclick="closeMission()" class="w-full py-2 text-slate-500 hover:text-red-400 text-[9px] uppercase tracking-[0.3em] transition-colors">Abandonner</button>
            </div>
        </div>
    </div>

    <form id="vForm" method="POST" class="hidden"><input type="hidden" name="quest_id" id="vInput"></form>
    <form id="fForm" method="POST" class="hidden"><input type="hidden" name="fraud" value="1"></form>

    <script>
        let startTime, timerInterval;
        function startMission(id, time, name) {
            startTime = Date.now();
            document.getElementById('vTitle').innerText = name;
            document.getElementById('vModal').classList.replace('hidden', 'flex');
            let s = 0;
            const circle = document.getElementById('vCircle');
            timerInterval = setInterval(() => {
                s++;
                document.getElementById('vTimer').innerText = s + "s";
                circle.style.strokeDashoffset = 377 - (s / time) * 377;
                if(s >= time) {
                    const btn = document.getElementById('vBtn');
                    btn.disabled = false;
                    btn.innerText = "VALIDER LA MISSION";
                    btn.classList.replace('bg-white/5', 'bg-blue-600');
                    btn.classList.replace('text-white/20', 'text-white');
                    btn.onclick = () => {
                        if((Date.now() - startTime)/1000 < time) document.getElementById('fForm').submit();
                        else { document.getElementById('vInput').value = id; document.getElementById('vForm').submit(); }
                    };
                    clearInterval(timerInterval);
                }
            }, 1000);
        }
        function closeMission() {
            clearInterval(timerInterval);
            document.getElementById('vModal').classList.replace('flex', 'hidden');
        }
    </script>
</body>
</html>