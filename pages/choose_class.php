<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_login();

// Si classe déjà choisie, rediriger vers le dashboard
if (!empty($_SESSION['class'])) {
    header('Location: ' . BASE_URL . '/pages/dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $class = $_POST['class'] ?? '';
    if (!in_array($class, ['warrior', 'assassin', 'tank'])) {
        $error = "Classe invalide.";
    } else {
        $pdo = get_pdo();
        $pdo->prepare("UPDATE users SET class = ? WHERE id = ?")
            ->execute([$class, $_SESSION['user_id']]);
        
        // Mettre à jour la session pour que les autres pages sachent que la classe est choisie
        $_SESSION['class'] = $class;
        
        header('Location: ' . BASE_URL . '/pages/dashboard.php');
        exit;
    }
}

$classes = [
    'warrior' => [
        'label'    => 'Guerrier',
        'subtitle' => 'La Force Brute',
        'icon'     => '⚔',
        'stat1'    => 'Force',       'val1' => 95,
        'stat2'    => 'Endurance',   'val2' => 70,
        'stat3'    => 'Agilité',     'val3' => 45,
        'desc'     => 'Tu cherches un corps musclé, puissant, taillé dans la pierre. Chaque rep est un pas vers la domination.',
        'perks'    => ['Musculation lourde', 'Volume & hypertrophie', 'Force maximale'],
        'color'    => '#e55d3a',
    ],
    'assassin' => [
        'label'    => 'Assassin',
        'subtitle' => 'L\'Ombre Rapide',
        'icon'     => '◆',
        'stat1'    => 'Agilité',     'val1' => 95,
        'stat2'    => 'Explosivité', 'val2' => 85,
        'stat3'    => 'Force',       'val3' => 60,
        'desc'     => 'Corps athlétique, rapide et tranchant. Le HIIT et l\'explosivité sont tes alliés les plus fidèles.',
        'perks'    => ['HIIT & cardio', 'Agilité & explosivité', 'Corps svelte & défini'],
        'color'    => '#8b5cf6',
    ],
    'tank' => [
        'label'    => 'Tank',
        'subtitle' => 'La Forteresse',
        'icon'     => '■',
        'stat1'    => 'Résistance',  'val1' => 95,
        'stat2'    => 'Force',       'val2' => 80,
        'stat3'    => 'Endurance',   'val3' => 75,
        'desc'     => 'Imposant, massif, inébranlable. Tu t\'entraînes pour être une forteresse vivante que rien ne peut arrêter.',
        'perks'    => ['Volume élevé', 'Résistance absolue', 'Masse musculaire'],
        'color'    => '#3b82f6',
    ],
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Choisis ta Classe — Sport RPG</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;800&family=Rajdhani:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="class-page min-h-screen text-slate-200">

<?php include __DIR__ . '/../includes/background.php'; ?>

<div class="class-container relative z-10 py-12 px-4 max-w-6xl mx-auto">
    <div class="class-header text-center mb-16">
        <div class="rank-badge inline-block bg-slate-800 border border-slate-600 px-4 py-1 rounded text-xs font-bold mb-4">RANG E</div>
        <h1 class="class-title font-['Cinzel'] text-4xl md:text-5xl font-bold text-white mb-4 uppercase tracking-tighter">
            Choisis ta Voie, <span class="text-[#f0a93a]"><?= htmlspecialchars($_SESSION['username']) ?></span>
        </h1>
        <p class="class-subtitle font-['Rajdhani'] text-slate-400 uppercase tracking-widest text-sm">Cette décision définira chaque entraînement. Choisis avec sagesse.</p>
    </div>

    <?php if ($error): ?>
    <div class="bg-red-500/20 border border-red-500/50 text-red-400 p-4 rounded-xl mb-8 text-center font-bold uppercase tracking-widest">
        <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>

    <form method="POST" id="classForm">
        <input type="hidden" name="class" id="selectedClass" value="">

        <div class="class-grid grid grid-cols-1 lg:grid-cols-3 gap-8">
            <?php foreach ($classes as $key => $c): ?>
            <div class="class-card group cursor-pointer" data-class="<?= $key ?>" onclick="selectClass('<?= $key ?>')" 
                 style="--class-color: <?= $c['color'] ?>; background: rgba(13, 21, 38, 0.7); backdrop-filter: blur(12px); border: 1px solid rgba(255,255,255,0.1); border-radius: 2rem; transition: all 0.4s ease;">
                <div class="class-card-inner p-8 h-full flex flex-col">
                    <div class="class-icon text-5xl mb-4 group-hover:scale-110 transition-transform duration-300" style="color: <?= $c['color'] ?>; filter: drop-shadow(0 0 10px <?= $c['color'] ?>44);">
                        <?= $c['icon'] ?>
                    </div>
                    <h2 class="class-name font-['Cinzel'] text-3xl font-bold text-white mb-1 uppercase tracking-tighter"><?= $c['label'] ?></h2>
                    <p class="class-subtitle-label text-xs font-bold uppercase tracking-[0.2em] mb-6" style="color: <?= $c['color'] ?>"><?= $c['subtitle'] ?></p>

                    <div class="class-stats space-y-4 mb-8">
                        <?php for($i=1; $i<=3; $i++): ?>
                        <div class="stat-row">
                            <div class="flex justify-between text-[10px] uppercase font-bold mb-1 tracking-widest text-slate-500">
                                <span><?= $c['stat'.$i] ?></span>
                                <span><?= $c['val'.$i] ?></span>
                            </div>
                            <div class="stat-bar w-full h-1.5 bg-black/40 rounded-full overflow-hidden">
                                <div class="stat-fill h-full rounded-full transition-all duration-1000" style="width:<?= $c['val'.$i] ?>%; background-color: <?= $c['color'] ?>; box-shadow: 0 0 10px <?= $c['color'] ?>66;"></div>
                            </div>
                        </div>
                        <?php endfor; ?>
                    </div>

                    <p class="class-desc text-slate-400 text-sm leading-relaxed mb-8 flex-grow"><?= $c['desc'] ?></p>

                    <ul class="class-perks space-y-3 mb-8">
                        <?php foreach ($c['perks'] as $perk): ?>
                        <li class="flex items-center gap-3 text-xs font-bold uppercase tracking-wide text-slate-300">
                            <span class="perk-dot w-2 h-2 rounded-full" style="background-color: <?= $c['color'] ?>"></span>
                            <?= $perk ?>
                        </li>
                        <?php endforeach; ?>
                    </ul>

                    <button type="button" class="w-full py-4 rounded-xl font-bold font-['Cinzel'] tracking-widest text-xs transition-all border border-white/10 group-hover:border-white group-hover:bg-white group-hover:text-black">
                        CHOISIR CETTE VOIE
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </form>
</div>

<div id="confirmModal" class="fixed inset-0 z-[9999] hidden items-center justify-center bg-black/90 backdrop-blur-md px-4">
    <div class="modal-box max-w-md w-full p-10 rounded-[2.5rem] border-2 text-center" id="modalBorder" style="background: rgba(13, 21, 38, 0.95); border-color: rgba(255,255,255,0.1);">
        <div class="modal-icon text-7xl mb-6 animate-bounce" id="modalIcon"></div>
        <h3 class="modal-title font-['Cinzel'] text-3xl text-white mb-4 uppercase tracking-tighter" id="modalTitle"></h3>
        <p class="modal-text text-slate-400 text-sm leading-relaxed mb-10" id="modalText"></p>
        <div class="modal-actions flex flex-col sm:flex-row gap-4">
            <button class="flex-1 py-4 rounded-xl font-bold uppercase tracking-widest text-xs border border-white/10 text-slate-500 hover:text-white transition" onclick="closeModal()">Reconsidérer</button>
            <button class="flex-1 py-4 rounded-xl font-bold uppercase tracking-widest text-xs text-black transition shadow-xl" id="modalConfirm">Confirmer</button>
        </div>
    </div>
</div>

<script>
const classData = <?= json_encode($classes) ?>;
let pendingClass = '';

function selectClass(key) {
    pendingClass = key;
    const c = classData[key];
    
    // Remplissage du Modal
    document.getElementById('modalIcon').textContent = c.icon;
    document.getElementById('modalIcon').style.color = c.color;
    document.getElementById('modalTitle').textContent = 'Devenir ' + c.label + ' ?';
    document.getElementById('modalText').textContent  = c.desc;
    
    // Style du bouton de confirmation selon la classe
    const confirmBtn = document.getElementById('modalConfirm');
    confirmBtn.style.backgroundColor = c.color;
    confirmBtn.style.boxShadow = `0 0 20px ${c.color}66`;
    
    // Affichage
    const modal = document.getElementById('confirmModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');

    document.getElementById('modalConfirm').onclick = () => {
        document.getElementById('selectedClass').value = pendingClass;
        document.getElementById('classForm').submit();
    };
}

function closeModal() {
    const modal = document.getElementById('confirmModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}
</script>

<style>
/* Petits correctifs CSS pour l'harmonie */
.class-card:hover {
    border-color: var(--class-color) !important;
    box-shadow: 0 20px 40px rgba(0,0,0,0.5), 0 0 20px var(--class-color) !important;
}
</style>

</body>
</html>