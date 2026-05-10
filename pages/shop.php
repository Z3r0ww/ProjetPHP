<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_login();

$pdo = get_pdo();
$uid = $_SESSION['user_id'];
$message = '';
$msg_type = 'gold';

// --- CONFIGURATION DES PRIX ÉQUILIBRÉS ---
$prices = [
    'xp'    => 2500,   // Contrat de Croissance
    'reset' => 5000,  // Reset Donjon
    'name'  => 7500,  // Changement de nom
    'class' => 9500   // Changement de classe
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    // Récupérer les données du joueur (Or, XP et Niveau)
    $stmt = $pdo->prepare("SELECT gold, experience, level FROM users WHERE id = ?");
    $stmt->execute([$uid]);
    $u = $stmt->fetch();

    if ($action == 'buy_xp') {
        if ($u['gold'] >= $prices['xp']) {
            // LOGIQUE 20% XP MANQUANT
            $level = (int)$u['level'];
            $current_xp = (int)$u['experience'];

            // Calcul de l'XP requis pour le prochain palier
            $xp_for_next_lvl = xp_for_level($level + 1);
            $xp_missing = $xp_for_next_lvl - $current_xp;

            // Calcul des 20% (arrondi au supérieur)
            $xp_to_add = ceil($xp_missing * 0.20);

            // Application de la transaction
            $pdo->prepare("UPDATE users SET gold = gold - ? WHERE id = ?")->execute([$prices['xp'], $uid]);
            
            // Ajout de l'XP via la fonction système (gère les levels-up)
            add_xp($pdo, $uid, $xp_to_add);

            $message = "[ SYSTEME ] : Essence absorbée. +" . number_format($xp_to_add) . " XP ajoutés (20% du palier restant).";
        } else {
            $message = "[ ALERTE ] : Or insuffisant pour cette transaction.";
            $msg_type = 'red';
        }
    } 
    elseif ($action == 'reset_dungeons') {
        if ($u['gold'] >= $prices['reset']) {
            $pdo->prepare("DELETE FROM user_dungeons WHERE user_id = ? AND completed_at = CURDATE()")->execute([$uid]);
            $pdo->prepare("UPDATE users SET gold = gold - ? WHERE id = ?")->execute([$prices['reset'], $uid]);
            $message = "[ SYSTEME ] : Les brèches temporelles sont réinitialisées. Le donjon est à nouveau accessible.";
        } else {
            $message = "[ ALERTE ] : Fonds insuffisants pour manipuler le temps.";
            $msg_type = 'red';
        }
    }
}

// Récupération du solde mis à jour
$stmt = $pdo->prepare("SELECT gold FROM users WHERE id = ?");
$stmt->execute([$uid]);
$user = $stmt->fetch();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Marché Noir — Sport RPG</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@700;900&family=Rajdhani:wght@500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: { gold: '#f0a93a', accent: '#4f8aff' }
                }
            }
        }
    </script>
    <style>
        .shop-card {
            background: linear-gradient(145deg, rgba(13, 21, 38, 0.9) 0%, rgba(8, 13, 26, 0.95) 100%);
            border: 1px solid rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(12px);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
        .shop-card:hover {
            border-color: rgba(240, 169, 58, 0.4);
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.6), 0 0 20px rgba(240, 169, 58, 0.1);
        }
    </style>
