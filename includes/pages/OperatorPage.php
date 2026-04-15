<?php

/**
 * Description of OperatorPage
 *
 * @author sergio
 */
class OperatorPage extends Page {

    private $operator = null;
    private $desk = null;
    private $ticket_served = null;
    private $td_served = array();
    private $message = '';
    private $pauseButtonEnabled = true;
    private $disableNextButton = false;
    
    public function canUse( $userLevel ) {
        return $userLevel == Page::OPERATOR_USER;
    }

    public function execute() {
        // ✅ Récupérer les domaines depuis POST
        $this->td_served = gfPostVar( 'td_served', array() );
        
        // Assurer que td_served est un array
        if ( !is_array( $this->td_served ) ) {
            $this->td_served = array();
        }
        
        // Sauvegarder en session
        $_SESSION['td_served'] = $this->td_served;

        // ✅ Si "next" a été cliqué
        if ( isset( $_POST['next'] ) ) {
            // Vérifier si au moins un domaine est sélectionné
            if ( empty( $this->td_served ) ) {
                $this->message = "Erreur: sélectionnez au moins un domaine thématique.";
                return true;
            }

            // Handle served ticket
            $served = Ticket::fromDatabaseByDesk( $this->getDesk()->getNumber() );
            if ( $served ) {
                $stats = TicketStats::newFromTicket( $served );
                $served->delete();
                if ( !$stats->save() ) {
                    throw new Exception( "Unable to save ticket stats." );
                }
            }

            // Call next ticket
            try {
                $ticket = Ticket::serveNextTicket(
                    $this->td_served,
                    $this->getOperator()->getCode(),
                    $this->getDesk()->getNumber()
                );
                
                if ( !$ticket ) {
                    $this->message = "Aucun ticket à appeler";
                    $this->pauseButtonEnabled = false;
                    $this->ticket_served = null;
                    return true;
                }
                
                $this->ticket_served = $ticket;
                $this->disableNextButton = true;
                $this->pauseButtonEnabled = true;
                return true;
                
            } catch ( Exception $e ) {
                $this->message = "Erreur: " . $e->getMessage();
                return true;
            }
        }
        
        // Handle pause button
        if ( isset( $_POST['pause'] ) ) {
            $this->pauseButtonEnabled = false;
            $this->ticket_served = null;
            return true;
        }

        return true;
    }
    
    public function afterPermissionCheck() {
        $this->td_served = gfSessionVar( 'td_served', array() );
        
        // Assurer que td_served est un array
        if ( !is_array( $this->td_served ) ) {
            $this->td_served = array();
        }
        
        $this->ticket_served = Ticket::fromDatabaseByOperator(
                $this->getOperator()->getCode()
        );
        if ( $this->ticket_served == null ) {
            $this->pauseButtonEnabled = false;
        } else {
            $this->disableNextButton = true;
        }
    }

    public function getOutput() {
        global $gvPath;

        $page = new WebPageOutput();
        $page->setHtmlPageTitle( "Espace opérateur" );
        $page->setHtmlBodyHeader( $this->getDesignCSS() );
        $page->setHtmlBodyContent( $this->getPageContent() );
        $page->linkStyleSheet( "$gvPath/assets/css/style.css" );
        $page->importJquery();
        $page->addJavascript( "$gvPath/assets/js/opPage.js" );
        return $page;
    }
    
