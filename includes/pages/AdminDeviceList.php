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


    /** ✅ LAYOUT COMPLET AVEC SIDEBAR PREMIUM + ICONES */
    private function getLayout() {

        global $gvPath;
        $table = $this->getTableBody();

        return <<<HTML
<div class="layout">

    <!-- ✅ SIDEBAR VIOLETTE -->
    <aside class="sidebar">

        <div class="sidebar-header">
            <div class="logo-circle">FQ</div>
            <h3 class="brand">FastQueue Admin</h3>
        </div>

        <nav class="menu">

            <a class="menu-item" href="$gvPath/application/adminPage">
                <i class="fa-solid fa-house"></i> Dashboard
            </a>

            <a class="menu-item" href="$gvPath/application/adminOperatorList">
                <i class="fa-solid fa-user-gear"></i> Operatori
            </a>

            <a class="menu-item" href="$gvPath/application/adminDeskList">
                <i class="fa-solid fa-desktop"></i> Sportelli
            </a>

            <a class="menu-item" href="$gvPath/application/adminTopicalDomainList">
                <i class="fa-solid fa-folder-tree"></i> Aree Tematiche
            </a>

            <a class="menu-item active" href="$gvPath/application/adminDeviceList">
                <i class="fa-solid fa-display"></i> Dispositivi
            </a>

            <a class="menu-item" href="$gvPath/application/adminStats">
                <i class="fa-solid fa-chart-line"></i> Statistiche
            </a>

        </nav>

        <!-- ✅ BAS DE SIDEBAR -->
        <div class="menu-bottom">

            <a class="menu-item" href="$gvPath/application/adminSettings">
                <i class="fa-solid fa-gear"></i> Impostazioni
            </a>

            <a class="menu-item logout" href="$gvPath/application/logoutPage">
                <i class="fa-solid fa-arrow-right-from-bracket"></i> Logout
            </a>

        </div>

    </aside>


    <!-- ✅ CONTENU PRINCIPAL -->
    <main class="content">

        <h2 class="page-title">Gestione Dispositivi</h2>

        <div class="table-container">
            <table class="styled-table">
                <tr>
                    <th>Indirizzo IP</th>
                    <th>Funzione</th>
                    <th>Aree tematiche</th>
                    <th>Azioni</th>
                </tr>

                $table
            </table>
        </div>

        <div class="actions">
            <a class="btn-add" href="$gvPath/application/adminDeviceEdit">
                <i class="fa-solid fa-plus"></i> Aggiungi dispositivo
            </a>
        </div>

    </main>

</div>
HTML;
    }


    /** ✅ TABLE BODY AVEC ICONES PRO */
    private function getTableBody() {
        global $gvPath;

        $devices = Device::fromDatabaseCompleteList();

        if (!count($devices)) {
            return '<tr><td colspan="4" style="text-align:center;color:#777;padding:20px;">Nessun dispositivo</td></tr>';
        }

        $html = "";

        foreach ($devices as $dev) {

            $id  = $dev->getId();
            $ip  = $dev->getIpAddress();

            if ($dev->getDeskNumber()) {
                $role = "Display sportello " . $dev->getDeskNumber();
                $tdTxt = "-";
            } else {
                $role  = "Display di sala";
                $tdTxt = $dev->getTdCode() ? $dev->getTdCode() : "Tutte";
            }

            $html .= <<<HTML
<tr>
    <td>$ip</td>
    <td>$role</td>
    <td>$tdTxt</td>
    <td class="actions-col">

        <a href="$gvPath/application/adminDeviceEdit?dev_id=$id" class="icon-btn edit">
            <i class="fa-solid fa-pen-to-square"></i>
        </a>

        <a href="$gvPath/ajax/removeRecord?dev_id=$id" class="icon-btn delete">
            <i class="fa-solid fa-trash"></i>
        </a>

    </td>
</tr>
HTML;
        }

        return $html;
    }



    public function getPageTitle() {
        return "Gestione dispositivi";
    }



    /** ✅ CSS PREMIUM COMPLET (ICÔNES + VIOLET) */
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
    display:flex;
    flex-direction:column;
    border-radius:0 25px 25px 0;
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

.sidebar-header {
    text-align:center;
    margin-bottom:35px;
}

.menu {
    display:flex;
    flex-direction:column;
}

.menu-item {
    padding:12px 25px;
    display:flex;
    align-items:center;
    gap:12px;
    color:white;
    text-decoration:none;
    opacity:.85;
    transition:.25s;
}
.menu-item:hover { opacity:1; background:rgba(255,255,255,0.15); }
.menu-item.active { background:rgba(255,255,255,0.25); font-weight:bold; }

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
    padding:22px;
    border-radius:15px;
    box-shadow:0 4px 18px rgba(0,0,0,0.08);
}

.styled-table {
    width:100%;
    border-collapse:collapse;
}
.styled-table th {
    background:#6C63FF;
    padding:12px;
    color:white;
    border-radius:6px;
    text-align:left;
}
.styled-table td {
    padding:12px;
    border-bottom:1px solid #eee;
}
.styled-table tr:hover {
    background:#F3EEFF;
}

/* ✅ ICONES ACTIONS */
.actions-col {
    display:flex;
    gap:12px;
}

.icon-btn {
    width:38px;height:38px;
    display:flex;justify-content:center;align-items:center;
    border-radius:10px;
    color:white;
    text-decoration:none;
    font-size:18px;
}

/* Modifier */
.icon-btn.edit { background:#6C63FF; }
.icon-btn.edit:hover { background:#5149E8; }

/* Supprimer */
.icon-btn.delete { background:#D94141; }
.icon-btn.delete:hover { background:#B02D2D; }

/* ✅ BOUTON AJOUTER */
.btn-add {
    display:inline-flex;
    align-items:center;
    gap:10px;
    background:#6C63FF;
    color:white;
    padding:12px 25px;
    border-radius:30px;
    font-weight:600;
    text-decoration:none;
    margin-top:25px;
    font-size:15px;
}
.btn-add:hover {
    background:#5149E8;
}

</style>
CSS;
    }
}