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

    <!-- ✅ SIDEBAR STYLE COMME L’IMAGE -->
    <aside class="sidebar">

        <div class="sidebar-header">
            <div class="logo-circle">FQ</div>
            <h3 class="brand">FastQueue Admin</h3>
        </div>

        <nav class="menu">

            <a href="$gvPath/application/adminPage" class="menu-item active">
                <span>🏠</span> Dashboard
            </a>

            <a href="$gvPath/application/adminOperatorList" class="menu-item">
                <span>👤</span> Operatori
            </a>

            <a href="$gvPath/application/adminDeskList" class="menu-item">
                <span>🖥️</span> Sportelli
            </a>

            <a href="$gvPath/application/adminTopicalDomainList" class="menu-item">
                <span>📂</span> Aree Tematiche
            </a>

            <a href="$gvPath/application/adminDeviceList" class="menu-item">
                <span>📱</span> Dispositivi
            </a>

            <a href="$gvPath/application/adminStats" class="menu-item">
                <span>📈</span> Statistiche
            </a>

        </nav>

        <!-- ✅ PARAMÈTRES & LOGOUT EN BAS DE LA SIDEBAR -->
        <div class="menu-bottom">

            <a href="$gvPath/application/adminSettings" class="menu-item">
                <span>⚙️</span> Impostazioni
            </a>

            <a href="$gvPath/application/logoutPage" class="menu-item logout">
                <span>🚪</span> Logout
            </a>

        </div>
    </aside>


    <!-- ✅ CONTENU PRINCIPAL -->
    <main class="content">

        <h2 class="page-title">Dashboard</h2>

        <!-- ✅ WIDGET TOP -->
        <div class="widget">
            <div class="widget-number">$operatorCount</div>
            <div class="widget-label">
                <strong>Operatori Registrati</strong><br>
                Totale operatori attivi
            </div>
        </div>

        <!-- ✅ CARTES STYLE PASTEL -->
        <div class="cards">

            <div class="card">
                <h3>Operatori</h3>
                <p>Gestione completa degli operatori del sistema.</p>
                <a href="$gvPath/application/adminOperatorList" class="card-link">Apri →</a>
            </div>

            <div class="card">
                <h3>Sportelli</h3>
                <p>Configurazione e supervisione degli sportelli.</p>
                <a href="$gvPath/application/adminDeskList" class="card-link">Apri →</a>
            </div>

            <div class="card">
                <h3>Aree Tematiche</h3>
                <p>Organizzazione delle code e categorie di servizio.</p>
                <a href="$gvPath/application/adminTopicalDomainList" class="card-link">Apri →</a>
            </div>

            <div class="card">
                <h3>Dispositivi</h3>
                <p>Monitoraggio e gestione dei dispositivi connessi.</p>
                <a href="$gvPath/application/adminDeviceList" class="card-link">Apri →</a>
            </div>

            <div class="card">
                <h3>Statistiche</h3>
                <p>Analisi e report dettagliati sull’uso del sistema.</p>
                <a href="$gvPath/application/adminStats" class="card-link">Apri →</a>
            </div>

            <div class="card">
                <h3>Impostazioni</h3>
                <p>Configurazioni generali del sistema FastQueue.</p>
                <a href="$gvPath/application/adminSettings" class="card-link">Apri →</a>
            </div>

        </div>

    </main>

</div>
HTML;
    }

    private function getDesignCSS() {
        return <<<CSS
<style>

/* ✅ GLOBAL */
body {
    margin: 0;
    background: #F0ECFF;
    font-family: 'Segoe UI', sans-serif;
}

/* ✅ LAYOUT */
.layout {
    display: flex;
    width: 100%;
    height: 100vh;
}

/* ✅ SIDEBAR (STYLE IDENTIQUE À L’IMAGE) */
.sidebar {
    width: 250px;
    background: linear-gradient(180deg, #6C63FF, #8B7FFF, #C7B8FF);
    color: white;
    padding: 25px 0;
    display: flex;
    flex-direction: column;
    border-radius: 0 25px 25px 0;
    box-shadow: 2px 0 15px rgba(0,0,0,0.08);
}

/* Logo */
.logo-circle {
    width: 65px;
    height: 65px;
    border-radius: 50%;
    background: white;
    margin: 0 auto 10px auto;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #6C63FF;
    font-size: 26px;
    font-weight: 800;
}

.sidebar-header {
    text-align: center;
    margin-bottom: 35px;
}

.brand {
    margin: 0;
    font-size: 17px;
    opacity: 0.85;
    margin-top: 5px;
}

/* ✅ MENU */
.menu {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.menu-item {
    padding: 12px 25px;
    color: white;
    text-decoration: none;
    font-size: 15px;
    display: flex;
    gap: 12px;
    align-items: center;
    opacity: 0.9;
    transition: 0.25s;
}

.menu-item:hover {
    opacity: 1;
    background: rgba(255,255,255,0.15);
}

.menu-item.active {
    background: rgba(255,255,255,0.30);
    font-weight: bold;
}

/* ✅ BAS DE LA SIDEBAR */
.menu-bottom {
    margin-top: auto;
    display: flex;
    flex-direction: column;
}

/* Logout rouge */
.logout:hover {
    background: rgba(255, 50, 50, 0.28);
}

/* ✅ MAIN CONTENT */
.content {
    flex: 1;
    padding: 40px;
    overflow-y: auto;
}

.page-title {
    font-size: 28px;
    margin-bottom: 25px;
}

/* ✅ Widget */
.widget {
    background: white;
    padding: 25px;
    border-radius: 15px;
    display: flex;
    align-items: center;
    gap: 20px;
    box-shadow: 0 4px 22px rgba(0,0,0,0.08);
    margin-bottom: 35px;
}

.widget-number {
    font-size: 38px;
    font-weight: 700;
    color: #6C63FF;
}

/* ✅ Cards pastel style */
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
    transition: 0.25s;
}

.card:hover {
    transform: translateY(-3px);
}

.card-link {
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