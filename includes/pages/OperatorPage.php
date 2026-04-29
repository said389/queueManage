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
    private $services_served = array();
    private $message = '';
    private $pauseButtonEnabled = true;
    private $selected_ticket_id = null;
    private $pending_tickets = array();
    private $is_ajax = false;
    
    public function canUse( $userLevel ) {
        return $userLevel == Page::OPERATOR_USER;
    }

    public function execute() {
        error_log("=== OperatorPage execute() START ===");
        
        // Déterminer si c'est une requête AJAX
        $this->is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
        
        if ($this->is_ajax) {
            error_log("AJAX request detected");
            return $this->handleAjaxRequest();
        }
        
        // Récupérer les services depuis POST
        $this->services_served = gfPostVar( 'services_served', array() );
        
        // Assurer que services_served est un array
        if ( !is_array( $this->services_served ) ) {
            $this->services_served = array();
        }
        
        // Sauvegarder en session
        $_SESSION['services_served'] = $this->services_served;
        error_log("Services served: " . json_encode($this->services_served));
        
        // ========== GESTION DU CLIC "SÉLECTIONNER UN TICKET" ==========
        if ( isset( $_POST['select_ticket'] ) ) {
            $this->selected_ticket_id = (int)gfPostVar( 'ticket_id', 0 );
            error_log("Ticket selected: " . $this->selected_ticket_id);
            
            if ( $this->selected_ticket_id > 0 ) {
                $_SESSION['selected_ticket_id'] = $this->selected_ticket_id;
                $this->ticket_served = $this->getTicketById( $this->selected_ticket_id );
                
                if ( $this->ticket_served ) {
                    error_log("Ticket loaded: " . json_encode($this->ticket_served));
                    return true;
                }
            }
        }
        
        // ========== GESTION DU CLIC "TERMINER" ==========
        if ( isset( $_POST['finish'] ) ) {
            error_log("Finish button clicked");
            
            if ( $this->selected_ticket_id > 0 ) {
                try {
                    // Récupérer le ticket
                    $ticket = $this->getTicketById( $this->selected_ticket_id );
                    
                    if ( $ticket ) {
                        // Créer les stats
                        error_log("Creating ticket stats for ticket ID: " . $this->selected_ticket_id);
                        $stats = $this->createTicketStats( $ticket );
                        
                        // Sauvegarder les stats
                        if ( $this->saveTicketStats( $stats ) ) {
                            error_log("Stats saved successfully");
                        } else {
                            error_log("Failed to save stats");
                        }
                        
                        // Supprimer le ticket
                        if ( $this->deleteTicket( $this->selected_ticket_id ) ) {
                            error_log("Ticket deleted successfully");
                            $this->message = "✅ Ticket traité avec succès";
                            $this->selected_ticket_id = null;
                            $this->ticket_served = null;
                            unset( $_SESSION['selected_ticket_id'] );
                            return true;
                        } else {
                            $this->message = "❌ Erreur lors de la suppression du ticket";
                            return true;
                        }
                    }
                } catch ( Exception $e ) {
                    $this->message = "❌ Erreur: " . $e->getMessage();
                    error_log("Exception in finish: " . $e->getMessage());
                    return true;
                }
            } else {
                $this->message = "⚠️ Aucun ticket sélectionné";
                return true;
            }
        }
        
        // ========== GESTION DU CLIC "RETOUR À LA LISTE" ==========
        if ( isset( $_POST['back_to_list'] ) ) {
            error_log("Back to list button clicked");
            $this->selected_ticket_id = null;
            $this->ticket_served = null;
            unset( $_SESSION['selected_ticket_id'] );
            return true;
        }
        
        // ========== GESTION DU BOUTON PAUSE/RETOUR AUX SERVICES ==========
        if ( isset( $_POST['pause'] ) || isset( $_POST['back_to_services'] ) ) {
            error_log("Pause/Back button clicked");
            $this->pauseButtonEnabled = false;
            $this->ticket_served = null;
            $this->selected_ticket_id = null;
            $this->services_served = array();
            unset( $_SESSION['selected_ticket_id'] );
            unset( $_SESSION['services_served'] );
            return true;
        }

        error_log("=== OperatorPage execute() END ===");
        return true;
    }
    
    /**
     * Gère les requêtes AJAX
     */
    private function handleAjaxRequest() {
        error_log("=== handleAjaxRequest() START ===");
        
        $action = gfPostVar('action', '');
        error_log("AJAX action: " . $action);
        
        switch ($action) {
            case 'get_tickets':
                return $this->ajaxGetTickets();
            case 'select_ticket':
                return $this->ajaxSelectTicket();
            case 'finish_ticket':
                return $this->ajaxFinishTicket();
            case 'back_to_list':
                return $this->ajaxBackToList();
            case 'back_to_services':
                return $this->ajaxBackToServices();
            default:
                $this->jsonResponse(false, 'Action inconnue', 400);
                return false;
        }
    }
    
    /**
     * AJAX: Récupère la liste des tickets
     */
    private function ajaxGetTickets() {
        error_log("=== ajaxGetTickets() START ===");
        
        try {
            $services = gfPostVar('services', array());
            error_log("Services received: " . json_encode($services));
            
            if (!is_array($services)) {
                $services = array();
            }
            
            $_SESSION['services_served'] = $services;
            
            $tickets = $this->getPendingTicketsByServices($services);
            error_log("Found " . count($tickets) . " tickets");
            
            // Générer le HTML de la liste
            $ticketsHtml = $this->generateTicketsListHtml($tickets);
            
            $this->jsonResponse(true, 'Tickets chargés', 200, array(
                'ticketsHtml' => $ticketsHtml,
                'count' => count($tickets),
                'services' => $services
            ));
            return false;
            
        } catch (Exception $e) {
            error_log("ajaxGetTickets Exception: " . $e->getMessage());
            $this->jsonResponse(false, 'Erreur: ' . $e->getMessage(), 500);
            return false;
        }
    }
    
    /**
     * AJAX: Sélectionne un ticket
     */
    private function ajaxSelectTicket() {
        error_log("=== ajaxSelectTicket() START ===");
        
        try {
            $ticketId = (int)gfPostVar('ticket_id', 0);
            error_log("Selecting ticket ID: " . $ticketId);
            
            if ($ticketId <= 0) {
                $this->jsonResponse(false, 'ID ticket invalide', 400);
                return false;
            }
            
            $ticket = $this->getTicketById($ticketId);
            
            if (!$ticket) {
                $this->jsonResponse(false, 'Ticket non trouvé', 404);
                return false;
            }
            
            $_SESSION['selected_ticket_id'] = $ticketId;
            $this->ticket_served = $ticket;
            
            // Générer le HTML d'affichage du ticket
            $ticketHtml = $this->generateTicketDisplayHtml($ticket);
            
            $this->jsonResponse(true, 'Ticket sélectionné', 200, array(
                'ticketHtml' => $ticketHtml,
                'ticket' => $ticket
            ));
            return false;
            
        } catch (Exception $e) {
            error_log("ajaxSelectTicket Exception: " . $e->getMessage());
            $this->jsonResponse(false, 'Erreur: ' . $e->getMessage(), 500);
            return false;
        }
    }
    
    /**
     * AJAX: Termine un ticket
     */
    private function ajaxFinishTicket() {
        error_log("=== ajaxFinishTicket() START ===");
        
        try {
            $ticketId = (int)gfPostVar('ticket_id', 0);
            error_log("Finishing ticket ID: " . $ticketId);
            
            if ($ticketId <= 0) {
                $this->jsonResponse(false, 'ID ticket invalide', 400);
                return false;
            }
            
            $ticket = $this->getTicketById($ticketId);
            
            if (!$ticket) {
                $this->jsonResponse(false, 'Ticket non trouvé', 404);
                return false;
            }
            
            // Créer les stats
            $stats = $this->createTicketStats($ticket);
            
            // Sauvegarder les stats
            if (!$this->saveTicketStats($stats)) {
                error_log("Failed to save ticket stats");
            }
            
            // Supprimer le ticket
            if (!$this->deleteTicket($ticketId)) {
                $this->jsonResponse(false, 'Erreur lors de la suppression', 500);
                return false;
            }
            
            unset($_SESSION['selected_ticket_id']);
            
            // Récupérer les tickets restants
            $services = gfSessionVar('services_served', array());
            $remainingTickets = $this->getPendingTicketsByServices($services);
            
            $this->jsonResponse(true, 'Ticket traité avec succès', 200, array(
                'remainingCount' => count($remainingTickets)
            ));
            return false;
            
        } catch (Exception $e) {
            error_log("ajaxFinishTicket Exception: " . $e->getMessage());
            $this->jsonResponse(false, 'Erreur: ' . $e->getMessage(), 500);
            return false;
        }
    }
    
    /**
     * AJAX: Retour à la liste des tickets
     */
    private function ajaxBackToList() {
        error_log("=== ajaxBackToList() START ===");
        
        try {
            unset($_SESSION['selected_ticket_id']);
            
            $services = gfSessionVar('services_served', array());
            $tickets = $this->getPendingTicketsByServices($services);
            
            $ticketsHtml = $this->generateTicketsListHtml($tickets);
            
            $this->jsonResponse(true, 'Retour à la liste', 200, array(
                'ticketsHtml' => $ticketsHtml,
                'count' => count($tickets)
            ));
            return false;
            
        } catch (Exception $e) {
            error_log("ajaxBackToList Exception: " . $e->getMessage());
            $this->jsonResponse(false, 'Erreur: ' . $e->getMessage(), 500);
            return false;
        }
    }
    
    /**
     * AJAX: Retour aux services
     */
    private function ajaxBackToServices() {
        error_log("=== ajaxBackToServices() START ===");
        
        try {
            unset($_SESSION['selected_ticket_id']);
            unset($_SESSION['services_served']);
            
            $this->jsonResponse(true, 'Retour aux services', 200);
            return false;
            
        } catch (Exception $e) {
            error_log("ajaxBackToServices Exception: " . $e->getMessage());
            $this->jsonResponse(false, 'Erreur: ' . $e->getMessage(), 500);
            return false;
        }
    }
    
    /**
     * Génère le HTML de la liste des tickets
     */
    private function generateTicketsListHtml($tickets) {
        if (empty($tickets)) {
            return '<div class="ticket-section card">'
                . '<div class="alert alert-info">'
                . '<i class="fas fa-info-circle"></i> Aucun ticket en attente pour les services sélectionnés'
                . '</div>'
                . '</div>';
        }
        
        $ticketsHtml = '';
        foreach ($tickets as $ticket) {
            $ticketId = $ticket['id'];
            $ticketNumber = htmlspecialchars($ticket['ticket_number'], ENT_QUOTES, 'UTF-8');
            $clientName = htmlspecialchars($ticket['name'], ENT_QUOTES, 'UTF-8');
            $clientPhone = htmlspecialchars($ticket['phone'], ENT_QUOTES, 'UTF-8');
            $service = htmlspecialchars($ticket['service'], ENT_QUOTES, 'UTF-8');
            $status = htmlspecialchars($ticket['status'], ENT_QUOTES, 'UTF-8');
            $createdAt = $this->formatTime($ticket['created_at']);
            
            $statusBadge = $this->getStatusBadge($status);
            
            $ticketsHtml .= '<div class="ticket-item" data-ticket-id="' . $ticketId . '">'
                . '<div class="ticket-info">'
                . '<div class="ticket-number-small">' . $ticketNumber . '</div>'
                . '<div class="client-info">'
                . '<div class="client-detail">'
                . '<i class="fas fa-user"></i>'
                . '<span>' . $clientName . '</span>'
                . '</div>'
                . '<div class="client-detail">'
                . '<i class="fas fa-phone"></i>'
                . '<span>' . $clientPhone . '</span>'
                . '</div>'
                . '</div>'
                . '<div class="ticket-meta">'
                . '<span class="service-tag">' . $service . '</span>'
                . $statusBadge
                . '<span class="time-tag">' . $createdAt . '</span>'
                . '</div>'
                . '</div>'
                . '</div>';
        }
        
        return '<div class="ticket-section card">'
            . '<div class="ticket-header">'
            . '<h2><i class="fas fa-list"></i> Sélectionner un ticket à traiter (' . count($tickets) . ' en attente)</h2>'
            . '</div>'
            . '<div class="tickets-list">'
            . $ticketsHtml
            . '</div>'
            . '</div>';
    }
    
    /**
     * Génère le HTML d'affichage du ticket
     */
    private function generateTicketDisplayHtml($ticket) {
        $ticketNumber = htmlspecialchars($ticket['ticket_number'], ENT_QUOTES, 'UTF-8');
        $clientName = htmlspecialchars($ticket['name'], ENT_QUOTES, 'UTF-8');
        $clientPhone = htmlspecialchars($ticket['phone'], ENT_QUOTES, 'UTF-8');
        $service = htmlspecialchars($ticket['service'], ENT_QUOTES, 'UTF-8');
        $status = htmlspecialchars($ticket['status'], ENT_QUOTES, 'UTF-8');
        $createdAt = $this->formatTime($ticket['created_at']);
        
        $statusBadge = $this->getStatusBadge($status);
        
        return '<div class="ticket-section card">'
            . '<div class="ticket-header">'
            . '<h2>Ticket en cours de traitement</h2>'
            . '</div>'
            . '<div class="ticket-display">'
            . '<div class="ticket-number">'
            . '<span>' . $ticketNumber . '</span>'
            . '</div>'
            . '</div>'
            . '<div class="ticket-details">'
            . '<div class="detail-row">'
            . '<span class="detail-label"><i class="fas fa-user"></i> Client:</span>'
            . '<span class="detail-value">' . $clientName . '</span>'
            . '</div>'
            . '<div class="detail-row">'
            . '<span class="detail-label"><i class="fas fa-phone"></i> Téléphone:</span>'
            . '<span class="detail-value">' . $clientPhone . '</span>'
            . '</div>'
            . '<div class="detail-row">'
            . '<span class="detail-label"><i class="fas fa-briefcase"></i> Service:</span>'
            . '<span class="detail-value">' . $service . '</span>'
            . '</div>'
            . '<div class="detail-row">'
            . '<span class="detail-label"><i class="fas fa-info-circle"></i> Statut:</span>'
            . '<span class="detail-value">' . $statusBadge . '</span>'
            . '</div>'
            . '<div class="detail-row">'
            . '<span class="detail-label"><i class="fas fa-clock"></i> Créé à:</span>'
            . '<span class="detail-value">' . $createdAt . '</span>'
            . '</div>'
            . '</div>'
            . '</div>';
    }
    
    /**
     * Envoie une réponse JSON
     */
    private function jsonResponse($success, $message, $code = 200, $data = array()) {
        header('Content-Type: application/json; charset=UTF-8');
        http_response_code($code);
        
        $response = array(
            'success' => $success,
            'message' => $message,
            'code' => $code
        );
        
        if (!empty($data)) {
            $response = array_merge($response, $data);
        }
        
        echo json_encode($response);
        exit;
    }
    
    public function afterPermissionCheck() {
        error_log("=== OperatorPage afterPermissionCheck() START ===");
        
        $this->services_served = gfSessionVar( 'services_served', array() );
        
        // Assurer que services_served est un array
        if ( !is_array( $this->services_served ) ) {
            $this->services_served = array();
        }
        
        // Récupérer le ticket actuellement sélectionné
        if ( isset( $_SESSION['selected_ticket_id'] ) ) {
            $this->selected_ticket_id = (int)$_SESSION['selected_ticket_id'];
            $this->ticket_served = $this->getTicketById( $this->selected_ticket_id );
            error_log("Ticket served from session: " . $this->selected_ticket_id);
        }
        
        error_log("afterPermissionCheck() END");
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

        $operator = $this->getOperator();
        $desk = $this->getDesk();
        
        // Sécuriser services_served
        $servicesServedArray = is_array( $this->services_served ) ? $this->services_served : array();
        $servicesServedText = !empty( $servicesServedArray ) ? implode( ', ', $servicesServedArray ) : 'Aucun';
        
        $messageClass = '';
        if ( $this->message ) {
            if ( strpos($this->message, '❌') !== false || strpos($this->message, 'Erreur') !== false ) {
                $messageClass = 'error';
            } else {
                $messageClass = 'success';
            }
        }
        $pMessage = $this->message ? '<div class="alert alert-' . $messageClass . '"><i class="fas fa-check-circle"></i> ' . $this->message . '</div>' : '';
        
        // Déterminer quoi afficher
        $servicesSelectionBlock = '';
        $ticketsListBlock = '';
        $ticketDisplayBlock = '';
        $finishButtonBlock = '';
        
        if ( empty( $this->services_served ) || !is_array( $this->services_served ) ) {
            // Afficher la sélection des services
            $tableBody = $this->getTableBody();
            $servicesSelectionBlock = $this->getServicesSelectionBlock( $tableBody );
        } else {
            // Services sélectionnés
            if ( !$this->ticket_served ) {
                // Afficher la liste des tickets
                $ticketsListBlock = $this->getTicketsListBlock();
            } else {
                // Un ticket est sélectionné, l'afficher en grand
                $ticketDisplayBlock = $this->getTicketDisplayBlock();
                $finishButtonBlock = $this->getFinishButtonBlock();
            }
        }
        
        $operatorName = $operator->getFullName();
        $operatorCode = $operator->getCode();
        $deskNumber = $desk->getNumber();
        
        return '<div class="layout">'
            . '<div class="operator-header">'
            . '<div class="header-top">'
            . '<div class="logo-section">'
            . '<h1>FastQueue</h1>'
            . '<span class="operator-badge">Opérateur</span>'
            . '</div>'
            . '<div class="header-links">'
            . '<a href="' . $gvPath . '/application/help" class="header-link">'
            . '<i class="fas fa-question-circle"></i> Aide'
            . '</a>'
            . '<a href="' . $gvPath . '/application/logoutPage" class="header-link logout">'
            . '<i class="fas fa-sign-out-alt"></i> Déconnexion'
            . '</a>'
            . '</div>'
            . '</div>'
            . '<div class="operator-info-bar">'
            . '<div class="info-item">'
            . '<i class="fas fa-user-circle"></i>'
            . '<div>'
            . '<span class="info-label">Opérateur</span>'
            . '<span class="info-value">' . htmlspecialchars($operatorName, ENT_QUOTES, 'UTF-8') . '</span>'
            . '</div>'
            . '</div>'
            . '<div class="info-item">'
            . '<i class="fas fa-id-badge"></i>'
            . '<div>'
            . '<span class="info-label">Code</span>'
            . '<span class="info-value">' . htmlspecialchars($operatorCode, ENT_QUOTES, 'UTF-8') . '</span>'
            . '</div>'
            . '</div>'
            . '<div class="info-item">'
            . '<i class="fas fa-desktop"></i>'
            . '<div>'
            . '<span class="info-label">Compteur</span>'
            . '<span class="info-value">' . htmlspecialchars($deskNumber, ENT_QUOTES, 'UTF-8') . '</span>'
            . '</div>'
            . '</div>'
            . '<div class="info-item">'
            . '<i class="fas fa-layer-group"></i>'
            . '<div>'
            . '<span class="info-label">Services servis</span>'
            . '<span class="info-value" id="services-served-display">' . htmlspecialchars($servicesServedText, ENT_QUOTES, 'UTF-8') . '</span>'
            . '</div>'
            . '</div>'
            . '</div>'
            . '</div>'
            . '<main class="operator-main">'
            . '<div class="content-container">'
            . '<div id="main-content" class="main-content">'
            . $pMessage
            . $servicesSelectionBlock
            . $ticketsListBlock
            . $ticketDisplayBlock
            . $finishButtonBlock
            . '</div>'
            . '</div>'
            . '</main>'
            . '</div>';
    }

    /**
     * Bloc de sélection des services
     */
    private function getServicesSelectionBlock( $tableBody ) {
        return '<div class="ticket-section card">'
            . '<div class="ticket-header">'
            . '<h2><i class="fas fa-briefcase"></i> Sélectionner les services à servir</h2>'
            . '</div>'
            . '<div class="domains-grid">'
            . $tableBody
            . '</div>'
            . '</div>'
            . '<div class="controls-section card">'
            . '<button type="button" id="btn-show-tickets" class="btn btn-primary btn-large">'
            . '<i class="fas fa-arrow-right"></i> Afficher les tickets'
            . '</button>'
            . '</div>';
    }

    /**
     * Liste des tickets en attente
     */
    private function getTicketsListBlock() {
        error_log("=== getTicketsListBlock() START ===");
        
        try {
            $this->pending_tickets = $this->getPendingTicketsByServices( $this->services_served );
            error_log("Pending tickets found: " . count($this->pending_tickets));
            
            $pendingCount = count($this->pending_tickets);
            
            if ( $pendingCount === 0 ) {
                return '<div class="ticket-section card">'
                    . '<div class="alert alert-info">'
                    . '<i class="fas fa-info-circle"></i> Aucun ticket en attente pour les services sélectionnés'
                    . '</div>'
                    . '</div>'
                    . '<div class="controls-section card">'
                    . '<button type="button" id="btn-back-services" class="btn btn-secondary btn-large">'
                    . '<i class="fas fa-arrow-left"></i> Retour aux services'
                    . '</button>'
                    . '</div>';
            }
            
            $ticketsHtml = '';
            foreach ( $this->pending_tickets as $ticket ) {
                $ticketId = $ticket['id'];
                $ticketNumber = htmlspecialchars( $ticket['ticket_number'], ENT_QUOTES, 'UTF-8' );
                $clientName = htmlspecialchars( $ticket['name'], ENT_QUOTES, 'UTF-8' );
                $clientPhone = htmlspecialchars( $ticket['phone'], ENT_QUOTES, 'UTF-8' );
                $service = htmlspecialchars( $ticket['service'], ENT_QUOTES, 'UTF-8' );
                $status = htmlspecialchars( $ticket['status'], ENT_QUOTES, 'UTF-8' );
                $createdAt = $this->formatTime( $ticket['created_at'] );
                
                // Récupérer le badge de statut
                $statusBadge = $this->getStatusBadge( $status );
                
                $ticketsHtml .= '<div class="ticket-item" data-ticket-id="' . $ticketId . '">'
                    . '<div class="ticket-info">'
                    . '<div class="ticket-number-small">' . $ticketNumber . '</div>'
                    . '<div class="client-info">'
                    . '<div class="client-detail">'
                    . '<i class="fas fa-user"></i>'
                    . '<span>' . $clientName . '</span>'
                    . '</div>'
                    . '<div class="client-detail">'
                    . '<i class="fas fa-phone"></i>'
                    . '<span>' . $clientPhone . '</span>'
                    . '</div>'
                    . '</div>'
                    . '<div class="ticket-meta">'
                    . '<span class="service-tag">' . $service . '</span>'
                    . $statusBadge
                    . '<span class="time-tag">' . $createdAt . '</span>'
                    . '</div>'
                    . '</div>'
                    . '</div>';
            }
            
            return '<div class="ticket-section card">'
                . '<div class="ticket-header">'
                . '<h2><i class="fas fa-list"></i> Sélectionner un ticket à traiter (' . $pendingCount . ' en attente)</h2>'
                . '</div>'
                . '<div class="tickets-list">'
                . $ticketsHtml
                . '</div>'
                . '</div>'
                . '<div class="controls-section card">'
                . '<div class="button-group">'
                . '<button type="button" id="btn-select-ticket" class="btn btn-primary btn-large">'
                . '<i class="fas fa-hand-paper"></i> Sélectionner le ticket'
                . '</button>'
                . '<button type="button" id="btn-back-services" class="btn btn-secondary">'
                . '<i class="fas fa-arrow-left"></i> Retour aux services'
                . '</button>'
                . '</div>'
                . '</div>';
        } catch ( Exception $e ) {
            error_log("getTicketsListBlock Exception: " . $e->getMessage());
            return '<div class="alert alert-error">'
                . '<i class="fas fa-exclamation-circle"></i> Erreur: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8')
                . '</div>';
        }
    }

    /**
     * Affichage du ticket sélectionné
     */
    private function getTicketDisplayBlock() {
        if ( !$this->ticket_served ) {
            return '';
        }
        
        $ticketNumber = htmlspecialchars( $this->ticket_served['ticket_number'], ENT_QUOTES, 'UTF-8' );
        $clientName = htmlspecialchars( $this->ticket_served['name'], ENT_QUOTES, 'UTF-8' );
        $clientPhone = htmlspecialchars( $this->ticket_served['phone'], ENT_QUOTES, 'UTF-8' );
        $service = htmlspecialchars( $this->ticket_served['service'], ENT_QUOTES, 'UTF-8' );
        $status = htmlspecialchars( $this->ticket_served['status'], ENT_QUOTES, 'UTF-8' );
        $createdAt = $this->formatTime( $this->ticket_served['created_at'] );
        
        // Récupérer le badge de statut
        $statusBadge = $this->getStatusBadge( $status );
        
        return '<div class="ticket-section card">'
            . '<div class="ticket-header">'
            . '<h2>Ticket en cours de traitement</h2>'
            . '</div>'
            . '<div class="ticket-display">'
            . '<div class="ticket-number">'
            . '<span>' . $ticketNumber . '</span>'
            . '</div>'
            . '</div>'
            . '<div class="ticket-details">'
            . '<div class="detail-row">'
            . '<span class="detail-label"><i class="fas fa-user"></i> Client:</span>'
            . '<span class="detail-value">' . $clientName . '</span>'
            . '</div>'
            . '<div class="detail-row">'
            . '<span class="detail-label"><i class="fas fa-phone"></i> Téléphone:</span>'
            . '<span class="detail-value">' . $clientPhone . '</span>'
            . '</div>'
            . '<div class="detail-row">'
            . '<span class="detail-label"><i class="fas fa-briefcase"></i> Service:</span>'
            . '<span class="detail-value">' . $service . '</span>'
            . '</div>'
            . '<div class="detail-row">'
            . '<span class="detail-label"><i class="fas fa-info-circle"></i> Statut:</span>'
            . '<span class="detail-value">' . $statusBadge . '</span>'
            . '</div>'
            . '<div class="detail-row">'
            . '<span class="detail-label"><i class="fas fa-clock"></i> Créé à:</span>'
            . '<span class="detail-value">' . $createdAt . '</span>'
            . '</div>'
            . '</div>'
            . '</div>';
    }

    /**
     * Bouton Terminer
     */
    private function getFinishButtonBlock() {
        return '<div class="controls-section card">'
            . '<div class="button-group">'
            . '<button type="button" id="btn-finish-ticket" class="btn btn-success btn-large">'
            . '<i class="fas fa-check-circle"></i> Terminer ce ticket'
            . '</button>'
            . '<button type="button" id="btn-back-list" class="btn btn-secondary">'
            . '<i class="fas fa-arrow-left"></i> Retour à la liste'
            . '</button>'
            . '</div>'
            . '</div>';
    }

    /**
     * Récupère le badge HTML pour le statut
     */
    private function getStatusBadge( $status ) {
        $status = strtolower( trim( $status ) );
        
        switch ( $status ) {
            case 'standard':
                return '<span class="status-badge status-standard"><i class="fas fa-circle"></i> Standard</span>';
            case 'pregnant':
                return '<span class="status-badge status-pregnant"><i class="fas fa-heart"></i> Femme enceinte</span>';
            case 'disability':
                return '<span class="status-badge status-disability"><i class="fas fa-wheelchair"></i> Handicap</span>';
            case 'waiting':
            default:
                return '<span class="status-badge status-waiting"><i class="fas fa-hourglass-half"></i> En attente</span>';
        }
    }

    /**
     * Formate l'heure pour l'affichage
     */
    private function formatTime( $timestamp ) {
        if ( empty( $timestamp ) ) {
            return 'N/A';
        }
        
        $time = strtotime( $timestamp );
        if ( $time === false ) {
            return htmlspecialchars( $timestamp );
        }
        
        $diff = time() - $time;
        
        if ( $diff < 60 ) {
            return 'À l\'instant';
        } elseif ( $diff < 3600 ) {
            $minutes = floor( $diff / 60 );
            return $minutes . ' min';
        } elseif ( $diff < 86400 ) {
            $hours = floor( $diff / 3600 );
            return $hours . ' h';
        } else {
            return date( 'd/m/Y H:i', $time );
        }
    }

    /**
     * Récupère les services distincts et leurs files d'attente
     */
    private function getTableBody() {
        error_log("=== getTableBody() START ===");
        
        $services = array();
        $queueData = array();
        $tableBody = '';
        
        try {
            $db = $this->getDatabase();
            
            if (!$db) {
                throw new Exception("Impossible de se connecter à la base de données");
            }
            
            // Récupérer les services distincts
            $query = "SELECT DISTINCT service FROM tickets 
                      WHERE service IS NOT NULL AND service != '' 
                      ORDER BY service ASC";
            
            error_log("Query: " . $query);
            
            if ($db instanceof PDO) {
                $result = $db->query($query);
                if (!$result) {
                    throw new Exception("Erreur SQL PDO");
                }
                $services = $result->fetchAll(PDO::FETCH_ASSOC);
            } else {
                $result = $db->query($query);
                if (!$result) {
                    throw new Exception("Erreur SQL MySQLi: " . $db->error);
                }
                while ($row = $result->fetch_assoc()) {
                    if (!empty($row['service'])) {
                        $services[] = $row;
                    }
                }
            }
            
            if (empty($services)) {
                error_log("No services found");
                return '<p style="text-align: center; color: #999; padding: 20px;">Aucun service disponible</p>';
            }
            
            // Récupérer le nombre de tickets par service (statuts en attente)
            $countQuery = "SELECT service, COUNT(*) as cnt FROM tickets 
                           WHERE service IS NOT NULL AND status IN ('waiting', 'standard', 'pregnant', 'disability')
                           GROUP BY service";
            
            error_log("Count Query: " . $countQuery);
            
            if ($db instanceof PDO) {
                $countResult = $db->query($countQuery);
                if ($countResult) {
                    $countRows = $countResult->fetchAll(PDO::FETCH_ASSOC);
                    foreach ($countRows as $row) {
                        $serviceName = trim($row['service']);
                        $queueData[$serviceName] = (int)$row['cnt'];
                    }
                }
            } else {
                $countResult = $db->query($countQuery);
                if ($countResult) {
                    while ($row = $countResult->fetch_assoc()) {
                        $serviceName = trim($row['service']);
                        $queueData[$serviceName] = (int)$row['cnt'];
                    }
                }
            }
            
            // Générer le HTML
            $services_served = is_array($this->services_served) ? $this->services_served : array();
            
            foreach ($services as $service) {
                $serviceName = trim($service['service']);
                $queueLength = isset($queueData[$serviceName]) ? $queueData[$serviceName] : 0;
                $isChecked = in_array($serviceName, $services_served);
                
                $checkedAttr = $isChecked ? ' checked' : '';
                $escapedService = htmlspecialchars($serviceName, ENT_QUOTES, 'UTF-8');
                
                $tableBody .= '<label class="service-item">'
                    . '<input type="checkbox" name="services_served[]" value="' . $escapedService . '" class="service-checkbox"' . $checkedAttr . ' />'
                    . '<span class="service-label">'
                    . $escapedService
                    . ' <span class="queue-count">' . $queueLength . '</span>'
                    . '</span>'
                    . '</label>';
            }
            
            return $tableBody;
            
        } catch (Exception $e) {
            error_log("getTableBody Exception: " . $e->getMessage());
            return '<p style="color: #c33; padding: 20px;">❌ Erreur: ' . htmlspecialchars($e->getMessage()) . '</p>';
        }
    }

    /**
     * Récupère les tickets en attente pour les services sélectionnés
     */
    private function getPendingTicketsByServices( $services ) {
        error_log("=== getPendingTicketsByServices() START ===");
        error_log("Services: " . json_encode($services));
        
        if ( empty( $services ) || !is_array( $services ) ) {
            error_log("No services provided");
            return array();
        }
        
        try {
            $db = $this->getDatabase();
            
            if (!$db) {
                throw new Exception("Impossible de se connecter à la base de données");
            }
            
            $tickets = array();
            
            if ($db instanceof PDO) {
                $placeholders = array_fill(0, count($services), '?');
                $serviceList = implode(",", $placeholders);
                
                $query = "SELECT id, ticket_number, name, phone, service, status, created_at 
                          FROM tickets 
                          WHERE service IN ($serviceList) 
                          AND status IN ('waiting', 'standard', 'pregnant', 'disability')
                          ORDER BY created_at ASC";
                
                error_log("PDO Query: " . $query);
                
                $stmt = $db->prepare($query);
                
                foreach ($services as $key => $service) {
                    $trimmedService = trim($service);
                    $stmt->bindValue($key + 1, $trimmedService, PDO::PARAM_STR);
                }
                
                $stmt->execute();
                $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
            } else {
                $escapedServices = array();
                foreach ($services as $service) {
                    $trimmed = trim($service);
                    $escaped = $db->real_escape_string($trimmed);
                    $escapedServices[] = "'" . $escaped . "'";
                }
                
                $serviceList = implode(",", $escapedServices);
                
                $query = "SELECT id, ticket_number, name, phone, service, status, created_at 
                          FROM tickets 
                          WHERE service IN ($serviceList) 
                          AND status IN ('waiting', 'standard', 'pregnant', 'disability')
                          ORDER BY created_at ASC";
                
                error_log("MySQLi Query: " . $query);
                
                $result = $db->query($query);
                
                if (!$result) {
                    throw new Exception("Erreur SQL: " . $db->error);
                }
                
                while ($row = $result->fetch_assoc()) {
                    $tickets[] = $row;
                }
            }
            
            error_log("Found " . count($tickets) . " tickets");
            return $tickets;
            
        } catch ( Exception $e ) {
            error_log("getPendingTicketsByServices Exception: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Récupère un ticket par ID
     */
    private function getTicketById( $ticket_id ) {
        error_log("=== getTicketById($ticket_id) START ===");
        
        if ( $ticket_id <= 0 ) {
            error_log("Invalid ticket ID");
            return null;
        }
        
        try {
            $db = $this->getDatabase();
            
            if (!$db) {
                throw new Exception("Impossible de se connecter à la base de données");
            }
            
            if ($db instanceof PDO) {
                $query = "SELECT * FROM tickets WHERE id = ?";
                $stmt = $db->prepare($query);
                $stmt->bindValue(1, $ticket_id, PDO::PARAM_INT);
                $stmt->execute();
                $ticket = $stmt->fetch(PDO::FETCH_ASSOC);
            } else {
                $query = "SELECT * FROM tickets WHERE id = " . (int)$ticket_id;
                $result = $db->query($query);
                
                if (!$result) {
                    throw new Exception("Erreur SQL: " . $db->error);
                }
                
                $ticket = $result->fetch_assoc();
            }
            
            error_log("Ticket found: " . json_encode($ticket));
            return $ticket;
            
        } catch ( Exception $e ) {
            error_log("getTicketById Exception: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Supprime un ticket
     */
    private function deleteTicket( $ticket_id ) {
        error_log("=== deleteTicket($ticket_id) START ===");
        
        try {
            $db = $this->getDatabase();
            
            if (!$db) {
                throw new Exception("Impossible de se connecter à la base de données");
            }
            
            if ($db instanceof PDO) {
                $query = "DELETE FROM tickets WHERE id = ?";
                $stmt = $db->prepare($query);
                $stmt->bindValue(1, $ticket_id, PDO::PARAM_INT);
                return $stmt->execute();
            } else {
                $query = "DELETE FROM tickets WHERE id = " . (int)$ticket_id;
                return $db->query($query);
            }
            
        } catch ( Exception $e ) {
            error_log("deleteTicket Exception: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Crée les stats du ticket
     */
    private function createTicketStats( $ticket ) {
        error_log("=== createTicketStats() START ===");
        
        $stats = array(
            'ticket_id' => $ticket['id'],
            'ticket_number' => $ticket['ticket_number'],
            'operator_code' => $this->getOperator()->getCode(),
            'desk_number' => $this->getDesk()->getNumber(),
            'service' => $ticket['service'],
            'status' => $ticket['status'],
            'client_name' => $ticket['name'],
            'created_at' => $ticket['created_at'],
            'served_at' => date('Y-m-d H:i:s'),
            'wait_time' => strtotime(date('Y-m-d H:i:s')) - strtotime($ticket['created_at'])
        );
        
        error_log("Stats created: " . json_encode($stats));
        return $stats;
    }

    /**
     * Sauvegarde les stats du ticket
     */
    private function saveTicketStats( $stats ) {
        error_log("=== saveTicketStats() START ===");
        
        try {
            $db = $this->getDatabase();
            
            if (!$db) {
                throw new Exception("Impossible de se connecter à la base de données");
            }
            
            // Vérifier si la table ticket_stats existe
            if ($db instanceof PDO) {
                // PDO
                try {
                    $query = "INSERT INTO ticket_stats (ticket_id, ticket_number, operator_code, desk_number, service, status, client_name, created_at, served_at, wait_time) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    
                    $stmt = $db->prepare($query);
                    $stmt->bindValue(1, $stats['ticket_id'], PDO::PARAM_INT);
                    $stmt->bindValue(2, $stats['ticket_number'], PDO::PARAM_STR);
                    $stmt->bindValue(3, $stats['operator_code'], PDO::PARAM_STR);
                    $stmt->bindValue(4, $stats['desk_number'], PDO::PARAM_INT);
                    $stmt->bindValue(5, $stats['service'], PDO::PARAM_STR);
                    $stmt->bindValue(6, $stats['status'], PDO::PARAM_STR);
                    $stmt->bindValue(7, $stats['client_name'], PDO::PARAM_STR);
                    $stmt->bindValue(8, $stats['created_at'], PDO::PARAM_STR);
                    $stmt->bindValue(9, $stats['served_at'], PDO::PARAM_STR);
                    $stmt->bindValue(10, $stats['wait_time'], PDO::PARAM_INT);
                    
                    return $stmt->execute();
                } catch ( Exception $e ) {
                    error_log("Table ticket_stats might not exist: " . $e->getMessage());
                    return true; // Continuer même si la table n'existe pas
                }
            } else {
                // MySQLi
                try {
                    $query = "INSERT INTO ticket_stats (ticket_id, ticket_number, operator_code, desk_number, service, status, client_name, created_at, served_at, wait_time) 
                              VALUES (" . (int)$stats['ticket_id'] . ", '" . $db->real_escape_string($stats['ticket_number']) . "', '" . $db->real_escape_string($stats['operator_code']) . "', " . (int)$stats['desk_number'] . ", '" . $db->real_escape_string($stats['service']) . "', '" . $db->real_escape_string($stats['status']) . "', '" . $db->real_escape_string($stats['client_name']) . "', '" . $db->real_escape_string($stats['created_at']) . "', '" . $db->real_escape_string($stats['served_at']) . "', " . (int)$stats['wait_time'] . ")";
                    
                    return $db->query($query);
                } catch ( Exception $e ) {
                    error_log("Table ticket_stats might not exist: " . $e->getMessage());
                    return true; // Continuer même si la table n'existe pas
                }
            }
            
        } catch ( Exception $e ) {
            error_log("saveTicketStats Exception: " . $e->getMessage());
            return false;
        }
    }

    private function getOperator() {
        if ( !$this->operator ) {
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

    /**
     * Récupère la connexion à la base de données
     */
    private function getDatabase() {
        $db = null;
        
        // Méthode 1: Variable globale standard
        if (isset($GLOBALS['gvSQLDatabase']) && $GLOBALS['gvSQLDatabase']) {
            error_log("Database found via gvSQLDatabase");
            return $GLOBALS['gvSQLDatabase'];
        }
        
        // Méthode 2: Variable globale alternative
        if (isset($GLOBALS['mysqli']) && $GLOBALS['mysqli']) {
            error_log("Database found via mysqli");
            return $GLOBALS['mysqli'];
        }
        
        // Méthode 3: Fonction
        if (function_exists('getDatabaseConnection')) {
            $db = getDatabaseConnection();
            error_log("Database found via getDatabaseConnection function");
            return $db;
        }
        
        // Méthode 4: Classe Database
        if (class_exists('Database')) {
            if (method_exists('Database', 'getInstance')) {
                $db = Database::getInstance();
                error_log("Database found via Database::getInstance");
                return $db;
            }
            if (method_exists('Database', 'getConnection')) {
                $db = Database::getConnection();
                error_log("Database found via Database::getConnection");
                return $db;
            }
        }
        
        error_log("No database connection found");
        return null;
    }

    private function getDesignCSS() {
        return '<!DOCTYPE html>'
            . '<html lang="fr">'
            . '<head>'
            . '<meta charset="UTF-8">'
            . '<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">'
            . '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">'
            . '<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">'
            . '<style>'
            . '* { margin: 0; padding: 0; box-sizing: border-box; }'
            . 'body { font-family: "Inter", -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; background: linear-gradient(135deg, #f5f7fb 0%, #eef2f9 100%); color: #1a1a2e; overflow-x: hidden; }'
            . '.layout { display: flex; flex-direction: column; min-height: 100vh; }'
            . '.operator-header { background: white; border-bottom: 2px solid #f0f0f0; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }'
            . '.header-top { display: flex; align-items: center; justify-content: space-between; padding: 20px 40px; border-bottom: 1px solid #f0f0f0; }'
            . '.logo-section { display: flex; align-items: center; gap: 16px; }'
            . '.logo-section h1 { font-size: 24px; font-weight: 800; color: #1a1a2e; }'
            . '.operator-badge { background: linear-gradient(135deg, #6C63FF, #8B82FF); color: white; padding: 6px 16px; border-radius: 20px; font-size: 12px; font-weight: 700; }'
            . '.header-links { display: flex; align-items: center; gap: 20px; }'
            . '.header-link { display: flex; align-items: center; gap: 8px; color: #666; text-decoration: none; font-weight: 500; transition: all 0.3s ease; padding: 8px 12px; border-radius: 8px; }'
            . '.header-link:hover { background: #f0f0f0; color: #6C63FF; }'
            . '.header-link.logout { color: #ff6b6b; }'
            . '.header-link.logout:hover { background: rgba(255, 107, 107, 0.1); }'
            . '.operator-info-bar { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 24px; padding: 24px 40px; background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%); }'
            . '.info-item { display: flex; align-items: center; gap: 14px; }'
            . '.info-item i { font-size: 24px; color: #6C63FF; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; background: rgba(108, 99, 255, 0.1); border-radius: 12px; }'
            . '.info-label { display: block; font-size: 12px; color: #999; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600; margin-bottom: 4px; }'
            . '.info-value { display: block; font-size: 16px; font-weight: 700; color: #1a1a2e; }'
            . '.operator-main { flex: 1; padding: 40px; }'
            . '.content-container { max-width: 1200px; margin: 0 auto; width: 100%; }'
            . '.main-content { display: grid; gap: 30px; }'
            . '.card { background: white; border-radius: 18px; padding: 30px; box-shadow: 0 2px 12px rgba(0,0,0,0.06); border: 1px solid rgba(0,0,0,0.03); transition: all 0.3s ease; }'
            . '.card:hover { box-shadow: 0 8px 24px rgba(0,0,0,0.1); }'
            . '.card h2, .card h3 { font-size: 18px; font-weight: 700; color: #1a1a2e; margin-bottom: 20px; display: flex; align-items: center; gap: 12px; }'
            . '.card h2 i, .card h3 i { color: #6C63FF; }'
            . '.ticket-header { margin-bottom: 24px; padding-bottom: 16px; border-bottom: 2px solid #f0f0f0; }'
            . '.ticket-header h2 { font-size: 22px; font-weight: 700; color: #1a1a2e; margin-bottom: 0; display: flex; align-items: center; gap: 12px; }'
            . '.ticket-display { display: flex; justify-content: center; align-items: center; min-height: 280px; background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%); border-radius: 16px; margin-bottom: 20px; }'
            . '.ticket-number { text-align: center; }'
            . '.ticket-number span { display: block; font-size: 72px; font-weight: 800; background: linear-gradient(135deg, #6C63FF, #8B82FF); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; line-height: 1; margin-bottom: 12px; }'
            . '.ticket-details { background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%); border-radius: 12px; padding: 20px; gap: 16px; display: flex; flex-direction: column; }'
            . '.detail-row { display: flex; align-items: center; gap: 12px; padding: 12px; background: white; border-radius: 8px; border-left: 4px solid #6C63FF; }'
            . '.detail-label { display: flex; align-items: center; gap: 8px; font-weight: 600; color: #666; min-width: 100px; }'
            . '.detail-label i { color: #6C63FF; font-size: 16px; }'
            . '.detail-value { font-size: 16px; font-weight: 700; color: #1a1a2e; }'
            . '.alert { padding: 14px 16px; border-radius: 12px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; font-weight: 500; font-size: 14px; }'
            . '.alert i { font-size: 18px; flex-shrink: 0; }'
            . '.alert.alert-error { background: #fff0f0; color: #c0392b; border-left: 4px solid #e74c3c; }'
            . '.alert.alert-info { background: #f0f8ff; color: #0066cc; border-left: 4px solid #00a8ff; }'
            . '.alert.alert-success { background: #f0fff0; color: #27ae60; border-left: 4px solid #2ecc71; }'
            . '.controls-section { background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%); }'
            . '.button-group { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; }'
            . '.btn { padding: 12px 24px; border-radius: 12px; font-weight: 600; font-size: 14px; cursor: pointer; transition: all 0.3s ease; border: none; font-family: inherit; display: inline-flex; align-items: center; justify-content: center; gap: 8px; text-decoration: none; }'
            . '.btn-primary { background: linear-gradient(135deg, #6C63FF, #8B82FF); color: white; }'
            . '.btn-primary:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(108,99,255,0.4); }'
            . '.btn-secondary { background: #f0f0f0; color: #666; }'
            . '.btn-secondary:hover { background: #e0e0e0; color: #333; }'
            . '.btn-success { background: linear-gradient(135deg, #27ae60, #2ecc71); color: white; }'
            . '.btn-success:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(46,204,113,0.4); }'
            . '.btn-large { padding: 16px 32px; font-size: 16px; min-height: 56px; }'
            . '.btn:disabled { opacity: 0.5; cursor: not-allowed; }'
            . '.domains-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 16px; }'
            . '.service-item { display: flex; align-items: center; gap: 12px; padding: 16px; background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%); border: 2px solid #e0e0e0; border-radius: 12px; cursor: pointer; transition: all 0.3s ease; }'
            . '.service-item:hover { background: #f8f9fa; border-color: #6C63FF; box-shadow: 0 4px 12px rgba(108,99,255,0.1); }'
            . '.service-checkbox { width: 18px; height: 18px; cursor: pointer; accent-color: #6C63FF; }'
            . '.service-checkbox:checked + .service-label { color: #6C63FF; font-weight: 600; }'
            . '.service-label { flex: 1; font-weight: 500; color: #1a1a2e; cursor: pointer; }'
            . '.queue-count { display: inline-block; background: linear-gradient(135deg, #6C63FF, #8B82FF); color: white; padding: 2px 8px; border-radius: 12px; font-size: 12px; font-weight: 700; margin-left: 4px; }'
            . '.tickets-list { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 16px; margin-bottom: 20px; }'
            . '.ticket-item { display: grid; align-items: start; gap: 12px; padding: 16px; background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%); border: 2px solid #e0e0e0; border-radius: 12px; cursor: pointer; transition: all 0.3s ease; }'
            . '.ticket-item:hover { background: #f8f9fa; border-color: #6C63FF; box-shadow: 0 4px 12px rgba(108,99,255,0.1); }'
            . '.ticket-item.selected { border-color: #6C63FF; background: #f8f9fa; }'
            . '.ticket-info { display: flex; flex-direction: column; gap: 12px; }'
            . '.ticket-number-small { font-size: 24px; font-weight: 800; background: linear-gradient(135deg, #6C63FF, #8B82FF); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }'
            . '.client-info { display: flex; flex-direction: column; gap: 8px; }'
            . '.client-detail { display: flex; align-items: center; gap: 8px; font-size: 13px; color: #1a1a2e; }'
            . '.client-detail i { color: #6C63FF; font-size: 14px; }'
            . '.ticket-meta { display: flex; align-items: center; justify-content: space-between; gap: 8px; padding-top: 8px; border-top: 1px solid #e0e0e0; flex-wrap: wrap; }'
            . '.service-tag { display: inline-block; background: linear-gradient(135deg, #6C63FF, #8B82FF); color: white; padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.3px; }'
            . '.time-tag { display: inline-block; background: #f0f0f0; color: #666; padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: 600; }'
            . '.status-badge { display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; border-radius: 12px; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.3px; }'
            . '.status-waiting { background: #fff3cd; color: #856404; }'
            . '.status-standard { background: #d1ecf1; color: #0c5460; }'
            . '.status-pregnant { background: #f8d7da; color: #721c24; }'
            . '.status-disability { background: #e2e3e5; color: #383d41; }'
            . '.status-badge i { font-size: 12px; }'
            . '.loading { display: inline-block; width: 20px; height: 20px; border: 3px solid #f3f3f3; border-top: 3px solid #6C63FF; border-radius: 50%; animation: spin 1s linear infinite; }'
            . '@keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }'
            . '@media (max-width: 1024px) { .header-top { padding: 16px 24px; } .operator-info-bar { padding: 20px 24px; gap: 16px; } .operator-main { padding: 24px; } }'
            . '@media (max-width: 768px) { .header-top { flex-direction: column; gap: 16px; text-align: center; } .logo-section { justify-content: center; } .header-links { justify-content: center; width: 100%; } .operator-info-bar { grid-template-columns: 1fr; gap: 12px; } .operator-main { padding: 16px; } .card { padding: 20px; } .ticket-display { min-height: 200px; } .ticket-number span { font-size: 48px; } .domains-grid { grid-template-columns: 1fr; } .tickets-list { grid-template-columns: 1fr; } .button-group { grid-template-columns: 1fr; } .btn-large { width: 100%; } }'
            . '@media (max-width: 480px) { .header-top { padding: 12px 16px; } .logo-section h1 { font-size: 20px; } .operator-info-bar { padding: 16px; gap: 12px; } .info-item { gap: 10px; } .info-item i { width: 36px; height: 36px; font-size: 18px; } .info-label { font-size: 10px; } .info-value { font-size: 14px; } .operator-main { padding: 12px; } .card { padding: 16px; margin-bottom: 16px; } .card h2, .card h3 { font-size: 16px; margin-bottom: 16px; } .ticket-number span { font-size: 36px; } .domains-grid { gap: 10px; } .service-item { padding: 12px; font-size: 13px; } .btn { padding: 12px 16px; font-size: 13px; } .btn-large { padding: 14px 16px; min-height: 48px; } .tickets-list { grid-template-columns: 1fr; gap: 10px; } .ticket-item { padding: 12px; } .ticket-meta { flex-direction: column; align-items: flex-start; gap: 6px; } .status-badge { padding: 4px 8px; font-size: 10px; } }'
            . '@media print { body { background: white; } .operator-header { display: none; } .operator-main { padding: 0; } .card { box-shadow: none; border: 1px solid #ddd; page-break-inside: avoid; } }'
            . '::-webkit-scrollbar { width: 8px; height: 8px; }'
            . '::-webkit-scrollbar-track { background: #f0f0f0; }'
            . '::-webkit-scrollbar-thumb { background: #c0c0c0; border-radius: 4px; }'
            . '::-webkit-scrollbar-thumb:hover { background: #a0a0a0; }'
            . '</style>'
            . '</head>'
            . '<body>'
            . '<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>'
            . '<script>'
            . 'var operatorPageScript = (function() {'
            . '  var selectedTicketId = null;'
            . '  var selectedServices = [];'
            . ''
            . '  function showLoading() {'
            . '    $(\'#main-content\').html(\'<div style="text-align: center; padding: 40px;"><div class="loading"></div><p style="margin-top: 20px; color: #666;">Chargement...</p></div>\');'
            . '  }'
            . ''
            . '  function showMessage(message, type) {'
            . '    var icon = type === \'success\' ? \'fa-check-circle\' : \'fa-exclamation-circle\';'
            . '    var alertClass = type === \'success\' ? \'alert-success\' : \'alert-error\';'
            . '    var alert = \'<div class="alert \' + alertClass + \'"><i class="fas \' + icon + \'"></i> \' + message + \'</div>\';'
            . '    $(\'#main-content\').prepend(alert);'
            . '    setTimeout(function() { $(\'#main-content > .alert\').fadeOut(500, function() { $(this).remove(); }); }, 3000);'
            . '  }'
            . ''
            . '  function updateServicesDisplay() {'
            . '    var text = selectedServices.length > 0 ? selectedServices.join(\', \') : \'Aucun\';'
            . '    $(\'#services-served-display\').text(text);'
            . '  }'
            . ''
            . '  function attachTicketListeners() {'
            . '    $(\'.ticket-item\').off(\'click\').on(\'click\', function(e) {'
            . '      e.preventDefault();'
            . '      $(\'.ticket-item\').removeClass(\'selected\');'
            . '      $(this).addClass(\'selected\');'
            . '      selectedTicketId = $(this).data(\'ticket-id\');'
            . '    });'
            . '  }'
            . ''
            . '  function attachServiceListeners() {'
            . '    $(\'#btn-show-tickets\').off(\'click\').on(\'click\', function(e) {'
            . '      e.preventDefault();'
            . '      selectedServices = [];'
            . '      $(\'.service-checkbox:checked\').each(function() {'
            . '        selectedServices.push($(this).val());'
            . '      });'
            . ''
            . '      if (selectedServices.length === 0) {'
            . '        showMessage(\'Sélectionnez au moins un service\', \'error\');'
            . '        return;'
            . '      }'
            . ''
            . '      updateServicesDisplay();'
            . '      showLoading();'
            . '      getTickets();'
            . '    });'
            . '  }'
            . ''
            . '  function attachListControls() {'
            . '    $(\'#btn-select-ticket\').off(\'click\').on(\'click\', function(e) {'
            . '      e.preventDefault();'
            . '      if (!selectedTicketId) {'
            . '        showMessage(\'Sélectionnez un ticket\', \'error\');'
            . '        return;'
            . '      }'
            . '      selectTicket(selectedTicketId);'
            . '    });'
            . ''
            . '    $(\'#btn-back-services\').off(\'click\').on(\'click\', function(e) {'
            . '      e.preventDefault();'
            . '      backToServices();'
            . '    });'
            . '  }'
            . ''
            . '  function attachTicketControls() {'
            . '    $(\'#btn-finish-ticket\').off(\'click\').on(\'click\', function(e) {'
            . '      e.preventDefault();'
            . '      finishTicket(selectedTicketId);'
            . '    });'
            . ''
            . '    $(\'#btn-back-list\').off(\'click\').on(\'click\', function(e) {'
            . '      e.preventDefault();'
            . '      backToList();'
            . '    });'
            . '  }'
            . ''
            . '  function getTickets() {'
            . '    $.ajax({'
            . '      url: location.pathname,'
            . '      method: \'POST\','
            . '      dataType: \'json\','
            . '      headers: { \'X-Requested-With\': \'XMLHttpRequest\' },'
            . '      data: {'
            . '        action: \'get_tickets\','
            . '        services: selectedServices'
            . '      },'
            . '      success: function(response) {'
            . '        if (response.success) {'
            . '          var html = response.ticketsHtml;'
            . '          html += \'<div class="controls-section card"><div class="button-group">\';'
            . '          html += \'<button type="button" id="btn-select-ticket" class="btn btn-primary btn-large"><i class="fas fa-hand-paper"></i> Sélectionner le ticket</button>\';'
            . '          html += \'<button type="button" id="btn-back-services" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Retour aux services</button>\';'
            . '          html += \'</div></div>\';'
            . '          $(\'#main-content\').html(html);'
            . '          attachTicketListeners();'
            . '          attachListControls();'
            . '        } else {'
            . '          showMessage(\'Erreur: \' + response.message, \'error\');'
            . '        }'
            . '      },'
            . '      error: function() {'
            . '        showMessage(\'Erreur de connexion\', \'error\');'
            . '      }'
            . '    });'
            . '  }'
            . ''
            . '  function selectTicket(ticketId) {'
            . '    $.ajax({'
            . '      url: location.pathname,'
            . '      method: \'POST\','
            . '      dataType: \'json\','
            . '      headers: { \'X-Requested-With\': \'XMLHttpRequest\' },'
            . '      data: {'
            . '        action: \'select_ticket\','
            . '        ticket_id: ticketId'
            . '      },'
            . '      success: function(response) {'
            . '        if (response.success) {'
            . '          var html = response.ticketHtml;'
            . '          html += \'<div class="controls-section card"><div class="button-group">\';'
            . '          html += \'<button type="button" id="btn-finish-ticket" class="btn btn-success btn-large"><i class="fas fa-check-circle"></i> Terminer ce ticket</button>\';'
            . '          html += \'<button type="button" id="btn-back-list" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Retour à la liste</button>\';'
            . '          html += \'</div></div>\';'
            . '          $(\'#main-content\').html(html);'
            . '          attachTicketControls();'
            . '        } else {'
            . '          showMessage(\'Erreur: \' + response.message, \'error\');'
            . '        }'
            . '      },'
            . '      error: function() {'
            . '        showMessage(\'Erreur de connexion\', \'error\');'
            . '      }'
            . '    });'
            . '  }'
            . ''
            . '  function finishTicket(ticketId) {'
            . '    showLoading();'
            . '    $.ajax({'
            . '      url: location.pathname,'
            . '      method: \'POST\','
            . '      dataType: \'json\','
            . '      headers: { \'X-Requested-With\': \'XMLHttpRequest\' },'
            . '      data: {'
            . '        action: \'finish_ticket\','
            . '        ticket_id: ticketId'
            . '      },'
            . '      success: function(response) {'
            . '        showMessage(\'✅ Ticket traité avec succès!\', \'success\');'
            . '        setTimeout(function() { getTickets(); }, 1000);'
            . '      },'
            . '      error: function() {'
            . '        showMessage(\'Erreur lors de la suppression\', \'error\');'
            . '      }'
            . '    });'
            . '  }'
            . ''
            . '  function backToList() {'
            . '    $.ajax({'
            . '      url: location.pathname,'
            . '      method: \'POST\','
            . '      dataType: \'json\','
            . '      headers: { \'X-Requested-With\': \'XMLHttpRequest\' },'
            . '      data: {'
            . '        action: \'back_to_list\''
            . '      },'
            . '      success: function(response) {'
            . '        if (response.success) {'
            . '          selectedTicketId = null;'
            . '          var html = response.ticketsHtml;'
            . '          html += \'<div class="controls-section card"><div class="button-group">\';'
            . '          html += \'<button type="button" id="btn-select-ticket" class="btn btn-primary btn-large"><i class="fas fa-hand-paper"></i> Sélectionner le ticket</button>\';'
            . '          html += \'<button type="button" id="btn-back-services" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Retour aux services</button>\';'
            . '          html += \'</div></div>\';'
            . '          $(\'#main-content\').html(html);'
            . '          attachTicketListeners();'
            . '          attachListControls();'
            . '        }'
            . '      }'
            . '    });'
            . '  }'
            . ''
            . '  function backToServices() {'
            . '    $.ajax({'
            . '      url: location.pathname,'
            . '      method: \'POST\','
            . '      dataType: \'json\','
            . '      headers: { \'X-Requested-With\': \'XMLHttpRequest\' },'
            . '      data: {'
            . '        action: \'back_to_services\''
            . '      },'
            . '      success: function(response) {'
            . '        selectedServices = [];'
            . '        updateServicesDisplay();'
            . '        location.reload();'
            . '      }'
            . '    });'
            . '  }'
            . ''
            . '  $(document).ready(function() {'
            . '    attachServiceListeners();'
            . '  });'
            . '})();'
            . '</script>'
            . '</body>'
            . '</html>';
    }
}
