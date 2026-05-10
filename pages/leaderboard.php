<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_login();

$pdo = get_pdo();
$uid = $_SESSION['user_id'];
$filter = $_GET['class'] ?? 'global';

// --- STATS PERSONNELLES (Excluant les admins) ---
// Position globale exacte sans compter les admins
$stmtPos = $pdo->prepare("SELECT COUNT(*) + 1 as pos FROM users WHERE role != 'admin' AND experience > (SELECT experience FROM users WHERE id = ?)");
$stmtPos->execute([$uid]);
$myGlobalPos = $stmtPos->fetch()['pos'];

// Position dans sa propre classe sans compter les admins
$stmtPosClass = $pdo->prepare("SELECT COUNT(*) + 1 as pos FROM users WHERE role != 'admin' AND class = ? AND experience > (SELECT experience FROM users WHERE id = ?)");
$stmtPosClass->execute([$_SESSION['class'], $uid]);
$myClassPos = $stmtPosClass->fetch()['pos'];

// Infos du joueur connecté
$stmtMe = $pdo->prepare("SELECT username, class, level, experience, title, `rank` FROM users WHERE id = ?");
$stmtMe->execute([$uid]);
$me = $stmtMe->fetch();

// --- SYSTÈME DE CACHE (Performance) ---
$cache_file = __DIR__ . "/../cache/leaderboard_{$filter}.json";
$cache_time = 15; 

if (file_exists($cache_file) && (time() - filemtime($cache_file) < $cache_time)) {
    $players = json_decode(file_get_contents($cache_file), true);
    $last_update = filemtime($cache_file);
} else {
    // Requête principale excluant les admins
    $query = "SELECT username, class, level, experience, title, `rank` FROM users WHERE role != 'admin' ";
    
    if ($filter !== 'global' && in_array($filter, ['warrior', 'assassin', 'tank'])) {
        $query .= " AND class = :class ";
    }
    $query .= " ORDER BY experience DESC LIMIT 100";
    
    $stmt = $pdo->prepare($query);
    if ($filter !== 'global' && in_array($filter, ['warrior', 'assassin', 'tank'])) {
        $stmt->execute(['class' => $filter]);
    } else {
        $stmt->execute();
    }
    $players = $stmt->fetchAll();
    if (!is_dir(__DIR__ . '/../cache')) mkdir(__DIR__ . '/../cache', 0777, true);
    file_put_contents($cache_file, json_encode($players));
    $last_update = time();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Temple des Héros — Sport RPG</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600;800&family=Rajdhani:wght@400;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        gold: '#f0a93a',
                        accent: '#4f8aff',
                    }
                }
            }
        }
    </script>
</head>
<body class="dashboard-page font-['Rajdhani']">

<?php include __DIR__ . '/../includes/background.php'; ?>
<?php include __DIR__ . '/../includes/navbar.php'; ?>

