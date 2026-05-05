<?php

/**
 * Description of OperatorPage - Version avec Chronomètre 10 secondes
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
    private $selected_statuses = array();
    
    public function canUse( $userLevel ) {
        return $userLevel == Page::OPERATOR_USER;
    }

    public function execute() {
        error_log("=== OperatorPage execute() START ===");
        
        $this->is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
        
        if ($this->is_ajax) {
            return $this->handleAjaxRequest();
        }
        
        $this->services_served = gfPostVar( 'services_served', array() );
        
        if ( !is_array( $this->services_served ) ) {
            $this->services_served = array();
        }
        
        $_SESSION['services_served'] = $this->services_served;
        
        if ( isset( $_POST['select_ticket'] ) ) {
            $this->selected_ticket_id = (int)gfPostVar( 'ticket_id', 0 );
            
            if ( $this->selected_ticket_id > 0 ) {
                $_SESSION['selected_ticket_id'] = $this->selected_ticket_id;
                $_SESSION['timer_start'] = time();
                $_SESSION['timer_paused'] = false;
                $_SESSION['timer_remaining'] = 60;
                $this->ticket_served = $this->getTicketById( $this->selected_ticket_id );
                
                // 🆕 SYNCHRONIZE WITH WAITING ROOM DISPLAY
                if ( $this->ticket_served ) {
                    $this->syncTicketToWaitingRoom( $this->ticket_served );
                }
                
                return true;
            }
        }
        
        if ( isset( $_POST['pause_timer'] ) ) {
            if ( isset( $_SESSION['timer_start'] ) && !$_SESSION['timer_paused'] ) {
                $elapsed = time() - $_SESSION['timer_start'];
                $_SESSION['timer_remaining'] = max(0, 10 - $elapsed);
                $_SESSION['timer_paused'] = true;
                $this->message = "⏸️ Chronomètre en pause - Client présent";
                return true;
            }
        }
        
        if ( isset( $_POST['resume_timer'] ) ) {
            if ( isset( $_SESSION['timer_start'] ) && $_SESSION['timer_paused'] ) {
                $_SESSION['timer_start'] = time();
                $_SESSION['timer_paused'] = false;
                $this->message = "▶️ Chronomètre repris";
                return true;
            }
        }
        
        if ( isset( $_POST['client_absent'] ) ) {
            if ( $this->selected_ticket_id > 0 ) {
                $this->deleteTicket( $this->selected_ticket_id );
                $this->message = "❌ Client absent - Ticket annulé";
                $this->selected_ticket_id = null;
                $this->ticket_served = null;
                unset( $_SESSION['selected_ticket_id'] );
                unset( $_SESSION['timer_start'] );
                unset( $_SESSION['timer_paused'] );
                unset( $_SESSION['timer_remaining'] );
                return true;
            }
        }
        
        if ( isset( $_POST['finish'] ) ) {
            if ( $this->selected_ticket_id > 0 ) {
                try {
                    $ticket = $this->getTicketById( $this->selected_ticket_id );
                    if ( $ticket ) {
                        $stats = $this->createTicketStats( $ticket );
                        $this->saveTicketStats( $stats );
                        $this->deleteTicket( $this->selected_ticket_id );
                        $this->message = "✅ Ticket traité avec succès";
                        $this->selected_ticket_id = null;
                        $this->ticket_served = null;
                        unset( $_SESSION['selected_ticket_id'] );
                        unset( $_SESSION['timer_start'] );
                        unset( $_SESSION['timer_paused'] );
                        unset( $_SESSION['timer_remaining'] );
                        return true;
                    }
                } catch ( Exception $e ) {
                    $this->message = "❌ Erreur: " . $e->getMessage();
                    return true;
                }
            }
        }
        
        if ( isset( $_POST['back_to_list'] ) ) {
            $this->selected_ticket_id = null;
            $this->ticket_served = null;
            unset( $_SESSION['selected_ticket_id'] );
            unset( $_SESSION['timer_start'] );
            unset( $_SESSION['timer_paused'] );
            unset( $_SESSION['timer_remaining'] );
            return true;
        }
        
        if ( isset( $_POST['back_to_services'] ) ) {
            $this->ticket_served = null;
            $this->selected_ticket_id = null;
            $this->services_served = array();
            unset( $_SESSION['selected_ticket_id'] );
            unset( $_SESSION['services_served'] );
            unset( $_SESSION['selected_statuses'] );
            unset( $_SESSION['timer_start'] );
            unset( $_SESSION['timer_paused'] );
            unset( $_SESSION['timer_remaining'] );
            return true;
        }

        return true;
    }
    
    private function handleAjaxRequest() {
        $action = gfPostVar('action', '');
        
        switch ($action) {
            case 'get_tickets': return $this->ajaxGetTickets();
            case 'select_ticket': return $this->ajaxSelectTicket();
            case 'finish_ticket': return $this->ajaxFinishTicket();
            case 'back_to_list': return $this->ajaxBackToList();
            case 'back_to_services': return $this->ajaxBackToServices();
            case 'filter_tickets': return $this->ajaxFilterTickets();
            case 'check_new_tickets': return $this->ajaxCheckNewTickets();
            case 'get_timer_status': return $this->ajaxGetTimerStatus();
            case 'pause_timer': return $this->ajaxPauseTimer();
            case 'resume_timer': return $this->ajaxResumeTimer();
            case 'client_absent': return $this->ajaxClientAbsent();
            default: $this->jsonResponse(false, 'Action inconnue', 400); return false;
        }
    }
    
    private function ajaxGetTimerStatus() {
        $timer_start = isset($_SESSION['timer_start']) ? (int)$_SESSION['timer_start'] : null;
        $timer_paused = isset($_SESSION['timer_paused']) ? $_SESSION['timer_paused'] : false;
        $timer_remaining = isset($_SESSION['timer_remaining']) ? (int)$_SESSION['timer_remaining'] : 60;
        
        $remaining = 10;
        $expired = false;
        
        if ($timer_start && !$timer_paused) {
            $elapsed = time() - $timer_start;
            $remaining = max(0, $timer_remaining - $elapsed);
            $expired = ($remaining <= 0);
        } else if ($timer_paused) {
            $remaining = $timer_remaining;
        }
        
        $this->jsonResponse(true, 'Timer status', 200, array(
            'remaining' => $remaining, 'expired' => $expired, 'paused' => $timer_paused
        ));
        return false;
    }
    
    private function ajaxPauseTimer() {
        if (isset($_SESSION['timer_start']) && !$_SESSION['timer_paused']) {
            $elapsed = time() - $_SESSION['timer_start'];
            $_SESSION['timer_remaining'] = max(0, 60 - $elapsed);

            $_SESSION['timer_paused'] = true;
            $this->jsonResponse(true, 'Chronomètre en pause', 200, array('remaining' => $_SESSION['timer_remaining']));
        } else {
            $this->jsonResponse(false, 'Impossible de mettre en pause', 400);
        }
        return false;
    }
    
    private function ajaxResumeTimer() {
        if (isset($_SESSION['timer_start']) && $_SESSION['timer_paused']) {
            $_SESSION['timer_start'] = time();
            $_SESSION['timer_paused'] = false;
            $this->jsonResponse(true, 'Chronomètre repris', 200, array('remaining' => $_SESSION['timer_remaining']));
        } else {
            $this->jsonResponse(false, 'Impossible de reprendre', 400);
        }
        return false;
    }
    
    private function ajaxClientAbsent() {
        $ticketId = (int)gfPostVar('ticket_id', 0);
        if ($ticketId > 0) {
            $this->deleteTicket($ticketId);
            unset($_SESSION['selected_ticket_id'], $_SESSION['timer_start'], $_SESSION['timer_paused'], $_SESSION['timer_remaining']);
            $this->jsonResponse(true, 'Client absent - Ticket annulé', 200);
        } else {
            $this->jsonResponse(false, 'ID ticket invalide', 400);
        }
        return false;
    }
    
    private function ajaxCheckNewTickets() {
        try {
            $lastCheck = (int)gfPostVar('lastCheck', 0);
            $services = gfSessionVar('services_served', array());
            if (empty($services)) {
                $this->jsonResponse(true, 'Pas de services', 200, array('newTickets' => array()));
                return false;
            }
            
            $db = $this->getDatabase();
            if (!$db) throw new Exception("Impossible de se connecter à la base de données");
            
            $newTickets = array();
            if ($db instanceof PDO) {
                $placeholders = array_fill(0, count($services), '?');
                $serviceList = implode(",", $placeholders);
                $query = "SELECT id, ticket_number, name, service, status, created_at FROM tickets WHERE service IN ($serviceList) AND UNIX_TIMESTAMP(created_at) > ? AND status IN ('waiting', 'standard', 'pregnant', 'disability') ORDER BY created_at DESC LIMIT 5";
                $stmt = $db->prepare($query);
                $paramIndex = 1;
                foreach ($services as $service) { $stmt->bindValue($paramIndex, trim($service), PDO::PARAM_STR); $paramIndex++; }
                $stmt->bindValue($paramIndex, $lastCheck, PDO::PARAM_INT);
                $stmt->execute();
                $newTickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            
            $this->jsonResponse(true, 'Vérification effectuée', 200, array('newTickets' => $newTickets, 'timestamp' => time()));
            return false;
        } catch (Exception $e) {
            $this->jsonResponse(false, 'Erreur: ' . $e->getMessage(), 500);
            return false;
        }
    }
    
    private function ajaxGetTickets() {
        try {
            $services = gfPostVar('services', array());
            if (!is_array($services)) $services = array();
            $_SESSION['services_served'] = $services;
            $statuses = array('waiting', 'standard', 'pregnant', 'disability');
            $_SESSION['selected_statuses'] = $statuses;
            $tickets = $this->getPendingTicketsByServices($services, $statuses);
            $ticketsHtml = $this->generateTicketsListHtml($tickets, $statuses);
            $this->jsonResponse(true, 'Tickets chargés', 200, array('ticketsHtml' => $ticketsHtml, 'count' => count($tickets)));
            return false;
        } catch (Exception $e) {
            $this->jsonResponse(false, 'Erreur: ' . $e->getMessage(), 500);
            return false;
        }
    }

    private function ajaxFilterTickets() {
        try {
            $statuses = gfPostVar('statuses', array());
            if (!is_array($statuses) || empty($statuses)) $statuses = array('waiting', 'standard', 'pregnant', 'disability');
            $_SESSION['selected_statuses'] = $statuses;
            $services = gfSessionVar('services_served', array());
            $tickets = $this->getPendingTicketsByServices($services, $statuses);
            $ticketsHtml = $this->generateTicketsListHtml($tickets, $statuses);
            $this->jsonResponse(true, 'Tickets filtrés', 200, array('ticketsHtml' => $ticketsHtml, 'count' => count($tickets)));
            return false;
        } catch (Exception $e) {
            $this->jsonResponse(false, 'Erreur: ' . $e->getMessage(), 500);
            return false;
        }
    }
    
    private function ajaxSelectTicket() {
        try {
            $ticketId = (int)gfPostVar('ticket_id', 0);
            if ($ticketId <= 0) { $this->jsonResponse(false, 'ID ticket invalide', 400); return false; }
            
            $ticket = $this->getTicketById($ticketId);
            if (!$ticket) { $this->jsonResponse(false, 'Ticket non trouvé', 404); return false; }
            
            $_SESSION['selected_ticket_id'] = $ticketId;
            $_SESSION['timer_start'] = time();
            $_SESSION['timer_paused'] = false;
            $_SESSION['timer_remaining'] = 60;
            
            // 🆕 SYNCHRONIZE WITH WAITING ROOM DISPLAY
            $this->syncTicketToWaitingRoom( $ticket );
            
            $ticketHtml = $this->generateTicketDisplayHtml($ticket);
            $this->jsonResponse(true, 'Ticket sélectionné', 200, array('ticketHtml' => $ticketHtml));
            return false;
        } catch (Exception $e) {
            $this->jsonResponse(false, 'Erreur: ' . $e->getMessage(), 500);
            return false;
        }
    }
    
    private function ajaxFinishTicket() {
        try {
            $ticketId = (int)gfPostVar('ticket_id', 0);
            if ($ticketId <= 0) { $this->jsonResponse(false, 'ID ticket invalide', 400); return false; }
            
            $ticket = $this->getTicketById($ticketId);
            if ($ticket) {
                $stats = $this->createTicketStats($ticket);
                $this->saveTicketStats($stats);
                $this->deleteTicket($ticketId);
            }
            
            unset($_SESSION['selected_ticket_id'], $_SESSION['timer_start'], $_SESSION['timer_paused'], $_SESSION['timer_remaining']);
            
            $services = gfSessionVar('services_served', array());
            $statuses = gfSessionVar('selected_statuses', array('waiting', 'standard', 'pregnant', 'disability'));
            $remainingTickets = $this->getPendingTicketsByServices($services, $statuses);
            $this->jsonResponse(true, 'Ticket traité avec succès', 200, array('remainingCount' => count($remainingTickets)));
            return false;
        } catch (Exception $e) {
            $this->jsonResponse(false, 'Erreur: ' . $e->getMessage(), 500);
            return false;
        }
    }
    
    private function ajaxBackToList() {
        unset($_SESSION['selected_ticket_id'], $_SESSION['timer_start'], $_SESSION['timer_paused'], $_SESSION['timer_remaining']);
        $services = gfSessionVar('services_served', array());
        $statuses = gfSessionVar('selected_statuses', array('waiting', 'standard', 'pregnant', 'disability'));
        $tickets = $this->getPendingTicketsByServices($services, $statuses);
        $ticketsHtml = $this->generateTicketsListHtml($tickets, $statuses);
        $this->jsonResponse(true, 'Retour à la liste', 200, array('ticketsHtml' => $ticketsHtml, 'count' => count($tickets)));
        return false;
    }
    
    private function ajaxBackToServices() {
        unset($_SESSION['selected_ticket_id'], $_SESSION['services_served'], $_SESSION['selected_statuses'], $_SESSION['timer_start'], $_SESSION['timer_paused'], $_SESSION['timer_remaining']);
        $this->jsonResponse(true, 'Retour aux services', 200);
        return false;
    }
    
    /**
     * Synchronize selected ticket to waiting room display via display_main
     * Called when operator selects a ticket
     * Immediately updates the waiting room display with real-time sync
     * 
     * @param array $ticket The selected ticket data
     */
    private function syncTicketToWaitingRoom( $ticket ) {
        try {
            $db = $this->getDatabase();
            if ( !$db ) return;
            
            if ( $db instanceof PDO ) {
                // Extract ticket information
                $ticket_number = $ticket['ticket_number'];
                $desk_number = $this->getDesk()->getNumber();
                
                // Step 1: Clear existing display_main records
                $clear_query = "DELETE FROM display_main";
                $clear_stmt = $db->prepare( $clear_query );
                $clear_stmt->execute();
                
                // Step 2: Insert new ticket into display_main for immediate synchronization
                $insert_query = "INSERT INTO display_main (dm_ticket, dm_desk) VALUES (?, ?)";
                $insert_stmt = $db->prepare( $insert_query );
                $insert_stmt->bindValue( 1, $ticket_number, PDO::PARAM_STR );
                $insert_stmt->bindValue( 2, $desk_number, PDO::PARAM_INT );
                $insert_stmt->execute();
                
                error_log( "✅ [Salle Attente] Ticket synced - Number: " . $ticket_number . " | Desk: " . $desk_number );
            }
        } catch ( Exception $e ) {
            error_log( "⚠️ [Salle Attente] Sync error: " . $e->getMessage() );
        }
    }
    
    private function generateTicketsListHtml($tickets, $statuses = array()) {
        if (empty($statuses)) $statuses = array('waiting', 'standard', 'pregnant', 'disability');
        
        $filterHtml = '<div class="filter-section"><h3><i class="fas fa-filter"></i> Filtrer par statut</h3><div class="filter-checkboxes">'
            . '<label class="filter-item"><input type="checkbox" name="status_filter" value="standard" class="status-filter-checkbox"' . (in_array('standard', $statuses) ? ' checked' : '') . ' /><span class="filter-label"><i class="fas fa-circle"></i> Standard</span></label>'
            . '<label class="filter-item"><input type="checkbox" name="status_filter" value="pregnant" class="status-filter-checkbox"' . (in_array('pregnant', $statuses) ? ' checked' : '') . ' /><span class="filter-label"><i class="fas fa-heart"></i> Femme enceinte</span></label>'
            . '<label class="filter-item"><input type="checkbox" name="status_filter" value="disability" class="status-filter-checkbox"' . (in_array('disability', $statuses) ? ' checked' : '') . ' /><span class="filter-label"><i class="fas fa-wheelchair"></i> PMR/Handicap</span></label>'
            . '</div></div>';
        
        if (empty($tickets)) {
            return '<div class="ticket-section card">' . $filterHtml . '<div class="alert alert-info"><i class="fas fa-info-circle"></i> Aucun ticket en attente</div></div>';
        }
        
        $ticketsHtml = '';
        foreach ($tickets as $ticket) {
            $ticketsHtml .= '<div class="ticket-item" data-ticket-id="' . $ticket['id'] . '">'
                . '<div class="ticket-info">'
                . '<div class="ticket-number-small">' . htmlspecialchars($ticket['ticket_number'], ENT_QUOTES, 'UTF-8') . '</div>'
                . '<div class="client-info">'
                . '<div class="client-detail"><i class="fas fa-user"></i><span>' . htmlspecialchars($ticket['name'], ENT_QUOTES, 'UTF-8') . '</span></div>'
                . '<div class="client-detail"><i class="fas fa-phone"></i><span>' . htmlspecialchars($ticket['phone'], ENT_QUOTES, 'UTF-8') . '</span></div>'
                . '</div>'
                . '<div class="ticket-meta">'
                . '<span class="service-tag">' . htmlspecialchars($ticket['service'], ENT_QUOTES, 'UTF-8') . '</span>'
                . $this->getStatusBadge($ticket['status'])
                . '<span class="time-tag">' . $this->formatTime($ticket['created_at']) . '</span>'
                . '</div></div></div>';
        }
        
        return '<div class="ticket-section card">' . $filterHtml
            . '<div class="ticket-header"><h2><i class="fas fa-list"></i> Sélectionner un ticket à traiter (' . count($tickets) . ' en attente)</h2></div>'
            . '<div class="tickets-list">' . $ticketsHtml . '</div></div>';
    }
    
    private function generateTicketDisplayHtml($ticket) {
        return '<div class="ticket-section card ticket-display-card">'
            . '<div class="ticket-header">'
            . '<h2><i class="fas fa-user-clock"></i> Ticket en cours de traitement</h2>'
            . '<div class="timer-simple" id="timer-simple">'
            . '<i class="fas fa-hourglass-half"></i>'
            . '<span id="timer-value">10</span> secondes'
            . '</div>'
            . '</div>'
            . '<div class="timer-buttons-simple">'
            . '<button type="button" id="btn-pause-timer" class="btn-timer-simple btn-pause"><i class="fas fa-pause"></i> Pause (client présent)</button>'
            . '<button type="button" id="btn-resume-timer" class="btn-timer-simple btn-resume" style="display:none;"><i class="fas fa-play"></i> Reprendre</button>'
            . '</div>'
            . '<div class="client-absent-warning" id="client-absent-warning" style="display:none;">'
            . '<i class="fas fa-user-slash"></i> Client ABSENT ! Le délai de 10 secondes est écoulé'
            . '</div>'
            . '<div class="ticket-display"><div class="ticket-number"><span>' . htmlspecialchars($ticket['ticket_number'], ENT_QUOTES, 'UTF-8') . '</span></div></div>'
            . '<div class="ticket-details">'
            . '<div class="detail-row"><span class="detail-label"><i class="fas fa-user"></i> Client:</span><span class="detail-value">' . htmlspecialchars($ticket['name'], ENT_QUOTES, 'UTF-8') . '</span></div>'
            . '<div class="detail-row"><span class="detail-label"><i class="fas fa-phone"></i> Téléphone:</span><span class="detail-value">' . htmlspecialchars($ticket['phone'], ENT_QUOTES, 'UTF-8') . '</span></div>'
            . '<div class="detail-row"><span class="detail-label"><i class="fas fa-briefcase"></i> Service:</span><span class="detail-value">' . htmlspecialchars($ticket['service'], ENT_QUOTES, 'UTF-8') . '</span></div>'
            . '<div class="detail-row"><span class="detail-label"><i class="fas fa-info-circle"></i> Statut:</span><span class="detail-value">' . $this->getStatusBadge($ticket['status']) . '</span></div>'
            . '<div class="detail-row"><span class="detail-label"><i class="fas fa-clock"></i> Créé à:</span><span class="detail-value">' . $this->formatTime($ticket['created_at']) . '</span></div>'
            . '</div>'
            . '<div class="controls-section"><div class="button-group">'
            . '<button type="button" id="btn-finish-ticket" class="btn btn-success btn-large"><i class="fas fa-check-circle"></i> Terminer (client servi)</button>'
            . '<button type="button" id="btn-client-absent" class="btn btn-danger btn-large" style="display:none;"><i class="fas fa-user-slash"></i> Client ABSENT</button>'
            . '<button type="button" id="btn-back-list" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Retour à la liste</button>'
            . '</div></div></div>';
    }
    
    private function getStatusBadge($status) {
        $status = strtolower(trim($status));
        switch ($status) {
            case 'standard': return '<span class="status-badge status-standard"><i class="fas fa-circle"></i> Standard</span>';
            case 'pregnant': return '<span class="status-badge status-pregnant"><i class="fas fa-heart"></i> Femme enceinte</span>';
            case 'disability': return '<span class="status-badge status-disability"><i class="fas fa-wheelchair"></i> PMR/Handicap</span>';
            default: return '<span class="status-badge status-waiting"><i class="fas fa-hourglass-half"></i> En attente</span>';
        }
    }
    
    private function formatTime($timestamp) {
        if (empty($timestamp)) return 'N/A';
        $time = strtotime($timestamp);
        if ($time === false) return htmlspecialchars($timestamp);
        $diff = time() - $time;
        if ($diff < 60) return 'À l\'instant';
        elseif ($diff < 3600) return floor($diff / 60) . ' min';
        elseif ($diff < 86400) return floor($diff / 3600) . ' h';
        else return date('d/m/Y H:i', $time);
    }
    
    private function getTableBody() {
        $services = array();
        $queueData = array();
        $tableBody = '';
        
        try {
            $db = $this->getDatabase();
            if (!$db) throw new Exception("Impossible de se connecter à la base de données");
            
            $query = "SELECT DISTINCT service FROM tickets WHERE service IS NOT NULL AND service != '' ORDER BY service ASC";
            if ($db instanceof PDO) {
                $result = $db->query($query);
                $services = $result->fetchAll(PDO::FETCH_ASSOC);
            }
            
            if (empty($services)) return '<p style="text-align:center;color:#999;padding:20px;">Aucun service disponible</p>';
            
            $countQuery = "SELECT service, COUNT(*) as cnt FROM tickets WHERE service IS NOT NULL AND status IN ('waiting', 'standard', 'pregnant', 'disability') GROUP BY service";
            if ($db instanceof PDO) {
                $countResult = $db->query($countQuery);
                $countRows = $countResult->fetchAll(PDO::FETCH_ASSOC);
                foreach ($countRows as $row) { $queueData[trim($row['service'])] = (int)$row['cnt']; }
            }
            
            $services_served = is_array($this->services_served) ? $this->services_served : array();
            foreach ($services as $service) {
                $serviceName = trim($service['service']);
                $queueLength = isset($queueData[$serviceName]) ? $queueData[$serviceName] : 0;
                $isChecked = in_array($serviceName, $services_served);
                $checkedAttr = $isChecked ? ' checked' : '';
                $tableBody .= '<label class="service-item"><input type="checkbox" name="services_served[]" value="' . htmlspecialchars($serviceName, ENT_QUOTES, 'UTF-8') . '" class="service-checkbox"' . $checkedAttr . ' /><span class="service-label">' . htmlspecialchars($serviceName, ENT_QUOTES, 'UTF-8') . ' <span class="queue-count">' . $queueLength . '</span></span></label>';
            }
            return $tableBody;
        } catch (Exception $e) {
            return '<p style="color:#c33;padding:20px;">❌ Erreur: ' . htmlspecialchars($e->getMessage()) . '</p>';
        }
    }
    
    private function getPendingTicketsByServices($services, $statuses = array()) {
        if (empty($statuses)) $statuses = array('waiting', 'standard', 'pregnant', 'disability');
        if (empty($services) || !is_array($services)) return array();
        
        try {
            $db = $this->getDatabase();
            if (!$db) throw new Exception("Impossible de se connecter à la base de données");
            $tickets = array();
            
            if ($db instanceof PDO) {
                $placeholders = array_fill(0, count($services), '?');
                $serviceList = implode(",", $placeholders);
                $statusPlaceholders = array_fill(0, count($statuses), '?');
                $statusList = implode(",", $statusPlaceholders);
                $query = "SELECT id, ticket_number, name, phone, service, status, created_at FROM tickets WHERE service IN ($serviceList) AND status IN ($statusList) ORDER BY created_at ASC";
                $stmt = $db->prepare($query);
                $paramIndex = 1;
                foreach ($services as $service) { $stmt->bindValue($paramIndex, trim($service), PDO::PARAM_STR); $paramIndex++; }
                foreach ($statuses as $status) { $stmt->bindValue($paramIndex, trim($status), PDO::PARAM_STR); $paramIndex++; }
                $stmt->execute();
                $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            return $tickets;
        } catch (Exception $e) { throw $e; }
    }
    
    private function getTicketById($ticket_id) {
        if ($ticket_id <= 0) return null;
        try {
            $db = $this->getDatabase();
            if (!$db) return null;
            if ($db instanceof PDO) {
                $query = "SELECT * FROM tickets WHERE id = ?";
                $stmt = $db->prepare($query);
                $stmt->bindValue(1, $ticket_id, PDO::PARAM_INT);
                $stmt->execute();
                return $stmt->fetch(PDO::FETCH_ASSOC);
            }
            return null;
        } catch (Exception $e) { return null; }
    }
    
    private function deleteTicket($ticket_id) {
        try {
            $db = $this->getDatabase();
            if (!$db) return false;
            if ($db instanceof PDO) {
                $query = "DELETE FROM tickets WHERE id = ?";
                $stmt = $db->prepare($query);
                $stmt->bindValue(1, $ticket_id, PDO::PARAM_INT);
                return $stmt->execute();
            }
            return false;
        } catch (Exception $e) { return false; }
    }
    
    private function createTicketStats($ticket) {
        return array(
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
    }
    
    private function saveTicketStats($stats) {
        try {
            $db = $this->getDatabase();
            if (!$db) return true;
            if ($db instanceof PDO) {
                try {
                    $query = "INSERT INTO ticket_stats (ticket_id, ticket_number, operator_code, desk_number, service, status, client_name, created_at, served_at, wait_time) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
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
                } catch (Exception $e) { return true; }
            }
            return true;
        } catch (Exception $e) { return false; }
    }
    
    private function getOperator() {
        if (!$this->operator) {
            if (isset($_SESSION['operator'])) $this->operator = $_SESSION['operator'];
            else if (isset($_SESSION['op_code'])) $this->operator = Operator::fromDatabaseByCode($_SESSION['op_code']);
            else throw new Exception("Unable to retrieve logged-in operator.");
        }
        return $this->operator;
    }
    
    private function getDesk() {
        if (!$this->desk) {
            if (isset($_SESSION['desk'])) $this->desk = $_SESSION['desk'];
            else if (isset($_SESSION['desk_number'])) $this->desk = Desk::fromDatabaseByNumber($_SESSION['desk_number']);
            else throw new Exception("Unable to retrieve operator's desk.");
        }
        return $this->desk;
    }
    
    private function getDatabase() {
        if (isset($GLOBALS['gvSQLDatabase']) && $GLOBALS['gvSQLDatabase']) return $GLOBALS['gvSQLDatabase'];
        if (isset($GLOBALS['mysqli']) && $GLOBALS['mysqli']) return $GLOBALS['mysqli'];
        if (function_exists('getDatabaseConnection')) return getDatabaseConnection();
        if (class_exists('Database')) {
            if (method_exists('Database', 'getInstance')) return Database::getInstance();
            if (method_exists('Database', 'getConnection')) return Database::getConnection();
        }
        return null;
    }
    
    private function jsonResponse($success, $message, $code = 200, $data = array()) {
        header('Content-Type: application/json; charset=UTF-8');
        http_response_code($code);
        echo json_encode(array_merge(array('success' => $success, 'message' => $message, 'code' => $code), $data));
        exit;
    }
    
    public function afterPermissionCheck() {
        $this->services_served = gfSessionVar('services_served', array());
        $this->selected_statuses = gfSessionVar('selected_statuses', array('waiting', 'standard', 'pregnant', 'disability'));
        if (!is_array($this->services_served)) $this->services_served = array();
        if (!is_array($this->selected_statuses)) $this->selected_statuses = array('waiting', 'standard', 'pregnant', 'disability');
        if (isset($_SESSION['selected_ticket_id'])) {
            $this->selected_ticket_id = (int)$_SESSION['selected_ticket_id'];
            $this->ticket_served = $this->getTicketById($this->selected_ticket_id);
        }
    }
    
    public function getOutput() {
        global $gvPath;
        $page = new WebPageOutput();
        $page->setHtmlPageTitle("Espace opérateur - FastQueue");
        $page->setHtmlBodyHeader($this->getDesignCSS());
        $page->setHtmlBodyContent($this->getPageContent());
        $page->linkStyleSheet("$gvPath/assets/css/style.css");
        $page->importJquery();
        $page->addJavascript("$gvPath/assets/js/opPage.js");
        return $page;
    }
    
    private function getPageContent() {
        global $gvPath;
        $operator = $this->getOperator();
        $desk = $this->getDesk();
        $servicesServedArray = is_array($this->services_served) ? $this->services_served : array();
        $servicesServedText = !empty($servicesServedArray) ? implode(', ', $servicesServedArray) : 'Aucun';
        
        $messageClass = '';
        if ($this->message) {
            if (strpos($this->message, '❌') !== false || strpos($this->message, 'Erreur') !== false) $messageClass = 'error';
            else $messageClass = 'success';
        }
        $pMessage = $this->message ? '<div class="alert alert-' . $messageClass . '"><i class="fas fa-check-circle"></i> ' . $this->message . '</div>' : '';
        
        $servicesSelectionBlock = '';
        $ticketsListBlock = '';
        $ticketDisplayBlock = '';
        
        if (empty($this->services_served) || !is_array($this->services_served)) {
            $tableBody = $this->getTableBody();
            $servicesSelectionBlock = '<div class="ticket-section card services-card"><div class="ticket-header"><h2><i class="fas fa-briefcase"></i> Sélectionner les services à servir</h2></div><div class="domains-grid">' . $tableBody . '</div></div><div class="controls-section card"><button type="button" id="btn-show-tickets" class="btn btn-primary btn-large"><i class="fas fa-arrow-right"></i> Afficher les tickets</button></div>';
        } else {
            if (!$this->ticket_served) {
                $ticketsListBlock = $this->getTicketsListBlock();
            } else {
                $ticketDisplayBlock = $this->getTicketDisplayBlock();
            }
        }
        
        return '<div class="layout"><div class="operator-header"><div class="header-top"><div class="logo-section"><h1><i class="fas fa-rocket"></i> FastQueue</h1><span class="operator-badge">Opérateur</span></div><div class="header-links"><a href="' . $gvPath . '/application/help" class="header-link"><i class="fas fa-question-circle"></i> Aide</a><a href="' . $gvPath . '/application/logoutPage" class="header-link logout"><i class="fas fa-sign-out-alt"></i> Déconnexion</a></div></div><div class="operator-info-bar"><div class="info-item"><i class="fas fa-user-circle"></i><div><span class="info-label">Opérateur</span><span class="info-value">' . htmlspecialchars($operator->getFullName(), ENT_QUOTES, 'UTF-8') . '</span></div></div><div class="info-item"><i class="fas fa-id-badge"></i><div><span class="info-label">Code</span><span class="info-value">' . htmlspecialchars($operator->getCode(), ENT_QUOTES, 'UTF-8') . '</span></div></div><div class="info-item"><i class="fas fa-desktop"></i><div><span class="info-label">Compteur</span><span class="info-value">' . htmlspecialchars($desk->getNumber(), ENT_QUOTES, 'UTF-8') . '</span></div></div><div class="info-item"><i class="fas fa-layer-group"></i><div><span class="info-label">Services servis</span><span class="info-value" id="services-served-display">' . htmlspecialchars($servicesServedText, ENT_QUOTES, 'UTF-8') . '</span></div></div></div></div><main class="operator-main"><div class="content-container"><div id="main-content" class="main-content">' . $pMessage . $servicesSelectionBlock . $ticketsListBlock . $ticketDisplayBlock . '</div></div></main></div>';
    }
    
    private function getTicketsListBlock() {
        try {
            $statuses = gfSessionVar('selected_statuses', array('waiting', 'standard', 'pregnant', 'disability'));
            if (!is_array($statuses)) $statuses = array('waiting', 'standard', 'pregnant', 'disability');
            $this->pending_tickets = $this->getPendingTicketsByServices($this->services_served, $statuses);
            $pendingCount = count($this->pending_tickets);
            
            if ($pendingCount === 0) {
                return '<div class="ticket-section card"><div class="alert alert-info">Aucun ticket en attente</div></div><div class="controls-section card"><button type="button" id="btn-back-services" class="btn btn-secondary btn-large"><i class="fas fa-arrow-left"></i> Retour aux services</button></div>';
            }
            
            $filterHtml = '<div class="filter-section"><h3><i class="fas fa-filter"></i> Filtrer par statut</h3><div class="filter-checkboxes">'
                . '<label class="filter-item"><input type="checkbox" name="status_filter" value="standard" class="status-filter-checkbox"' . (in_array('standard', $statuses) ? ' checked' : '') . ' /><span class="filter-label"><i class="fas fa-circle"></i> Standard</span></label>'
                . '<label class="filter-item"><input type="checkbox" name="status_filter" value="pregnant" class="status-filter-checkbox"' . (in_array('pregnant', $statuses) ? ' checked' : '') . ' /><span class="filter-label"><i class="fas fa-heart"></i> Femme enceinte</span></label>'
                . '<label class="filter-item"><input type="checkbox" name="status_filter" value="disability" class="status-filter-checkbox"' . (in_array('disability', $statuses) ? ' checked' : '') . ' /><span class="filter-label"><i class="fas fa-wheelchair"></i> PMR/Handicap</span></label>'
                . '</div></div>';
            
            $ticketsHtml = '';
            foreach ($this->pending_tickets as $ticket) {
                $ticketsHtml .= '<div class="ticket-item" data-ticket-id="' . $ticket['id'] . '">'
                    . '<div class="ticket-info"><div class="ticket-number-small">' . htmlspecialchars($ticket['ticket_number'], ENT_QUOTES, 'UTF-8') . '</div>'
                    . '<div class="client-info"><div class="client-detail"><i class="fas fa-user"></i><span>' . htmlspecialchars($ticket['name'], ENT_QUOTES, 'UTF-8') . '</span></div>'
                    . '<div class="client-detail"><i class="fas fa-phone"></i><span>' . htmlspecialchars($ticket['phone'], ENT_QUOTES, 'UTF-8') . '</span></div></div>'
                    . '<div class="ticket-meta"><span class="service-tag">' . htmlspecialchars($ticket['service'], ENT_QUOTES, 'UTF-8') . '</span>'
                    . $this->getStatusBadge($ticket['status']) . '<span class="time-tag">' . $this->formatTime($ticket['created_at']) . '</span></div></div></div>';
            }
            
            return '<div class="ticket-section card">' . $filterHtml . '<div class="ticket-header"><h2><i class="fas fa-list"></i> Sélectionner un ticket à traiter (' . $pendingCount . ' en attente)</h2></div><div class="tickets-list">' . $ticketsHtml . '</div></div><div class="controls-section card"><div class="button-group"><button type="button" id="btn-select-ticket" class="btn btn-primary btn-large"><i class="fas fa-hand-paper"></i> Sélectionner le ticket</button><button type="button" id="btn-back-services" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Retour aux services</button></div></div>';
        } catch (Exception $e) {
            return '<div class="alert alert-error">Erreur: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</div>';
        }
    }
    
    private function getTicketDisplayBlock() {
        if (!$this->ticket_served) return '';
        return $this->generateTicketDisplayHtml($this->ticket_served);
    }
    
    private function getDesignCSS() {
        return '<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"><link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet"><style>
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:"Inter",sans-serif;background:#f5f7fb;color:#1a1a2e;}
.operator-header{background:white;border-bottom:2px solid #f0f0f0;box-shadow:0 4px 12px rgba(0,0,0,0.08);}
.header-top{display:flex;align-items:center;justify-content:space-between;padding:20px 40px;gap:20px;}
.logo-section{display:flex;align-items:center;gap:16px;}
.logo-section h1{font-size:28px;font-weight:800;color:#1a1a2e;display:flex;align-items:center;gap:10px;}
.logo-section h1 i{color:#6C63FF;}
.operator-badge{background:linear-gradient(135deg,#6C63FF,#8B82FF);color:white;padding:6px 16px;border-radius:20px;font-size:12px;font-weight:700;}
.header-links{display:flex;gap:20px;}
.header-link{display:flex;align-items:center;gap:8px;color:#666;text-decoration:none;font-weight:500;padding:8px 12px;border-radius:8px;}
.header-link:hover{background:#f0f0f0;color:#6C63FF;}
.header-link.logout{color:#ff6b6b;}
.operator-info-bar{display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:24px;padding:24px 40px;background:#f8f9fa;}
.info-item{display:flex;align-items:center;gap:14px;}
.info-item i{font-size:24px;color:#6C63FF;width:40px;height:40px;display:flex;align-items:center;justify-content:center;background:rgba(108,99,255,0.1);border-radius:12px;}
.info-label{display:block;font-size:12px;color:#999;font-weight:600;margin-bottom:4px;}
.info-value{display:block;font-size:16px;font-weight:700;color:#1a1a2e;}
.operator-main{padding:40px;}
.content-container{max-width:1200px;margin:0 auto;}
.main-content{display:grid;gap:30px;}
.card{background:white;border-radius:18px;padding:30px;box-shadow:0 2px 12px rgba(0,0,0,0.06);border:1px solid rgba(0,0,0,0.03);}
.card h2,.card h3{font-size:18px;font-weight:700;color:#1a1a2e;margin-bottom:20px;display:flex;align-items:center;gap:12px;}
.card h2 i,.card h3 i{color:#6C63FF;}
.ticket-header{margin-bottom:20px;padding-bottom:16px;border-bottom:2px solid #f0f0f0;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:15px;}
.ticket-header h2{font-size:22px;margin-bottom:0;}
.timer-simple{display:flex;align-items:center;gap:8px;background:#f8f9fa;padding:8px 16px;border-radius:30px;font-size:14px;font-weight:600;color:#6C63FF;border:1px solid #e0e0e0;}
.timer-simple i{font-size:14px;}
.timer-simple span{font-size:18px;font-weight:800;color:#dc3545;background:#fff0f0;padding:2px 8px;border-radius:20px;margin:0 4px;}
.timer-buttons-simple{display:flex;gap:10px;justify-content:center;margin-bottom:20px;}
.btn-timer-simple{padding:6px 16px;border-radius:20px;font-weight:600;font-size:13px;cursor:pointer;border:none;font-family:inherit;}
.btn-pause{background:#ffc107;color:#333;}
.btn-pause:hover{background:#ffb300;}
.btn-resume{background:#28a745;color:white;}
.btn-resume:hover{background:#218838;}
.client-absent-warning{background:linear-gradient(135deg,#dc3545,#c82333);color:white;padding:12px 20px;border-radius:10px;margin-bottom:20px;display:flex;align-items:center;justify-content:center;gap:10px;font-weight:600;animation:shake 0.5s ease;}
@keyframes shake{0%,100%{transform:translateX(0);}25%{transform:translateX(-5px);}75%{transform:translateX(5px);}}
.ticket-display-card{background:linear-gradient(135deg,#fff,#f8f9fa);border:2px solid #e0e0e0;}
.ticket-display{display:flex;justify-content:center;align-items:center;min-height:280px;background:linear-gradient(135deg,#f8f9fa,#fff);border-radius:16px;margin-bottom:20px;}
.ticket-number{text-align:center;}
.ticket-number span{display:block;font-size:72px;font-weight:800;background:linear-gradient(135deg,#6C63FF,#8B82FF);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;}
.ticket-details{background:#f8f9fa;border-radius:12px;padding:20px;display:flex;flex-direction:column;gap:16px;margin-bottom:20px;}
.detail-row{display:flex;align-items:center;gap:12px;padding:12px;background:white;border-radius:8px;border-left:4px solid #6C63FF;}
.detail-label{display:flex;align-items:center;gap:8px;font-weight:600;color:#666;min-width:120px;}
.detail-label i{color:#6C63FF;}
.detail-value{font-weight:700;color:#1a1a2e;}
.filter-section{background:#f8f9fa;border-radius:12px;padding:20px;margin-bottom:20px;border-left:4px solid #6C63FF;}
.filter-checkboxes{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px;}
.filter-item{display:flex;align-items:center;gap:10px;cursor:pointer;}
.status-filter-checkbox{width:16px;height:16px;accent-color:#6C63FF;}
.filter-label{display:flex;align-items:center;gap:6px;font-weight:500;font-size:13px;}
.domains-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:16px;}
.service-item{display:flex;align-items:center;gap:12px;padding:16px;background:#f8f9fa;border:2px solid #e0e0e0;border-radius:12px;cursor:pointer;transition:all 0.3s ease;}
.service-item:hover{border-color:#6C63FF;box-shadow:0 4px 12px rgba(108,99,255,0.1);}
.service-checkbox{width:18px;height:18px;accent-color:#6C63FF;}
.service-label{flex:1;font-weight:500;color:#1a1a2e;cursor:pointer;}
.queue-count{display:inline-block;background:linear-gradient(135deg,#6C63FF,#8B82FF);color:white;padding:2px 8px;border-radius:12px;font-size:12px;font-weight:700;margin-left:8px;}
.tickets-list{display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:16px;margin-bottom:20px;}
.ticket-item{padding:16px;background:#f8f9fa;border:2px solid #e0e0e0;border-radius:12px;cursor:pointer;transition:all 0.3s ease;}
.ticket-item:hover{border-color:#6C63FF;box-shadow:0 4px 12px rgba(108,99,255,0.1);transform:translateY(-2px);}
.ticket-item.selected{border-color:#6C63FF;background:#e8e7ff;}
.ticket-info{display:flex;flex-direction:column;gap:12px;}
.ticket-number-small{font-size:24px;font-weight:800;background:linear-gradient(135deg,#6C63FF,#8B82FF);-webkit-background-clip:text;-webkit-text-fill-color:transparent;}
.client-info{display:flex;flex-direction:column;gap:8px;}
.client-detail{display:flex;align-items:center;gap:8px;font-size:13px;}
.client-detail i{color:#6C63FF;width:16px;}
.ticket-meta{display:flex;align-items:center;justify-content:space-between;gap:8px;padding-top:8px;border-top:1px solid #e0e0e0;flex-wrap:wrap;}
.service-tag{background:linear-gradient(135deg,#6C63FF,#8B82FF);color:white;padding:4px 10px;border-radius:12px;font-size:11px;font-weight:700;}
.time-tag{background:#f0f0f0;color:#666;padding:4px 10px;border-radius:12px;font-size:11px;}
.status-badge{display:inline-flex;align-items:center;gap:6px;padding:6px 12px;border-radius:12px;font-size:12px;font-weight:700;}
.status-waiting{background:#fff3cd;color:#856404;}
.status-standard{background:#d1ecf1;color:#0c5460;}
.status-pregnant{background:#f8d7da;color:#721c24;}
.status-disability{background:#e2e3e5;color:#383d41;}
.controls-section{margin-top:20px;}
.button-group{display:flex;gap:16px;flex-wrap:wrap;}
.btn{padding:12px 24px;border-radius:12px;font-weight:600;font-size:14px;cursor:pointer;border:none;font-family:inherit;display:inline-flex;align-items:center;justify-content:center;gap:8px;flex:1;min-width:200px;transition:all 0.3s ease;}
.btn-primary{background:linear-gradient(135deg,#6C63FF,#8B82FF);color:white;}
.btn-primary:hover{transform:translateY(-2px);box-shadow:0 6px 20px rgba(108,99,255,0.4);}
.btn-success{background:linear-gradient(135deg,#28a745,#20c997);color:white;}
.btn-success:hover{transform:translateY(-2px);box-shadow:0 6px 20px rgba(40,167,69,0.4);}
.btn-danger{background:linear-gradient(135deg,#dc3545,#c82333);color:white;}
.btn-danger:hover{transform:translateY(-2px);box-shadow:0 6px 20px rgba(220,53,69,0.4);}
.btn-secondary{background:#f0f0f0;color:#666;}
.btn-secondary:hover{background:#e0e0e0;}
.btn-large{padding:16px 32px;font-size:16px;}
.alert{padding:14px 16px;border-radius:12px;margin-bottom:20px;display:flex;align-items:center;gap:10px;animation:slideDown 0.3s ease;}
.alert-error{background:#fff0f0;color:#c0392b;border-left:4px solid #e74c3c;}
.alert-info{background:#f0f8ff;color:#0066cc;border-left:4px solid #00a8ff;}
.alert-success{background:#f0fff0;color:#27ae60;border-left:4px solid #2ecc71;}
@keyframes slideDown{from{transform:translateY(-10px);opacity:0;}to{transform:translateY(0);opacity:1;}}
.loading{display:inline-block;width:20px;height:20px;border:3px solid #f3f3f3;border-top:3px solid #6C63FF;border-radius:50%;animation:spin 1s linear infinite;}
@keyframes spin{0%{transform:rotate(0deg);}100%{transform:rotate(360deg);}}
@media(max-width:768px){.header-top{padding:16px 24px;flex-direction:column;}.operator-info-bar{padding:20px 24px;}.operator-main{padding:24px;}.button-group{flex-direction:column;}.btn{width:100%;}.ticket-number span{font-size:48px;}.ticket-header{flex-direction:column;text-align:center;}}
@media(max-width:480px){.operator-main{padding:16px;}.card{padding:20px;}.ticket-header h2{font-size:18px;}.timer-simple{font-size:12px;}.timer-simple span{font-size:14px;}.ticket-number span{font-size:36px;}}
</style></head><body><script src="https://code.jquery.com/jquery-3.6.0.min.js"></script><script>' . $this->getJavaScriptCode() . '</script></body></html>';
    }
    
    private function getJavaScriptCode() {
        return 'var operatorPageScript = (function() {
            var selectedTicketId = null;
            var selectedServices = [];
            var selectedStatuses = ["waiting", "standard", "pregnant", "disability"];
            var lastCheckTime = Math.floor(Date.now() / 1000);
            var checkInterval = null;
            var timerInterval = null;
            var currentTicketId = null;
            var timerRunning = false;
            var timerPaused = false;
            
            function showLoading() { $("#main-content").html(\'<div style="text-align:center;padding:40px;"><div class="loading"></div><p>Chargement...</p></div>\'); }
            function showMessage(message, type) { var icon = type === "success" ? "fa-check-circle" : "fa-exclamation-circle"; var alertClass = type === "success" ? "alert-success" : "alert-error"; var alert = \'<div class="alert \' + alertClass + \'"><i class="fas \' + icon + \'"></i> \' + message + \'</div>\'; $("#main-content").prepend(alert); setTimeout(function() { $("#main-content > .alert").fadeOut(300, function() { $(this).remove(); }); }, 3000); }
            
            function startTimer() {
                if (timerInterval) clearInterval(timerInterval);
                timerRunning = true;
                timerInterval = setInterval(function() {
                    if (!timerRunning || timerPaused) return;
                    $.ajax({ url: location.pathname, method: "POST", dataType: "json", headers: {"X-Requested-With": "XMLHttpRequest"}, data: {action: "get_timer_status"},
                        success: function(response) {
                            if (response.success) {
                                var remaining = response.remaining, expired = response.expired, paused = response.paused;
                                $("#timer-value").text(remaining);
                                if (remaining <= 3 && remaining > 0) $("#timer-simple").css("background", "#fff0f0").css("border-color", "#dc3545");
                                else $("#timer-simple").css("background", "#f8f9fa").css("border-color", "#e0e0e0");
                                if (expired && !paused) {
                                    timerRunning = false;
                                    $(".timer-simple, .timer-buttons-simple").hide();
                                    $("#client-absent-warning").fadeIn();
                                    $("#btn-client-absent").show();
                                    $("#btn-finish-ticket").hide();
                                    showMessage("⚠️ Client ABSENT - Le délai de 10 secondes est écoulé !", "error");
                                }
                            }
                        }
                    });
                }, 1000);
            }
            
            function stopTimer() { if (timerInterval) { clearInterval(timerInterval); timerInterval = null; } timerRunning = false; }
            function pauseTimer() {
                $.ajax({ url: location.pathname, method: "POST", dataType: "json", headers: {"X-Requested-With": "XMLHttpRequest"}, data: {action: "pause_timer"},
                    success: function(response) {
                        if (response.success) {
                            timerPaused = true;
                            $("#btn-pause-timer").hide();
                            $("#btn-resume-timer").show();
                            showMessage("⏸️ Chronomètre en pause - Client présent", "success");
                        }
                    }
                });
            }
            function resumeTimer() {
                $.ajax({ url: location.pathname, method: "POST", dataType: "json", headers: {"X-Requested-With": "XMLHttpRequest"}, data: {action: "resume_timer"},
                    success: function(response) {
                        if (response.success) {
                            timerPaused = false;
                            $("#btn-pause-timer").show();
                            $("#btn-resume-timer").hide();
                            startTimer();
                            showMessage("▶️ Chronomètre repris", "success");
                        }
                    }
                });
            }
            function clientAbsent() {
                if (confirm("⚠️ Confirmez-vous que le client est ABSENT ?\\nLe ticket sera annulé.")) {
                    showLoading(); stopTimer();
                    $.ajax({ url: location.pathname, method: "POST", dataType: "json", headers: {"X-Requested-With": "XMLHttpRequest"}, data: {action: "client_absent", ticket_id: currentTicketId},
                        success: function(response) { if (response.success) { showMessage("❌ Client absent - Ticket annulé", "error"); setTimeout(function() { getTickets(); }, 1500); } }
                    });
                }
            }
            function checkNewTickets() { if (selectedServices.length === 0) return; $.ajax({ url: location.pathname, method: "POST", dataType: "json", headers: {"X-Requested-With": "XMLHttpRequest"}, data: {action: "check_new_tickets", lastCheck: lastCheckTime}, success: function(response) { if (response.success && response.newTickets && response.newTickets.length > 0) lastCheckTime = response.timestamp; } }); }
            function updateServicesDisplay() { var text = selectedServices.length > 0 ? selectedServices.join(", ") : "Aucun"; $("#services-served-display").text(text); }
            function attachTicketListeners() { $(".ticket-item").off("click").on("click", function() { $(".ticket-item").removeClass("selected"); $(this).addClass("selected"); selectedTicketId = $(this).data("ticket-id"); }); }
            function attachFilterListeners() { $(".status-filter-checkbox").off("change").on("change", function() { selectedStatuses = []; $(".status-filter-checkbox:checked").each(function() { selectedStatuses.push($(this).val()); }); if (selectedStatuses.length === 0) selectedStatuses = ["waiting", "standard", "pregnant", "disability"]; filterTickets(); }); }
            function attachServiceListeners() { $("#btn-show-tickets").off("click").on("click", function() { selectedServices = []; $(".service-checkbox:checked").each(function() { selectedServices.push($(this).val()); }); if (selectedServices.length === 0) { showMessage("Sélectionnez au moins un service", "error"); return; } updateServicesDisplay(); showLoading(); getTickets(); startNotificationCheck(); }); }
            function startNotificationCheck() { if (checkInterval) clearInterval(checkInterval); checkNewTickets(); checkInterval = setInterval(checkNewTickets, 5000); }
            function stopNotificationCheck() { if (checkInterval) { clearInterval(checkInterval); checkInterval = null; } }
            function attachListControls() { $("#btn-select-ticket").off("click").on("click", function() { if (!selectedTicketId) { showMessage("Sélectionnez un ticket", "error"); return; } selectTicket(selectedTicketId); }); $("#btn-back-services").off("click").on("click", function() { backToServices(); }); }
            function attachTicketControls() { $("#btn-finish-ticket").off("click").on("click", function() { finishTicket(currentTicketId); }); $("#btn-client-absent").off("click").on("click", function() { clientAbsent(); }); $("#btn-pause-timer").off("click").on("click", function() { pauseTimer(); }); $("#btn-resume-timer").off("click").on("click", function() { resumeTimer(); }); $("#btn-back-list").off("click").on("click", function() { stopTimer(); backToList(); }); }
            function getTickets() { $.ajax({ url: location.pathname, method: "POST", dataType: "json", headers: {"X-Requested-With": "XMLHttpRequest"}, data: {action: "get_tickets", services: selectedServices}, success: function(response) { if (response.success) { var html = response.ticketsHtml; html += \'<div class="controls-section card"><div class="button-group"><button type="button" id="btn-select-ticket" class="btn btn-primary btn-large"><i class="fas fa-hand-paper"></i> Sélectionner le ticket</button><button type="button" id="btn-back-services" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Retour aux services</button></div></div>\'; $("#main-content").html(html); attachTicketListeners(); attachFilterListeners(); attachListControls(); } } }); }
            function filterTickets() { $.ajax({ url: location.pathname, method: "POST", dataType: "json", headers: {"X-Requested-With": "XMLHttpRequest"}, data: {action: "filter_tickets", statuses: selectedStatuses}, success: function(response) { if (response.success) { var html = response.ticketsHtml; html += \'<div class="controls-section card"><div class="button-group"><button type="button" id="btn-select-ticket" class="btn btn-primary btn-large"><i class="fas fa-hand-paper"></i> Sélectionner le ticket</button><button type="button" id="btn-back-services" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Retour aux services</button></div></div>\'; $("#main-content").html(html); attachTicketListeners(); attachFilterListeners(); attachListControls(); } } }); }
            function selectTicket(ticketId) { currentTicketId = ticketId; timerRunning = true; timerPaused = false; $.ajax({ url: location.pathname, method: "POST", dataType: "json", headers: {"X-Requested-With": "XMLHttpRequest"}, data: {action: "select_ticket", ticket_id: ticketId}, success: function(response) { if (response.success) { $("#main-content").html(response.ticketHtml); attachTicketControls(); startTimer(); } } }); }
            function finishTicket(ticketId) { if (confirm("✅ Confirmez-vous que le client a été SERVi ?")) { showLoading(); stopTimer(); $.ajax({ url: location.pathname, method: "POST", dataType: "json", headers: {"X-Requested-With": "XMLHttpRequest"}, data: {action: "finish_ticket", ticket_id: ticketId}, success: function(response) { if (response.success) { showMessage("✅ Ticket traité avec succès !", "success"); setTimeout(function() { getTickets(); }, 1000); } } }); } }
            function backToList() { $.ajax({ url: location.pathname, method: "POST", dataType: "json", headers: {"X-Requested-With": "XMLHttpRequest"}, data: {action: "back_to_list"}, success: function(response) { if (response.success) { selectedTicketId = null; currentTicketId = null; var html = response.ticketsHtml; html += \'<div class="controls-section card"><div class="button-group"><button type="button" id="btn-select-ticket" class="btn btn-primary btn-large"><i class="fas fa-hand-paper"></i> Sélectionner le ticket</button><button type="button" id="btn-back-services" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Retour aux services</button></div></div>\'; $("#main-content").html(html); attachTicketListeners(); attachFilterListeners(); attachListControls(); } } }); }
            function backToServices() { stopNotificationCheck(); stopTimer(); $.ajax({ url: location.pathname, method: "POST", dataType: "json", headers: {"X-Requested-With": "XMLHttpRequest"}, data: {action: "back_to_services"}, success: function() { location.reload(); } }); }
            $(document).ready(function() { attachServiceListeners(); });
            $(window).on("unload", function() { stopNotificationCheck(); stopTimer(); });
        })();';
    }
}

?>