<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

if (is_logged_in()) {
    header('Location: ' . BASE_URL . '/pages/dashboard.php');
    exit;
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm'] ?? '';

    if (strlen($username) < 3) $errors[] = "Nom de chasseur trop court.";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Email magique invalide.";
    if (strlen($password) < 8) $errors[] = "Mot de passe trop faible (8 min).";
    if ($password !== $confirm) $errors[] = "Les sceaux ne correspondent pas.";

    if (empty($errors)) {
        $pdo = get_pdo();
        $check = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $check->execute([$username, $email]);
        
        if ($check->fetch()) {
            $errors[] = "Identifiants déjà gravés dans la pierre.";
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $ins = $pdo->prepare("INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)");
            $ins->execute([$username, $email, $hash]);
            
            $new_user_id = $pdo->lastInsertId();
            $_SESSION['user_id'] = $new_user_id;
            $_SESSION['username'] = $username;
            header('Location: ' . BASE_URL . '/pages/choose_class.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Inscription - Sport RPG</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@700&family=Rajdhani:wght@400;600&display=swap" rel="stylesheet">
</head>
<body class="min-h-screen text-slate-200 font-['Rajdhani']">

    <?php include __DIR__ . '/../includes/background.php'; ?>

    <div class="flex items-center justify-center min-h-screen px-4 py-12">
        <div class="auth-card w-full max-w-md bg-[#0d1526]/80 backdrop-blur-xl border border-white/10 p-8 rounded-[2rem] shadow-2xl">
            <h2 class="font-['Cinzel'] text-2xl text-center text-white mb-8 tracking-widest uppercase">Éveille tes Pouvoirs</h2>

            <?php if (!empty($errors)): ?>
                <div class="bg-red-500/10 border border-red-500/50 text-red-400 p-4 rounded-xl mb-6 text-[10px] font-bold uppercase tracking-widest leading-relaxed">
                    <?php foreach($errors as $e): ?> <div>• <?= $e ?></div> <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-5">
                <div>
                    <label class="block text-slate-400 text-[10px] uppercase font-bold tracking-[0.2em] mb-1 ml-1">Nom de Chasseur</label>
                    <input type="text" name="username" class="w-full bg-black/40 border border-white/10 rounded-xl p-4 text-white outline-none focus:border-[#f0a93a] transition-all" placeholder="Ex: Sung Jinwoo" required>
                </div>

                <div>
                    <label class="block text-slate-400 text-[10px] uppercase font-bold tracking-[0.2em] mb-1 ml-1">Email</label>
                    <input type="email" name="email" class="w-full bg-black/40 border border-white/10 rounded-xl p-4 text-white outline-none focus:border-[#f0a93a] transition-all" placeholder="votre@grimoire.com" required>
                </div>

                <div>
                    <label class="block text-slate-400 text-[10px] uppercase font-bold tracking-[0.2em] mb-1 ml-1">Mot de passe</label>
                    <div class="relative">
                        <input type="password" id="pass_reg" name="password" class="w-full bg-black/40 border border-white/10 rounded-xl p-4 text-white outline-none focus:border-[#f0a93a] transition-all" placeholder="••••••••" required>
                        <button type="button" onclick="togglePass('pass_reg')" class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-500 hover:text-[#f0a93a] transition-colors text-xl">
                            👁️
                        </button>
                    </div>
                </div>

                <div>
                    <label class="block text-slate-400 text-[10px] uppercase font-bold tracking-[0.2em] mb-1 ml-1">Confirmer Destinée</label>
                    <div class="relative">
                        <input type="password" id="conf_reg" name="confirm" class="w-full bg-black/40 border border-white/10 rounded-xl p-4 text-white outline-none focus:border-[#f0a93a] transition-all" placeholder="••••••••" required>
                        <button type="button" onclick="togglePass('conf_reg')" class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-500 hover:text-[#f0a93a] transition-colors text-xl">
                            👁️
                        </button>
                    </div>
                </div>
                
                <button type="submit" class="w-full bg-[#f0a93a] text-black font-bold py-4 rounded-xl uppercase tracking-[0.2em] hover:bg-white hover:scale-[1.02] active:scale-95 transition-all shadow-[0_0_20px_rgba(240,169,58,0.2)] mt-6">
                    Invoquer mon compte
                </button>
            </form>

            <p class="text-center text-slate-500 text-sm mt-8 tracking-wide">
                Déjà éveillé ? <a href="login.php" class="text-[#f0a93a] font-bold hover:underline">Se connecter</a>
            </p>
        </div>
    </div>

    <script>
        function togglePass(id) {
            const input = document.getElementById(id);
            input.type = input.type === 'password' ? 'text' : 'password';
        }
    </script>
</body>
</html>