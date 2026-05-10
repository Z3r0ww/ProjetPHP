<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

if (is_logged_in()) {
    header('Location: ' . BASE_URL . '/pages/dashboard.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim($_POST['identifier'] ?? '');
    $password   = $_POST['password'] ?? '';

    if ($identifier && $password) {
        $pdo = get_pdo();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ? LIMIT 1");
        $stmt->execute([$identifier, $identifier]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            login_user($user);
            header('Location: ' . BASE_URL . (empty($user['class']) ? '/pages/choose_class.php' : '/pages/dashboard.php'));
            exit;
        } else {
            $error = "Identifiants incorrects.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Connexion - Sport RPG</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@700&family=Rajdhani:wght@400;600&display=swap" rel="stylesheet">
</head>
<body class="min-h-screen text-slate-200 font-['Rajdhani']">

    <?php include __DIR__ . '/../includes/background.php'; ?>

    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="auth-card w-full max-w-md bg-[#0d1526]/80 backdrop-blur-xl border border-white/10 p-8 rounded-[2rem] shadow-2xl">
            <h2 class="font-['Cinzel'] text-2xl text-center text-white mb-8 tracking-widest uppercase">Reprends le Combat</h2>
            
            <?php if ($error): ?>
                <div class="bg-red-500/10 border border-red-500/50 text-red-400 p-3 rounded-xl mb-6 text-center text-xs font-bold uppercase tracking-widest">
                    <?= $error ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" class="space-y-6">
                <div>
                    <label class="block text-slate-400 text-[10px] uppercase font-bold tracking-[0.2em] mb-2 ml-1">Invocateur ou Email</label>
                    <input type="text" name="identifier" class="w-full bg-black/40 border border-white/10 rounded-xl p-4 text-white outline-none focus:border-[#f0a93a] transition-all" placeholder="Pseudo ou Email" required>
                </div>

                <div>
                    <label class="block text-slate-400 text-[10px] uppercase font-bold tracking-[0.2em] mb-2 ml-1">Mot de passe</label>
                    <div class="relative">
                        <input type="password" id="pass_login" name="password" class="w-full bg-black/40 border border-white/10 rounded-xl p-4 text-white outline-none focus:border-[#f0a93a] transition-all" placeholder="••••••••" required>
                        <button type="button" onclick="togglePass('pass_login')" class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-500 hover:text-[#f0a93a] transition-colors text-xl">
                            👁️
                        </button>
                    </div>
                </div>

                <button type="submit" class="w-full bg-[#f0a93a] text-black font-bold py-4 rounded-xl uppercase tracking-[0.2em] hover:bg-white hover:scale-[1.02] active:scale-95 transition-all shadow-[0_0_20px_rgba(240,169,58,0.2)] mt-4">
                    Ouvrir le portail
                </button>
            </form>
            
            <p class="text-center text-slate-500 text-sm mt-8 tracking-wide">
                Première fois ? <a href="register.php" class="text-[#f0a93a] font-bold hover:underline">Éveille-toi</a>
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