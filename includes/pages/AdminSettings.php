<?php

class AdminSettings extends Page {

    private $message = '';

    public function canUse($userLevel) {
        return $userLevel === Page::SYSADMIN_USER;
    }

    public function execute() {
        global $gvEditableConfs, $gvDirectory;

        $modifiedConfs = array();

        foreach ($gvEditableConfs as $conf) {

            if (!isset($_POST[$conf->getName()])) {
                if ($conf->getType() == 'boolean') {
                    $_POST[$conf->getName()] = 0;
                } else {
                    $this->message = "Errore nel processare la richiesta. Riprovare in seguito.";
                    return true;
                }
            }

            $newValue = $_POST[$conf->getName()];
            if ($newValue === '') {
                $newValue = $conf->getDefault();
            }

            $conf->setNewValue($newValue);
            $modifiedConfs[] = $conf;
        }

        $generator = new LocalSettingsGenerator($modifiedConfs);

        if ($generator->writeFile("$gvDirectory/LocalSettings.php")) {
            foreach ($modifiedConfs as $conf) {
                $conf->exportNewValue();
            }

            global $gvPath;
            $this->message = "Configurazione salvata correttamente.<br>" .
                             "È possibile tornare al <a href=\"$gvPath/application/adminPage\">menù principale</a>.";

            return true;

        } else {
            $this->message = "Errore nel salvataggio del file. Controllare i permessi di scrittura.";
            return true;
        }
    }

    public function getOutput() {
        global $gvPath;

        $page = new WebPageOutput();
        $page->setHtmlPageTitle("Impostazioni");

        /* ✅ Inject CSS */
        $page->setHtmlBodyHeader($this->getDesignCSS() . $this->getHeaderAndNav());
        $page->setHtmlBodyContent($this->getPageContent());
        $page->setHtmlBodyFooter("");

        return $page;
    }

    /* ✅ Nouveau header + navbar, comme AdminPage */
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
    <a class="nav-item" href="$gvPath/application/adminDeviceList">Dispositivi</a>
    <a class="nav-item" href="$gvPath/application/adminStats">Statistiche</a>

    <!-- Onglet actif -->
    <a class="nav-item active" href="$gvPath/application/adminSettings">Impostazioni</a>

    <a class="nav-item" style="margin-left:auto;" href="$gvPath/application/logoutPage">Logout</a>
</div>
HTML;
    }

    /* ✅ Page Content (formulaire + texte + message) */
    public function getPageContent() {

        $message = $this->message ? "<div class='message-box'>{$this->message}</div>" : "";
        $form = $this->getForm();

        return <<<HTML
<div class="settings-container">

    

    $message
    $form

</div>
HTML;
    }

    /* ✅ Formulaire stylisé conservant ta logique */
    public function getForm() {
        global $gvEditableConfs;

        $fields = "";

        foreach ($gvEditableConfs as $conf) {
            $tag = $this->generateInputTag($conf);

            $fields .= <<<HTML
<tr>
    <td><strong>{$conf->getText()}</strong></td>
    <td>$tag</td>
</tr>
HTML;
        }

        return <<<HTML
<form method="post">
    <table class="settings-table">
        $fields
        <tr>
            <td colspan="2" style="text-align:right;">
                <button class="save-btn" type="submit">Salva</button>
            </td>
        </tr>
    </table>
</form>
HTML;
    }

    /* ✅ Inputs */
    protected function generateInputTag($conf) {

        $tagName = 'input';
        $attributes = '';
        $value = $GLOBALS[$conf->getName()];
        $type = $conf->getType();

        if ($type == 'integer') {
            $attributes .= ' type="number"';
        } elseif ($type == 'boolean') {
            $attributes .= ' type="checkbox"';
            if ($value) { $attributes .= ' checked'; }
            $value = 1;
        } elseif ($type == 'textarea') {
            $tagName = 'textarea';
            $attributes .= ' cols="30" rows="5"';
        } else {
            $attributes .= ' type="text" size="30"';
        }

        if ($tagName == 'input') {
            return "<input name=\"{$conf->getName()}\" value=\"$value\" $attributes />";
        } else {
            return "<textarea name=\"{$conf->getName()}\" $attributes>$value</textarea>";
        }
    }

    /* ✅ CSS identique au Admin principal */
    private function getDesignCSS() {
        return <<<CSS
<style>

/* ---- STYLE GLOBAL ---- */
* { margin: 0; padding: 0; box-sizing: border-box; }
body { background: hsl(210,5%,85%); font-family: 'Segoe UI', Tahoma; }

/* ---- HEADER ---- */
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
/* ---- CONTENU ---- */
.settings-container {
    padding: 40px;
    background: transparent;
}
.section-title { 
    font-size: 22px; 
    font-weight: 700; 
    margin-bottom: 15px;
}

/* ---- MESSAGE ---- */
.message-box {
    background: #fff3cd;
    padding: 15px 20px;
    border-left: 4px solid hsl(354,82%,70%);
    border-radius: 6px;
    margin-bottom: 20px;
}

/* ---- TABLE ---- */
.settings-table {
    width: 100%;
    background: white;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 4px 18px rgba(0,0,0,0.08);
}
.settings-table td {
    padding: 12px 8px;
}

/* ---- BOUTON ---- */
.save-btn {
    padding: 10px 22px;
    background: hsl(354,82%,70%);
    color: white;
    border: none;
    border-radius: 30px;
    cursor: pointer;
    font-weight: 600;
}
.save-btn:hover { background: hsl(354,82%,60%); }

</style>
CSS;
    }
}