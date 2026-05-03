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
                    $this->message = "Erreur lors du traitement de la requête. Veuillez réessayer.";
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
                "Configuration enregistrée avec succès.<br>" .
                "Retour au <a href=\"$gvPath/application/adminPage\" style=\"color:#6C63FF;\">menu principal</a>.";

            return true;

        } else {
            $this->message = "Erreur lors de l'enregistrement. Vérifiez les permissions d'écriture.";
            return true;
        }
    }

    public function getOutput() {

        $page = new WebPageOutput();
        $page->setHtmlPageTitle("Paramètres");

        $page->setHtmlBodyHeader($this->getDesignCSS());
        $page->setHtmlBodyContent($this->getLayout());

        return $page;
    }


    /** GABARIT COMPLET */
    private function getLayout() {
        global $gvPath;

        $messageBox = $this->message
            ? "<div class='message-box succes'><i class=\"fas fa-check-circle\"></i> {$this->message}</div>"
            : "";

        $form = $this->getForm();

        return <<<HTML
<div class="layout">
    <aside class="sidebar" id="sidebar">
        <button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-chevron-left"></i></button>
        <div class="sidebar-header">
            <div class="logo"><i class="fas fa-tasks"></i><h2 class="logo-text">FastQueue</h2></div>
            <span class="admin-badge">Administrateur</span>
        </div>

        <nav class="sidebar-nav">
            <a href="$gvPath/application/adminPage" class="nav-item" title="Tableau de bord"><i class="fas fa-tachometer-alt"></i><span class="nav-text">Tableau de bord</span></a>
            <a href="$gvPath/application/adminOperatorList" class="nav-item" title="Opérateurs"><i class="fas fa-users"></i><span class="nav-text">Opérateurs</span></a>
            <a href="$gvPath/application/adminDeskList" class="nav-item" title="Compteurs"><i class="fas fa-desktop"></i><span class="nav-text">Compteurs</span></a>
            <a href="$gvPath/application/adminTopicalDomainList" class="nav-item" title="Domaines"><i class="fas fa-folder-tree"></i><span class="nav-text">Domaines thématiques</span></a>
            <a href="$gvPath/application/adminDeviceList" class="nav-item" title="Appareils"><i class="fas fa-mobile-alt"></i><span class="nav-text">Appareils</span></a>
            <a href="$gvPath/application/adminStats" class="nav-item" title="Statistiques"><i class="fas fa-chart-line"></i><span class="nav-text">Statistiques</span></a>
            <a href="$gvPath/application/adminSettings" class="nav-item actif" title="Paramètres"><i class="fas fa-cog"></i><span class="nav-text">Paramètres</span></a>
        </nav>

        <div class="sidebar-footer">
            <a href="$gvPath/application/logoutPage" class="nav-item deconnexion" title="Déconnexion"><i class="fas fa-sign-out-alt"></i><span class="nav-text">Déconnexion</span></a>
        </div>
    </aside>

    <main class="main-content" id="mainContent">
        <div class="content-wrapper">
            <div class="page-header">
                <h1><i class="fas fa-cog"></i> Paramètres système</h1>
                <p class="subtitle">Configuration générale de FastQueue</p>
            </div>

            $messageBox

            <div class="card">
                <div class="card-header">
                    <i class="fas fa-sliders-h"></i>
                    <h3>Configuration avancée</h3>
                </div>
                $form
            </div>
        </div>
    </main>
</div>

<script>
const sidebarToggle = document.getElementById('sidebarToggle');
const sidebar = document.getElementById('sidebar');
const mainContent = document.getElementById('mainContent');
let sidebarIsExpanded = true;

function collapseSidebar() {
    sidebar.classList.add('reduit');
    mainContent.classList.add('agrandi');
    sidebarIsExpanded = false;
    updateToggleIcon();
    localStorage.setItem('sidebarState', 'reduit');
}

function expandSidebar() {
    sidebar.classList.remove('reduit');
    mainContent.classList.remove('agrandi');
    sidebarIsExpanded = true;
    updateToggleIcon();
    localStorage.setItem('sidebarState', 'agrandi');
}

function toggleSidebar() {
    sidebarIsExpanded ? collapseSidebar() : expandSidebar();
}

function updateToggleIcon() {
    const icon = sidebarToggle.querySelector('i');
    if (sidebarIsExpanded) {
        icon.classList.remove('fa-chevron-right');
        icon.classList.add('fa-chevron-left');
    } else {
        icon.classList.remove('fa-chevron-left');
        icon.classList.add('fa-chevron-right');
    }
}

sidebarToggle.addEventListener('click', function(e) {
    e.preventDefault();
    toggleSidebar();
    this.classList.add('anime');
    setTimeout(() => this.classList.remove('anime'), 300);
});

function restoreSidebarState() {
    const savedState = localStorage.getItem('sidebarState');
    if (savedState === 'reduit') collapseSidebar();
    else expandSidebar();
}

restoreSidebarState();

window.addEventListener('load', () => {
    if (window.innerWidth <= 768) collapseSidebar();
});

window.addEventListener('resize', () => {
    if (window.innerWidth <= 768 && sidebarIsExpanded) collapseSidebar();
    else if (window.innerWidth > 768 && !sidebarIsExpanded) expandSidebar();
});
</script>
HTML;
    }


    /** FORMULAIRE */
    public function getForm() {
        global $gvEditableConfs;

        $fields = "";

        foreach ($gvEditableConfs as $conf) {
            $tag = $this->generateInputTag($conf);
            $description = $this->getFieldDescription($conf->getName());
            $label = $this->getFieldLabel($conf->getName());

            $fields .= <<<HTML
            <div class="settings-group">
                <div class="settings-label">
                    <label>{$label}</label>
                    <small class="conf-desc">$description</small>
                </div>
                <div class="settings-field">
                    $tag
                </div>
            </div>
HTML;
        }

        return <<<HTML
        <form method="post" class="settings-form">
            $fields
            <div class="form-actions">
                <button class="btn-principal" type="submit">
                    <i class="fas fa-save"></i> Enregistrer
                </button>
                <button type="button" class="btn-secondaire" onclick="window.location.reload();">
                    <i class="fas fa-undo-alt"></i> Annuler
                </button>
            </div>
        </form>
HTML;
    }


    /** LIBELLÉS DES CHAMPS EN FRANÇAIS */
    private function getFieldLabel($fieldName) {
        $labels = [
            'site_name' => 'Nom du site',
            'site_email' => 'Email du site',
            'ticket_prefix' => 'Préfixe des tickets',
            'max_ticket_daily' => 'Maximum de tickets par jour',
            'session_timeout' => 'Délai d\'expiration de session',
            'notifications_enabled' => 'Activer les notifications',
            'maintenance_mode' => 'Mode maintenance',
            'default_language' => 'Langue par défaut',
            'timezone' => 'Fuseau horaire',
            'date_format' => 'Format de date',
            'items_per_page' => 'Éléments par page',
            'ticket_expiry_days' => 'Expiration des tickets (jours)',
            'sysadmin_code' => 'Code sysAdmin',
            'sysadmin_password' => 'Mot de passe sysAdmin',
            'password_min_length' => 'Longueur minimale du mot de passe',
            'session_duration' => 'Durée de session'
        ];
        
        return isset($labels[$fieldName]) ? $labels[$fieldName] : ucfirst(str_replace('_', ' ', $fieldName));
    }


    /** DESCRIPTIONS DES CHAMPS EN FRANÇAIS */
    private function getFieldDescription($fieldName) {
        $descriptions = [
            'site_name' => 'Nom du site affiché dans l\'interface utilisateur et les emails',
            'site_email' => 'Adresse e-mail de contact pour les notifications système',
            'ticket_prefix' => 'Préfixe utilisé pour les numéros de ticket (ex: TK-, TKT-, etc.)',
            'max_ticket_daily' => 'Nombre maximum de tickets pouvant être émis par jour (0 = illimité)',
            'session_timeout' => 'Durée d\'inactivité (en minutes) avant déconnexion automatique',
            'notifications_enabled' => 'Activer l\'envoi des notifications par e-mail aux utilisateurs',
            'maintenance_mode' => 'Mode maintenance : restreint l\'accès aux administrateurs uniquement',
            'default_language' => 'Langue par défaut de l\'interface utilisateur',
            'timezone' => 'Fuseau horaire du système pour les horodatages',
            'date_format' => 'Format d\'affichage des dates (exemple : d/m/Y, Y-m-d, etc.)',
            'items_per_page' => 'Nombre d\'éléments affichés par page dans les listes',
            'ticket_expiry_days' => 'Nombre de jours avant expiration automatique des tickets non traités',
            'sysadmin_code' => 'Code d\'authentification pour l\'accès administrateur système',
            'sysadmin_password' => 'Mot de passe d\'accès pour l\'administrateur système',
            'password_min_length' => 'Longueur minimale requise pour les mots de passe utilisateur',
            'session_duration' => 'Durée de validité de la session utilisateur en secondes'
        ];
        
        return isset($descriptions[$fieldName]) ? $descriptions[$fieldName] : 'Configurer ce paramètre système';
    }


    /** GÉNÉRATION DES CHAMPS DE SAISIE */
    protected function generateInputTag($conf) {

        $tagName = 'input';
        $attributes = 'class="settings-input"';
        $value = htmlspecialchars($GLOBALS[$conf->getName()]);
        $type = $conf->getType();

        if ($type == 'integer') {
            $attributes .= ' type="number" step="1"';
        }
        elseif ($type == 'boolean') {
            $checked = $value ? 'checked' : '';
            return <<<HTML
            <label class="interrupteur">
                <input type="hidden" name="{$conf->getName()}" value="0">
                <input type="checkbox" name="{$conf->getName()}" value="1" $checked class="interrupteur-input">
                <span class="interrupteur-curseur"></span>
            </label>
HTML;
        }
        elseif ($type == 'textarea') {
            $tagName = 'textarea';
            $attributes .= ' rows="4" placeholder="Saisissez votre texte ici..."';
        }
        elseif ($type == 'color') {
            $attributes .= ' type="color"';
        }
        elseif ($type == 'email') {
            $attributes .= ' type="email" placeholder="exemple@domaine.com"';
        }
        elseif ($type == 'password') {
            $attributes .= ' type="password" placeholder="Saisissez un mot de passe..."';
        }
        else {
            $attributes .= ' type="text" placeholder="Saisissez une valeur..."';
        }

        if ($tagName == 'input') {
            return "<input name=\"{$conf->getName()}\" value=\"$value\" $attributes />";
        } else {
            return "<textarea name=\"{$conf->getName()}\" $attributes>$value</textarea>";
        }
    }


    /** CSS MODERNE */
    private function getDesignCSS() {
        return <<<CSS
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #f5f7fb; color: #1a1a2e; overflow-x: hidden; }
    .layout { display: flex; min-height: 100vh; }

    .sidebar {
        width: 280px;
        background: linear-gradient(180deg, #1a1a2e 0%, #16213e 100%);
        color: #fff;
        display: flex;
        flex-direction: column;
        position: fixed;
        left: 0;
        top: 0;
        height: 100vh;
        z-index: 1000;
        transition: width 0.3s ease;
        overflow-y: auto;
    }

    .sidebar.reduit { width: 80px; }
    .sidebar.reduit .sidebar-header { padding: 20px 15px; }
    .sidebar.reduit .logo { justify-content: center; margin-bottom: 20px; }
    .sidebar.reduit .logo-text, .sidebar.reduit .admin-badge, .sidebar.reduit .nav-text { display: none; }
    .sidebar.reduit .nav-item { justify-content: center; padding: 12px 15px; }

    .sidebar-toggle {
        position: absolute; top: 20px; right: -15px; z-index: 1001;
        background: linear-gradient(135deg, #6C63FF, #8B82FF);
        border: none; width: 35px; height: 35px; border-radius: 50%;
        color: white; font-size: 18px; cursor: pointer;
        box-shadow: 0 4px 12px rgba(108, 99, 255, 0.3);
        transition: all 0.3s ease; display: flex; align-items: center; justify-content: center;
    }

    .sidebar-toggle:hover { transform: scale(1.1); }
    .sidebar-toggle.anime { animation: pulse 0.3s ease; }

    @keyframes pulse {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.15); }
    }

    .sidebar-header { padding: 30px 25px; border-bottom: 1px solid rgba(255,255,255,0.1); position: relative; }
    .logo { display: flex; align-items: center; gap: 12px; margin-bottom: 15px; }
    .logo i { font-size: 28px; color: #6C63FF; flex-shrink: 0; }
    .logo-text { font-size: 22px; font-weight: 700; }
    .admin-badge { background: rgba(108, 99, 255, 0.2); padding: 6px 12px; border-radius: 20px; font-size: 12px; color: #6C63FF; }

    .sidebar-nav { flex: 1; padding: 20px 0; }
    .nav-item { display: flex; align-items: center; gap: 12px; padding: 12px 25px; color: rgba(255,255,255,0.8); text-decoration: none; transition: all 0.3s ease; font-size: 14px; font-weight: 500; }
    .nav-item i { width: 20px; font-size: 18px; flex-shrink: 0; }
    .nav-item:hover { background: rgba(108, 99, 255, 0.1); color: #fff; padding-left: 30px; }
    .sidebar.reduit .nav-item:hover { padding-left: 15px; background: rgba(108, 99, 255, 0.2); border-radius: 10px; }
    .nav-item.actif { background: linear-gradient(90deg, #6C63FF, rgba(108, 99, 255, 0.1)); color: #fff; border-right: 3px solid #6C63FF; }
    .sidebar.reduit .nav-item.actif { border-right: none; border-radius: 10px; }

    .sidebar-footer { padding: 20px 0; border-top: 1px solid rgba(255,255,255,0.1); }
    .deconnexion { color: #ff6b6b; }
    .deconnexion:hover { background: rgba(255, 107, 107, 0.1); padding-left: 30px; }

    .main-content { flex: 1; margin-left: 280px; min-height: 100vh; background: #f5f7fb; transition: margin-left 0.3s ease; }
    .main-content.agrandi { margin-left: 80px; }
    
    .content-wrapper { padding: 30px 40px; max-width: 1200px; margin: 0 auto; }
    .page-header { margin-bottom: 30px; }
    .page-header h1 { font-size: 28px; font-weight: 700; color: #1a1a2e; margin-bottom: 8px; display: flex; align-items: center; gap: 12px; }
    .page-header h1 i { color: #6C63FF; }
    .subtitle { color: #666; font-size: 14px; }

    .card { background: white; border-radius: 20px; box-shadow: 0 2px 12px rgba(0,0,0,0.05); overflow: hidden; }
    .card-header { display: flex; align-items: center; gap: 12px; padding: 20px 25px; background: #f8f9fa; border-bottom: 1px solid #e0e0e0; }
    .card-header i { font-size: 22px; color: #6C63FF; }
    .card-header h3 { font-size: 18px; font-weight: 600; color: #1a1a2e; }

    .message-box { padding: 15px 20px; border-radius: 12px; margin-bottom: 20px; display: flex; align-items: center; gap: 12px; font-size: 14px; }
    .message-box.succes { background: #e8f5e9; border-left: 4px solid #4caf50; color: #2e7d32; }
    .message-box.succes i { color: #4caf50; font-size: 20px; }
    .message-box a { color: #6C63FF; text-decoration: none; font-weight: 600; }
    .message-box a:hover { text-decoration: underline; }

    .settings-form { padding: 25px; }
    .settings-group { display: flex; align-items: flex-start; gap: 20px; padding: 20px 0; border-bottom: 1px solid #f0f0f0; }
    .settings-group:last-child { border-bottom: none; }
    .settings-label { flex: 0 0 250px; }
    .settings-label label { font-weight: 600; color: #1a1a2e; font-size: 14px; display: block; margin-bottom: 5px; }
    .conf-desc { font-size: 12px; color: #999; display: block; line-height: 1.4; }
    .settings-field { flex: 1; }

    .settings-input { width: 100%; padding: 12px 15px; border: 2px solid #e0e0e0; border-radius: 12px; font-size: 14px; font-family: 'Inter', monospace; transition: all 0.3s ease; }
    .settings-input:focus { outline: none; border-color: #6C63FF; box-shadow: 0 0 0 3px rgba(108,99,255,0.1); }
    textarea.settings-input { resize: vertical; font-family: inherit; }

    .interrupteur { position: relative; display: inline-block; width: 52px; height: 28px; cursor: pointer; }
    .interrupteur-input { opacity: 0; width: 0; height: 0; position: absolute; }
    .interrupteur-curseur { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #ccc; transition: 0.3s; border-radius: 34px; }
    .interrupteur-curseur:before { position: absolute; content: ""; height: 22px; width: 22px; left: 3px; bottom: 3px; background-color: white; transition: 0.3s; border-radius: 50%; }
    .interrupteur-input:checked + .interrupteur-curseur { background-color: #6C63FF; }
    .interrupteur-input:checked + .interrupteur-curseur:before { transform: translateX(24px); }

    .form-actions { display: flex; gap: 15px; justify-content: flex-end; margin-top: 30px; padding-top: 20px; border-top: 1px solid #f0f0f0; }
    .btn-principal, .btn-secondaire { padding: 12px 28px; border-radius: 12px; font-weight: 600; font-size: 14px; cursor: pointer; transition: all 0.3s ease; border: none; font-family: inherit; display: inline-flex; align-items: center; gap: 8px; }
    .btn-principal { background: linear-gradient(135deg, #6C63FF, #8B82FF); color: white; }
    .btn-principal:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(108,99,255,0.4); }
    .btn-secondaire { background: #f0f0f0; color: #666; }
    .btn-secondaire:hover { background: #e0e0e0; }

    @media (max-width: 1024px) {
        .content-wrapper { padding: 20px 25px; }
        .settings-group { flex-direction: column; gap: 12px; }
        .settings-label { flex: auto; }
    }

    @media (max-width: 768px) {
        .sidebar-toggle { display: none; }
        .main-content { margin-left: 80px; }
        .main-content.agrandi { margin-left: 80px; }
        .content-wrapper { padding: 80px 15px 20px 15px; }
        .page-header h1 { font-size: 24px; }
        .card-header { padding: 15px 20px; }
        .settings-form { padding: 15px; }
        .settings-group { padding: 15px 0; }
        .form-actions { flex-direction: column; }
        .btn-principal, .btn-secondaire { width: 100%; justify-content: center; }
    }

    @media (max-width: 480px) {
        .content-wrapper { padding: 70px 12px 15px 12px; }
        .settings-label label { font-size: 13px; }
        .conf-desc { font-size: 11px; }
    }

    @media print {
        .sidebar { display: none; }
        .main-content { margin-left: 0; }
        .content-wrapper { padding: 0; }
        .card { box-shadow: none; border: 1px solid #ddd; }
        .form-actions { display: none; }
    }

    ::-webkit-scrollbar { width: 8px; height: 8px; }
    ::-webkit-scrollbar-track { background: #f0f0f0; }
    ::-webkit-scrollbar-thumb { background: #c0c0c0; border-radius: 4px; }
    ::-webkit-scrollbar-thumb:hover { background: #a0a0a0; }
</style>
CSS;
    }
}
?>