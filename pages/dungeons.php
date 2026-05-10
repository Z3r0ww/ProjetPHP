<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_login();

$pdo = get_pdo();
$uid = $_SESSION['user_id'];
$today = date('Y-m-d');

// --- SYSTÈME DE PÉNALITÉ ANTI-FRAUDE ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['fraud_detected'])) {
    $penalty_xp = 500;
    $penalty_gold = 200;
    $pdo->prepare("UPDATE users SET experience = GREATEST(0, experience - ?), gold = GREATEST(0, gold - ?) WHERE id = ?")
        ->execute([$penalty_xp, $penalty_gold, $uid]);
    header("Location: dungeons.php?error=fraud");
    exit;
}

// 1. Vérifier si un donjon a déjà été fait aujourd'hui
$check_stmt = $pdo->prepare("SELECT 1 FROM user_dungeons WHERE user_id = ? AND completed_at = ?");
$check_stmt->execute([$uid, $today]);
$dungeon_done_today = (bool)$check_stmt->fetch();

// Récupérer les infos du joueur
$stmt = $pdo->prepare("SELECT level, `rank`, gold FROM users WHERE id = ?");
$stmt->execute([$uid]);
$user = $stmt->fetch();

$user_rank = $user['rank'];
$rank_values = ['E' => 1, 'D' => 2, 'C' => 3, 'B' => 4, 'A' => 5, 'S' => 6];
$user_rank_val = $rank_values[$user_rank] ?? 1;

$selected_rank = $_GET['rank'] ?? 'E';
if (!isset($rank_values[$selected_rank])) $selected_rank = 'E';
$dungeon_rank_val = $rank_values[$selected_rank];

// Calcul de la difficulté
$diff_multiplier = ($user_rank_val < $dungeon_rank_val) ? 1 + ($dungeon_rank_val - $user_rank_val) * 0.5 : 1;

$rank_configs = [
    'E' => ['id' => 1, 'name' => 'Grotte des Gobelins', 'color' => '#9ca3af', 'xp' => 200,  'gold' => 50,  'base' => 10],
    'D' => ['id' => 2, 'name' => 'Crypte des Morts',   'color' => '#2dd4bf', 'xp' => 400,  'gold' => 120, 'base' => 20],
    'C' => ['id' => 3, 'name' => 'Antre des Araignées','color' => '#60a5fa', 'xp' => 800,  'gold' => 250, 'base' => 30],
    'B' => ['id' => 4, 'name' => 'Forêt des Ogres',    'color' => '#4ade80', 'xp' => 1500, 'gold' => 500, 'base' => 45],
    'A' => ['id' => 5, 'name' => 'Pic des Dragons',    'color' => '#ff8c00', 'xp' => 3000, 'gold' => 1200,'base' => 60],
    'S' => ['id' => 6, 'name' => 'Château du Monarque','color' => '#ff4444', 'xp' => 7000, 'gold' => 3000,'base' => 100],
];

