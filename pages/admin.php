<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// Sécurité Admin
require_admin(); 

$pdo = get_pdo();
$message = "";
$generated_password = "";

// --- LOGIQUE DES ACTIONS (C'est ici que se trouve le générateur) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $target_id = (int)$_POST['user_id'];

    // 1. MISE À JOUR STATS
    if (isset($_POST['update_stats'])) {
        $gold = (int)$_POST['gold'];
        $new_lvl = (int)$_POST['level'];
        $xp = (int)$_POST['xp'];
        $min_xp_for_lvl = xp_for_level($new_lvl);
        if ($xp < $min_xp_for_lvl) { $xp = $min_xp_for_lvl; }
        $new_rank = get_rank($new_lvl);

        $pdo->prepare("UPDATE users SET gold = ?, level = ?, experience = ?, `rank` = ? WHERE id = ?")
            ->execute([$gold, $new_lvl, $xp, $new_rank, $target_id]);
        $message = "Données synchronisées pour #$target_id.";
    }

    // 2. RESET DAILY
    if (isset($_POST['reset_progress'])) {
        $pdo->prepare("DELETE FROM user_quests WHERE user_id = ? AND completed_at = CURDATE()")->execute([$target_id]);
        $pdo->prepare("DELETE FROM user_dungeons WHERE user_id = ? AND completed_at = CURDATE()")->execute([$target_id]);
        $message = "Accès journaliers réinitialisés.";
    }

    // 3. ICI LE GÉNÉRATEUR DE MDP ALÉATOIRE
    if (isset($_POST['reset_pwd'])) {
        // On crée le mot de passe en clair
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#";
        $pass_brut = substr(str_shuffle($chars), 0, 10); 
        $hash = password_hash($pass_brut, PASSWORD_DEFAULT);
        
        // On cherche le nom de ta colonne (password, mdp, etc.)
        $q = $pdo->query("DESCRIBE users");
        $cols = $q->fetchAll(PDO::FETCH_COLUMN);
        $tests = ['password', 'mdp', 'pass', 'user_pass', 'pwd', 'password_hash'];
        $found_col = "";
        foreach ($tests as $c) {
            if (in_array($c, $cols)) { $found_col = $c; break; }
        }

        if ($found_col) {
            $pdo->prepare("UPDATE users SET $found_col = ? WHERE id = ?")->execute([$hash, $target_id]);
            $generated_password = $pass_brut; // Sera affiché dans le HTML plus bas
            $message = "Succès ! Colonne utilisée : $found_col";
        } else {
            $liste = implode(', ', $cols);
            $message = "ERREUR : Aucune colonne de mot de passe trouvée. Colonnes : ($liste)";
        }
    }

    // 4. BANNISSEMENT
    if (isset($_POST['delete_user']) && $target_id != $_SESSION['user_id']) {
        $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$target_id]);
        $message = "Compte supprimé.";
    }
}

$users = $pdo->query("SELECT id, username, level, experience, gold, role, class, `rank` FROM users ORDER BY id DESC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Console Admin — Sport RPG</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@700&family=Rajdhani:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body class="bg-[#04060e] text-slate-200 font-['Rajdhani']">
    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <main class="max-w-7xl mx-auto pt-28 px-6 pb-20 relative z-10">
        <h1 class="font-['Cinzel'] text-3xl text-white tracking-widest uppercase mb-10 text-center">Console <span class="text-red-500">Admin</span></h1>

        <?php if($generated_password): ?>
            <div class="bg-yellow-500/20 border-2 border-yellow-500 text-yellow-400 p-6 rounded-3xl mb-8 text-center shadow-lg">
                <p class="text-xs uppercase font-bold mb-2 tracking-widest">Nouveau mot de passe pour le Chasseur :</p>
                <code class="text-4xl font-mono font-black tracking-widest bg-black/60 px-6 py-2 rounded-xl border border-white/10"><?= $generated_password ?></code>
                <p class="text-[10px] mt-4 italic">Le mot de passe a été crypté avant l'enregistrement. Notez-le bien.</p>
            </div>
        <?php endif; ?>

        <?php if($message && !$generated_password): ?>
            <div class="bg-blue-600/20 border border-blue-500/50 text-blue-400 p-4 rounded-2xl mb-8 text-center font-bold">
                [ SYSTEME ] : <?= $message ?>
            </div>
        <?php endif; ?>

        <div class="bg-white/5 border border-white/10 rounded-[2rem] overflow-hidden shadow-2xl backdrop-blur-md">
            <table class="w-full text-left text-sm">
                <thead class="bg-white/5 text-[10px] uppercase font-bold text-slate-500 tracking-[0.2em] border-b border-white/10">
                    <tr>
                        <th class="p-6">Chasseur</th>
                        <th class="p-6">Ressources & Niveau</th>
                        <th class="p-6 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    <?php foreach ($users as $u): ?>
                    <tr class="hover:bg-white/5 transition-colors">
                        <td class="p-6">
                            <div class="text-white font-bold text-lg"><?= htmlspecialchars($u['username']) ?></div>
                            <div class="text-[10px] text-blue-400 font-black italic">UID: #<?= $u['id'] ?> — RANK <?= $u['rank'] ?></div>
                        </td>
                        
                        <td class="p-6">
                            <form method="POST" class="flex items-center gap-4">
                                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                <div class="grid grid-cols-2 gap-2">
                                    <div class="flex items-center gap-2 bg-black/40 p-1 rounded-lg border border-white/5">
                                        <span class="text-[9px] text-gold font-bold pl-2 uppercase">Or</span>
                                        <input type="number" name="gold" value="<?= $u['gold'] ?>" class="bg-transparent text-xs text-[#f0a93a] w-20 outline-none">
                                    </div>
                                    <div class="flex items-center gap-2 bg-black/40 p-1 rounded-lg border border-white/5">
                                        <span class="text-[9px] text-white font-bold pl-2 uppercase">Lvl</span>
                                        <input type="number" name="level" value="<?= $u['level'] ?>" class="bg-transparent text-xs text-white w-20 outline-none">
                                    </div>
                                    <div class="flex items-center gap-2 bg-black/40 p-1 rounded-lg border border-white/5 col-span-2">
                                        <span class="text-[9px] text-blue-400 font-bold pl-2 uppercase">XP</span>
                                        <input type="number" name="xp" value="<?= $u['experience'] ?>" class="bg-transparent text-xs text-blue-400 w-full outline-none">
                                    </div>
                                </div>
                                <button type="submit" name="update_stats" class="p-3 bg-white/5 hover:bg-blue-600 rounded-xl border border-white/10 transition-all">💾</button>
                            </form>
                        </td>

                        <td class="p-6 text-right">
                            <div class="flex justify-end gap-2">
                                <form method="POST" class="flex gap-2">
                                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                    <button type="submit" name="reset_progress" class="p-2 bg-blue-600/10 text-blue-400 border border-blue-600/20 rounded-lg hover:bg-blue-600 hover:text-white transition" title="Reset Daily">🔄</button>
                                    <button type="submit" name="reset_pwd" class="p-2 bg-yellow-600/10 text-yellow-500 border border-yellow-500/20 rounded-lg hover:bg-yellow-600 hover:text-white transition" title="Générer MDP">🔑</button>
                                    <?php if($u['id'] != $_SESSION['user_id']): ?>
                                        <button type="submit" name="delete_user" class="p-2 bg-red-600/10 text-red-500 border border-red-500/20 rounded-lg hover:bg-red-600 hover:text-white transition" title="Bannir">🚫</button>
                                    <?php endif; ?>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>
</body>
</html>