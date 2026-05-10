<nav class="navbar" style="background: rgba(4, 6, 14, 0.85); backdrop-filter: blur(12px); position: fixed; top: 0; width: 100%; z-index: 1000; display: flex; align-items: center; justify-content: space-between; padding: 0 2rem; height: 70px; border-bottom: 1px solid rgba(255,255,255,0.05);">
    <div class="nav-brand">
        <span class="nav-rune" style="color: #f0a93a; margin-right: 5px;">&#9670;</span>
        <span class="nav-title" style="font-family: 'Cinzel', serif; font-weight: 800; letter-spacing: 2px; color: white;">
            SPORT<span style="color: #4f8aff;">RPG</span>
        </span>
    </div>
    
    <div class="nav-links" style="display: flex; gap: 1.5rem; align-items: center; font-family: 'Rajdhani', sans-serif;">
        <?php 
        $current_page = basename($_SERVER['PHP_SELF']); 
        $links = [
            'dashboard.php'   => 'Sanctuaire',
            'quests.php'      => 'Quêtes',
            'dungeons.php'    => 'Donjons',
            'leaderboard.php' => 'Classement',
            'shop.php'        => 'Boutique'
        ];

        // Ajout dynamique du lien Admin si l'utilisateur a le rôle requis
        if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
            $links['admin.php'] = 'Console Admin';
        }
        
        foreach ($links as $file => $label): 
            $is_active = ($current_page === $file);
            // Couleur spéciale pour l'onglet Admin pour le faire ressortir
            $link_color = $is_active ? '#fff' : ($file === 'admin.php' ? '#ef4444' : '#7a8ab5');
        ?>
            <a href="<?= BASE_URL ?>/pages/<?= $file ?>" 
               class="nav-link" 
               style="text-transform: uppercase; font-weight: 600; font-size: 0.85rem; transition: 0.3s; text-decoration: none; color: <?= $link_color ?>; <?= ($file === 'admin.php' && !$is_active) ? 'border: 1px solid rgba(239, 68, 68, 0.3); padding: 2px 8px; border-radius: 4px;' : '' ?>">
               <?= $label ?>
            </a>
        <?php endforeach; ?>

        <div style="width: 1px; height: 20px; background: rgba(255,255,255,0.1); margin: 0 5px;"></div>

        <a href="<?= BASE_URL ?>/pages/logout.php" 
           class="nav-link nav-logout" 
           style="color: #e84444; text-transform: uppercase; font-weight: 600; font-size: 0.85rem; text-decoration: none;" 
           onclick="return confirm('Es-tu sûr de vouloir te déconnecter, Chasseur ?')">
           &#9660; Déconnexion
        </a>
    </div>
</nav>