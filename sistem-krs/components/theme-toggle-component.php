<?php
// Komponen Theme Toggle yang bisa digunakan di semua halaman
function renderThemeToggle($position = 'header') {
    $positionClass = '';
    switch($position) {
        case 'fixed':
            $positionClass = 'position: fixed; top: 20px; right: 20px; z-index: 9999;';
            break;
        case 'header':
            $positionClass = '';
            break;
        case 'sidebar':
            $positionClass = 'margin: 10px 0;';
            break;
    }
    
    return '
    <div class="theme-toggle-wrapper" style="' . $positionClass . '">
        <div class="theme-toggle" id="themeToggle">
            <span class="theme-toggle-label">
                <i class="fas fa-palette mr-1"></i>
                Theme
            </span>
            <div class="theme-toggle-switch">
                <div class="theme-toggle-slider">
                    <i class="fas fa-sun" id="lightIcon"></i>
                    <i class="fas fa-moon" id="darkIcon" style="display: none;"></i>
                </div>
            </div>
        </div>
    </div>';
}

// Function untuk include theme assets
function includeThemeAssets() {
    echo '<link href="assets/css/theme-toggle.css" rel="stylesheet">';
    echo '<script src="assets/js/theme-toggle.js"></script>';
}
?>
