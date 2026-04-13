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

        return <<<HTML
<div class="layout">

    <!-- ✅ SIDEBAR -->
    <aside class="sidebar">

        <div class="sidebar-header">
            <div class="logo-circle">FQ</div>
            <h3 class="brand">FastQueue Admin</h3>
        </div>

        <nav class="menu">

            <a href="$gvPath/application/adminPage" class="menu-item active">
                <i class="fa-solid fa-house"></i> Dashboard
            </a>

            <a href="$gvPath/application/adminOperatorList" class="menu-item">
                <i class="fa-solid fa-user-gear"></i> Operatori
            </a>

            <a href="$gvPath/application/adminDeskList" class="menu-item">
                <i class="fa-solid fa-desktop"></i> Sportelli
            </a>

            <a href="$gvPath/application/adminTopicalDomainList" class="menu-item">
                <i class="fa-solid fa-folder-tree"></i> Aree Tematiche
            </a>

            <a href="$gvPath/application/adminDeviceList" class="menu-item">
                <i class="fa-solid fa-display"></i> Dispositivi
            </a>

            <a href="$gvPath/application/adminStats" class="menu-item">
                <i class="fa-solid fa-chart-line"></i> Statistiche
            </a>

        </nav>

        <!-- ✅ Menu bas -->
        <div class="menu-bottom">

            <a href="$gvPath/application/adminSettings" class="menu-item">
                <i class="fa-solid fa-gear"></i> Impostazioni
            </a>

            <a href="$gvPath/application/logoutPage" class="menu-item logout">
                <i class="fa-solid fa-arrow-right-from-bracket"></i> Logout
            </a>

        </div>

    </aside>


    <!-- ✅ CONTENU PRINCIPAL -->
    <main class="content">

        <h2 class="page-title">Dashboard</h2>

        <!-- ✅ WIDGET -->
        <div class="widget">
            <div class="widget-number">$operatorCount</div>
            <div class="widget-label">
                <strong>Operatori Registrati</strong><br>
                Totale operatori attivi
            </div>
        </div>

        <!-- ✅ CARTES -->
        <div class="cards">

            <div class="card">
                <h3><i class="fa-solid fa-user-gear"></i> Operatori</h3>
                <p>Gestione completa degli operatori.</p>
                <a href="$gvPath/application/adminOperatorList" class="card-link">
                    <i class="fa-solid fa-up-right-from-square"></i> Apri
                </a>
            </div>

            <div class="card">
                <h3><i class="fa-solid fa-desktop"></i> Sportelli</h3>
                <p>Configurazione e supervisione degli sportelli.</p>
                <a href="$gvPath/application/adminDeskList" class="card-link">
                    <i class="fa-solid fa-up-right-from-square"></i> Apri
                </a>
            </div>

            <div class="card">
                <h3><i class="fa-solid fa-folder-tree"></i> Aree Tematiche</h3>
                <p>Organizzazione delle aree di servizio.</p>
                <a href="$gvPath/application/adminTopicalDomainList" class="card-link">
                    <i class="fa-solid fa-up-right-from-square"></i> Apri
                </a>
            </div>

            <div class="card">
                <h3><i class="fa-solid fa-display"></i> Dispositivi</h3>
                <p>Monitoraggio e gestione dei dispositivi collegati.</p>
                <a href="$gvPath/application/adminDeviceList" class="card-link">
                    <i class="fa-solid fa-up-right-from-square"></i> Apri
                </a>
            </div>

            <div class="card">
                <h3><i class="fa-solid fa-chart-line"></i> Statistiche</h3>
                <p>Analisi dettagliate sull'attività del sistema.</p>
                <a href="$gvPath/application/adminStats" class="card-link">
                    <i class="fa-solid fa-up-right-from-square"></i> Apri
                </a>
            </div>

            <div class="card">
                <h3><i class="fa-solid fa-gear"></i> Impostazioni</h3>
                <p>Impostazioni generali del sistema FastQueue.</p>
                <a href="$gvPath/application/adminSettings" class="card-link">
                    <i class="fa-solid fa-up-right-from-square"></i> Apri
                </a>
            </div>

        </div>

    </main>

</div>
HTML;
    }


    /** ✅ CSS complet avec FontAwesome */
    private function getDesignCSS() {
        return <<<CSS
<!-- ✅ Font Awesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>

body {
    margin: 0;
    background: #F0ECFF;
    font-family: 'Segoe UI', sans-serif;
}

.layout {
    display: flex;
    height: 100vh;
}

/* ✅ SIDEBAR */
.sidebar {
    width: 250px;
    background: linear-gradient(180deg, #6C63FF, #8B7FFF, #C7B8FF);
    padding: 25px 0;
    border-radius: 0 25px 25px 0;
    display: flex;
    flex-direction: column;
    color: white;
}

.logo-circle {
    width: 65px;
    height: 65px;
    border-radius: 50%;
    background: white;
    font-size: 26px;
    color: #6C63FF;
    font-weight: 800;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 10px auto;
}

.sidebar-header {
    text-align: center;
    margin-bottom: 35px;
}

.brand {
    font-size: 17px;
    opacity: 0.85;
}

/* ✅ MENU */
.menu {
    display: flex;
    flex-direction: column;
}

.menu-item {
    padding: 12px 25px;
    display: flex;
    align-items: center;
    gap: 12px;
    color: white;
    text-decoration: none;
    opacity: .85;
    transition: .25s;
}

.menu-item:hover {
    opacity: 1;
    background: rgba(255,255,255,0.15);
}

.menu-item.active {
    background: rgba(255,255,255,0.30);
    font-weight: bold;
}

.menu-bottom {
    margin-top: auto;
}

/* ✅ CONTENU */
.content {
    flex: 1;
    padding: 40px;
    overflow-y: auto;
}

.page-title {
    font-size: 28px;
    margin-bottom: 25px;
}

/* ✅ WIDGET */
.widget {
    display: flex;
    align-items: center;
    gap: 20px;
    background: white;
    padding: 25px;
    border-radius: 15px;
    box-shadow: 0 4px 22px rgba(0,0,0,0.08);
    margin-bottom: 35px;
}

.widget-number {
    font-size: 38px;
    font-weight: 700;
    color: #6C63FF;
}

/* ✅ CARDS */
.cards {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(260px,1fr));
    gap: 25px;
}

.card {
    background: white;
    padding: 22px;
    border-radius: 15px;
    box-shadow: 0 4px 22px rgba(0,0,0,0.08);
    transition: .25s;
}

.card:hover {
    transform: translateY(-3px);
}

.card h3 {
    display: flex;
    align-items: center;
    gap: 10px;
}

.card-link {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    margin-top: 12px;
    color: #6C63FF;
    font-weight: 600;
    text-decoration: none;
}

.card-link:hover {
    text-decoration: underline;
}

</style>
CSS;
    }

}