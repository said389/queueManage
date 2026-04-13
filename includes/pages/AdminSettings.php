<?php

class AdminSettings extends Page {

    private $message = '';

    public function canUse($userLevel) {
        return $userLevel === Page::SYSADMIN_USER;
    }

    public function execute() {
        global $gvEditableConfs, $gvDirectory;

        $modifiedConfs = [];

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
            $this->message =
                "Configurazione salvata correttamente.<br>" .
                "È possibile tornare al <a href=\"$gvPath/application/adminPage\">menù principale</a>.";

            return true;

        } else {
            $this->message = "Errore nel salvataggio del file. Controllare i permessi di scrittura.";
            return true;
        }
    }

    public function getOutput() {

        $page = new WebPageOutput();
        $page->setHtmlPageTitle("Impostazioni");

        $page->setHtmlBodyHeader($this->getDesignCSS());
        $page->setHtmlBodyContent($this->getLayout());

        return $page;
    }


    /** ✅ LAYOUT COMPLET */
    private function getLayout() {
        global $gvPath;

        $messageBox = $this->message
            ? "<div class='message-box'><i class=\"fa-solid fa-circle-check\"></i> {$this->message}</div>"
            : "";

        $form = $this->getForm();

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

            <a class="menu-item" href="$gvPath/application/adminOperatorList">
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

            <a class="menu-item active" href="$gvPath/application/adminSettings">
                <i class="fa-solid fa-gear"></i> Impostazioni
            </a>

            <a class="menu-item logout" href="$gvPath/application/logoutPage">
                <i class="fa-solid fa-arrow-right-from-bracket"></i> Logout
            </a>

        </div>

    </aside>


    <!-- ✅ CONTENU -->
    <main class="content">

        <h2 class="page-title"><i class="fa-solid fa-gear"></i> Impostazioni di Sistema</h2>

        $messageBox

        <div class="settings-table-container">
            $form
        </div>

    </main>

</div>
HTML;
    }


    /** ✅ FORMULAIRE */
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
                <button class="save-btn" type="submit">
                    <i class="fa-solid fa-floppy-disk"></i> Salva
                </button>
            </td>
        </tr>
    </table>
</form>
HTML;
    }


    /** ✅ INPUTS */
    protected function generateInputTag($conf) {

        $tagName = 'input';
        $attributes = '';
        $value = htmlspecialchars($GLOBALS[$conf->getName()]);
        $type = $conf->getType();

        if ($type == 'integer') {
            $attributes .= ' type="number"';
        }
        elseif ($type == 'boolean') {
            $attributes .= ' type="checkbox"';
            if ($value) { $attributes .= ' checked'; }
            $value = 1;
        }
        elseif ($type == 'textarea') {
            $tagName = 'textarea';
            $attributes .= ' cols="30" rows="5"';
        }
        else {
            $attributes .= ' type="text" size="30"';
        }

        if ($tagName == 'input') {
            return "<input name=\"{$conf->getName()}\" value=\"$value\" $attributes />";
        } else {
            return "<textarea name=\"{$conf->getName()}\" $attributes>$value</textarea>";
        }
    }


    /** ✅ CSS PREMIUM */
    private function getDesignCSS() {
        return <<<CSS
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>

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
    width:250px;
    background:linear-gradient(180deg,#6C63FF,#8978FF,#CAB8FF);
    color:white;
    padding:25px 0;
    display:flex;
    flex-direction:column;
    border-radius:0 25px 25px 0;
    box-shadow:3px 0 15px rgba(0,0,0,0.08);
}
.sidebar-header { text-align:center; margin-bottom:35px; }

.logo-circle {
    width:60px;height:60px;
    background:white;border-radius:50%;
    margin:0 auto 10px auto;
    display:flex;align-items:center;justify-content:center;
    font-size:26px;font-weight:800;color:#6C63FF;
}
.brand { font-size:17px; opacity:.85; }

.menu { display:flex; flex-direction:column; }
.menu-item {
    padding:12px 25px;
    color:white;
    gap:12px;
    text-decoration:none;
    font-size:15px;
    display:flex;
    align-items:center;
    opacity:.85;
    transition:.25s;
}
.menu-item:hover {
    opacity:1;
    background:rgba(255,255,255,0.15);
}
.menu-item.active {
    background:rgba(255,255,255,0.25);
    font-weight:bold;
}
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
    display:flex;
    gap:10px;
    align-items:center;
}

/* ✅ MESSAGE */
.message-box {
    padding:15px;
    background:#E8E2FF;
    border-left:5px solid #6C63FF;
    border-radius:8px;
    margin-bottom:20px;
    display:flex;
    gap:10px;
    align-items:center;
    font-weight:600;
}

/* ✅ FORM */
.settings-table-container {
    background:white;
    padding:25px;
    border-radius:15px;
    box-shadow:0 4px 20px rgba(0,0,0,0.08);
}
.settings-table {
    width:100%;
    border-collapse:collapse;
}
.settings-table td {
    padding:12px;
    border-bottom:1px solid #eee;
}
.settings-table input[type="text"],
.settings-table input[type="number"],
.settings-table textarea {
    width:100%;
    padding:10px;
    border-radius:8px;
    border:1px solid #ccc;
    font-size:15px;
}

.save-btn {
    background:#6C63FF;
    color:white;
    padding:12px 25px;
    border-radius:30px;
    border:none;
    font-weight:600;
    cursor:pointer;
    display:flex;
    align-items:center;
    gap:10px;
}
.save-btn:hover {
    background:#5149E8;
}

.logout:hover {
    background:rgba(255,50,50,0.25);
}

</style>
CSS;
    }
}