    private function getPageContent() {
        global $gvPath, $gvAllowPause;

        $servedText = $this->ticket_served ? $this->ticket_served->getTextString() : 'Aucun';
        $tableBody = $this->getTableBody();
        $operator = $this->getOperator();
        $desk = $this->getDesk();
        
        // Sécuriser td_served
        $tdServedArray = is_array( $this->td_served ) ? $this->td_served : array();
        $tdServedText = !empty( $tdServedArray ) ? implode( ', ', $tdServedArray ) : 'Aucun';
        
        $messageClass = '';
        if ( $this->message ) {
            $messageClass = strpos($this->message, 'Erreur') !== false ? 'error' : 'info';
        }
        $pMessage = $this->message ? "<div class=\"alert alert-$messageClass\"><i class=\"fas fa-exclamation-circle\"></i> {$this->message}</div>" : '';
        
        $pauseButton = '';
        if ( $gvAllowPause ) {
            $disabled = $this->pauseButtonEnabled ? '' : ' disabled';
            $pauseButton = '<button type="submit" name="pause" value="1" class="btn btn-secondary"' . $disabled . '><i class="fas fa-pause"></i> Pause</button>';
        }

        $nextButtonBlock = $this->getHiddenForNextButton();
        $disabledClass = $this->disableNextButton ? ' disabled-btn' : '';

        return <<<HTML
$nextButtonBlock
<div class="layout">
    <!-- Header Navigation -->
    <div class="operator-header">
        <div class="header-top">
            <div class="logo-section">
                <h1>FastQueue</h1>
                <span class="operator-badge">Opérateur</span>
            </div>
            <div class="header-links">
                <a href="$gvPath/application/help" class="header-link">
                    <i class="fas fa-question-circle"></i> Aide
                </a>
                <a href="$gvPath/application/logoutPage" class="header-link logout">
                    <i class="fas fa-sign-out-alt"></i> Déconnexion
                </a>
            </div>
        </div>

        <!-- Operator Info -->
        <div class="operator-info-bar">
            <div class="info-item">
                <i class="fas fa-user-circle"></i>
                <div>
                    <span class="info-label">Opérateur</span>
                    <span class="info-value">{$operator->getFullName()}</span>
                </div>
            </div>
            <div class="info-item">
                <i class="fas fa-id-badge"></i>
                <div>
                    <span class="info-label">Code</span>
                    <span class="info-value">{$operator->getCode()}</span>
                </div>
            </div>
            <div class="info-item">
                <i class="fas fa-desktop"></i>
                <div>
                    <span class="info-label">Compteur</span>
                    <span class="info-value">{$desk->getNumber()}</span>
                </div>
            </div>
            <div class="info-item">
                <i class="fas fa-layer-group"></i>
                <div>
                    <span class="info-label">Domaines servis</span>
                    <span class="info-value">$tdServedText</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <main class="operator-main">
        <div class="content-container">
            <!-- ✅ UN SEUL FORMULAIRE PRINCIPAL -->
            <form method="post" class="main-form">
            
                <!-- Ticket Display -->
                <div class="ticket-section card">
                    <div class="ticket-header">
                        <h2>Ticket actuel</h2>
                    </div>
                    <div class="ticket-display">
                        <div class="ticket-number">
                            <span>$servedText</span>
                        </div>
                    </div>
                    $pMessage
                </div>

                <!-- Topical Domains Selection -->
                <div class="domains-section card">
                    <h3>Sélectionner les domaines thématiques à servir</h3>
                    <div class="domains-grid">
                        $tableBody
                    </div>
                </div>

                <!-- Control Buttons -->
                <div class="controls-section card">
                    <h3>Contrôles</h3>
                    <div class="button-group">
                        <button type="submit" name="next" value="1" class="btn btn-primary btn-large$disabledClass">
                            <i class="fas fa-arrow-right"></i> Prochain ticket
                        </button>
                        $pauseButton
                    </div>
                </div>
                
            </form>
        </div>
    </main>
</div>

HTML;
    }

    private function getHiddenForNextButton() {
        $ret = '<div id="disableNextButton" style="display: none;">' . PHP_EOL;
        if ( $this->disableNextButton ) {
            $ret .= "true\n";
        } else {
            $ret .= "false\n";
        }
        $ret .= "</div>\n";
        return $ret;
    }

    private function getOperator() {
        if ( !$this->operator ) {
            // Get from session
            if ( isset( $_SESSION['operator'] ) ) {
                $this->operator = $_SESSION['operator'];
            } else if ( isset( $_SESSION['op_code'] ) ) {
                $this->operator = Operator::fromDatabaseByCode( $_SESSION['op_code'] );
            } else {
                throw new Exception( "Unable to retrieve logged-in operator." );
            }
        }
        return $this->operator;
    }

    private function getDesk() {
        if ( !$this->desk ) {
            if ( isset( $_SESSION['desk'] ) ) {
                $this->desk = $_SESSION['desk'];
            } else if ( isset( $_SESSION['desk_number'] ) ) {
                $this->desk = Desk::fromDatabaseByNumber( $_SESSION['desk_number'] );
            } else {
                throw new Exception( "Unable to retrieve operator's desk." );
            }
        }
        return $this->desk;
    }
    
