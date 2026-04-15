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
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }

body {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #01030e 0%, #f1eaf8 100%);
    font-family: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    padding: 20px;
}

.login-container {
    display: flex;
    width: 920px;
    max-width: 100%;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 25px 60px rgba(0, 0, 0, 0.35);
}

/* ============ PANEL GAUCHE ============ */
.left-panel {
    position: relative;
    width: 42%;
    min-height: 540px;
    background: linear-gradient(160deg, #1a1a2e 0%, #16213e 60%, #0f1628 100%);
    overflow: hidden;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 40px 30px;
    gap: 0;
}

/* Decorative strips */
.left-panel .strip-1,
.left-panel .strip-2,
.left-panel .strip-3 {
    position: absolute;
    height: 120%;
    transform: rotate(-20deg);
    pointer-events: none;
}
.left-panel .strip-1 { left: -20%; top: 10%; width: 80%; background: rgba(108, 99, 255, 0.12); }
.left-panel .strip-2 { left: -10%; top: 15%; width: 60%; background: rgba(108, 99, 255, 0.08); }
.left-panel .strip-3 { left:   5%; top: 20%; width: 50%; background: rgba(108, 99, 255, 0.04); }

.left-panel .curve {
    position: absolute; right: -40px; top: 30%;
    width: 120px; height: 200px;
    background: white; border-radius: 9999px 0 0 9999px;
    pointer-events: none;
}

/* ---- Logo area ---- */
.logo-area {
    position: relative;
    z-index: 2;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 22px;
    text-align: center;
}

/* Queue illustration built with pure CSS + Font Awesome */
.queue-illustration {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 6px;
}

/* The ticket/counter icon at the top */
.counter-icon {
    width: 72px;
    height: 72px;
    background: linear-gradient(135deg, #6C63FF, #8B82FF);
    border-radius: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 8px 24px rgba(108, 99, 255, 0.5);
    font-size: 32px;
    color: white;
    position: relative;
    margin-bottom: 4px;
}

/* Glowing ring around the counter */
.counter-icon::before {
    content: '';
    position: absolute;
    inset: -6px;
    border-radius: 22px;
    border: 2px solid rgba(108, 99, 255, 0.35);
    animation: pulse-ring 2.5s ease-in-out infinite;
}
@keyframes pulse-ring {
    0%, 100% { opacity: 0.6; transform: scale(1); }
    50%       { opacity: 0.15; transform: scale(1.08); }
}

/* Queue of people */
.queue-people {
    display: flex;
    align-items: flex-end;
    gap: 0;
    margin-top: 6px;
}

.queue-person {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 2px;
    opacity: 0;
    animation: person-appear 0.4s ease forwards;
}

.queue-person:nth-child(1) { animation-delay: 0.1s; }
.queue-person:nth-child(2) { animation-delay: 0.25s; }
.queue-person:nth-child(3) { animation-delay: 0.4s; }
.queue-person:nth-child(4) { animation-delay: 0.55s; }
.queue-person:nth-child(5) { animation-delay: 0.7s; }

@keyframes person-appear {
    from { opacity: 0; transform: translateY(8px); }
    to   { opacity: 1; transform: translateY(0); }
}

.queue-person .p-head {
    font-size: 14px;
    color: rgba(255,255,255,0.85);
    line-height: 1;
}
.queue-person .p-body {
    font-size: 18px;
    color: rgba(255,255,255,0.75);
    line-height: 1;
}

/* first person (served) is highlighted */
.queue-person:first-child .p-head,
.queue-person:first-child .p-body {
    color: #8B82FF;
}

/* connector arrow between people */
.queue-arrow {
    font-size: 10px;
    color: rgba(108,99,255,0.5);
    margin: 0 2px;
    align-self: center;
    padding-bottom: 4px;
}

/* Ticket number badge */
.ticket-badge {
    display: flex;
    align-items: center;
    gap: 8px;
    background: rgba(255,255,255,0.07);
    border: 1px solid rgba(255,255,255,0.12);
    border-radius: 30px;
    padding: 6px 16px;
    margin-top: 6px;
}
.ticket-badge .ticket-num {
    font-size: 13px;
    font-weight: 700;
    color: #6C63FF;
    letter-spacing: 1px;
}
.ticket-badge .ticket-label {
    font-size: 11px;
    color: rgba(255,255,255,0.45);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Blinking dot */
.live-dot {
    width: 7px; height: 7px;
    background: #4ade80;
    border-radius: 50%;
    animation: blink 1.4s ease-in-out infinite;
}
@keyframes blink {
    0%, 100% { opacity: 1; }
    50%       { opacity: 0.2; }
}

/* Brand name */
.brand-name {
    font-size: 30px;
    font-weight: 800;
    color: #ffffff;
    letter-spacing: 2px;
    line-height: 1;
}
.brand-name span {
    color: #6C63FF;
}

.brand-tagline {
    font-size: 12px;
    color: rgba(255,255,255,0.4);
    letter-spacing: 1.5px;
    text-transform: uppercase;
    margin-top: 4px;
}

/* Stats row at bottom of left panel */
.stats-row {
    position: relative;
    z-index: 2;
    display: flex;
    gap: 16px;
    margin-top: 28px;
}
.stat-chip {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 3px;
    background: rgba(255,255,255,0.06);
    border: 1px solid rgba(255,255,255,0.1);
    border-radius: 12px;
    padding: 10px 16px;
    min-width: 70px;
}
.stat-chip i {
    font-size: 14px;
    color: #6C63FF;
}
.stat-chip .stat-val {
    font-size: 15px;
    font-weight: 700;
    color: #fff;
}
.stat-chip .stat-lbl {
    font-size: 10px;
    color: rgba(255,255,255,0.35);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* ============ PANEL DROIT ============ */
.right-panel {
    width: 58%;
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
    padding: 40px 52px;
}

.form-title {
    font-size: 26px;
    font-weight: 700;
    letter-spacing: 2px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    margin-bottom: 8px;
    text-align: center;
}
.form-subtitle {
    font-size: 13px;
    color: #999;
    text-align: center;
    margin-bottom: 36px;
}

/* CHAMPS */
.input-group { width: 100%; margin-bottom: 20px; }
.input-label {
    font-size: 11px;
    font-weight: 600;
    color: #888;
    text-transform: uppercase;
    letter-spacing: 0.8px;
    margin-bottom: 7px;
    display: block;
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
.input-icon { color: #bbb; font-size: 15px; transition: color 0.3s ease; }
.input-wrapper:focus-within .input-icon { color: #6C63FF; }

.input-wrapper input {
    width: 100%; padding: 14px 0; border: none; outline: none;
    font-size: 14px; color: #1a1a2e; background: transparent; font-family: inherit;
}
.input-wrapper input::placeholder { color: #ccc; }

.password-toggle {
    background: none; border: none; color: #bbb; cursor: pointer;
    padding: 5px; display: flex; align-items: center; transition: color 0.3s ease;
}
.password-toggle:hover { color: #6C63FF; }

/* BOUTON */
.login-btn {
    background: linear-gradient(135deg, #6C63FF, #8B82FF);
    color: white; border: none; border-radius: 12px;
    padding: 14px 40px; font-size: 15px; font-weight: 600; cursor: pointer;
    box-shadow: 0 8px 20px rgba(108, 99, 255, 0.40);
    transition: all 0.3s ease; width: 100%; margin-top: 8px;
    display: flex; align-items: center; justify-content: center; gap: 10px;
    font-family: inherit;
}
.login-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 28px rgba(108, 99, 255, 0.5);
}

/* ERREUR */
.error-message {
    background: #fee2e2; border-left: 3px solid #ef4444;
    padding: 12px 15px; border-radius: 10px; margin-top: 20px; width: 100%;
    animation: shake 0.3s ease-in-out;
}
.error-message p { color: #991b1b; font-size: 13px; margin: 0; display: flex; align-items: center; gap: 8px; }

@keyframes shake {
    0%, 100% { transform: translateX(0); }
    25% { transform: translateX(-5px); }
    75% { transform: translateX(5px); }
}

/* RESPONSIVE */
@media (max-width: 768px) {
    .login-container { flex-direction: column; }
    .left-panel { width: 100%; min-height: auto; padding: 36px 30px; }
    .stats-row { display: none; }
    .right-panel { width: 100%; }
    .form-area { padding: 36px 28px; }
}
@media (max-width: 480px) {
    .form-area { padding: 28px 20px; }
    .brand-name { font-size: 24px; }
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
            $message = <<<HTML
<div class="error-message">
    <p><i class="fas fa-exclamation-circle"></i>{$this->errorMessage}</p>
</div>
HTML;
        }

        return <<<EOS
<div class="login-container">

    <!-- ===== PANEL GAUCHE : Logo & Illustration ===== -->
    <div class="left-panel">
        <div class="strip-1"></div>
        <div class="strip-2"></div>
        <div class="strip-3"></div>
        <div class="curve"></div>

        <div class="logo-area">

            <!-- Icône comptoir (guichet) -->
            <div class="queue-illustration">
                <div class="counter-icon">
                    <i class="fas fa-ticket-alt"></i>
                </div>

                <!-- File de personnes stylisée avec des icônes FA -->
                <div class="queue-people">
                    <!-- Personne servie (en tête) -->
                    <div class="queue-person">
                        <i class="fas fa-user p-head"></i>
                        <i class="fas fa-person-walking p-body" style="font-size:20px;"></i>
                    </div>

                    <i class="fas fa-chevron-right queue-arrow"></i>

                    <div class="queue-person">
                        <i class="fas fa-user p-head"></i>
                        <i class="fas fa-user-large p-body"></i>
                    </div>
                    <div class="queue-person">
                        <i class="fas fa-user p-head"></i>
                        <i class="fas fa-user-large p-body"></i>
                    </div>
                    <div class="queue-person">
                        <i class="fas fa-user p-head"></i>
                        <i class="fas fa-user-large p-body"></i>
                    </div>
                    <div class="queue-person">
                        <i class="fas fa-user p-head" style="opacity:0.4"></i>
                        <i class="fas fa-user-large p-body" style="opacity:0.4"></i>
                    </div>
                </div>

                <!-- Badge numéro de ticket -->
                <div class="ticket-badge">
                    <div class="live-dot"></div>
                    <span class="ticket-num">N° 042</span>
                    <span class="ticket-label">En cours</span>
                </div>
            </div>

            <!-- Nom de l'application -->
            <div>
                <div class="brand-name">Fast<span>Queue</span></div>
                <div class="brand-tagline">Système de file d'attente</div>
            </div>
        </div>

        <!-- Mini statistiques décoratives -->
        <div class="stats-row">
            <div class="stat-chip">
                <i class="fas fa-users"></i>
                <span class="stat-val">12</span>
                <span class="stat-lbl">File</span>
            </div>
            <div class="stat-chip">
                <i class="fas fa-desktop"></i>
                <span class="stat-val">4</span>
                <span class="stat-lbl">Guichets</span>
            </div>
            <div class="stat-chip">
                <i class="fas fa-clock"></i>
                <span class="stat-val">~3'</span>
                <span class="stat-lbl">Attente</span>
            </div>
        </div>
    </div>

    <!-- ===== PANEL DROIT : Formulaire ===== -->
    <div class="right-panel">
        <div class="form-area">

            <div class="form-title">CONNEXION</div>
            <p class="form-subtitle">Accédez à votre espace de gestion</p>

            <form action="$gvPath/application/loginPage" method="post" autocomplete="off" style="width:100%">

                <div class="input-group">
                    <label class="input-label" for="code">Identifiant</label>
                    <div class="input-wrapper">
                        <i class="fas fa-user input-icon"></i>
                        <input type="text" name="code" id="code" placeholder="Votre identifiant" required>
                    </div>
                </div>

                <div class="input-group">
                    <label class="input-label" for="password">Mot de passe</label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" name="password" id="password" placeholder="••••••••" required>
                        <button type="button" class="password-toggle" onclick="togglePassword()">
                            <i class="fas fa-eye" id="pwIcon"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="login-btn">
                    <i class="fas fa-sign-in-alt"></i>
                    <span>Se connecter</span>
                </button>

            </form>

            $message

        </div>
    </div>

</div>

<script>
function togglePassword() {
    const input = document.getElementById('password');
    const icon  = document.getElementById('pwIcon');
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'fas fa-eye-slash';
    } else {
        input.type = 'password';
        icon.className = 'fas fa-eye';
    }
}
</script>
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