</head>
<body class="bg-[#04060e] text-slate-200 font-['Rajdhani']">
    <?php include __DIR__ . '/../includes/background.php'; ?>
    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <main class="pt-32 pb-20 max-w-6xl mx-auto px-6 relative z-10">
        
        <div class="flex flex-col md:flex-row justify-between items-center mb-16 gap-6">
            <div>
                <h1 class="font-['Cinzel'] text-5xl font-black text-white tracking-tighter mb-2 italic">MARCHÉ <span class="text-gold">NOIR</span></h1>
                <p class="text-slate-500 uppercase tracking-[0.3em] text-xs">Objets Adaptatifs — Accès Niveau S</p>
            </div>
            <div class="bg-black/40 border border-gold/30 rounded-2xl p-6 flex items-center gap-6 shadow-2xl">
                <div class="text-right">
                    <p class="text-[10px] text-slate-500 uppercase font-bold tracking-widest">Tes Crédits</p>
                    <p class="text-3xl font-bold text-gold font-['Cinzel'] italic"><?= number_format($user['gold'] ?? 0) ?></p>
                </div>
                <div class="text-4xl animate-pulse">🪙</div>
            </div>
        </div>

        <?php if($message): ?>
            <div class="<?= $msg_type == 'red' ? 'bg-red-500/20 border-red-500' : 'bg-gold/10 border-gold/50' ?> border text-center p-6 rounded-2xl mb-12 font-bold tracking-widest shadow-xl">
                <?= $message ?>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            
            <div class="shop-card rounded-[2.5rem] p-8 flex flex-col justify-between group">
                <div>
                    <div class="w-16 h-16 bg-blue-500/10 rounded-2xl flex items-center justify-center border border-blue-500/20 mb-6 group-hover:scale-110 transition">
                        <span class="text-3xl">⚡</span>
                    </div>
                    <h3 class="font-['Cinzel'] text-xl text-white mb-2">Contrat de Croissance</h3>
                    <p class="text-slate-500 text-[10px] uppercase tracking-widest mb-4">Injection d'XP Adaptative</p>
                    <p class="text-slate-400 text-sm italic mb-4">"Plus le sommet est loin, plus l'élan est grand."</p>
                    <div class="p-3 bg-blue-500/10 rounded-xl text-blue-400 text-[10px] font-bold text-center border border-blue-500/20 uppercase tracking-tighter">
                        GAIN : +20% du palier manquant
                    </div>
                </div>
                <form method="POST" class="mt-8">
                    <input type="hidden" name="action" value="buy_xp">
                    <button class="w-full py-4 rounded-xl bg-white/5 border border-white/10 text-white font-bold hover:bg-blue-600 hover:text-white hover:border-blue-600 transition-all flex justify-between px-6">
                        <span>ACQUÉRIR</span>
                        <span class="text-gold"><?= number_format($prices['xp']) ?> 🪙</span>
                    </button>
                </form>
            </div>

            <div class="shop-card rounded-[2.5rem] p-8 flex flex-col justify-between group">
                <div>
                    <div class="w-16 h-16 bg-purple-500/10 rounded-2xl flex items-center justify-center border border-purple-500/20 mb-6 group-hover:scale-110 transition">
                        <span class="text-3xl">🌀</span>
                    </div>
                    <h3 class="font-['Cinzel'] text-xl text-white mb-2">Clef du Néant</h3>
                    <p class="text-slate-500 text-[10px] uppercase tracking-widest mb-4">Anomalie Temporelle</p>
                    <p class="text-slate-400 text-sm italic">Réinitialise ton accès aux brèches. Permet un deuxième donjon aujourd'hui.</p>
                </div>
                <form method="POST" class="mt-8">
                    <input type="hidden" name="action" value="reset_dungeons">
                    <button class="w-full py-4 rounded-xl bg-white/5 border border-white/10 text-white font-bold hover:bg-purple-600 hover:text-white hover:border-purple-600 transition-all flex justify-between px-6">
                        <span>ACQUÉRIR</span>
                        <span class="text-gold"><?= number_format($prices['reset']) ?> 🪙</span>
                    </button>
                </form>
            </div>

            <div class="shop-card rounded-[2.5rem] p-8 flex flex-col justify-between group">
                <div>
                    <div class="w-16 h-16 bg-gold/10 rounded-2xl flex items-center justify-center border border-gold/20 mb-6 group-hover:scale-110 transition">
                        <span class="text-3xl">📜</span>
                    </div>
                    <h3 class="font-['Cinzel'] text-xl text-white mb-2">Nouveau Décret</h3>
                    <p class="text-slate-500 text-[10px] uppercase tracking-widest mb-4">Refonte d'Identité</p>
                    <input type="text" placeholder="Nouveau Pseudo..." class="w-full bg-black/40 border border-white/10 rounded-xl p-4 text-sm outline-none focus:border-gold transition">
                </div>
                <button class="mt-8 w-full py-4 rounded-xl bg-white/5 border border-white/10 text-white font-bold hover:bg-gold hover:text-black transition-all flex justify-between px-6">
                    <span>SIGNER</span>
                    <span class="text-gold"><?= number_format($prices['name']) ?> 🪙</span>
                </button>
            </div>

        </div>

        <div class="shop-card rounded-[2.5rem] p-8 mt-8 border-red-900/30">
            <div class="flex flex-col md:flex-row items-center gap-10">
                <div class="w-32 h-32 bg-red-500/10 rounded-full flex items-center justify-center border-4 border-red-500/20 shadow-[0_0_30px_rgba(239,68,68,0.2)] shrink-0">
                    <span class="text-6xl animate-pulse">🎭</span>
                </div>
                <div class="flex-1 text-center md:text-left">
                    <h3 class="font-['Cinzel'] text-2xl text-white mb-2">Rituel de Réincarnation</h3>
                    <p class="text-red-500 text-[10px] font-bold uppercase tracking-[0.4em] mb-4">⚠️ EFFET SECONDAIRE : PERTE DE 3 NIVEAUX</p>
                    <p class="text-slate-400 text-sm leading-relaxed mb-6">
                        Efface ton lien actuel avec ta classe. Le processus est violent : tu seras rétrogradé de <span class="text-red-500 font-bold">3 niveaux</span> d'expérience lors du transfert énergétique.
                    </p>
                    <button class="py-4 px-10 rounded-xl bg-red-600/10 border border-red-600/40 text-red-500 font-bold hover:bg-red-600 hover:text-white transition-all shadow-xl">
                        INITIER LE RITUEL (<?= number_format($prices['class']) ?> 🪙)
                    </button>
                </div>
            </div>
        </div>
    </main>
</body>
</html>