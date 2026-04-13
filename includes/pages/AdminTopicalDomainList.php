<?php

class AdminTopicalDomainList extends Page {

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

    /** ✅ LAYOUT COMPLET AVEC SIDEBAR VIOLET */
    private function getLayout() {
        global $gvPath;

        $table = $this->getTableBody();

        return <<<HTML
<div class="layout">

    <!-- ✅ SIDEBAR -->
    <aside class="sidebar">

        <div class="sidebar-header">
            <div class="logo-circle">FQ</div>
            <h3 class="brand">FastQueue Admin</h3>
        </div>

        <!-- ✅ MENU PRINCIPAL -->
        <nav class="menu">
            <a class="menu-item" href="$gvPath/application/adminPage"><span>🏠</span> Dashboard</a>
            <a class="menu-item" href="$gvPath/application/adminOperatorList"><span>👤</span> Operatori</a>
            <a class="menu-item" href="$gvPath/application/adminDeskList"><span>🖥️</span> Sportelli</a>
            <a class="menu-item active" href="$gvPath/application/adminTopicalDomainList"><span>📂</span> Aree Tematiche</a>
            <a class="menu-item" href="$gvPath/application/adminDeviceList"><span>📱</span> Dispositivi</a>
            <a class="menu-item" href="$gvPath/application/adminStats"><span>📈</span> Statistiche</a>
        </nav>

        <!-- ✅ MENU EN BAS -->
        <div class="menu-bottom">
            <a class="menu-item" href="$gvPath/application/adminSettings"><span>⚙️</span> Impostazioni</a>
            <a class="menu-item logout" href="$gvPath/application/logoutPage"><span>🚪</span> Logout</a>
        </div>

    </aside>


    <!-- ✅ CONTENU PRINCIPAL -->
    <main class="content">

        <h2 class="page-title">Gestione Aree Tematiche</h2>

        <!-- ✅ TABLE -->
        <div class="table-container">
            <table class="styled-table">
                <tr>
                    <th>Codice</th>
                    <th>Nome</th>
                    <th>Descrizione</th>
                    <th>Attivo?</th>
                    <th>Azioni</th>
                </tr>

                $table
            </table>
        </div>

        <div class="actions">
            <a class="btn-primary" href="$gvPath/application/adminTopicalDomainEdit">+ Aggiungi area tematica</a>
        </div>

    </main>

</div>
HTML;
    }

    /** ✅ TABLE BODY — liens 100% corrigés */
    private function getTableBody() {
        global $gvPath;

        $tds = TopicalDomain::fromDatabaseCompleteList(false);

        if (!count($tds)) {
            return '<tr><td colspan="5" style="text-align:center;color:#777;padding:20px;">Nessuna area tematica</td></tr>';
        }

        $html = "";

        foreach ($tds as $td) {

            $id    = $td->getId();
            $code  = $td->getCode();
            $name  = $td->getName();
            $desc  = $td->getDescription();
            $active = $td->getActive() ? "checked" : "";

            $html .= <<<HTML
<tr>
    <td>$code</td>
    <td>$name</td>
    <td>$desc</td>
    <td><input type="checkbox" disabled $active></td>
    <td>
        <a class="action-link" href="$gvPath/application/adminTopicalDomainEdit?td_id=$id">Modifica</a> |
        <a class="remove-link" href="$gvPath/ajax/removeRecord?td_id=$id">Rimuovi</a>
    </td>
</tr>
HTML;
        }

        return $html;
    }

    public function getPageTitle() {
        return "Gestione aree tematiche";
    }

    /** ✅ CSS STYLE VIOLET EXACTEMENT COMME LES AUTRES */
    private function getDesignCSS() {
        return <<<CSS
<style>

/* GLOBAL */
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
    background: linear-gradient(180deg, #6C63FF, #8978FF, #CAB8FF);
    color: white;
    padding: 25px 0;
    display: flex;
    flex-direction: column;
    border-radius: 0 25px 25px 0;
    box-shadow: 3px 0 15px rgba(0,0,0,0.08);
}

.sidebar-header {
    text-align: center;
    margin-bottom: 35px;
}

.logo-circle {
    width: 60px; height: 60px;
    background:white;
    border-radius: 50%;
    display:flex; align-items:center; justify-content:center;
    font-size:26px; font-weight:800;
    color:#6C63FF;
    margin: 0 auto 10px auto;
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
    color: white;
    text-decoration: none;
    font-size: 15px;
    display: flex;
    gap: 12px;
    align-items: center;
    opacity: 0.85;
    transition: 0.25s;
}

.menu-item:hover {
    opacity: 1;
    background: rgba(255,255,255,0.15);
}

.menu-item.active {
    background: rgba(255,255,255,0.25);
    font-weight: bold;
}

/* ✅ MENU BAS */
.menu-bottom {
    margin-top: auto;
    display:flex;
    flex-direction:column;
}
.logout:hover {
    background: rgba(255,50,50,0.25);
}

/* ✅ CONTENU */
.content {
    flex: 1;
    padding: 45px;
    overflow-y: auto;
}
.page-title {
    font-size: 28px;
    margin-bottom: 25px;
}

/* ✅ TABLE STYLÉE */
.table-container {
    background: white;
    padding: 22px;
    border-radius: 15px;
    box-shadow: 0 4px 18px rgba(0,0,0,0.08);
}
.styled-table {
    width: 100%;
    border-collapse: collapse;
}
.styled-table th {
    background:#6C63FF;
    color:white;
    padding:12px;
    text-align:left;
    border-radius:6px;
}
.styled-table td {
    padding:12px;
    border-bottom:1px solid #eee;
}
.styled-table tr:hover {
    background:#F3EEFF;
}

/* ✅ BOUTONS */
.btn-primary {
    background:#6C63FF;
    color:white;
    padding:12px 25px;
    border-radius:30px;
    font-size:15px;
    font-weight:600;
    text-decoration:none;
}
.btn-primary:hover {
    background:#5149E8;
}

/* ✅ LIENS */
.action-link {
    color:#6C63FF;
    font-weight:600;
    text-decoration:none;
}
.action-link:hover {
    text-decoration: underline;
}
.remove-link {
    color:#d9534f;
    font-weight:600;
    text-decoration:none;
}
.remove-link:hover {
    text-decoration: underline;
}

</style>
CSS;
    }
}