<main class="dashboard-main relative z-10 pt-24 max-w-6xl mx-auto px-4">
    
    <section class="mb-10 flex justify-between items-end">
        <div>
            <h1 class="font-['Cinzel'] text-4xl text-white font-bold tracking-widest uppercase">Temple des Héros</h1>
            <p class="text-slate-500 text-xs uppercase tracking-[0.3em] mt-2 italic">Dernière mise à jour système : <?= date('H:i:s', $last_update) ?></p>
        </div>
        <div class="hidden md:block">
            <span class="text-white/20 font-['Cinzel'] text-6xl font-black italic tracking-tighter">LEADERBOARD</span>
        </div>
    </section>

    <section class="relative overflow-hidden mb-12 p-8 rounded-[2rem] border-2 border-accent/30 shadow-[0_0_30px_rgba(79,138,255,0.15)]" style="background: rgba(13, 21, 38, 0.8); backdrop-filter: blur(15px);">
        <div class="absolute top-0 right-0 p-4 opacity-5 pointer-events-none">
            <span class="font-['Cinzel'] text-9xl">#<?= $myGlobalPos ?></span>
        </div>
        
        <div class="flex flex-col md:flex-row items-center justify-between gap-8 relative z-10">
            <div class="flex items-center gap-6">
                <div class="text-center">
                    <div class="text-gold font-['Cinzel'] text-5xl font-black italic drop-shadow-[0_0_10px_rgba(240,169,58,0.5)]">#<?= $myGlobalPos ?></div>
                    <div class="text-[10px] text-slate-500 uppercase tracking-widest font-bold">Global</div>
                </div>
                <div class="h-16 w-px bg-white/10 mx-2"></div>
                <div>
                    <h2 class="text-3xl text-white font-['Cinzel'] font-bold tracking-tighter uppercase"><?= htmlspecialchars($me['username']) ?></h2>
                    <p class="text-accent font-bold uppercase text-xs tracking-widest"><?= $me['title'] ?> • <?= $me['rank'] ?>-RANK</p>
                </div>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-3 gap-6 w-full md:w-auto">
                <div class="bg-white/5 p-4 rounded-2xl border border-white/5 text-center">
                    <div class="text-slate-500 text-[10px] uppercase font-bold tracking-widest mb-1">Rang <?= ucfirst($me['class']) ?></div>
                    <div class="text-white font-bold">N° <?= $myClassPos ?></div>
                </div>
                <div class="bg-white/5 p-4 rounded-2xl border border-white/5 text-center">
                    <div class="text-slate-500 text-[10px] uppercase font-bold tracking-widest mb-1">Niveau</div>
                    <div class="text-accent font-bold">LVL <?= $me['level'] ?></div>
                </div>
                <div class="bg-white/5 p-4 rounded-2xl border border-white/5 text-center col-span-2 md:col-span-1">
                    <div class="text-slate-500 text-[10px] uppercase font-bold tracking-widest mb-1">Expérience</div>
                    <div class="text-gold font-bold"><?= number_format($me['experience']) ?> <span class="text-[10px]">XP</span></div>
                </div>
            </div>
        </div>
    </section>

    <div class="flex flex-wrap gap-4 mb-8">
        <a href="?class=global" class="filter-btn <?= $filter === 'global' ? 'active' : '' ?>">Global</a>
        <a href="?class=warrior" class="filter-btn warrior <?= $filter === 'warrior' ? 'active' : '' ?>">Guerriers</a>
        <a href="?class=assassin" class="filter-btn assassin <?= $filter === 'assassin' ? 'active' : '' ?>">Assassins</a>
        <a href="?class=tank" class="filter-btn tank <?= $filter === 'tank' ? 'active' : '' ?>">Tanks</a>
    </div>

    <section class="bg-[#0d1526]/80 backdrop-blur-xl rounded-[2rem] border border-white/10 overflow-hidden shadow-2xl mb-20">
        <table class="w-full text-left">
            <thead>
                <tr class="bg-white/5 text-[10px] text-slate-500 uppercase tracking-[0.3em] font-bold">
                    <th class="p-6">Position</th>
                    <th class="p-6">Invocateur</th>
                    <th class="p-6">Classe</th>
                    <th class="p-6">Niveau</th>
                    <th class="p-6 text-right">XP</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($players as $i => $p): 
                    $pos = $i + 1;
                    $is_me = ($p['username'] === $_SESSION['username']);
                    $podium_color = ($pos === 1) ? '#f0a93a' : (($pos === 2) ? '#e2e8f0' : (($pos === 3) ? '#cd7f32' : ''));
                ?>
                <tr class="border-b border-white/5 transition-colors duration-300 <?= $is_me ? 'bg-accent/10' : 'hover:bg-white/5' ?>">
                    <td class="p-6">
                        <div class="font-['Cinzel'] font-black text-xl italic" style="color: <?= $podium_color ?: '#475569' ?>">
                            <?= str_pad($pos, 2, '0', STR_PAD_LEFT) ?>
                        </div>
                    </td>
                    <td class="p-6">
                        <div class="font-bold text-white tracking-wide uppercase"><?= htmlspecialchars($p['username']) ?></div>
                        <div class="text-[10px] text-slate-500 font-bold uppercase tracking-widest"><?= $p['rank'] ?>-RANK • <?= $p['title'] ?></div>
                    </td>
                    <td class="p-6">
                        <span class="px-3 py-1 rounded-md text-[9px] font-bold uppercase tracking-widest border border-white/10 bg-white/5 class-text-<?= $p['class'] ?>">
                            <?= $p['class'] ?>
                        </span>
                    </td>
                    <td class="p-6 font-['Cinzel'] text-accent font-bold">
                        NV. <?= $p['level'] ?>
                    </td>
                    <td class="p-6 text-right font-bold text-gold tracking-tighter">
                        <?= number_format($p['experience']) ?> <span class="text-[9px] opacity-50">XP</span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </section>
</main>

<style>
.filter-btn {
    padding: 10px 25px;
    background: rgba(255,255,255,0.03);
    border: 1px solid rgba(255,255,255,0.1);
    border-radius: 12px;
    color: #7a8ab5;
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 2px;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.filter-btn:hover { border-color: white; color: white; }

.filter-btn.active {
    background: #4f8aff;
    border-color: #4f8aff;
    color: white;
    box-shadow: 0 0 20px rgba(79, 138, 255, 0.3);
}

.filter-btn.warrior.active { background: #e55d3a; border-color: #e55d3a; box-shadow: 0 0 20px rgba(229, 93, 58, 0.3); }
.filter-btn.assassin.active { background: #8b5cf6; border-color: #8b5cf6; box-shadow: 0 0 20px rgba(139, 92, 246, 0.3); }
.filter-btn.tank.active { background: #3b82f6; border-color: #3b82f6; box-shadow: 0 0 20px rgba(59, 130, 246, 0.3); }

.class-text-warrior { color: #e55d3a; }
.class-text-assassin { color: #8b5cf6; }
.class-text-tank { color: #3b82f6; }
</style>

</body>
</html>