<?php

class AdminDeviceList extends Page {

    public function canUse($userLevel) {
        return $userLevel === Page::SYSADMIN_USER;
    }

    public function execute() {
        return true;
    }

    public function getOutput() {

        $page = new WebPageOutput();
        $page->setHtmlPageTitle($this->getPageTitle());

        $page->setHtmlBodyHeader($this->getDesignCSS());
        $page->setHtmlBodyContent($this->getLayout());

        return $page;
    }


    /** ✅ LAYOUT PRINCIPAL */
    private function getLayout() {
        global $gvPath;
        $table = $this->getTableBody();

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
            <a href="$gvPath/application/adminPage" class="nav-item">
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
            <a href="$gvPath/application/adminDeviceList" class="nav-item active">
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
                <h1>Gestion des appareils</h1>
                <p class="subtitle">Gérez les appareils affichage de votre système</p>
            </div>

            <!-- Table des appareils -->
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-mobile-alt"></i>
                    <h3>Liste des appareils</h3>
                </div>
                <div class="table-container">
                    <table class="stats-table">
                        <thead>
                            <tr>
                                <th>Adresse IP</th>
                                <th>Fonction</th>
                                <th>Domaines thématiques</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            $table
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Bouton Ajouter -->
            <div class="actions-section">
                <a href="$gvPath/application/adminDeviceEdit" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Ajouter un appareil
                </a>
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


    /** ✅ TABLE BODY */
    private function getTableBody() {
        global $gvPath;

        $devices = Device::fromDatabaseCompleteList();

        if (!count($devices)) {
            return '<tr><td colspan="4" class="empty-row">Aucun appareil disponible</td></tr>';
        }

        $html = "";

        foreach ($devices as $dev) {

            $id  = $dev->getId();
            $ip  = $dev->getIpAddress();

            if ($dev->getDeskNumber()) {
                $role = "Affichage compteur " . $dev->getDeskNumber();
                $tdTxt = "-";
            } else {
                $role  = "Affichage salle";
                $tdTxt = $dev->getTdCode() ? $dev->getTdCode() : "Tous";
            }

            $html .= <<<HTML
<tr>
    <td>$ip</td>
    <td>$role</td>
    <td>$tdTxt</td>
    <td class="actions-cell">
        <a href="$gvPath/application/adminDeviceEdit?dev_id=$id" class="action-btn edit" title="Modifier">
            <i class="fas fa-edit"></i>
        </a>
        <a href="$gvPath/ajax/removeRecord?dev_id=$id" class="action-btn delete" title="Supprimer">
            <i class="fas fa-trash"></i>
        </a>
    </td>
</tr>
HTML;
        }

        return $html;
    }



    public function getPageTitle() {
        return "Gestion des appareils";
    }



    /** ✅ CSS COMPLET */
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

        /* Cards */
        .card {
            background: white;
            border-radius: 16px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .card-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }

        .card-header i {
            color: #6C63FF;
            font-size: 20px;
        }

        .card-header h3 {
            font-size: 16px;
            font-weight: 600;
            color: #1a1a2e;
            margin: 0;
        }

        /* Table */
        .table-container {
            overflow-x: auto;
        }

        .stats-table {
            width: 100%;
            border-collapse: collapse;
        }

        .stats-table thead {
            background: #f8f9fa;
        }

        .stats-table th {
            padding: 12px 15px;
            text-align: left;
            font-size: 13px;
            font-weight: 600;
            color: #1a1a2e;
            border-bottom: 2px solid #eee;
        }

        .stats-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #f0f0f0;
            font-size: 14px;
        }

        .stats-table tbody tr:hover {
            background: #f8f9fa;
        }

        .empty-row {
            text-align: center;
            color: #999;
            padding: 20px !important;
        }

        /* Actions */
        .actions-cell {
            display: flex;
            gap: 8px;
        }

        .action-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .action-btn.edit {
            background: #6C63FF;
            color: white;
        }

        .action-btn.edit:hover {
            background: #5149E8;
            transform: scale(1.05);
        }

        .action-btn.delete {
            background: #ff6b6b;
            color: white;
        }

        .action-btn.delete:hover {
            background: #ee5a52;
            transform: scale(1.05);
        }

        /* Buttons */
        .actions-section {
            margin-top: 25px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 12px 24px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }

        .btn-primary {
            background: #6C63FF;
            color: white;
        }

        .btn-primary:hover {
            background: #5149E8;
        }

        .btn-secondary {
            background: #e9ecef;
            color: #1a1a2e;
        }

        .btn-secondary:hover {
            background: #dee2e6;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.open {
                transform: translateX(0);
            }

            .mobile-toggle {
                display: flex;
            }

            .main-content {
                margin-left: 0;
            }

            .content-wrapper {
                padding: 20px;
            }

            .page-header h1 {
                font-size: 22px;
            }

            .card {
                padding: 15px;
            }

            .stats-table th,
            .stats-table td {
                padding: 10px;
                font-size: 12px;
            }
        }

    </style>
</head>
</html>
CSS;
    }
}
