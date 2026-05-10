<?php
// --- SÉCURITÉ ET ACCÈS ---
// require_login() bloque l'accès si l'utilisateur n'est pas authentifié.
// require_class_chosen() force l'utilisateur à choisir un rôle avant d'accéder au dashboard.
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_class_chosen(); 

$pdo  = get_pdo();
$uid  = $_SESSION['user_id'];

// --- RÉCUPÉRATION DES DONNÉES DU PROFIL ---
// On utilise une requête préparée avec un marqueur "?" pour éviter les injections SQL.
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$uid]);
$user = $stmt->fetch();

// --- CALCULS DE PROGRESSION (LOGIQUE MÉTIER) ---
// Ces variables préparent l'affichage dynamique des barres de progression.
$level   = (int) $user['level'];
$xp      = (int) $user['experience'];
$percent = xp_percent($xp, $level); // Transforme l'XP actuelle en pourcentage du niveau.
$remain  = xp_remaining($xp, $level); // Calcule la différence entre l'XP actuelle et le palier suivant.
$rank    = get_rank($level); // Détermine le rang (S, A, B...) basé sur les paliers de niveau.
$rank_color = get_rank_color($rank); // Récupère le code couleur hexadécimal associé au rang.

// --- CLASSEMENT GLOBAL (LOGIQUE CONCURRENTIELLE) ---
// On exclut les administrateurs pour que le classement reste juste pour les joueurs.
// La fonction COUNT(*) + 1 définit la position : s'il y a 5 personnes devant moi, je suis 6ème.
$stmtPos = $pdo->prepare("SELECT COUNT(*) + 1 as pos FROM users WHERE role != 'admin' AND experience > ?");
$stmtPos->execute([$user['experience']]);
$global_rank = $stmtPos->fetch()['pos'];

// --- STATISTIQUES QUOTIDIENNES ---
// On filtre par la date du jour (CURDATE) pour réinitialiser les objectifs chaque matin.
$today = date('Y-m-d');
$done_stmt = $pdo->prepare("SELECT COUNT(*) as total FROM user_quests WHERE user_id = ? AND completed_at = ?");
$done_stmt->execute([$uid, $today]);
$quests_today = $done_stmt->fetch()['total'];

