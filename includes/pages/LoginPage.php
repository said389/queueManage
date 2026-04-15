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
        
        $code = $_POST['code'] ?? '';
        $password = $_POST['password'] ?? '';

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
        
        $this->errorMessage = "Code ou mot de passe invalide !";
        return true;
    }

    public function getOutput() {
        global $gvPath;

        $output = new WebPageOutput();
        $output->setHtmlPageTitle("Connexion - FastQueue");

        $css = <<<CSS
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #01030e 0%, #f1eaf8 100%);
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

/* PANEL GAUCHE */
.left-panel {
    position: relative;
    width: 40%;
    min-height: 500px;
    background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
    overflow: hidden;
}

.left-panel .strip-1,
.left-panel .strip-2,
.left-panel .strip-3 {
    position: absolute;
    height: 120%;
    transform: rotate(-20deg);
}

.left-panel .strip-1 { left: -20%; top: 10%; width: 80%; background: rgba(108, 99, 255, 0.15); }
.left-panel .strip-2 { left: -10%; top: 15%; width: 60%; background: rgba(108, 99, 255, 0.1); }
.left-panel .strip-3 { left: 5%;   top: 20%; width: 50%; background: rgba(108, 99, 255, 0.05); }

.left-panel .curve {
    position: absolute; right: -40px; top: 30%;
    width: 120px; height: 200px;
    background: white; border-radius: 9999px 0 0 9999px;
}

/* PANEL DROIT */
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

/* TITRE */
.form-title {
    font-size: 28px;
    font-weight: 700;
    letter-spacing: 2px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    margin-bottom: 40px;
    text-align: center;
}

/* CHAMPS AVEC ICONES */
.input-group { 
    width: 100%; 
    margin-bottom: 24px; 
}

.input-wrapper {
    display: flex;
    align-items: center;
    gap: 12px;
    background: #f5f7fb;
    border-radius: 12px;
    padding: 0 15px;
    border: 2px solid transparent;
    transition: all 0.3s ease;
}

.input-wrapper:focus-within {
    border-color: #6C63FF;
    background: white;
    box-shadow: 0 0 0 3px rgba(108, 99, 255, 0.1);
}

.input-icon {
    color: #999;
    font-size: 16px;
    transition: color 0.3s ease;
}

.input-wrapper:focus-within .input-icon {
    color: #6C63FF;
}

.input-wrapper input {
    width: 100%;
    padding: 14px 0;
    border: none;
    outline: none;
    font-size: 14px;
    color: #1a1a2e;
    background: transparent;
    font-family: inherit;
}

.input-wrapper input::placeholder {
    color: #bbb;
}

/* Bouton toggle password */
.password-toggle {
    background: none;
    border: none;
    color: #999;
    cursor: pointer;
    padding: 5px;
    display: flex;
    align-items: center;
    transition: color 0.3s ease;
}

.password-toggle:hover {
    color: #6C63FF;
}

/* BOUTON LOGIN */
.login-btn {
    background: linear-gradient(135deg, #6C63FF, #8B82FF);
    color: white;
    border: none;
    border-radius: 12px;
    padding: 14px 40px;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    box-shadow: 0 8px 20px rgba(108, 99, 255, 0.40);
    transition: all 0.3s ease;
    width: 100%;
    margin-top: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
}

.login-btn:hover { 
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(108, 99, 255, 0.5);
    gap: 12px;
}

/* MESSAGE ERREUR */
.error-message {
    background: #fee2e2;
    border-left: 3px solid #ef4444;
    padding: 12px 15px;
    border-radius: 10px;
    margin-top: 20px;
    width: 100%;
}
.error-message p {
    color: #991b1b;
    font-size: 13px;
    margin: 0;
}

/* RESPONSIVE */
@media (max-width: 768px) {
    .login-container {
        flex-direction: column;
    }
    
    .left-panel {
        width: 100%;
        min-height: 200px;
    }
    
    .right-panel {
        width: 100%;
    }
    
    .form-area {
        padding: 30px 25px;
    }
    
    .form-title {
        font-size: 24px;
        margin-bottom: 30px;
    }
}

@media (max-width: 480px) {
    .form-area {
        padding: 25px 20px;
    }
    
    .form-title {
        font-size: 22px;
        margin-bottom: 25px;
    }
    
    .input-wrapper {
        padding: 0 12px;
    }
    
    .input-wrapper input {
        padding: 12px 0;
        font-size: 13px;
    }
    
    .login-btn {
        padding: 12px;
        font-size: 14px;
    }
}

/* Animation erreur */
@keyframes shake {
    0%, 100% { transform: translateX(0); }
    25% { transform: translateX(-4px); }
    75% { transform: translateX(4px); }
}

.error-message {
    animation: shake 0.3s ease-in-out;
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
            $message = "<div class=\"error-message\"><p>{$this->errorMessage}</p></div>";
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

            <div class="form-title">CONNEXION</div>

            <form action="$gvPath/application/loginPage" method="post" autocomplete="off">

                <div class="input-group">
                    <div class="input-wrapper">
                        <i class="fas fa-user input-icon"></i>
                        <input type="text" name="code" id="code" placeholder="Identifiant" required>
                    </div>
                </div>

                <div class="input-group">
                    <div class="input-wrapper">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" name="password" id="password" placeholder="Mot de passe" required>
                        <button type="button" class="password-toggle" onclick="togglePassword()">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="login-btn">
                    <i class="fas fa-sign-in-alt"></i>
                    <span>Connexion</span>
                </button>

            </form>

            $message

        </div>
    </div>

</div>

<script>
function togglePassword() {
    const passwordInput = document.getElementById('password');
    const toggleBtn = document.querySelector('.password-toggle i');
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        toggleBtn.classList.remove('fa-eye');
        toggleBtn.classList.add('fa-eye-slash');
    } else {
        passwordInput.type = 'password';
        toggleBtn.classList.remove('fa-eye-slash');
        toggleBtn.classList.add('fa-eye');
    }
}
</script>

<!-- Font Awesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
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
?>