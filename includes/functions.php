<?php
function xp_for_level($level) {
    return $level * 500;
}

function xp_percent($xp, $level) {
    $target = xp_for_level($level);
    return min(100, floor(($xp / $target) * 100));
}

function xp_remaining($xp, $level) {
    return xp_for_level($level) - $xp;
}

/**
 * Calcule le rang textuel en fonction du niveau
 * @param int $level
 * @return string
 */
function get_rank($level) {
    if ($level >= 50) return 'S';
    if ($level >= 40) return 'A';
    if ($level >= 30) return 'B';
    if ($level >= 20) return 'C';
    if ($level >= 10) return 'D';
    return 'E';
}
function get_rank_color($rank) {
    $colors = ['E' => '#9ca3af', 'D' => '#2dd4bf', 'C' => '#60a5fa', 'B' => '#4ade80', 'A' => '#ff8c00', 'S' => '#ff4444'];
    return $colors[$rank] ?? '#9ca3af';
}

function get_title($level) {
    if ($level >= 50) return "Souverain de l'Effort";
    if ($level >= 40) return "Chasseur de Rang S";
    if ($level >= 30) return "Elite Guerrier";
    if ($level >= 20) return "Vétéran";
    if ($level >= 10) return "Soldat Éveillé";
    return "Apprenti";
}

function add_xp($pdo, $uid, $amount) {
    $stmt = $pdo->prepare("SELECT experience, level FROM users WHERE id = ?");
    $stmt->execute([$uid]);
    $u = $stmt->fetch();
    
    $new_xp = $u['experience'] + $amount;
    $level = $u['level'];
    
    while ($new_xp >= xp_for_level($level)) {
        $new_xp -= xp_for_level($level);
        $level++;
    }
    
    $upd = $pdo->prepare("UPDATE users SET experience = ?, level = ?, `rank` = ? WHERE id = ?");
    $upd->execute([$new_xp, $level, get_rank($level), $uid]);
}