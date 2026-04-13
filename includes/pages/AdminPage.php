<?php

class AdminPage extends Page {
    
    public function canUse($userLevel) {
        return $userLevel === Page::SYSADMIN_USER;
    }

    public function execute() {
        return true;
    }

    public function getOutput() {
        global $gvPath;

        $page = new WebPageOutput();
        $page->setHtmlPageTitle('Pannello amministrazione FastQueue');

        // Injecter CSS + Header
        $page->setHtmlBodyHeader($this->getDesignCSS() . $this->getHeaderAndNav());
        $page->setHtmlBodyContent($this->getPageContent());
        
        return $page;
    }

    /** ✅ HEADER + NAVBAR */
    private function getHeaderAndNav() {
        global $gvPath;
        return <<<HTML
<div class="admin-header">
    <h2>Pannello amministrazione FastQueue</h2>
    <span>Gestione del sistema</span>
</div>

<div class="admin-navbar">
    <a class="nav-item active" href="$gvPath/application/adminPage">Dashboard</a>
    <a class="nav-item" href="$gvPath/application/adminOperatorList">Operatori</a>
    <a class="nav-item" href="$gvPath/application/adminDeskList">Sportelli</a>
    <a class="nav-item" href="$gvPath/application/adminTopicalDomainList">Aree tematiche</a>
    <a class="nav-item" href="$gvPath/application/adminDeviceList">Dispositivi</a>
    <a class="nav-item" href="$gvPath/application/adminStats">Statistiche</a>
    <a class="nav-item" href="$gvPath/application/adminSettings">Impostazioni</a>
    <a class="nav-item" style="margin-left:auto;" href="$gvPath/application/logoutPage">Logout</a>
</div>
HTML;
    }

    /** ✅ CONTENU DE LA PAGE */
    public function getPageContent() {
        global $gvPath;

        // ✅ COMPTEUR DYNAMIQUE DES OPERATEURS
        $operatorCount = count(Operator::fromDatabaseCompleteList());

        return <<<HTML
<div class="dashboard-container">

    <!-- ✅ COMPTEUR DYNAMIQUE -->
    <div class="widget-box">
        <div class="number">$operatorCount</div>
        <div>
            <strong>Operatori Registrati</strong><br>
            Numero totale degli operatori attivi nel sistema
        </div>
    </div>

    <!-- DASHBOARD CARDS -->
    <div class="cards-container">

        <div class="card">
            <div class="card-header">
                Operatori
                <span>Gestisci</span>
            </div>
            <div class="card-content">
                Gestisci gli operatori registrati nel sistema.<br>
                <a href="$gvPath/application/adminOperatorList">Vai alla gestione →</a>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                Sportelli
                <span>Gestisci</span>
            </div>
            <div class="card-content">
                Configura e modifica gli sportelli attivi.<br>
                <a href="$gvPath/application/adminDeskList">Vai alla gestione →</a>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                Aree tematiche
                <span>Gestisci</span>
            </div>
            <div class="card-content">
                Organizza le aree tematiche del servizio.<br>
                <a href="$gvPath/application/adminTopicalDomainList">Vai alla gestione →</a>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                Dispositivi
                <span>Gestisci</span>
            </div>
            <div class="card-content">
                Visualizza e configura i dispositivi collegati.<br>
                <a href="$gvPath/application/adminDeviceList">Vai alla gestione →</a>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                Statistiche
                <span>Visualizza</span>
            </div>
            <div class="card-content">
                Consultare le statistiche complete del sistema.<br>
                <a href="$gvPath/application/adminStats">Apri →</a>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                Impostazioni
                <span>Configura</span>
            </div>
            <div class="card-content">
                Configurazioni principali del sistema FastQueue.<br>
                <a href="$gvPath/application/adminSettings">Apri →</a>
            </div>
        </div>

    </div>
</div>
HTML;
    }

    /** ✅ DESIGN CSS GLOBAL */
    private function getDesignCSS() {
        return <<<CSS
<style>

* { margin:0; padding:0; box-sizing:border-box; }
body { background:hsl(210,5%,85%); font-family:'Segoe UI',Tahoma; }

/* HEADER */
.admin-header {
    background:linear-gradient(135deg,hsl(354,82%,70%),hsl(354,62%,78%));
    padding:22px 40px;
    color:white;
    box-shadow:0 4px 12px rgba(0,0,0,0.15);
}
.admin-header h2 { font-size:26px; font-weight:700; }

/* NAVBAR */
.admin-navbar {
    background:white;
    padding:12px 30px;
    display:flex;
    gap:18px;
    border-bottom:1px solid rgba(0,0,0,0.10);
}
.nav-item {
    padding:8px 18px;
    border-radius:30px;
    font-weight:600;
    color:hsl(354,82%,70%);
    text-decoration:none;
    transition:0.3s;
}
.nav-item:hover { background:hsl(354,82%,90%); }
.nav-item.active {
    background:hsl(354,82%,70%);
    color:white;
}

/* DASHBOARD */
.dashboard-container {
    padding:40px;
}

/* WIDGET */
.widget-box {
    background:white;
    padding:28px;
    border-radius:16px;
    box-shadow:0 4px 18px rgba(0,0,0,0.08);
    display:flex;
    gap:20px;
    align-items:center;
    margin-bottom:40px;
}
.widget-box .number {
    font-size:40px;
    font-weight:700;
    color:hsl(354,82%,70%);
}

/* CARDS */
.cards-container {
    display:grid;
    grid-template-columns:repeat(3,1fr);
    gap:30px;
}
.card {
    background:white;
    border-radius:16px;
    box-shadow:0 4px 18px rgba(0,0,0,0.08);
    overflow:hidden;
}
.card-header {
    background:hsl(354,82%,70%);
    color:white;
    padding:20px;
    font-size:18px;
    font-weight:600;
    display:flex;
    justify-content:space-between;
}
.card-content {
    padding:20px;
    font-size:14px;
    color:#444;
}
.card-content a {
    font-weight:600;
    color:hsl(354,82%,70%);
    text-decoration:none;
}
.card-content a:hover { text-decoration:underline; }

</style>
CSS;
    }
}