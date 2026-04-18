<?php

class AdminPage extends Page {
    
    public function canUse($userLevel) {
        return $userLevel === Page::SYSADMIN_USER;
    }

    public function execute() {
        return true;
    }

    public function getOutput() {
        $page = new WebPageOutput();
        $page->setHtmlPageTitle("Dashboard FastQueue");
        $page->setHtmlBodyHeader($this->getDesignCSS());
        $page->setHtmlBodyContent($this->getLayout());
        return $page;
    }

    private function getLayout() {
        global $gvPath;

        $operatorCount = count(Operator::fromDatabaseCompleteList());
        $deskCount = count(Desk::fromDatabaseCompleteList());
        $tdCount = count(TopicalDomain::fromDatabaseCompleteList(false));
        $deviceCount = count(Device::fromDatabaseCompleteList());

        return <<<HTML
<div class="layout">
    
    <!-- Sidebar Collapsible -->
    <aside class="sidebar" id="sidebar">
        <!-- Bouton Toggle du Sidebar -->
        <button class="sidebar-toggle" id="sidebarToggle" aria-label="Basculer la barre latérale">
            <i class="fas fa-chevron-left"></i>
        </button>

        <div class="sidebar-header">
            <div class="logo">
                <i class="fas fa-tasks"></i>
                <h2 class="logo-text">FastQueue</h2>
            </div>
            <span class="admin-badge">Administrateur</span>
        </div>

        <nav class="sidebar-nav">
            <a href="$gvPath/application/adminPage" class="nav-item active" data-menu-item="dashboard" title="Tableau de bord">
                <i class="fas fa-tachometer-alt"></i>
                <span class="nav-text">Tableau de bord</span>
            </a>
            <a href="$gvPath/application/adminOperatorList" class="nav-item" data-menu-item="operators" title="Opérateurs">
                <i class="fas fa-users"></i>
                <span class="nav-text">Opérateurs</span>
            </a>
            <a href="$gvPath/application/adminDeskList" class="nav-item" data-menu-item="desks" title="Compteurs">
                <i class="fas fa-desktop"></i>
                <span class="nav-text">Compteurs</span>
            </a>
            <a href="$gvPath/application/adminTopicalDomainList" class="nav-item" data-menu-item="domains" title="Domaines thématiques">
                <i class="fas fa-folder-tree"></i>
                <span class="nav-text">Domaines thématiques</span>
            </a>
            <a href="$gvPath/application/adminDeviceList" class="nav-item" data-menu-item="devices" title="Appareils">
                <i class="fas fa-mobile-alt"></i>
                <span class="nav-text">Appareils</span>
            </a>
            <a href="$gvPath/application/adminStats" class="nav-item" data-menu-item="stats" title="Statistiques">
                <i class="fas fa-chart-line"></i>
                <span class="nav-text">Statistiques</span>
            </a>
            <a href="$gvPath/application/adminSettings" class="nav-item" data-menu-item="settings" title="Paramètres">
                <i class="fas fa-cog"></i>
                <span class="nav-text">Paramètres</span>
            </a>
        </nav>

        <div class="sidebar-footer">
            <a href="$gvPath/application/logoutPage" class="nav-item logout" data-menu-item="logout" title="Déconnexion">
                <i class="fas fa-sign-out-alt"></i>
                <span class="nav-text">Déconnexion</span>
            </a>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="main-content" id="mainContent">
        <div class="content-wrapper">
            <div class="page-header">
                <h1>Tableau de bord</h1>
                <p class="subtitle">Vue d'ensemble du système FastQueue</p>
            </div>

            <!-- Statistiques Widgets -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #667eea, #764ba2);">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-details">
                        <div class="stat-number">$operatorCount</div>
                        <div class="stat-label">Opérateurs</div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #f093fb, #f5576c);">
                        <i class="fas fa-desktop"></i>
                    </div>
                    <div class="stat-details">
                        <div class="stat-number">$deskCount</div>
                        <div class="stat-label">Compteurs</div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #4facfe, #00f2fe);">
                        <i class="fas fa-folder-tree"></i>
                    </div>
                    <div class="stat-details">
                        <div class="stat-number">$tdCount</div>
                        <div class="stat-label">Domaines thématiques</div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #fa709a, #fee140);">
                        <i class="fas fa-mobile-alt"></i>
                    </div>
                    <div class="stat-details">
                        <div class="stat-number">$deviceCount</div>
                        <div class="stat-label">Appareils</div>
                    </div>
                </div>
            </div>

            <!-- Modules Cards -->
            <div class="section-header">
                <i class="fas fa-cubes"></i>
                <h3>Modules de gestion</h3>
            </div>

            <div class="cards-grid">
                <div class="card">
                    <div class="card-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="card-content">
                        <h3>Opérateurs</h3>
                        <p>Gestion complète des opérateurs du système.</p>
                        <a href="$gvPath/application/adminOperatorList" class="card-link">
                            <span>Gérer les opérateurs</span>
                            <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>

                <div class="card">
                    <div class="card-icon">
                        <i class="fas fa-desktop"></i>
                    </div>
                    <div class="card-content">
                        <h3>Compteurs</h3>
                        <p>Configuration et supervision des compteurs.</p>
                        <a href="$gvPath/application/adminDeskList" class="card-link">
                            <span>Gérer les compteurs</span>
                            <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>

                <div class="card">
                    <div class="card-icon">
                        <i class="fas fa-folder-tree"></i>
                    </div>
                    <div class="card-content">
                        <h3>Domaines thématiques</h3>
                        <p>Organisation des domaines de service.</p>
                        <a href="$gvPath/application/adminTopicalDomainList" class="card-link">
                            <span>Gérer les domaines</span>
                            <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>

                <div class="card">
                    <div class="card-icon">
                        <i class="fas fa-mobile-alt"></i>
                    </div>
                    <div class="card-content">
                        <h3>Appareils</h3>
                        <p>Suivi et gestion des appareils connectés.</p>
                        <a href="$gvPath/application/adminDeviceList" class="card-link">
                            <span>Gérer les appareils</span>
                            <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>

                <div class="card">
                    <div class="card-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="card-content">
                        <h3>Statistiques</h3>
                        <p>Analyses détaillées de l'activité système.</p>
                        <a href="$gvPath/application/adminStats" class="card-link">
                            <span>Voir les stats</span>
                            <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>

                <div class="card">
                    <div class="card-icon">
                        <i class="fas fa-cog"></i>
                    </div>
                    <div class="card-content">
                        <h3>Paramètres</h3>
                        <p>Configuration générale de FastQueue.</p>
                        <a href="$gvPath/application/adminSettings" class="card-link">
                            <span>Configurer</span>
                            <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<script>
    // ===== GESTION DU SIDEBAR COLLAPSIBLE =====
    
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.getElementById('mainContent');
    const navItems = document.querySelectorAll('.nav-item');
    
    // Variable pour tracker l'état du sidebar
    let sidebarIsExpanded = true;
    
    // Fonction pour RÉDUIRE le sidebar
    function collapseSidebar() {
        sidebar.classList.add('collapsed');
        mainContent.classList.add('expanded');
        sidebarIsExpanded = false;
        updateToggleIcon();
        localStorage.setItem('sidebarState', 'collapsed');
        console.log('Sidebar réduit');
    }
    
    // Fonction pour AGRANDIR le sidebar
    function expandSidebar() {
        sidebar.classList.remove('collapsed');
        mainContent.classList.remove('expanded');
        sidebarIsExpanded = true;
        updateToggleIcon();
        localStorage.setItem('sidebarState', 'expanded');
        console.log('Sidebar agrandi');
    }
    
    // Fonction pour BASCULER le sidebar
    function toggleSidebar() {
        console.log('Toggle appelé - État actuel:', sidebarIsExpanded);
        if (sidebarIsExpanded) {
            collapseSidebar();
        } else {
            expandSidebar();
        }
    }
    
    // Mettre à jour l'icône du bouton
    function updateToggleIcon() {
        const icon = sidebarToggle.querySelector('i');
        if (sidebarIsExpanded) {
            icon.classList.remove('fa-chevron-right');
            icon.classList.add('fa-chevron-left');
        } else {
            icon.classList.remove('fa-chevron-left');
            icon.classList.add('fa-chevron-right');
        }
    }
    
    // ===== EVENT LISTENERS =====
    
    // Bouton toggle du sidebar
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            toggleSidebar();
            // Animation du bouton
            this.classList.add('animate');
            setTimeout(() => this.classList.remove('animate'), 300);
        });
        
        // Initialiser l'état
        updateToggleIcon();
    }
    
    // Restaurer l'état précédent du sidebar
    function restoreSidebarState() {
        const savedState = localStorage.getItem('sidebarState');
        if (savedState === 'collapsed') {
            collapseSidebar();
        } else {
            expandSidebar();
        }
    }
    
    // Appeler au chargement
    restoreSidebarState();
    
    // Fermer sidebar automatiquement sur petit écran
    window.addEventListener('load', () => {
        if (window.innerWidth <= 768) {
            collapseSidebar();
        }
    });
    
    // Gérer le redimensionnement
    window.addEventListener('resize', () => {
        if (window.innerWidth <= 768 && sidebarIsExpanded) {
            collapseSidebar();
        } else if (window.innerWidth > 768 && !sidebarIsExpanded) {
            expandSidebar();
        }
    });
    
    // Mettre à jour l'élément actif du menu
    function updateActiveMenuItem() {
        const currentPath = window.location.pathname;
        navItems.forEach(item => {
            item.classList.remove('active');
            if (item.href.includes(currentPath)) {
                item.classList.add('active');
            }
        });
    }
    
    // Appeler au chargement
    updateActiveMenuItem();