// --- HISTORIQUE D'EXPLORATION ---
// GROUP BY dungeon_rank permet de compter les succès séparément pour chaque difficulté (E, D, C...).
// FETCH_KEY_PAIR transforme le résultat en un tableau simple : ['S' => 2, 'A' => 5...].
$d_stmt = $pdo->prepare("SELECT dungeon_rank, COUNT(*) as count FROM user_dungeons WHERE user_id = ? GROUP BY dungeon_rank");
$d_stmt->execute([$uid]);
$dungeon_stats = $d_stmt->fetchAll(PDO::FETCH_KEY_PAIR);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Système — Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@700&family=Rajdhani:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body class="dashboard-page bg-[#04060e] text-slate-200 font-['Rajdhani']">

    <?php include __DIR__ . '/../includes/background.php'; ?>
    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <main class="relative z-10 max-w-6xl mx-auto pt-28 px-6 pb-20">
        
        <header class="flex flex-col md:flex-row justify-between items-center mb-12 gap-6">
            <div class="flex items-center gap-6">
                <div class="text-center">
                    <div class="font-['Cinzel'] text-5xl font-black italic drop-shadow-lg" style="color: <?= $rank_color ?>">
                        <?= $rank ?>
                    </div>
                    <div class="text-[10px] uppercase tracking-[0.3em] text-slate-500 font-bold">Rang</div>
                </div>
                <div class="h-16 w-px bg-white/10 mx-2"></div>
                <div>
                    <h1 class="font-['Cinzel'] text-3xl text-white uppercase italic tracking-tighter">
                        <?= htmlspecialchars($user['username']) ?>
                    </h1>
                    <p class="text-blue-500 font-bold uppercase text-xs tracking-[0.4em]">
                        <?= htmlspecialchars($user['title']) ?> • LVL <?= $level ?>
                    </p>
                </div>
            </div>

            <div class="flex gap-4">
                <div class="bg-white/5 border border-white/10 px-6 py-3 rounded-2xl text-center">
                    <div class="text-slate-500 text-[10px] uppercase font-bold tracking-widest mb-1">Classement</div>
                    <div class="text-white font-bold text-xl font-['Cinzel']">#<?= $global_rank ?></div>
                </div>
                <div class="bg-white/5 border border-white/10 px-6 py-3 rounded-2xl text-center">
                    <div class="text-slate-500 text-[10px] uppercase font-bold tracking-widest mb-1">Or Disponible</div>
                    <div class="text-yellow-500 font-bold text-xl font-['Cinzel']"><?= number_format($user['gold']) ?></div>
                </div>
            </div>
        </header>

        <div class="grid lg:grid-cols-3 gap-8">
            
            <div class="lg:col-span-2 space-y-8">
                <section class="bg-[#0d1526]/80 backdrop-blur-xl border border-white/10 p-8 rounded-[2.5rem]">
                    <div class="flex justify-between items-end mb-6">
                        <h3 class="font-['Cinzel'] text-white uppercase italic tracking-widest">Progression du Niveau</h3>
                        <span class="text-slate-400 text-xs"><?= number_format($remain) ?> XP avant LVL <?= $level + 1 ?></span>
                    </div>
                    
                    <div class="h-4 bg-white/5 rounded-full overflow-hidden border border-white/5 p-0.5">
                        <div class="h-full bg-gradient-to-r from-blue-600 to-cyan-400 rounded-full transition-all duration-1000 shadow-[0_0_15px_rgba(37,99,235,0.4)]" 
                             style="width: <?= $percent ?>%"></div>
                    </div>
                    <div class="mt-3 text-right text-[10px] font-bold text-blue-400 uppercase tracking-widest">
                        <?= round($percent, 1) ?>% de l'objectif atteint
                    </div>
                </section>

                <section class="bg-[#0d1526]/80 backdrop-blur-xl border border-white/10 p-8 rounded-[2.5rem]">
                    <h3 class="font-['Cinzel'] text-white uppercase italic tracking-widest mb-8">Missions du Jour</h3>
                    <div class="flex items-center gap-4 mb-4">
                        <div class="flex-1 h-2 bg-white/5 rounded-full overflow-hidden">
                            <div class="h-full bg-green-500 transition-all duration-500" style="width: <?= ($quests_today / 4) * 100 ?>%"></div>
                        </div>
                        <span class="text-white font-bold font-['Cinzel']"><?= $quests_today ?>/4</span>
                    </div>
                </section>
            </div>

            <div class="space-y-8">
                <section class="bg-[#0d1526]/80 backdrop-blur-xl border border-white/10 p-8 rounded-[2.5rem]">
                    <h3 class="font-['Cinzel'] text-white uppercase italic tracking-widest mb-8 text-center">Exploration</h3>
                    
                    <div class="grid grid-cols-1 gap-3">
                        <?php 
                        // On définit les rangs manuellement pour assurer l'ordre d'affichage (S vers E)
                        $ranks = ['S', 'A', 'B', 'C', 'D', 'E'];
                        foreach ($ranks as $r): 
                            // Opérateur Null Coalescing (??) : si aucune donnée en base, on affiche 0.
                            $count = $dungeon_stats[$r] ?? 0;
                            $c = get_rank_color($r);
                        ?>
                        <div class="flex justify-between items-center bg-white/5 p-4 rounded-2xl border border-white/5 hover:bg-white/10 transition-colors">
                            <div class="flex items-center gap-4">
                                <div class="w-10 h-10 rounded-lg flex items-center justify-center font-bold text-sm" 
                                     style="background: <?= $c ?>22; color: <?= $c ?>; border: 1px solid <?= $c ?>44">
                                    <?= $r ?>
                                </div>
                                <span class="text-[10px] font-bold uppercase tracking-widest text-slate-400">Donjons <?= $r ?></span>
                            </div>
                            <span class="text-xl font-['Cinzel'] text-white"><?= $count ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </section>
            </div>
        </div>
    </main>
</body>
</html>