    private function getCheckBox( $td_code, $text, $queueLength, $checked = false ) {
        $checked = $checked ? ' checked' : '';
        return "<input type=\"checkbox\" name=\"td_served[]\" value=\"$td_code\" class=\"domain-checkbox\"$checked /><span class=\"domain-label\">$text <span class=\"queue-count\">($queueLength)</span></span>";
    }

    private function getTableBody() {
        $topicalDomains = TopicalDomain::fromDatabaseCompleteList( true );
        $tableBody = '';
        
        // Assurer que td_served est un array
        $td_served = is_array( $this->td_served ) ? $this->td_served : array();
        
        foreach ( $topicalDomains as $td ) {
            $description = $td->getCode() . " - " . htmlspecialchars( $td->getName() );
            $queueLength = Ticket::getNumberTicketInQueue( $td->getCode() );
            $checkbox = $this->getCheckBox(
                $td->getCode(),
                $description,
                $queueLength,
                in_array( $td->getCode(), $td_served )
            );
            
            $tableBody .= <<<HTML
            <label class="domain-item">
                $checkbox
            </label>
HTML;
        }
        
        return $tableBody;
    }

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
            background: linear-gradient(135deg, #f5f7fb 0%, #eef2f9 100%);
            color: #1a1a2e;
            overflow-x: hidden;
        }