</script>
HTML;
    }

    /** ✅ CSS complet avec FontAwesome et design moderne */
    private function getDesignCSS() {
        return <<<CSS
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: #f5f7fb;
            color: #1a1a2e;
            overflow-x: hidden;
        }

        /* Layout */
        .layout {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar Collapsible */
        .sidebar {
            width: 280px;
            background: linear-gradient(180deg, #1a1a2e 0%, #16213e 100%);
            color: #fff;
            display: flex;
            flex-direction: column;
            position: fixed;
            left: 0;
            top: 0;
            height: 100vh;
            z-index: 1000;
            transition: width 0.3s ease;
            overflow-y: auto;
        }

        /* Sidebar Réduit */
        .sidebar.collapsed {
            width: 80px;
        }

        .sidebar.collapsed .sidebar-header {
            padding: 20px 15px;
        }

        .sidebar.collapsed .logo {
            justify-content: center;
            margin-bottom: 20px;
        }

        .sidebar.collapsed .logo-text,
        .sidebar.collapsed .admin-badge,
        .sidebar.collapsed .nav-text {
            display: none;
        }

        .sidebar.collapsed .nav-item {
            justify-content: center;
            padding: 12px 15px;
        }

        .sidebar.collapsed .nav-item i {
            width: auto;
        }

        /* Bouton Toggle du Sidebar */
        .sidebar-toggle {
            position: absolute;
            top: 20px;
            right: -15px;
            z-index: 1001;
            background: linear-gradient(135deg, #6C63FF, #8B82FF);
            border: none;
            width: 35px;
            height: 35px;
            border-radius: 50%;
            color: white;
            font-size: 18px;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(108, 99, 255, 0.3);
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .sidebar-toggle:hover {
            transform: scale(1.1);
            box-shadow: 0 6px 16px rgba(108, 99, 255, 0.4);
        }

        .sidebar-toggle:active {
            transform: scale(0.95);
        }

        .sidebar-toggle.animate {
            animation: buttonPulse 0.3s ease;
        }

        .sidebar-header {
            padding: 30px 25px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            transition: padding 0.3s ease;
            position: relative;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 15px;
            transition: all 0.3s ease;
        }

        .logo i {
            font-size: 28px;
            color: #6C63FF;
            flex-shrink: 0;
        }

        .logo-text {
            font-size: 22px;
            font-weight: 700;
            transition: opacity 0.3s ease;
        }

        .admin-badge {
            background: rgba(108, 99, 255, 0.2);
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            color: #6C63FF;
            display: inline-block;
            transition: opacity 0.3s ease;
        }

        .sidebar-nav {
            flex: 1;
            padding: 20px 0;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 25px;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            transition: all 0.3s ease;
            font-size: 14px;
            font-weight: 500;
            position: relative;
        }

        .nav-text {
            transition: opacity 0.3s ease;
        }

        .nav-item i {
            width: 20px;
            font-size: 18px;
            flex-shrink: 0;
        }

        .nav-item:hover {
            background: rgba(108, 99, 255, 0.1);
            color: #fff;
            padding-left: 30px;
        }

        .sidebar.collapsed .nav-item:hover {
            padding-left: 15px;
            background: rgba(108, 99, 255, 0.2);
            border-radius: 10px;
        }

        .nav-item.active {
            background: linear-gradient(90deg, #6C63FF, rgba(108, 99, 255, 0.1));
            color: #fff;
            border-right: 3px solid #6C63FF;
        }

        .sidebar.collapsed .nav-item.active {
            border-right: none;
            background: linear-gradient(90deg, rgba(108, 99, 255, 0.3), rgba(108, 99, 255, 0.1));
            border-radius: 10px;
        }

        .sidebar-footer {
            padding: 20px 0;
            border-top: 1px solid rgba(255,255,255,0.1);
            transition: padding 0.3s ease;
        }

        .logout {
            color: #ff6b6b;
        }

        .logout:hover {
            background: rgba(255, 107, 107, 0.1);
            padding-left: 30px;
        }

        .sidebar.collapsed .logout:hover {
            padding-left: 15px;
            background: rgba(255, 107, 107, 0.15);
            border-radius: 10px;
        }

        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 280px;
            min-height: 100vh;
            background: #f5f7fb;
            transition: margin-left 0.3s ease;
        }

        .main-content.expanded {
            margin-left: 80px;
        }

        .content-wrapper {
            padding: 30px 40px;
            max-width: 1400px;
            margin: 0 auto;
        }

        /* Page Header */
        .page-header {
            margin-bottom: 30px;
        }

        .page-header h1 {
            font-size: 28px;
            font-weight: 700;
            color: #1a1a2e;
            margin-bottom: 8px;
        }

        .subtitle {
            color: #666;
            font-size: 14px;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
        }

        .stat-icon {
            width: 55px;
            height: 55px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
        }

        .stat-details {
            flex: 1;
        }

        .stat-number {
            font-size: 32px;
            font-weight: 800;
            color: #1a1a2e;
            line-height: 1;
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: 13px;
            color: #666;
        }

        /* Section Header */
        .section-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
        }

        .section-header i {
            font-size: 24px;
            color: #6C63FF;
        }

        .section-header h3 {
            font-size: 18px;
            font-weight: 600;
            color: #1a1a2e;
        }

        /* Cards Grid */
        .cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 25px;
        }

        .card {
            background: white;
            border-radius: 16px;
            padding: 25px;
            display: flex;
            gap: 18px;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 24px rgba(0,0,0,0.1);
        }

        .card-icon {
            width: 55px;
            height: 55px;
            background: linear-gradient(135deg, #6C63FF, #8B82FF);
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
            flex-shrink: 0;
        }

        .card-content {
            flex: 1;
        }

        .card-content h3 {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 8px;
            color: #1a1a2e;
        }

        .card-content p {
            font-size: 13px;
            color: #666;
            margin-bottom: 15px;
            line-height: 1.5;
        }

        .card-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #6C63FF;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            transition: gap 0.3s ease;
        }

        .card-link:hover {
            gap: 12px;
        }

        /* Animations */
        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }

        @keyframes slideIn {
            from {
                transform: translateX(-100%);
            }
            to {
                transform: translateX(0);
            }
        }

        @keyframes buttonPulse {
            0%, 100% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.15);
            }
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .content-wrapper {
                padding: 20px 25px;
            }
            
            .cards-grid {
                grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            }
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 80px;
            }

            .sidebar.collapsed {
                width: 80px;
            }

            .main-content {
                margin-left: 80px;
            }

            .main-content.expanded {
                margin-left: 80px;
            }

            .sidebar-toggle {
                display: none;
            }

            .content-wrapper {
                padding: 20px 15px;
            }

            .stats-grid {
                grid-template-columns: 1fr;
                gap: 15px;
            }

            .cards-grid {
                grid-template-columns: 1fr;
                gap: 15px;
            }

            .card {
                padding: 18px;
            }

            .page-header h1 {
                font-size: 24px;
            }

            .sidebar.collapsed .logo-text,
            .sidebar.collapsed .admin-badge {
                display: none;
            }

            .nav-item:hover {
                padding-left: 15px;
            }
        }

        @media (max-width: 480px) {
            .content-wrapper {
                padding: 15px 12px;
            }

            .stat-card {
                padding: 15px;
            }

            .stat-icon {
                width: 45px;
                height: 45px;
                font-size: 20px;
            }

            .stat-number {
                font-size: 24px;
            }

            .card {
                flex-direction: column;
                text-align: center;
            }

            .card-icon {
                margin: 0 auto;
            }

            .card-link {
                justify-content: center;
            }
        }

        /* Print */
        @media print {
            .sidebar,
            .sidebar-toggle,
            .overlay {
                display: none;
            }

            .main-content {
                margin-left: 0;
            }

            .content-wrapper {
                padding: 0;
            }

            .card {
                break-inside: avoid;
                box-shadow: none;
                border: 1px solid #ddd;
            }
        }

        /* Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #f0f0f0;
        }

        ::-webkit-scrollbar-thumb {
            background: #c0c0c0;
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #a0a0a0;
        }
    </style>
</head>
<body>
CSS;
    }

}
?>