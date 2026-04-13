<?php

class AdminOperatorList extends Page {

    public function canUse($userLevel) {
        return $userLevel === Page::SYSADMIN_USER;
    }

    public function execute() {
        return true;
    }

    public function getOutput() {
        global $gvPath;

        $page = new WebPageOutput();
        $page->setHtmlPageTitle("Gestione operatori");

        /* ✅ Ajout du design global */
        $page->setHtmlBodyHeader($this->getDesignCSS() . $this->getHeaderAndNav());
        $page->setHtmlBodyContent($this->getPageContent());

        return $page;
    }

    /* ✅ HEADER + NAV identiques à AdminPage */
    private function getHeaderAndNav() {
        global $gvPath;

        return <<<HTML
<div class="admin-header">
    <h2>Pannello amministrazione FastQueue</h2>
    <span>Gestione del sistema</span>
</div>

<div class="admin-navbar">
    <a class="nav-item" href="$gvPath/application/adminPage">Dashboard</a>
    <a class="nav-item active" href="$gvPath/application/adminOperatorList">Operatori</a>
    <a class="nav-item" href="$gvPath/application/adminDeskList">Sportelli</a>
    <a class="nav-item" href="$gvPath/application/adminTopicalDomainList">Aree tematiche</a>
    <a class="nav-item" href="$gvPath/application/adminDeviceList">Dispositivi</a>
    <a class="nav-item" href="$gvPath/application/adminStats">Statistiche</a>

    <!-- Onglet actif -->
    <a class="nav-item " href="$gvPath/application/adminSettings">Impostazioni</a>

    <a class="nav-item" style="margin-left:auto;" href="$gvPath/application/logoutPage">Logout</a>
</div>
HTML;
    }

    /* ✅ CONTENU (Tableau opérateurs) */
    public function getPageContent() {
        global $gvPath;

        $tableBody = $this->getTableBody();

        return <<<HTML
<div class="content-wrapper">

    <h3 class="section-title">Gestione operatori</h3>

    <div class="table-container">
        <table class="styled-table">
            <tr>
                <th>Codice</th>
                <th>Nome</th>
                <th>Azioni</th>
            </tr>
            $tableBody
        </table>
    </div>

    <div style="margin-top:25px;">
        <a class="btn-primary" href="$gvPath/application/adminOperatorEdit">+ Aggiungi operatore</a>
    </div>

</div>
HTML;
    }

    /* ✅ Corps du tableau (inchangé) */
    private function getTableBody() {
        global $gvPath;

        $operators = Operator::fromDatabaseCompleteList();

        if (count($operators) === 0) {
            return '<tr><td colspan="3" style="text-align:center;color:#777;padding:20px;">Nessun operatore</td></tr>';
        }

        $ret = "";
        foreach ($operators as $operator) {
            $ret .= <<<HTML
<tr>
    <td>{$operator->getCode()}</td>
    <td>{$operator->getFullName()}</td>
    <td>
        <a class="action-link" href="$gvPath/application/adminOperatorEdit?op_id={$operator->getId()}">Modifica</a>
        |
        <a class="action-link remove-link" href="$gvPath/ajax/removeRecord?op_id={$operator->getId()}">Rimuovi</a>
    </td>
</tr>
HTML;
        }
        return $ret;
    }

    /* ✅ CSS IDENTIQUE au thème principal */
    private function getDesignCSS() {
        return <<<CSS
<style>

/* GLOBAL ----------------------------------------------------- */
* { margin: 0; padding: 0; box-sizing: border-box; }
body { background: hsl(210,5%,85%); font-family: 'Segoe UI', Tahoma; }

/* HEADER ----------------------------------------------------- */
.admin-header {
    background: linear-gradient(135deg, hsl(354,82%,70%), hsl(354,62%,78%));
    padding: 22px 40px;
    color: white;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}
.admin-header h2 { font-size: 26px; font-weight: 700; }

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

/* CONTENU ----------------------------------------------------- */
.content-wrapper { padding: 40px; }
.section-title { 
    font-size: 22px; 
    font-weight: 700; 
    margin-bottom: 15px;
}

/* TABLE ----------------------------------------------------- */
.table-container {
    background: white;
    padding: 25px;
    border-radius: 16px;
    box-shadow: 0 4px 18px rgba(0,0,0,0.08);
}
.styled-table {
    width: 100%;
    border-collapse: collapse;
}
.styled-table th {
    padding: 12px;
    background: hsl(354,82%,70%);
    color: white;
    text-align: left;
    font-size: 15px;
}
.styled-table td {
    padding: 12px;
    border-bottom: 1px solid #eee;
}
.styled-table tr:hover {
    background: hsl(354,82%,95%);
}

/* LINKS ----------------------------------------------------- */
.action-link {
    color: hsl(354,82%,60%);
    font-weight: 600;
    text-decoration: none;
}
.action-link:hover { text-decoration: underline; }
.remove-link { color: #c62828; }

/* BUTTON ----------------------------------------------------- */
.btn-primary {
    background: hsl(354,82%,70%);
    color: white;
    padding: 12px 22px;
    border-radius: 30px;
    font-weight: 600;
    text-decoration: none;
}
.btn-primary:hover {
    background: hsl(354,82%,60%);
}

</style>
CSS;
    }
}