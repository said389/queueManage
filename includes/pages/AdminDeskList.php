<?php

class AdminDeskList extends Page {

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

    /** ✅ LAYOUT COMPLET AVEC SIDEBAR VIOLETTE */
    private function getLayout() {

        global $gvPath;
        $tableBody = $this->getTableBody();

        return <<<HTML
<div class="layout">

    <!-- ✅ SIDEBAR -->
    <aside class="sidebar">

        <div class="sidebar-header">
            <div class="logo-circle">FQ</div>
            <h3 class="brand">FastQueue Admin</h3>
        </div>

        <nav class="menu">

            <a href="$gvPath/application/adminPage" class="menu-item ">
                <span>🏠</span> Dashboard
            </a>

            <a href="$gvPath/application/adminOperatorList" class="menu-item">
                <span>👤</span> Operatori
            </a>

            <a href="$gvPath/application/adminDeskList" class="menu-item active">
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

        <h2 class="page-title">Gestione Sportelli</h2>

        <div class="table-container">
            <table class="styled-table">
                <tr>
                    <th>Numero</th>
                    <th>Indirizzo IP</th>
                    <th>Azioni</th>
                </tr>

                $tableBody
            </table>
        </div>

        <div class="actions">
            <a href="$gvPath/application/adminDeskEdit" class="btn-primary">+ Aggiungi sportello manualmente</a>
            <a href="$gvPath/application/adminDeskEdit?pairing=1" class="btn-primary">+ Aggiungi questo computer</a>
        </div>

    </main>

</div>
HTML;
    }


    /** ✅ TABLE BODY */
    private function getTableBody() {
        global $gvPath;

        $desks = Desk::fromDatabaseCompleteList();

        if (count($desks) === 0) {
            return '<tr><td colspan="3" style="text-align:center;color:#777;padding:20px;">Nessuno sportello</td></tr>';
        }

        $ret = "";
        foreach ($desks as $desk) {
            $id = $desk->getId();
            $num = $desk->getNumber();
            $ip = $desk->getIpAddress();

            $ret .= <<<HTML
<tr>
    <td>$num</td>
    <td>$ip</td>
    <td>
        <a class="action-link" href="$gvPath/application/adminDeskEdit?desk_id=$id">Modifica</a>
        |
        <a class="remove-link" href="$gvPath/ajax/removeRecord?desk_id=$id">Rimuovi</a>
    </td>
</tr>
HTML;
        }

        return $ret;
    }


    public function getPageTitle() {
        return "Gestione sportelli";
    }


    /** ✅ CSS STYLE VIOLET EXACTEMENT COMME L'IMAGE */
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
    background: white;
    border-radius: 50%;
    display:flex; align-items:center; justify-content:center;
    font-size: 26px; font-weight: 800; color: #6C63FF;
    margin: 0 auto 10px auto;
}
.brand {
    font-size: 17px;
    opacity: 0.85;
}

/* ✅ MENU */
.menu { display:flex; flex-direction:column; }
.menu-item {
    padding:12px 25px;
    color:white;
    text-decoration:none;
    font-size:15px;
    display:flex; gap:12px;
    align-items:center;
    transition:0.25s;
    opacity:0.85;
}
.menu-item:hover {
    opacity:1;
    background:rgba(255,255,255,0.15);
}
.menu-item.active {
    background:rgba(255,255,255,0.25);
    font-weight:bold;
}

/* ✅ MENU BAS */
.menu-bottom {
    margin-top:auto;
    display:flex;
    flex-direction:column;
}
.logout:hover {
    background:rgba(255,50,50,0.25);
}

/* ✅ CONTENT */
.content {
    flex:1;
    padding:45px;
    overflow-y:auto;
}
.page-title {
    font-size:28px;
    margin-bottom:25px;
}

/* ✅ TABLE */
.table-container {
    background:white;
    padding:25px;
    border-radius:15px;
    box-shadow:0 4px 22px rgba(0,0,0,0.08);
}
.styled-table {
    width:100%;
    border-collapse:collapse;
}
.styled-table th {
    background:#6C63FF;
    color:white;
    padding:12px;
    text-align:left;
}
.styled-table td {
    padding:12px;
    border-bottom:1px solid #eee;
}
.styled-table tr:hover {
    background:#F3EEFF;
}

/* ✅ ACTIONS */
.actions {
    margin-top:25px;
    display:flex;
    gap:12px;
}
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
    background:#5149e8;
}

.action-link {
    color:#6C63FF;
    font-weight:600;
    text-decoration:none;
}
.remove-link {
    color:#d9534f;
    font-weight:600;
    text-decoration:none;
}
.action-link:hover,
.remove-link:hover {
    text-decoration:underline;
}

</style>
CSS;
    }
}