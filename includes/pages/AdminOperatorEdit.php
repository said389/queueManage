<?php

/**
 * AdminOperatorEdit
 * Handles POST form submission (add/edit operator).
 * Used by the modal in AdminOperatorList.
 * Can also be accessed directly as a fallback standalone page.
 */
class AdminOperatorEdit extends Page {

    private $message    = "";
    private $op_id      = 0;
    private $op_code    = "";
    private $op_name    = "";
    private $op_surname = "";

    public function canUse($userLevel) {
        return $userLevel === Page::SYSADMIN_USER;
    }

    public function afterPermissionCheck() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->op_id      = gfPostVar('op_id', 0);
            $this->op_code    = gfPostVar('op_code', '');
            $this->op_name    = gfPostVar('op_name', '');
            $this->op_surname = gfPostVar('op_surname', '');
        } else {
            $this->op_id = gfGetVar('op_id', 0);
            if ($this->op_id) {
                $op = Operator::fromDatabaseById($this->op_id);
                if ($op !== null) {
                    $this->op_code    = $op->getCode();
                    $this->op_name    = $op->getName();
                    $this->op_surname = $op->getSurname();
                } else {
                    $this->op_id = 0;
                }
            }
        }
    }

    public function execute() {
        global $gvMinPasswordLength, $gvPath;

        // Only process on POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return true;
        }

        $op_password        = gfPostVar('op_password', '');
        $op_password_repete = gfPostVar('op_password_repete', '');

        // Trim
        $this->op_code    = trim($this->op_code);
        $this->op_name    = trim($this->op_name);
        $this->op_surname = trim($this->op_surname);

        // Required fields
        if ($this->op_code === '' || $this->op_name === '' || $this->op_surname === '') {
            $this->message = "Erreur : tous les champs sont obligatoires.";
            return true;
        }

        // Password required for new operator
        if ($this->op_id === 0 && $op_password === '') {
            $this->message = "Erreur : le mot de passe est obligatoire pour un nouvel opérateur.";
            return true;
        }

        // Password length
        if ($op_password && strlen($op_password) < $gvMinPasswordLength) {
            $this->message = "Erreur : le mot de passe doit contenir au moins $gvMinPasswordLength caractères.";
            return true;
        }

        // Password match
        if ($op_password !== $op_password_repete) {
            $this->message = "Erreur : les mots de passe ne correspondent pas.";
            return true;
        }

        // Valid code (letters & digits only)
        if (preg_match('/^[0-9a-z]+$/i', $this->op_code) !== 1) {
            $this->message = "Erreur : le code opérateur n'est pas valide.";
            return true;
        }

        // Valid name
        if (preg_match('/^[a-z \'àâçéèêëîïôùûüÿœæ]+$/i', $this->op_name) !== 1) {
            $this->message = "Erreur : le prénom contient des caractères non valides.";
            return true;
        }

        // Valid surname
        if (preg_match('/^[a-z \'àâçéèêëîïôùûüÿœæ]+$/i', $this->op_surname) !== 1) {
            $this->message = "Erreur : le nom contient des caractères non valides.";
            return true;
        }

        // Code uniqueness
        $op = Operator::fromDatabaseByCode($this->op_code);
        if ($op && ($this->op_id === 0 || $this->op_id !== (int) $op->getId())) {
            $this->message = "Erreur : ce code opérateur est déjà utilisé.";
            return true;
        }
        unset($op);

        // Check operator is offline before edit
        if ($this->op_id !== 0) {
            $operator = Operator::fromDatabaseById($this->op_id);
            if (!$operator) {
                $this->message = "Erreur interne : l'enregistrement est introuvable.";
                return true;
            }
            if ($operator->isOnline()) {
                $this->message = "L'opérateur est en ligne, impossible de le modifier.";
                return true;
            }
        }

        // Save
        if ($this->op_id === 0) {
            $op = Operator::newRecord();
            $op->setCode($this->op_code);
            $op->setName($this->op_name);
            $op->setSurname($this->op_surname);
            $op->setPassword($op_password);
        } else {
            $op = Operator::fromDatabaseById($this->op_id);
            $op->setCode($this->op_code);
            $op->setName($this->op_name);
            $op->setSurname($this->op_surname);
            if ($op_password) {
                $op->setPassword($op_password);
            }
        }

        if ($op->save()) {
            gfSetDelayedMsg('Opération effectuée avec succès', 'Ok');
            $redirect = new RedirectOutput("$gvPath/application/adminOperatorList");
            return $redirect;
        } else {
            $this->message = "Impossible d'enregistrer les modifications. Veuillez réessayer.";
            return true;
        }
    }

    public function getOutput() {
        global $gvPath;

        $page = new WebPageOutput();
        $page->setHtmlPageTitle($this->getPageTitle());
        $page->setHtmlBodyHeader($this->getDesignCSS());
        $page->setHtmlBodyContent($this->getLayout());

        return $page;
    }

    private function getPageTitle() {
        return $this->op_id ? 'Modifier un opérateur' : 'Ajouter un opérateur';
    }

    /** LAYOUT — standalone fallback page with same design as the list */
    private function getLayout() {
        global $gvPath;

        $title   = $this->getPageTitle();
        $pwHint  = $this->op_id ? '<p class="pw-hint"><i class="fas fa-info-circle"></i> Laissez les champs mot de passe vides pour ne pas le modifier.</p>' : '';
        $message = $this->message ? '<div class="form-message error"><i class="fas fa-exclamation-circle"></i> ' . htmlspecialchars($this->message) . '</div>' : '';

        $opId      = (int) $this->op_id;
        $opCode    = htmlspecialchars($this->op_code);
        $opName    = htmlspecialchars($this->op_name);
        $opSurname = htmlspecialchars($this->op_surname);

        return <<<HTML
<div class="layout">
    <!-- Sidebar Toggle pour mobile -->
    <button class="mobile-toggle" id="mobileToggle">
        <i class="fas fa-bars"></i>
    </button>

    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="logo">
                <h2>FastQueue</h2>
            </div>
            <span class="admin-badge">Administrateur</span>
        </div>

        <nav class="sidebar-nav">
            <a href="$gvPath/application/adminPage" class="nav-item">
                <i class="fas fa-tachometer-alt"></i>
                <span>Tableau de bord</span>
            </a>
            <a href="$gvPath/application/adminOperatorList" class="nav-item active">
                <i class="fas fa-users"></i>
                <span>Opérateurs</span>
            </a>
            <a href="$gvPath/application/adminDeskList" class="nav-item">
                <i class="fas fa-desktop"></i>
                <span>Compteurs</span>
            </a>
            <a href="$gvPath/application/adminTopicalDomainList" class="nav-item">
                <i class="fas fa-folder-tree"></i>
                <span>Domaines thématiques</span>
            </a>
            <a href="$gvPath/application/adminDeviceList" class="nav-item">
                <i class="fas fa-mobile-alt"></i>
                <span>Appareils</span>
            </a>
            <a href="$gvPath/application/adminStats" class="nav-item">
                <i class="fas fa-chart-line"></i>
                <span>Statistiques</span>
            </a>
            <a href="$gvPath/application/adminSettings" class="nav-item">
                <i class="fas fa-cog"></i>
                <span>Paramètres</span>
            </a>
        </nav>

        <div class="sidebar-footer">
            <a href="$gvPath/application/logoutPage" class="nav-item logout">
                <i class="fas fa-sign-out-alt"></i>
                <span>Déconnexion</span>
            </a>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <div class="content-wrapper">
            <div class="page-header">
                <h1>$title</h1>
                <p class="subtitle">Remplissez les informations de l'opérateur</p>
            </div>

            <div class="card form-card">
                <div class="card-header">
                    <i class="fas fa-user-edit"></i>
                    <h3>$title</h3>
                </div>

                $message
                $pwHint

                <form method="post" action="$gvPath/application/adminOperatorEdit" class="operator-form">
                    <input type="hidden" name="op_id" value="$opId" />

                    <div class="form-group">
                        <label for="op_code"><i class="fas fa-id-badge"></i> Code opérateur</label>
                        <input type="text" name="op_code" id="op_code" value="$opCode" placeholder="ex: OP001" autocomplete="off" />
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="op_name"><i class="fas fa-user"></i> Prénom</label>
                            <input type="text" name="op_name" id="op_name" value="$opName" placeholder="Prénom" autocomplete="off" />
                        </div>
                        <div class="form-group">
                            <label for="op_surname"><i class="fas fa-user"></i> Nom</label>
                            <input type="text" name="op_surname" id="op_surname" value="$opSurname" placeholder="Nom de famille" autocomplete="off" />
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="op_password"><i class="fas fa-lock"></i> Mot de passe</label>
                            <div class="input-password">
                                <input type="password" name="op_password" id="op_password" placeholder="••••••••" autocomplete="new-password" />
                                <button type="button" class="toggle-pw" onclick="togglePw('op_password', this)">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="op_password_repete"><i class="fas fa-lock"></i> Répéter le mot de passe</label>
                            <div class="input-password">
                                <input type="password" name="op_password_repete" id="op_password_repete" placeholder="••••••••" autocomplete="new-password" />
                                <button type="button" class="toggle-pw" onclick="togglePw('op_password_repete', this)">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="form-actions">
                        <a href="$gvPath/application/adminOperatorList" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Retour
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Enregistrer
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </main>
</div>

<div class="overlay" id="overlay"></div>

<script>
const mobileToggle = document.getElementById('mobileToggle');
const sidebar      = document.getElementById('sidebar');
const overlay      = document.getElementById('overlay');

if (mobileToggle) {
    mobileToggle.addEventListener('click', () => {
        sidebar.classList.add('open');
        overlay.classList.add('show');
    });
}
if (overlay) {
    overlay.addEventListener('click', () => {
        sidebar.classList.remove('open');
        overlay.classList.remove('show');
    });
}
window.addEventListener('resize', () => {
    if (window.innerWidth > 768) {
        sidebar.classList.remove('open');
        overlay.classList.remove('show');
    }
});

function togglePw(inputId, btn) {
    const input = document.getElementById(inputId);
    const icon  = btn.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'fas fa-eye-slash';
    } else {
        input.type = 'password';
        icon.className = 'fas fa-eye';
    }
}
</script>
HTML;
    }

    /** CSS — identique au design de la liste */
    private function getDesignCSS() {
        return <<<CSS
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }

    body {
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        background: #f5f7fb;
        color: #1a1a2e;
        overflow-x: hidden;
    }

    .layout { display: flex; min-height: 100vh; }

    /* ---- Sidebar ---- */
    .sidebar {
        width: 280px;
        background: linear-gradient(180deg, #1a1a2e 0%, #16213e 100%);
        color: #fff;
        display: flex; flex-direction: column;
        position: fixed; left: 0; top: 0;
        height: 100vh; z-index: 1000;
        transition: transform 0.3s ease;
        overflow-y: auto;
    }
    .sidebar-header { padding: 30px 25px; border-bottom: 1px solid rgba(255,255,255,0.1); }
    .logo { display: flex; align-items: center; gap: 12px; margin-bottom: 15px; }
    .logo h2 { font-size: 22px; font-weight: 700; }
    .admin-badge {
        background: rgba(108,99,255,0.2); padding: 6px 12px; border-radius: 20px;
        font-size: 12px; color: #6C63FF; display: inline-block;
    }
    .sidebar-nav { flex: 1; padding: 20px 0; }
    .nav-item {
        display: flex; align-items: center; gap: 12px;
        padding: 12px 25px; color: rgba(255,255,255,0.8);
        text-decoration: none; transition: all 0.3s ease;
        font-size: 14px; font-weight: 500;
    }
    .nav-item i { width: 20px; font-size: 18px; }
    .nav-item:hover { background: rgba(108,99,255,0.1); color: #fff; }
    .nav-item.active {
        background: linear-gradient(90deg, #6C63FF, rgba(108,99,255,0.1));
        color: #fff; border-right: 3px solid #6C63FF;
    }
    .sidebar-footer { padding: 20px 0; border-top: 1px solid rgba(255,255,255,0.1); }
    .logout { color: #ff6b6b; }
    .logout:hover { background: rgba(255,107,107,0.1); }

    /* ---- Mobile ---- */
    .mobile-toggle {
        display: none; position: fixed; top: 20px; left: 20px;
        z-index: 1001; background: #6C63FF; border: none;
        width: 45px; height: 45px; border-radius: 12px;
        color: white; font-size: 20px; cursor: pointer;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }
    .overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 999; }
    .overlay.show { display: block; }

    /* ---- Main ---- */
    .main-content { flex: 1; margin-left: 280px; min-height: 100vh; background: #f5f7fb; }
    .content-wrapper { padding: 30px 40px; max-width: 800px; margin: 0 auto; }
    .page-header { margin-bottom: 30px; }
    .page-header h1 { font-size: 28px; font-weight: 700; color: #1a1a2e; margin-bottom: 8px; }
    .subtitle { color: #666; font-size: 14px; }

    /* ---- Card ---- */
    .card {
        background: white; border-radius: 16px;
        padding: 28px; margin-bottom: 25px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    }
    .card-header {
        display: flex; align-items: center; gap: 12px;
        margin-bottom: 24px; padding-bottom: 16px;
        border-bottom: 1px solid #eee;
    }
    .card-header i { color: #6C63FF; font-size: 20px; }
    .card-header h3 { font-size: 16px; font-weight: 600; color: #1a1a2e; margin: 0; }

    /* ---- Form ---- */
    .operator-form { display: flex; flex-direction: column; gap: 0; }
    .form-group { display: flex; flex-direction: column; gap: 6px; margin-bottom: 18px; }
    .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; }

    .form-group label {
        font-size: 12px; font-weight: 600; color: #1a1a2e;
        text-transform: uppercase; letter-spacing: 0.5px;
        display: flex; align-items: center; gap: 6px;
    }
    .form-group label i { color: #6C63FF; font-size: 11px; }

    .form-group input[type="text"],
    .form-group input[type="password"] {
        width: 100%; padding: 12px 16px;
        border: 2px solid #eee; border-radius: 10px;
        font-size: 14px; color: #1a1a2e; font-family: inherit;
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
        outline: none;
    }
    .form-group input:focus {
        border-color: #6C63FF;
        box-shadow: 0 0 0 3px rgba(108,99,255,0.12);
    }
    .form-group input::placeholder { color: #aaa; }

    .input-password { position: relative; }
    .input-password input { padding-right: 44px; }
    .toggle-pw {
        position: absolute; right: 12px; top: 50%; transform: translateY(-50%);
        background: none; border: none; cursor: pointer;
        color: #999; font-size: 14px; padding: 0;
        transition: color 0.2s ease;
    }
    .toggle-pw:hover { color: #6C63FF; }

    .pw-hint {
        display: flex; align-items: center; gap: 8px;
        background: #f0eeff; color: #6C63FF;
        padding: 10px 14px; border-radius: 8px;
        font-size: 13px; margin-bottom: 18px;
    }

    /* ---- Messages ---- */
    .form-message {
        display: flex; align-items: center; gap: 10px;
        padding: 12px 16px; border-radius: 10px;
        font-size: 13px; font-weight: 500; margin-bottom: 20px;
    }
    .form-message.error { background: #fff0f0; color: #c0392b; border-left: 3px solid #e74c3c; }

    /* ---- Actions ---- */
    .form-actions {
        display: flex; align-items: center; justify-content: flex-end;
        gap: 12px; margin-top: 8px; padding-top: 20px;
        border-top: 1px solid #f0f0f0;
    }
    .btn {
        display: inline-flex; align-items: center; gap: 10px;
        padding: 12px 24px; border-radius: 8px; text-decoration: none;
        font-size: 14px; font-weight: 500; transition: all 0.3s ease;
        border: none; cursor: pointer; font-family: inherit;
    }
    .btn-primary { background: #6C63FF; color: white; }
    .btn-primary:hover { background: #5149E8; }
    .btn-secondary { background: #e9ecef; color: #1a1a2e; }
    .btn-secondary:hover { background: #dee2e6; }

    /* ---- Responsive ---- */
    @media (max-width: 768px) {
        .sidebar { transform: translateX(-100%); }
        .sidebar.open { transform: translateX(0); }
        .mobile-toggle { display: flex; }
        .main-content { margin-left: 0; }
        .content-wrapper { padding: 20px; }
        .page-header h1 { font-size: 22px; }
        .card { padding: 20px; }
        .form-row { grid-template-columns: 1fr; }
    }
</style>
CSS;
    }
}