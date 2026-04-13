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

    /** ✅ LAYOUT AVEC SIDEBAR + ICONES PRO */
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

            <a href="$gvPath/application/adminPage" class="menu-item">
                <i class="fa-solid fa-house"></i> Dashboard
            </a>

            <a href="$gvPath/application/adminOperatorList" class="menu-item">
                <i class="fa-solid fa-user-gear"></i> Operatori
            </a>

            <a href="$gvPath/application/adminDeskList" class="menu-item active">
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
            <a href="$gvPath/application/adminDeskEdit" class="btn-add">
                <i class="fa-solid fa-plus"></i> Nuovo sportello
            </a>

            <a href="$gvPath/application/adminDeskEdit?pairing=1" class="btn-add">
                <i class="fa-solid fa-computer"></i> Aggiungi questo computer
            </a>
        </div>

    </main>

</div>
HTML;
    }


    /** ✅ TABLE DESK + ICONES EDIT/SUPPR */
    private function getTableBody() {
        global $gvPath;

        $desks = Desk::fromDatabaseCompleteList();

        if (!count($desks)) {
            return '<tr><td colspan="3" style="text-align:center;color:#777;padding:20px;">Nessuno sportello</td></tr>';
        }

        $html = "";

        foreach ($desks as $desk) {

            $id  = $desk->getId();
            $num = $desk->getNumber();
            $ip  = $desk->getIpAddress();

            $html .= <<<HTML
<tr>
    <td>$num</td>
    <td>$ip</td>
    <td class="actions-col">

        <a href="$gvPath/application/adminDeskEdit?desk_id=$id" class="icon-btn edit">
            <i class="fa-solid fa-pen-to-square"></i>
        </a>

        <a href="$gvPath/ajax/removeRecord?desk_id=$id" class="icon-btn delete">
            <i class="fa-solid fa-trash"></i>
        </a>

    </td>
</tr>
HTML;
        }

        return $html;
    }


    public function getPageTitle() {
        return "Gestione sportelli";
    }


    /** ✅ CSS COMPLET (VIOLET PREMIUM + ICONES) */
    private function getDesignCSS() {
        return <<<CSS
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

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
    background: linear-gradient(180deg,#6C63FF,#8978FF,#CAB8FF);
    color:white;
    padding:25px 0;
    border-radius:0 25px 25px 0;
    display:flex;
    flex-direction:column;
    box-shadow:3px 0 15px rgba(0,0,0,0.08);
}

.logo-circle {
    width:60px;height:60px;background:white;
    border-radius:50%;
    display:flex;align-items:center;justify-content:center;
    color:#6C63FF;
    font-size:26px;font-weight:800;
    margin:0 auto 10px auto;
}

.sidebar-header { text-align:center;margin-bottom:35px; }
.brand { font-size:17px; opacity:.85; }

/* ✅ MENU */
.menu { display:flex;flex-direction:column; }

.menu-item {
    padding:12px 25px;
    color:white;
    text-decoration:none;
    font-size:15px;
    display:flex;
    align-items:center;
    gap:12px;
    opacity:.85;
    transition:.25s;
}
.menu-item:hover { opacity:1; background:rgba(255,255,255,0.15); }
.menu-item.active { background:rgba(255,255,255,0.30); font-weight:bold; }

.menu-bottom { margin-top:auto; }

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
}
.styled-table td {
    padding:12px;
    border-bottom:1px solid #eee;
}
.styled-table tr:hover { background:#F3EEFF; }

/* ✅ ICONES ÉDIT/SUPPR */
.actions-col {
    display:flex;
    gap:12px;
}

.icon-btn {
    width:38px;
    height:38px;
    border-radius:10px;
    display:flex;
    justify-content:center;
    align-items:center;
    color:white;
    font-size:18px;
    text-decoration:none;
}

/* Modifier */
.icon-btn.edit { background:#6C63FF; }
.icon-btn.edit:hover { background:#5149E8; }

/* Supprimer */
.icon-btn.delete { background:#D94141; }
.icon-btn.delete:hover { background:#B02D2D; }

/* ✅ BUTTON AJOUTER */
.btn-add {
    display:inline-flex;
    align-items:center;
    gap:8px;
    background:#6C63FF;
    color:white;
    padding:12px 22px;
    border-radius:30px;
    font-weight:600;
    text-decoration:none;
    margin-top:25px;
}
.btn-add:hover { background:#5149E8; }

.actions {
    display:flex;
    gap:15px;
    margin-top:25px;
}

</style>
CSS;
    }
}