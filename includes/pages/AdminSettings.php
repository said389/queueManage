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


    /** ✅ LAYOUT COMPLET */
    private function getLayout() {
        global $gvPath;

        $messageBox = $this->message
            ? "<div class='message-box success'><i class=\"fas fa-check-circle\"></i> {$this->message}</div>"
            : "";

        $form = $this->getForm();

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
            <a href="$gvPath/application/adminOperatorList" class="nav-item">
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
            <a href="$gvPath/application/adminSettings" class="nav-item active">
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
                <h1></i> Paramètres système</h1>
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

<!-- Overlay pour mobile -->
<div class="overlay" id="overlay"></div>

<script>
const mobileToggle = document.getElementById('mobileToggle');
const sidebar = document.getElementById('sidebar');
const overlay = document.getElementById('overlay');

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

// Fermer sidebar sur resize si écran large
window.addEventListener('resize', () => {
    if (window.innerWidth > 768) {
        sidebar.classList.remove('open');
        overlay.classList.remove('show');
    }
});
</script>
HTML;
    }


    /** ✅ FORMULAIRE */
    public function getForm() {
        global $gvEditableConfs;

        $fields = "";

        foreach ($gvEditableConfs as $conf) {
            $tag = $this->generateInputTag($conf);
            
            // Ajout d'une petite description basée sur le nom du paramètre
            $description = $this->getFieldDescription($conf->getName());

            $fields .= <<<HTML
            <div class="settings-group">
                <div class="settings-label">
                    <label>{$conf->getText()}</label>
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
                <button class="btn-primary" type="submit">
                    <i class="fas fa-save"></i> Enregistrer
                </button>
                <button type="button" class="btn-secondary" onclick="window.location.reload();">
                    <i class="fas fa-undo-alt"></i> Annuler
                </button>
            </div>
        </form>
HTML;
    }


    /** ✅ Description des champs */
    private function getFieldDescription($fieldName) {
        $descriptions = [
            'site_name' => 'Nom du site affiché dans l\'interface',
            'site_email' => 'Email de contact pour les notifications',
            'ticket_prefix' => 'Préfixe utilisé pour les numéros de ticket',
            'max_ticket_daily' => 'Nombre maximum de tickets par jour',
            'session_timeout' => 'Durée d\'inactivité avant déconnexion (minutes)',
            'notifications_enabled' => 'Activer les notifications par email',
            'maintenance_mode' => 'Mode maintenance : restreint l\'accès utilisateur',
            'default_language' => 'Langue par défaut de l\'interface',
            'timezone' => 'Fuseau horaire du système',
            'date_format' => 'Format d\'affichage des dates',
            'items_per_page' => 'Nombre d\'éléments par page dans les listes',
            'ticket_expiry_days' => 'Nombre de jours avant expiration des tickets'
        ];
        
        return isset($descriptions[$fieldName]) ? $descriptions[$fieldName] : 'Configurer ce paramètre';
    }


    /** ✅ INPUTS */
    protected function generateInputTag($conf) {

        $tagName = 'input';
        $attributes = 'class="settings-input"';
        $value = htmlspecialchars($GLOBALS[$conf->getName()]);
        $type = $conf->getType();

        if ($type == 'integer') {
            $attributes .= ' type="number" step="1"';
        }
        elseif ($type == 'boolean') {
            // Version simplifiée du toggle switch sans texte à côté
            $checked = $value ? 'checked' : '';
            return <<<HTML
            <label class="toggle-switch">
                <input type="hidden" name="{$conf->getName()}" value="0">
                <input type="checkbox" name="{$conf->getName()}" value="1" $checked class="toggle-input">
                <span class="toggle-slider"></span>
            </label>
HTML;
        }
        elseif ($type == 'textarea') {
            $tagName = 'textarea';
            $attributes .= ' rows="4" placeholder="Entrez votre texte ici..."';
        }
        elseif ($type == 'color') {
            $attributes .= ' type="color"';
        }
        elseif ($type == 'email') {
            $attributes .= ' type="email" placeholder="exemple@domaine.com"';
        }
        else {
            $attributes .= ' type="text" placeholder="Entrez une valeur..."';
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
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: #f5f7fb;
            color: #1a1a2e;
            overflow-x: hidden;
        }

        /* Layout */
        .layout {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar */
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
            transition: transform 0.3s ease;
            overflow-y: auto;
        }

        .sidebar-header {
            padding: 30px 25px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 15px;
        }

        .logo i {
            font-size: 28px;
            color: #6C63FF;
        }

        .logo h2 {
            font-size: 22px;
            font-weight: 700;
        }

        .admin-badge {
            background: rgba(108, 99, 255, 0.2);
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            color: #6C63FF;
            display: inline-block;
        }

        .sidebar-nav {
            flex: 1;
            padding: 20px 0;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 25px;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            transition: all 0.3s ease;
            font-size: 14px;
            font-weight: 500;
        }

        .nav-item i {
            width: 20px;
            font-size: 18px;
        }

        .nav-item:hover {
            background: rgba(108, 99, 255, 0.1);
            color: #fff;
        }

        .nav-item.active {
            background: linear-gradient(90deg, #6C63FF, rgba(108, 99, 255, 0.1));
            color: #fff;
            border-right: 3px solid #6C63FF;
        }

        .sidebar-footer {
            padding: 20px 0;
            border-top: 1px solid rgba(255,255,255,0.1);
        }

        .logout {
            color: #ff6b6b;
        }

        .logout:hover {
            background: rgba(255, 107, 107, 0.1);
        }

        /* Mobile Toggle */
        .mobile-toggle {
            display: none;
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1001;
            background: #6C63FF;
            border: none;
            width: 45px;
            height: 45px;
            border-radius: 12px;
            color: white;
            font-size: 20px;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        /* Overlay */
        .overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 999;
        }

        .overlay.show {
            display: block;
        }

        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 280px;
            min-height: 100vh;
            background: #f5f7fb;
        }

        .content-wrapper {
            padding: 30px 40px;
            max-width: 1200px;
            margin: 0 auto;
        }

        /* Page Header */
        .page-header {
            margin-bottom: 30px;
        }

        .page-header h1 {
            font-size: 28px;
            font-weight: 700;
            color: #1a1a2e;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .page-header h1 i {
            color: #6C63FF;
        }

        .subtitle {
            color: #666;
            font-size: 14px;
        }

        /* Card */
        .card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.05);
            overflow: hidden;
        }

        .card-header {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 20px 25px;
            background: #f8f9fa;
            border-bottom: 1px solid #e0e0e0;
        }

        .card-header i {
            font-size: 22px;
            color: #6C63FF;
        }

        .card-header h3 {
            font-size: 18px;
            font-weight: 600;
            color: #1a1a2e;
        }

        /* Message Box */
        .message-box {
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 14px;
        }

        .message-box.success {
            background: #e8f5e9;
            border-left: 4px solid #4caf50;
            color: #2e7d32;
        }

        .message-box.success i {
            color: #4caf50;
            font-size: 20px;
        }

        .message-box a {
            color: #6C63FF;
            text-decoration: none;
            font-weight: 600;
        }

        .message-box a:hover {
            text-decoration: underline;
        }

        /* Settings Form */
        .settings-form {
            padding: 25px;
        }

        .settings-group {
            display: flex;
            align-items: flex-start;
            gap: 20px;
            padding: 20px 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .settings-group:last-child {
            border-bottom: none;
        }

        .settings-label {
            flex: 0 0 250px;
        }

        .settings-label label {
            font-weight: 600;
            color: #1a1a2e;
            font-size: 14px;
            display: block;
            margin-bottom: 5px;
        }

        .conf-desc {
            font-size: 12px;
            color: #999;
            display: block;
            line-height: 1.4;
        }

        .settings-field {
            flex: 1;
        }

        .settings-input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            font-size: 14px;
            font-family: 'Inter', monospace;
            transition: all 0.3s ease;
        }

        .settings-input:focus {
            outline: none;
            border-color: #6C63FF;
            box-shadow: 0 0 0 3px rgba(108,99,255,0.1);
        }

        textarea.settings-input {
            resize: vertical;
            font-family: inherit;
        }

        /* Toggle Switch - Version simplifiée sans texte */
        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 52px;
            height: 28px;
            cursor: pointer;
        }

        .toggle-input {
            opacity: 0;
            width: 0;
            height: 0;
            position: absolute;
        }

        .toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: 0.3s;
            border-radius: 34px;
        }

        .toggle-slider:before {
            position: absolute;
            content: "";
            height: 22px;
            width: 22px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: 0.3s;
            border-radius: 50%;
        }

        .toggle-input:checked + .toggle-slider {
            background-color: #6C63FF;
        }

        .toggle-input:checked + .toggle-slider:before {
            transform: translateX(24px);
        }

        /* Form Actions */
        .form-actions {
            display: flex;
            gap: 15px;
            justify-content: flex-end;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #f0f0f0;
        }

        .btn-primary,
        .btn-secondary {
            padding: 12px 28px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
            font-family: inherit;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #6C63FF, #8B82FF);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(108,99,255,0.4);
        }

        .btn-secondary {
            background: #f0f0f0;
            color: #666;
        }

        .btn-secondary:hover {
            background: #e0e0e0;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .content-wrapper {
                padding: 20px 25px;
            }
            
            .settings-group {
                flex-direction: column;
                gap: 12px;
            }
            
            .settings-label {
                flex: auto;
            }
        }

        @media (max-width: 768px) {
            .mobile-toggle {
                display: block;
            }

            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.open {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
            }

            .content-wrapper {
                padding: 80px 15px 20px 15px;
            }

            .page-header h1 {
                font-size: 24px;
            }

            .card-header {
                padding: 15px 20px;
            }

            .settings-form {
                padding: 15px;
            }

            .settings-group {
                padding: 15px 0;
            }

            .form-actions {
                flex-direction: column;
            }

            .btn-primary,
            .btn-secondary {
                width: 100%;
                justify-content: center;
            }
        }

        @media (max-width: 480px) {
            .content-wrapper {
                padding: 70px 12px 15px 12px;
            }

            .settings-label label {
                font-size: 13px;
            }

            .conf-desc {
                font-size: 11px;
            }
        }

        /* Print */
        @media print {
            .sidebar,
            .mobile-toggle,
            .overlay,
            .form-actions {
                display: none;
            }

            .main-content {
                margin-left: 0;
            }

            .content-wrapper {
                padding: 0;
            }

            .card {
                box-shadow: none;
                border: 1px solid #ddd;
            }
        }

        /* Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #f0f0f0;
        }

        ::-webkit-scrollbar-thumb {
            background: #c0c0c0;
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #a0a0a0;
        }
    </style>
</head>
<body>
CSS;
    }
}
?>