$config = $rank_configs[$selected_rank];
$stages = [
    ['name' => 'Garde de l\'Entrée', 'task' => round($config['base'] * $diff_multiplier) . ' Pompes', 'desc' => "L'ennemi te barre la route. Ne le laisse pas respirer."],
    ['name' => 'Embuscade', 'task' => round($config['base'] * 1.5 * $diff_multiplier) . ' Squats', 'desc' => "Ils t'encerclent ! Garde tes appuis solides."],
    ['name' => 'Troupe d\'Élite', 'task' => round($config['base'] * 1.2 * $diff_multiplier) . ' Fentes', 'desc' => "Des adversaires plus robustes approchent. Brise leur posture."],
    ['name' => 'Sous-Chef', 'task' => round(30 * $dungeon_rank_val * $diff_multiplier) . ' sec Gainage', 'desc' => "Une force écrasante pèse sur toi. Résiste de tout ton corps !"],
    ['name' => 'BOSS DU DONJON', 'task' => round(($config['base'] / 2) * $diff_multiplier) . ' Burpees', 'desc' => "Le maître des lieux est là. Donne tout ce qu'il te reste !"],
];

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$dungeon_done_today) {
    // Note: Ajout de dungeon_rank pour le dashboard
    $pdo->prepare("INSERT INTO user_dungeons (user_id, dungeon_id, completed_at, dungeon_rank) VALUES (?, ?, ?, ?)")
        ->execute([$uid, $config['id'], $today, $selected_rank]);
    
    $pdo->prepare("UPDATE users SET gold = gold + ? WHERE id = ?")->execute([$config['gold'], $uid]);
    add_xp($pdo, $uid, $config['xp']);
    
    $message = "PORTAIL FERMÉ ! +{$config['xp']} XP et +{$config['gold']} Gold 🪙";
    $dungeon_done_today = true;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Donjons — Sport RPG</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600;800&family=Rajdhani:wght@400;600;700&display=swap" rel="stylesheet">
    <script>tailwind.config = { theme: { extend: { colors: { accent: '#4f8aff', gold: '#f0a93a' }, fontFamily: { title: ['Cinzel', 'serif'] } } } }</script>
</head>
<body class="dashboard-page">
    <?php include __DIR__ . '/../includes/background.php'; ?>
    
    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <main class="relative z-10" style="padding-top: 100px; max-width: 900px; margin: 0 auto; padding-left: 20px; padding-right: 20px;">
        
        <?php if(isset($_GET['error']) && $_GET['error'] === 'fraud'): ?>
            <div class="bg-red-500/20 border border-red-500 text-red-500 p-4 rounded-xl mb-8 text-center font-bold animate-pulse uppercase tracking-widest">
                ⚠️ Alerte Système : Fraude Détectée. Pénalité Appliquée.
            </div>
        <?php endif; ?>

        <div class="text-center mb-10">
            <h1 class="font-title text-4xl font-bold text-white mb-4 tracking-widest">PORTAILS DE DONJONS</h1>
            
            <?php if(!$dungeon_done_today): ?>
                <div id="rankSelector" class="flex justify-center gap-2 mb-6">
                    <?php foreach($rank_values as $r => $v): ?>
                    <a href="?rank=<?= $r ?>" class="rank-btn w-12 h-12 flex items-center justify-center rounded-lg border-2 font-bold transition <?= $selected_rank === $r ? 'bg-white text-black border-white shadow-[0_0_15px_rgba(255,255,255,0.5)]' : 'text-slate-500 border-slate-800 hover:border-slate-500' ?>">
                        <?= $r ?>
                    </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($message): ?>
            <div class="bg-green-500/20 border border-green-500/50 text-green-400 p-4 rounded-xl mb-8 text-center font-bold tracking-widest"><?= $message ?></div>
        <?php endif; ?>

        <?php if($dungeon_done_today && !$message): ?>
            <div class="bg-blue-500/10 border border-blue-500/30 text-blue-300 p-6 rounded-2xl text-center font-bold tracking-widest uppercase shadow-lg">
                ⚔️ Ton corps a besoin de repos. Reviens demain pour explorer un autre portail.
            </div>
        <?php endif; ?>

        <div class="bg-[rgba(13,21,38,0.7)] backdrop-blur-md rounded-3xl p-8 border-2 shadow-2xl transition-all <?= $dungeon_done_today ? 'opacity-30 pointer-events-none' : '' ?>" style="border-color: <?= $config['color'] ?>;">
            <div class="flex justify-between items-start mb-8">
                <div>
                    <h2 class="font-title text-3xl text-white mb-2"><?= strtoupper($config['name']) ?></h2>
                    <span class="px-4 py-1 rounded-full text-xs font-bold uppercase" style="background: <?= $config['color'] ?>; color: black;">RANG <?= $selected_rank ?></span>
                </div>
                <div class="text-right">
                    <div class="text-gold font-bold">+<?= $config['xp'] ?> XP</div>
                    <div class="text-yellow-400 font-bold">+<?= $config['gold'] ?> Gold 🪙</div>
                </div>
            </div>

            <div class="space-y-4 mb-10 relative">
                <div class="absolute left-[24px] top-4 bottom-4 w-1 bg-white/5 -z-10"></div>
                
                <?php foreach ($stages as $i => $s): 
                    $is_first = ($i === 0);
                    $base_classes = "stage-item flex justify-between items-center bg-black/60 p-4 rounded-xl border-2 transition-all duration-300 ";
                    $active_classes = $is_first ? "border-accent/50 cursor-pointer hover:bg-accent/10 shadow-[0_0_15px_rgba(79,138,255,0.2)]" : "border-transparent opacity-40 pointer-events-none";
                ?>
                <div id="stage-<?= $i ?>" class="<?= $base_classes . $active_classes ?>" 
                     onclick="openCombat(<?= $i ?>, '<?= htmlspecialchars(addslashes($s['name'])) ?>', '<?= $s['task'] ?>', '<?= htmlspecialchars(addslashes($s['desc'])) ?>')">
                    
                    <div class="flex items-center gap-4">
                        <div class="check-box w-8 h-8 rounded-full border-2 border-slate-600 flex items-center justify-center font-bold text-lg bg-[#04060e] transition">
                            <?= $is_first ? '<span class="animate-pulse text-accent">!</span>' : '' ?>
                        </div>
                        <div>
                            <span class="text-slate-300 font-bold block"><?= $i + 1 ?>. <?= $s['name'] ?></span>
                        </div>
                    </div>
                    <span class="font-bold text-accent text-right bg-accent/10 px-3 py-1 rounded-lg"><?= $s['task'] ?></span>
                </div>
                <?php endforeach; ?>
            </div>

            <form method="POST" id="claimForm">
                <button type="button" id="claimBtn" disabled class="w-full py-4 rounded-2xl font-title tracking-widest font-bold transition duration-300 opacity-20 bg-slate-700 text-slate-400 cursor-not-allowed uppercase">
                    Ferme toutes les brèches
                </button>
            </form>
        </div>
    </main>

    <div id="combatModal" class="fixed inset-0 z-[2000] hidden items-center justify-center bg-black/90 backdrop-blur-sm px-4">
        <div class="relative w-full max-w-md bg-[rgba(13,21,38,0.95)] border-2 border-red-500/50 rounded-3xl p-8 text-center overflow-hidden shadow-[0_0_50px_rgba(239,68,68,0.3)]">
            <div class="absolute top-0 left-0 h-1 bg-red-600 transition-all duration-1000" id="modalProgress" style="width: 0%"></div>
            
            <div class="relative z-10">
                <div class="text-red-500 text-sm font-bold tracking-widest uppercase mb-2">Alerte Système</div>
                <h3 id="modalName" class="font-title text-3xl text-white mb-2">...</h3>
                <p id="modalDesc" class="text-slate-400 text-sm mb-8 italic">...</p>
                
                <div class="bg-black/50 border border-red-500/30 rounded-2xl p-6 mb-8">
                    <div class="text-xs text-red-400 uppercase tracking-widest font-bold mb-2">Objectif de Survie</div>
                    <div id="modalTask" class="text-4xl font-black text-red-500 font-title">...</div>
                    <div id="modalTimer" class="mt-2 text-white font-bold">Analyse : <span id="secDisplay">0</span>s</div>
                </div>

                <button id="modalConfirmBtn" disabled class="w-full py-4 bg-white/5 border border-white/10 text-white/20 font-title tracking-widest font-bold rounded-xl transition duration-300">
                    SURVEILLANCE EN COURS...
                </button>
                <button onclick="closeModal()" class="mt-6 text-slate-500 text-xs uppercase tracking-widest hover:text-white transition">
                    Fuir (Annuler)
                </button>
            </div>
        </div>
    </div>

    <form id="fraudForm" method="POST" class="hidden">
        <input type="hidden" name="fraud_detected" value="1">
    </form>

    <script>
        let currentActiveStage = 0;
        const totalStages = 5;
        let combatStartTime;
        const minTimeRequired = 10; // 10 secondes minimum par exercice

        function openCombat(index, name, task, desc) {
            if (index !== currentActiveStage) return;
            
            // Verrouiller la sélection de rang
            const rankSelector = document.getElementById('rankSelector');
            if(rankSelector) rankSelector.classList.add('pointer-events-none', 'opacity-20');

            combatStartTime = Date.now();
            document.getElementById('modalName').innerText = name.toUpperCase();
            document.getElementById('modalTask').innerText = task;
            document.getElementById('modalDesc').innerText = desc;
            
            const modal = document.getElementById('combatModal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');

            // Reset Timer UI
            const btn = document.getElementById('modalConfirmBtn');
            btn.disabled = true;
            btn.innerText = "SURVEILLANCE EN COURS...";
            btn.className = "w-full py-4 bg-white/5 border border-white/10 text-white/20 font-title tracking-widest font-bold rounded-xl transition duration-300";
            
            let elapsed = 0;
            const timerInterval = setInterval(() => {
                elapsed++;
                document.getElementById('secDisplay').innerText = elapsed;
                document.getElementById('modalProgress').style.width = (elapsed / minTimeRequired * 100) + '%';

                if (elapsed >= minTimeRequired) {
                    clearInterval(timerInterval);
                    btn.disabled = false;
                    btn.innerText = "J'AI VAINCU !";
                    btn.className = "w-full py-4 bg-red-600 hover:bg-red-500 text-white font-title tracking-widest font-bold rounded-xl transition duration-300 shadow-[0_0_20px_rgba(239,68,68,0.5)]";
                    btn.onclick = completeCombat;
                }
            }, 1000);
        }

        function closeModal() {
            const modal = document.getElementById('combatModal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        function completeCombat() {
            const timeElapsed = (Date.now() - combatStartTime) / 1000;
            
            // Sécurité anti-fraude finale
            if(timeElapsed < minTimeRequired) {
                document.getElementById('fraudForm').submit();
                return;
            }

            closeModal();
            const currentEl = document.getElementById('stage-' + currentActiveStage);
            currentEl.classList.remove('border-accent/50', 'hover:bg-accent/10', 'cursor-pointer', 'shadow-[0_0_15px_rgba(79,138,255,0.2)]');
            currentEl.classList.add('opacity-50', 'border-green-500/50', 'bg-green-500/5');
            currentEl.style.pointerEvents = 'none';
            
            const check = currentEl.querySelector('.check-box');
            check.innerHTML = '✓';
            check.classList.remove('border-slate-600', 'text-accent');
            check.classList.add('border-green-500', 'text-green-500');

            currentActiveStage++;

            if (currentActiveStage < totalStages) {
                const nextEl = document.getElementById('stage-' + currentActiveStage);
                nextEl.classList.remove('opacity-40', 'pointer-events-none', 'border-transparent');
                nextEl.classList.add('border-accent/50', 'cursor-pointer', 'hover:bg-accent/10');
                const nextCheck = nextEl.querySelector('.check-box');
                nextCheck.innerHTML = '<span class="animate-pulse text-accent">!</span>';
                nextCheck.classList.add('border-accent');
            } else {
                const btn = document.getElementById('claimBtn');
                btn.disabled = false;
                btn.type = "submit";
                btn.classList.remove('opacity-20', 'bg-slate-700', 'text-slate-400', 'cursor-not-allowed');
                btn.classList.add('bg-gold', 'text-black', 'hover:bg-yellow-400', 'shadow-[0_0_30px_rgba(240,169,58,0.5)]');
                btn.innerHTML = "RÉCLAMER LE BUTIN 🪙";
            }
        }
    </script>
</body>
</html>