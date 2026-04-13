<?php

class LoginPage extends Page {
    
    private $errorMessage;
    
    public function __construct($errorMessage = false) {
        $this->setErrorMessage($errorMessage);
    }

    public function canUse($userLevel) {
        return true;
    }
    
    private function isValidSysAdminLogin($code, $password) {
        global $gvSysAdminCode, $gvSysAdminPassword;
        return $code == $gvSysAdminCode && $password == $gvSysAdminPassword;
    }

    public function execute() {
        global $gvPath;
        
        $code = $_POST['code'];
        $password = $_POST['password'];

        session_destroy();
        unset($_SESSION);
        session_start();
                
        if ($this->isValidSysAdminLogin($code, $password)) {

            global $gvEditableConfs;
            $_SESSION['userLevel'] = Page::SYSADMIN_USER;

            if ($code == $gvEditableConfs[0]->getDefault()
                && $password == $gvEditableConfs[1]->getDefault()) {

                return new RedirectOutput($gvPath . "/application/adminSettings");
            } else {
                return new RedirectOutput($gvPath . "/application/adminPage");
            }
        }
        
        if (Operator::isValidLogin($code, $password)) {

            Operator::clearTableForLogout($code);

            try {
                Session::loginOperator($code);
            } catch (UnknownDeskException $e) {

                global $gvPath;

                return new ErrorPageOutput(
                    "Sportello non riconosciuto",
                    "Il presente computer non è stato registrato come sportello.<br />"
                    . "Indirizzo IP da registrare: " . $_SERVER['REMOTE_ADDR'],
                    "<a href=\"$gvPath/application/loginPage\">Torna indietro</a>"
                );
            }

            return new RedirectOutput($gvPath . "/application/opPage");
        }
        
        $this->errorMessage = "Codice o password non validi!";
        return true;
    }

    public function getOutput() {
        global $gvPath;

        $output = new WebPageOutput();
        $output->setHtmlPageTitle("Pagina di log in");

        /* ✅ CSS violet premium */
        $css = <<<CSS
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;

    /* ✅ Couleur du menu violet premium */
    background: linear-gradient(135deg, #d6d6e0, #dedcee, #CAB8FF);

    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    padding: 20px;
}

.login-container {
    display: flex;
    width: 900px;
    max-width: 100%;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 25px 60px rgba(0, 0, 0, 0.35);
}

/* ✅ PANEL GAUCHE (theme violet premium) */
.left-panel {
    position: relative;
    width: 40%;
    min-height: 500px;
    background: linear-gradient(135deg, #6C63FF, #8978FF, #CAB8FF);
    overflow: hidden;
}

.left-panel .strip-1,
.left-panel .strip-2,
.left-panel .strip-3 {
    position: absolute;
    height: 120%;
    transform: rotate(-20deg);
}

.left-panel .strip-1 { left: -20%; top: 10%; width: 80%; background: #7B72FF; }
.left-panel .strip-2 { left: -10%; top: 15%; width: 60%; background: #968BFF; }
.left-panel .strip-3 { left: 5%;   top: 20%; width: 50%; background: #D8CBFF; }

.left-panel .curve {
    position: absolute; right: -40px; top: 30%;
    width: 120px; height: 200px;
    background: white; border-radius: 9999px 0 0 9999px;
}

/* ✅ PANEL DROIT */
.right-panel {
    width: 60%;
    background: #fff;
    display: flex;
    flex-direction: column;
}

.form-area {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 40px 48px;
}

/* ✅ TITRE */
.form-title {
    font-size: 22px;
    font-weight: 700;
    letter-spacing: 4px;
    color: #6C63FF;
    margin: 8px 0 32px;
}

/* ✅ CHAMPS */
.input-group { width: 100%; margin-bottom: 24px; }

.input-wrapper {
    display: flex;
    align-items: center;
    gap: 12px;
    border-bottom: 1px solid hsl(0,0%,85%);
    padding-bottom: 12px;
}

.input-wrapper input {
    width: 100%; border: none; outline: none;
    font-size: 14px;
    color: hsl(220,10%,30%);
    background: transparent;
}

/* ✅ BOUTON VIOLET */
.login-btn {
    background: #6C63FF;
    color: white;
    border: none;
    border-radius: 9999px;
    padding: 14px 40px;
    font-size: 13px;
    font-weight: 700;
    letter-spacing: 3px;
    cursor: pointer;
    box-shadow: 0 8px 20px rgba(108, 99, 255, 0.40);
    transition: transform .2s;
}
.login-btn:hover { transform: scale(1.05); }

/* ✅ MESSAGE ERREUR */
.error-message {
    color: white;
    background: rgba(225, 40, 50, 0.8);
    padding: 12px 20px;
    border-radius: 6px;
    margin-top: 20px;
}
</style>
CSS;

        $output->setHtmlBodyHeader($css);
        $output->importJquery();
        $output->addJavascript("$gvPath/assets/js/animationError.js");

        $output->setHtmlBodyContent($this->getPageContent());
        
        return $output;
    }

    public function getPageContent() {
        global $gvPath;

        $message = "";
        if ($this->errorMessage) {
            $message = "<div class=\"error-message\"><p>$this->errorMessage</p></div>";
        }

        return <<<EOS
<div class="login-container">

    <div class="left-panel">
        <div class="strip-1"></div>
        <div class="strip-2"></div>
        <div class="strip-3"></div>
        <div class="curve"></div>
    </div>

    <div class="right-panel">
        <div class="form-area">

            <div class="form-title">SE CONNECTER</div>

            <form action="$gvPath/application/loginPage" method="post" autocomplete="off">

                <div class="input-group">
                    <div class="input-wrapper">
                        <input type="text" name="code" placeholder="Code">
                    </div>
                </div>

                <div class="input-group">
                    <div class="input-wrapper">
                        <input type="password" name="password" placeholder="Mot de passe">
                    </div>
                </div>

                <input class="login-btn" type="submit" value="Se connecter">

            </form>

            $message

        </div>
    </div>

</div>
EOS;
    }

    public function getPageHeader() { return ''; }
    
    public function setErrorMessage($message, $append = false) {
        $escaped = htmlspecialchars($message);
        if ($append) {
            $this->errorMessage .= $escaped;
        } else {
            $this->errorMessage = $escaped;
        }
    }
}