        /* Layout */
        .layout {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        /* Header */
        .operator-header {
            background: white;
            border-bottom: 2px solid #f0f0f0;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .header-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 20px 40px;
            border-bottom: 1px solid #f0f0f0;
        }

        .logo-section {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .logo-section h1 {
            font-size: 24px;
            font-weight: 800;
            color: #1a1a2e;
        }

        .operator-badge {
            background: linear-gradient(135deg, #6C63FF, #8B82FF);
            color: white;
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
        }

        .header-links {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .header-link {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #666;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            padding: 8px 12px;
            border-radius: 8px;
        }

        .header-link:hover {
            background: #f0f0f0;
            color: #6C63FF;
        }

        .header-link.logout {
            color: #ff6b6b;
        }

        .header-link.logout:hover {
            background: rgba(255, 107, 107, 0.1);
        }

        /* Operator Info Bar */
        .operator-info-bar {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 24px;
            padding: 24px 40px;
            background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
        }

        .info-item {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .info-item i {
            font-size: 24px;
            color: #6C63FF;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(108, 99, 255, 0.1);
            border-radius: 12px;
        }

        .info-label {
            display: block;
            font-size: 12px;
            color: #999;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
            margin-bottom: 4px;
        }

        .info-value {
            display: block;
            font-size: 16px;
            font-weight: 700;
            color: #1a1a2e;
        }

        /* Main Content */
        .operator-main {
            flex: 1;
            padding: 40px;
        }

        .content-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .main-form {
            display: grid;
            gap: 30px;
        }

        /* Cards */
        .card {
            background: white;
            border-radius: 18px;
            padding: 30px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.06);
            border: 1px solid rgba(0,0,0,0.03);
            transition: all 0.3s ease;
        }

        .card:hover {
            box-shadow: 0 8px 24px rgba(0,0,0,0.1);
        }

        .card h3 {
            font-size: 18px;
            font-weight: 700;
            color: #1a1a2e;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .card h3 i {
            color: #6C63FF;
        }

        /* Ticket Display */
        .ticket-header {
            margin-bottom: 24px;
            padding-bottom: 16px;
            border-bottom: 2px solid #f0f0f0;
        }

        .ticket-header h2 {
            font-size: 22px;
            font-weight: 700;
            color: #1a1a2e;
        }

        .ticket-display {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 280px;
            background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
            border-radius: 16px;
            margin-bottom: 20px;
        }

        .ticket-number {
            text-align: center;
        }

        .ticket-number span {
            display: block;
            font-size: 72px;
            font-weight: 800;
            background: linear-gradient(135deg, #6C63FF, #8B82FF);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            line-height: 1;
            margin-bottom: 12px;
        }

        /* Alerts */
        .alert {
            padding: 14px 16px;
            border-radius: 12px;
            margin-top: 16px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 500;
            font-size: 14px;
        }

        .alert i {
            font-size: 18px;
            flex-shrink: 0;
        }

        .alert.alert-error {
            background: #fff0f0;
            color: #c0392b;
            border-left: 4px solid #e74c3c;
        }

        .alert.alert-info {
            background: #f0f8ff;
            color: #0066cc;
            border-left: 4px solid #00a8ff;
        }

        /* Controls Section */
        .controls-section {
            background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
        }

        .button-group {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
        }

        /* Buttons */
        .btn {
            padding: 12px 24px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
            font-family: inherit;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            text-decoration: none;
        }

        .btn-primary {
            background: linear-gradient(135deg, #6C63FF, #8B82FF);
            color: white;
        }

        .btn-primary:hover:not(.disabled-btn) {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(108,99,255,0.4);
        }

        .btn-secondary {
            background: #f0f0f0;
            color: #666;
        }

        .btn-secondary:hover:not(:disabled) {
            background: #e0e0e0;
        }

        .btn-large {
            padding: 16px 32px;
            font-size: 16px;
            min-height: 56px;
        }

        .disabled-btn {
            opacity: 0.5;
            cursor: not-allowed;
            pointer-events: none;
        }

        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        /* Domains Grid */
        .domains-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 16px;
        }

        .domain-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 16px;
            background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .domain-item:hover {
            background: #f8f9fa;
            border-color: #6C63FF;
            box-shadow: 0 4px 12px rgba(108,99,255,0.1);
        }

        .domain-checkbox {
            width: 18px;
            height: 18px;
            cursor: pointer;
            accent-color: #6C63FF;
        }

        .domain-checkbox:checked + .domain-label {
            color: #6C63FF;
            font-weight: 600;
        }

        .domain-label {
            flex: 1;
            font-weight: 500;
            color: #1a1a2e;
            cursor: pointer;
        }

        .queue-count {
            display: inline-block;
            background: linear-gradient(135deg, #6C63FF, #8B82FF);
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 700;
            margin-left: 4px;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .header-top {
                padding: 16px 24px;
            }

            .operator-info-bar {
                padding: 20px 24px;
                gap: 16px;
            }

            .operator-main {
                padding: 24px;
            }
        }

        @media (max-width: 768px) {
            .header-top {
                flex-direction: column;
                gap: 16px;
                text-align: center;
            }

            .logo-section {
                justify-content: center;
            }

            .header-links {
                justify-content: center;
                width: 100%;
            }

            .operator-info-bar {
                grid-template-columns: 1fr;
                gap: 12px;
            }

            .operator-main {
                padding: 16px;
            }

            .card {
                padding: 20px;
            }

            .ticket-display {
                min-height: 200px;
            }

            .ticket-number span {
                font-size: 48px;
            }

            .domains-grid {
                grid-template-columns: 1fr;
            }

            .button-group {
                grid-template-columns: 1fr;
            }

            .btn-large {
                width: 100%;
            }
        }

        @media (max-width: 480px) {
            .header-top {
                padding: 12px 16px;
            }

            .logo-section h1 {
                font-size: 20px;
            }

            .operator-info-bar {
                padding: 16px;
                gap: 12px;
            }

            .info-item {
                gap: 10px;
            }

            .info-item i {
                width: 36px;
                height: 36px;
                font-size: 18px;
            }

            .info-label {
                font-size: 10px;
            }

            .info-value {
                font-size: 14px;
            }

            .operator-main {
                padding: 12px;
            }

            .card {
                padding: 16px;
                margin-bottom: 16px;
            }

            .card h3 {
                font-size: 16px;
                margin-bottom: 16px;
            }

            .ticket-number span {
                font-size: 36px;
            }

            .domains-grid {
                gap: 10px;
            }

            .domain-item {
                padding: 12px;
                font-size: 13px;
            }

            .btn {
                padding: 12px 16px;
                font-size: 13px;
            }

            .btn-large {
                padding: 14px 16px;
                min-height: 48px;
            }
        }

        /* Print */
        @media print {
            body {
                background: white;
            }

            .operator-header {
                display: none;
            }

            .operator-main {
                padding: 0;
            }

            .card {
                box-shadow: none;
                border: 1px solid #ddd;
                page-break-inside: avoid;
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