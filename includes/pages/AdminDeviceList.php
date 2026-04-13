<?php

class AdminDeviceList extends Page {

    public function canUse($userLevel) {
        return $userLevel === Page::SYSADMIN_USER;
    }

    public function execute() {
        return true;
    }

    public function getOutput() {
        global $gvPath;

        $page = new WebPageOutput();
        $page->setHtmlPageTitle($this->getPageTitle());

        /** ✅ CSS + Header + Navbar **/
        $page->setHtmlBodyHeader($this->getDesignCSS() . $this->getHeaderAndNav());

        /** ✅ Contenu **/
        $page->setHtmlBodyContent($this->getPageContent());

        return $page;
    }

    /** ✅ HEADER + NAVBAR (onglet actif : Dispositivi) */
    private function getHeaderAndNav() {
        global $gvPath;

        return <<<HTML
<div class="admin-header">
    <h2>Pannello amministrazione FastQueue</h2>
    <span>Gestione del sistema</span>
</div>

<div class="admin-navbar">
    <a class="nav-item" href="$gvPath/application/adminPage">Dashboard</a>
    <a class="nav-item" href="$gvPath/application/adminOperatorList">Operatori</a>
    <a class="nav-item" href="$gvPath/application/adminDeskList">Sportelli</a>
    <a class="nav-item" href="$gvPath/application/adminTopicalDomainList">Aree tematiche</a>
    <a class="nav-item active" href="$gvPath/application/adminDeviceList">Dispositivi</a>
    <a class="nav-item" href="$gvPath/application/adminStats">Statistiche</a>
    <a class="nav-item" href="$gvPath/application/adminSettings">Impostazioni</a>
    <a class="nav-item" style="margin-left:auto;" href="$gvPath/application/logoutPage">Logout</a>
</div>
HTML;
    }

    /** ✅ CONTENU PRINCIPAL */
    public function getPageContent() {
        global $gvPath;

        $tableBody = $this->getTableBody();

        return <<<HTML
<div class="content-wrapper">

    <h3 class="section-title">Gestione dispositivi</h3>

    <div class="table-container">
        <table class="styled-table">
            <tr>
                <th>Indirizzo IP</th>
                <th>Funzione</th>
                <th>Aree tematiche</th>
                <th>Azioni</th>
            </tr>
            $tableBody
        </table>
    </div>

    <div class="actions">
        <a class="btn-primary" href="$gvPath/application/adminDeviceEdit">+ Aggiungi dispositivo</a>
    </div>

</div>
HTML;
    }

    /** ✅ TABLE BODY */
    private function getTableBody() {
        global $gvPath;

        $devices = Device::fromDatabaseCompleteList();

        if (count($devices) === 0) {
            return '<tr><td colspan="4" style="text-align:center;color:#777;padding:20px;">Nessun dispositivo</td></tr>';
        }

        $ret = "";

        foreach ($devices as $device) {

            if ($device->getDeskNumber()) {
                $roleMsg = "Display sportello " . $device->getDeskNumber();
                $tdText  = "";
            } else {
                $roleMsg = "Display di sala";
                $tdText  = $device->getTdCode() ? $device->getTdCode() : "Tutte";
            }

            $ret .= <<<HTML
<tr>
    <td>{$device->getIpAddress()}</td>
    <td>$roleMsg</td>
    <td>$tdText</td>
    <td>
        <a class="action-link" href="$gvPath/application/adminDeviceEdit?dev_id={$device->getId()}">Modifica</a>
        |
        <a class="remove-link" href="$gvPath/ajax/removeRecord?dev_id={$device->getId()}">Rimuovi</a>
    </td>
</tr>
HTML;
        }

        return $ret;
    }

    public function getPageTitle() {
        return "Gestione dispositivi";
    }

    /** ✅ CSS GLOBAL IDENTIQUE AUX AUTRES PAGES */
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
    color: #fff;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}
.admin-header h2 { font-size: 26px; font-weight: 700; }

/* NAVBAR ----------------------------------------------------- */
.admin-navbar {
    background: white;
    padding: 12px 30px;
    display: flex;
    gap: 18px;
    border-bottom: 1px solid rgba(0,0,0,0.10);
}
.nav-item {
    padding: 8px 18px;
    border-radius: 40px;
    text-decoration: none;
    font-weight: 600;
    color: hsl(354,82%,70%);
    transition: .3s;
}
.nav-item:hover { background: hsl(354,82%,90%); }
.nav-item.active {
    background: hsl(354,82%,70%);
    color: white;
}

/* CONTENT ----------------------------------------------------- */
.content-wrapper { padding: 40px; }
.section-title {
    font-size: 22px;
    font-weight: 700;
    margin-bottom: 15px;
}

/* TABLE ----------------------------------------------------- */
.table-container {
    background: #fff;
    padding: 25px;
    border-radius: 16px;
    box-shadow: 0 4px 18px rgba(0,0,0,0.08);
}
.styled-table {
    width: 100%;
    border-collapse: collapse;
}
.styled-table th {
    background: hsl(354,82%,70%);
    color: white;
    padding: 12px;
    text-align: left;
}
.styled-table td {
    padding: 12px;
    border-bottom: 1px solid #eee;
}
.styled-table tr:hover {
    background: hsl(354,82%,95%);
}

/* ACTION BUTTONS -------------------------------------------- */
.actions {
    margin-top: 25px;
}
.btn-primary {
    background: hsl(354,82%,70%);
    color: white;
    padding: 10px 20px;
    border-radius: 30px;
    text-decoration: none;
    font-weight: 600;
}
.btn-primary:hover {
    background: hsl(354,82%,60%);
}

/* LINKS ------------------------------------------------------ */
.action-link { color: hsl(354,82%,60%); font-weight: 600; text-decoration:none; }
.action-link:hover { text-decoration: underline; }

.remove-link { color: #b71c1c; font-weight: 600; text-decoration:none; }
.remove-link:hover { text-decoration: underline; }

</style>
CSS;
    }
}