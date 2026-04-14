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
    <!-- Sidebar Toggle pour mobile -->
    <button class="mobile-toggle" id="mobileToggle">
        <i class="fas fa-bars"></i>
    </button>
    
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="logo">
                
                <h2>FastQueue</h2>
            </div>
            <span class="admin-badge">Administrateur</span>
        </div>

        <nav class="sidebar-nav">
            <a href="$gvPath/application/adminPage" class="nav-item active">
                <i class="fas fa-tachometer-alt"></i>
                <span>Tableau de bord</span>
            </a>
            <a href="$gvPath/application/adminOperatorList" class="nav-item">
                <i class="fas fa-users"></i>
                <span>Opérateurs</span>
            </a>
            <a href="$gvPath/application/adminDeskList" class="nav-item">
                <i class="fas fa-desktop"></i>
                <span>Compteurs</span>
            </a>
            <a href="$gvPath/application/adminTopicalDomainList" class="nav-item">
                <i class="fas fa-folder-tree"></i>
                <span>Domaines thématiques</span>
            </a>
            <a href="$gvPath/application/adminDeviceList" class="nav-item">
                <i class="fas fa-mobile-alt"></i>
                <span>Appareils</span>
            </a>
            <a href="$gvPath/application/adminStats" class="nav-item">
                <i class="fas fa-chart-line"></i>
                <span>Statistiques</span>
            </a>
            <a href="$gvPath/application/adminSettings" class="nav-item">
                <i class="fas fa-cog"></i>
                <span>Paramètres</span>
            </a>
        </nav>

        <div class="sidebar-footer">
            <a href="$gvPath/application/logoutPage" class="nav-item logout">
                <i class="fas fa-sign-out-alt"></i>
                <span>Déconnexion</span>
            </a>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
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

<!-- Overlay pour mobile -->
<div class="overlay" id="overlay"></div>

<script>
const mobileToggle = document.getElementById('mobileToggle');
const sidebar = document.getElementById('sidebar');
const overlay = document.getElementById('overlay');

if (mobileToggle) {
    mobileToggle.addEventListener('click', () => {
        sidebar.classList.add('open');
        overlay.classList.add('show');
    });
}

if (overlay) {
    overlay.addEventListener('click', () => {
        sidebar.classList.remove('open');
        overlay.classList.remove('show');
    });
}

// Fermer sidebar sur resize si écran large
window.addEventListener('resize', () => {
    if (window.innerWidth > 768) {
        sidebar.classList.remove('open');
        overlay.classList.remove('show');
    }
});
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

        /* Sidebar */
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
            transition: transform 0.3s ease;
            overflow-y: auto;
        }

        .sidebar-header {
            padding: 30px 25px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 15px;
        }

        .logo i {
            font-size: 28px;
            color: #6C63FF;
        }

        .logo h2 {
            font-size: 22px;
            font-weight: 700;
        }

        .admin-badge {
            background: rgba(108, 99, 255, 0.2);
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            color: #6C63FF;
            display: inline-block;
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
        }

        .nav-item i {
            width: 20px;
            font-size: 18px;
        }

        .nav-item:hover {
            background: rgba(108, 99, 255, 0.1);
            color: #fff;
        }

        .nav-item.active {
            background: linear-gradient(90deg, #6C63FF, rgba(108, 99, 255, 0.1));
            color: #fff;
            border-right: 3px solid #6C63FF;
        }

        .sidebar-footer {
            padding: 20px 0;
            border-top: 1px solid rgba(255,255,255,0.1);
        }

        .logout {
            color: #ff6b6b;
        }

        .logout:hover {
            background: rgba(255, 107, 107, 0.1);
        }

        /* Mobile Toggle */
        .mobile-toggle {
            display: none;
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1001;
            background: #6C63FF;
            border: none;
            width: 45px;
            height: 45px;
            border-radius: 12px;
            color: white;
            font-size: 20px;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        /* Overlay */
        .overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 999;
        }

        .overlay.show {
            display: block;
        }

        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 280px;
            min-height: 100vh;
            background: #f5f7fb;
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
            .mobile-toggle {
                display: block;
            }

            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.open {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
            }

            .content-wrapper {
                padding: 80px 15px 20px 15px;
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
        }

        @media (max-width: 480px) {
            .content-wrapper {
                padding: 70px 12px 15px 12px;
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
            .mobile-toggle,
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