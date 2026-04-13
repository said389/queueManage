<?php

class AdminOperatorList extends Page {

    public function canUse($userLevel) {
        return $userLevel === Page::SYSADMIN_USER;
    }

    public function execute() {
        return true;
    }

    public function getOutput() {

        $page = new WebPageOutput();
        $page->setHtmlPageTitle("Gestione operatori");

        // ✅ Sidebar + CSS violet + icônes
        $page->setHtmlBodyHeader($this->getDesignCSS());
        $page->setHtmlBodyContent($this->getLayout());

        return $page;
    }


    /** ✅ PAGE LAYOUT COMPLET */
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

        <nav class="menu">

            <a class="menu-item" href="$gvPath/application/adminPage">
                <i class="fa-solid fa-house"></i> Dashboard
            </a>

            <a class="menu-item active" href="$gvPath/application/adminOperatorList">
                <i class="fa-solid fa-user-gear"></i> Operatori
            </a>

            <a class="menu-item" href="$gvPath/application/adminDeskList">
                <i class="fa-solid fa-desktop"></i> Sportelli
            </a>

            <a class="menu-item" href="$gvPath/application/adminTopicalDomainList">
                <i class="fa-solid fa-folder-tree"></i> Aree Tematiche
            </a>

            <a class="menu-item" href="$gvPath/application/adminDeviceList">
                <i class="fa-solid fa-display"></i> Dispositivi
            </a>

            <a class="menu-item" href="$gvPath/application/adminStats">
                <i class="fa-solid fa-chart-line"></i> Statistiche
            </a>

        </nav>

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

        <h2 class="page-title">Gestione operatori</h2>

        <div class="table-container">
            <table class="styled-table">
                <tr>
                    <th>Codice</th>
                    <th>Nome</th>
                    <th>Azioni</th>
                </tr>
                $table
            </table>
        </div>

        <div class="add-btn-wrapper">
            <a class="btn-add" href="$gvPath/application/adminOperatorEdit">
                <i class="fa-solid fa-plus"></i> Aggiungi operatore
            </a>
        </div>

    </main>

</div>
HTML;
    }


    /** ✅ TABLE DYNAMIQUE AVEC ICÔNES */
    private function getTableBody() {
        global $gvPath;
        $ops = Operator::fromDatabaseCompleteList();

        if (!count($ops)) {
            return '<tr><td colspan="3" style="text-align:center;color:#777;padding:16px;">Nessun operatore</td></tr>';
        }

        $html = "";
        foreach ($ops as $op) {

            $id   = $op->getId();
            $code = $op->getCode();
            $name = $op->getFullName();

            $html .= <<<HTML
<tr>
    <td>$code</td>
    <td>$name</td>
    <td class="actions-col">

        <a class="icon-btn edit" href="$gvPath/application/adminOperatorEdit?op_id=$id">
            <i class="fa-solid fa-pen-to-square"></i>
        </a>

        <a class="icon-btn delete" href="$gvPath/ajax/removeRecord?op_id=$id"
           onclick="return confirm('Confermi la rimozione?');">
            <i class="fa-solid fa-trash"></i>
        </a>

    </td>
</tr>
HTML;
        }
        return $html;
    }


    /** ✅ CSS STYLE VIOLET + ICONES PRO */
    private function getDesignCSS() {
        return <<<CSS
<!-- ✅ Font Awesome -->
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
.sidebar-header { text-align:center; margin-bottom:35px; }
.logo-circle {
    width:60px;height:60px;background:white;color:#6C63FF;
    border-radius:50%;display:flex;align-items:center;
    justify-content:center;font-size:26px;font-weight:800;
    margin:0 auto 10px;
}
.brand { opacity:.85; font-size:17px; }

/* MENU */
.menu { display:flex; flex-direction:column; }
.menu-item {
    padding:12px 25px; color:white; text-decoration:none;
    display:flex; gap:12px; align-items:center;
    opacity:.85; transition:.25s;
}
.menu-item:hover { opacity:1; background:rgba(255,255,255,0.18); }
.menu-item.active { background:rgba(255,255,255,0.27); font-weight:bold; }

/* BAS */
.menu-bottom { margin-top:auto; }
.logout:hover { background:rgba(255,40,40,0.28); }

/* CONTENT */
.content { flex:1; padding:45px; overflow-y:auto; }
.page-title { font-size:28px; margin-bottom:30px; }

/* TABLE */
.table-container {
    background:white; padding:20px; border-radius:15px;
    box-shadow:0 4px 20px rgba(0,0,0,0.08);
}
.styled-table { width:100%; border-collapse:collapse; }
.styled-table th {
    background:#6C63FF; color:white; padding:12px;
    border-radius:6px; text-align:left;
}
.styled-table td { padding:12px; border-bottom:1px solid #eee; }
.styled-table tr:hover { background:#F3EEFF; }

/* ✅ ICON BUTTONS */
.actions-col {
    display:flex;
    gap:10px;
}

.icon-btn {
    width:38px;
    height:38px;
    border-radius:10px;
    display:flex;
    align-items:center;
    justify-content:center;
    color:white;
    font-size:16px;
    text-decoration:none;
}

/* Modifier */
.icon-btn.edit {
    background:#6C63FF;
}
.icon-btn.edit:hover {
    background:#5149E8;
}

/* Supprimer */
.icon-btn.delete {
    background:#D94141;
}
.icon-btn.delete:hover {
    background:#B32E2E;
}

/* Ajouter bouton */
.btn-add {
    display:inline-flex;
    align-items:center;
    gap:10px;
    background:#6C63FF;
    color:white;
    padding:12px 22px;
    border-radius:30px;
    text-decoration:none;
    font-weight:600;
    margin-top:25px;
}
.btn-add:hover {
    background:#5149E8;
}

</style>
CSS;
    }
}