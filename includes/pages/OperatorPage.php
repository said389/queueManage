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
    private $disableNextButton = false;
    
    public function canUse( $userLevel ) {
        return $userLevel == Page::OPERATOR_USER;
    }

    public function execute() {
        // ✅ Récupérer les services depuis POST
        $this->services_served = gfPostVar( 'services_served', array() );
        
        // Assurer que services_served est un array
        if ( !is_array( $this->services_served ) ) {
            $this->services_served = array();
        }
        
        // Sauvegarder en session
        $_SESSION['services_served'] = $this->services_served;

        // ✅ Si "next" a été cliqué
        if ( isset( $_POST['next'] ) ) {
            // Vérifier si au moins un service est sélectionné
            if ( empty( $this->services_served ) ) {
                $this->message = "⚠️ Erreur: sélectionnez au moins un service.";
                error_log("No service selected");
                return true;
            }

            error_log("Services selected: " . json_encode($this->services_served));

            // Handle served ticket
            $served = Ticket::fromDatabaseByDesk( $this->getDesk()->getNumber() );
            if ( $served ) {
                error_log("Previous ticket found: " . $served->getTextString());
                $stats = TicketStats::newFromTicket( $served );
                $served->delete();
                if ( !$stats->save() ) {
                    throw new Exception( "Unable to save ticket stats." );
                }
            }

            // Call next ticket based on selected services
            try {
                error_log("Calling serveNextTicketByServices");
                $ticket = $this->serveNextTicketByServices(
                    $this->services_served,
                    $this->getOperator()->getCode(),
                    $this->getDesk()->getNumber()
                );
                
                if ( !$ticket ) {
                    $this->message = "ℹ️ Aucun ticket à appeler pour les services sélectionnés";
                    error_log("No ticket found for services: " . json_encode($this->services_served));
                    $this->pauseButtonEnabled = false;
                    $this->ticket_served = null;
                    return true;
                }
                
                error_log("Ticket served: " . json_encode($ticket));
                $this->ticket_served = $ticket;
                $this->disableNextButton = true;
                $this->pauseButtonEnabled = true;
                return true;
                
            } catch ( Exception $e ) {
                $this->message = "❌ Erreur: " . $e->getMessage();
                error_log("Error in serveNextTicketByServices: " . $e->getMessage());
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
        $this->services_served = gfSessionVar( 'services_served', array() );
        
        // Assurer que services_served est un array
        if ( !is_array( $this->services_served ) ) {
            $this->services_served = array();
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

        // ✅ Gérer l'affichage du ticket de manière robuste
        $servedText = 'Aucun';
        if ($this->ticket_served) {
            if (method_exists($this->ticket_served, 'getTextString')) {
                $servedText = $this->ticket_served->getTextString();
            } else if (isset($this->ticket_served->ticket_number)) {
                $servedText = htmlspecialchars($this->ticket_served->ticket_number);
            }
        }
        
        $tableBody = $this->getTableBody();
        $operator = $this->getOperator();
        $desk = $this->getDesk();
        
        // Sécuriser services_served
        $servicesServedArray = is_array( $this->services_served ) ? $this->services_served : array();
        $servicesServedText = !empty( $servicesServedArray ) ? implode( ', ', $servicesServedArray ) : 'Aucun';
        
        $messageClass = '';
        if ( $this->message ) {
            $messageClass = strpos($this->message, 'Erreur') !== false || strpos($this->message, '❌') !== false ? 'error' : 'info';
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
                    <span class="info-label">Services servis</span>
                    <span class="info-value">$servicesServedText</span>
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

                <!-- Services Selection (from tickets table) -->
                <div class="domains-section card">
                    <h3><i class="fas fa-briefcase"></i> Sélectionner les services à servir</h3>
                    <div class="domains-grid">
                        $tableBody
                    </div>
                </div>

                <!-- Control Buttons -->
                <div class="controls-section card">
                    <h3><i class="fas fa-sliders-h"></i> Contrôles</h3>
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
     * ✅ Récupère la connexion à la base de données
     */
    private function getDatabase() {
        // Essayer d'accéder à la BD via différentes méthodes
        $db = null;
        
        // Méthode 1: Variable globale standard
        if (isset($GLOBALS['gvSQLDatabase']) && $GLOBALS['gvSQLDatabase']) {
            $db = $GLOBALS['gvSQLDatabase'];
            error_log("Database found via gvSQLDatabase");
            return $db;
        }
        
        // Méthode 2: Variable globale alternative
        if (isset($GLOBALS['mysqli']) && $GLOBALS['mysqli']) {
            $db = $GLOBALS['mysqli'];
            error_log("Database found via mysqli");
            return $db;
        }
        
        // Méthode 3: Chercher une instance de connexion BD
        if (function_exists('getDatabaseConnection')) {
            $db = getDatabaseConnection();
            error_log("Database found via getDatabaseConnection function");
            return $db;
        }
        
        // Méthode 4: Essayer d'accéder à une classe Database
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

    /**
     * ✅ Récupère les services distincts et leurs files d'attente
     */
    private function getTableBody() {
        // Initialiser les variables
        $services = array();
        $queueData = array();
        $tableBody = '';
        
        try {
            // Obtenir la connexion BD
            $db = $this->getDatabase();
            
            if (!$db) {
                throw new Exception("Impossible de se connecter à la base de données");
            }
            
            // Récupérer les services distincts
            $query = "SELECT DISTINCT service FROM tickets 
                      WHERE service IS NOT NULL AND service != '' 
                      ORDER BY service ASC";
            
            error_log("Executing query: " . $query);
            
            // Vérifier si c'est PDO ou MySQLi
            if ($db instanceof PDO) {
                // PDO
                error_log("Using PDO connection");
                $result = $db->query($query);
                
                if (!$result) {
                    throw new Exception("Erreur SQL PDO");
                }
                
                $services = $result->fetchAll(PDO::FETCH_ASSOC);
            } else {
                // MySQLi
                error_log("Using MySQLi connection");
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
            
            // Filtrer les services vides
            $services = array_filter($services, function($s) {
                return !empty($s['service']);
            });
            
            foreach ($services as $service) {
                error_log("Found service: " . $service['service']);
            }
            
            // Si pas de services, retourner un message
            if (empty($services)) {
                error_log("No services found in tickets table");
                return '<p style="text-align: center; color: #999; padding: 20px;">Aucun service disponible. Veuillez ajouter des tickets d\'abord.</p>';
            }
            
            error_log("Total services found: " . count($services));
            
            // ✅ Récupérer TOUS les tickets (pas seulement 'waiting')
            $countQuery = "SELECT service, COUNT(*) as cnt FROM tickets 
                           WHERE service IS NOT NULL 
                           GROUP BY service";
            
            error_log("Executing count query: " . $countQuery);
            
            if ($db instanceof PDO) {
                // PDO
                $countResult = $db->query($countQuery);
                
                if ($countResult) {
                    $countRows = $countResult->fetchAll(PDO::FETCH_ASSOC);
                    foreach ($countRows as $row) {
                        $serviceName = trim($row['service']);
                        $queueData[$serviceName] = $row['cnt'];
                        error_log("Service: " . $serviceName . " -> " . $row['cnt'] . " tickets");
                    }
                }
            } else {
                // MySQLi
                $countResult = $db->query($countQuery);
                
                if ($countResult) {
                    while ($row = $countResult->fetch_assoc()) {
                        $serviceName = trim($row['service']);
                        $queueData[$serviceName] = $row['cnt'];
                        error_log("Service: " . $serviceName . " -> " . $row['cnt'] . " tickets");
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
                
                $tableBody .= <<<HTML
            <label class="service-item">
                <input type="checkbox" name="services_served[]" value="$escapedService" class="service-checkbox"$checkedAttr />
                <span class="service-label">
                    $escapedService 
                    <span class="queue-count">($queueLength)</span>
                </span>
            </label>
HTML;
            }
            
            return $tableBody;
            
        } catch (Exception $e) {
            error_log("getTableBody Exception: " . $e->getMessage());
            return '<p style="color: #c33; padding: 20px;">❌ Erreur: ' . htmlspecialchars($e->getMessage()) . '</p>';
        }
    }

    /**
     * ✅ Appelle le prochain ticket selon les services sélectionnés
     */
    private function serveNextTicketByServices($services, $operator_code, $desk_number) {
        if (empty($services) || !is_array($services)) {
            error_log("serveNextTicketByServices: No services provided");
            return null;
        }
        
        try {
            // Obtenir la connexion BD
            $db = $this->getDatabase();
            
            if (!$db) {
                throw new Exception("Impossible de se connecter à la base de données");
            }
            
            error_log("serveNextTicketByServices called with services: " . json_encode($services));
            error_log("operator_code: $operator_code, desk_number: $desk_number");
            
            // ✅ Statuts valides pour chercher les tickets
            $validStatuses = array('waiting', 'standard', 'pregnant', 'disability', 'serving');
            
            // Déterminer le type de BD et construire la requête
            if ($db instanceof PDO) {
                // PDO - Utiliser des prepared statements
                error_log("Using PDO connection");
                $placeholders = array_fill(0, count($services), '?');
                $serviceList = implode(",", $placeholders);
                
                $statusPlaceholders = array_fill(0, count($validStatuses), '?');
                $statusList = implode(",", $statusPlaceholders);
                
                $query = "SELECT * FROM tickets 
                          WHERE service IN ($serviceList) 
                          AND status IN ($statusList)
                          ORDER BY created_at ASC 
                          LIMIT 1";
                
                error_log("Executing PDO query: " . $query);
                
                $stmt = $db->prepare($query);
                
                // Lier les paramètres de service
                foreach ($services as $key => $service) {
                    $trimmedService = trim($service);
                    $stmt->bindValue($key + 1, $trimmedService, PDO::PARAM_STR);
                    error_log("Bound service[$key]: '$trimmedService'");
                }
                
                // Lier les paramètres de status
                foreach ($validStatuses as $key => $status) {
                    $stmt->bindValue($key + count($services) + 1, $status, PDO::PARAM_STR);
                    error_log("Bound status[$key]: '$status'");
                }
                
                $stmt->execute();
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($row) {
                    error_log("Found ticket with PDO: " . json_encode($row));
                    
                    // Mettre à jour le statut du ticket
                    $ticket_id = (int)$row['id'];
                    $update_query = "UPDATE tickets SET status = 'serving' WHERE id = ?";
                    
                    error_log("Updating ticket ID $ticket_id with query: " . $update_query);
                    
                    $updateStmt = $db->prepare($update_query);
                    $updateStmt->bindValue(1, $ticket_id, PDO::PARAM_INT);
                    
                    if (!$updateStmt->execute()) {
                        error_log("PDO Update failed");
                        throw new Exception("Erreur update PDO");
                    }
                    
                    error_log("Ticket updated successfully");
                    
                    // Créer et retourner l'objet Ticket
                    if (method_exists('Ticket', 'fromArray')) {
                        error_log("Using Ticket::fromArray");
                        return Ticket::fromArray($row);
                    } else {
                        error_log("Creating stdClass ticket object");
                        $ticket = new stdClass();
                        $ticket->id = $row['id'];
                        $ticket->ticket_number = $row['ticket_number'];
                        $ticket->name = $row['name'];
                        $ticket->phone = $row['phone'];
                        $ticket->status = $row['status'];
                        $ticket->service = $row['service'];
                        $ticket->created_at = $row['created_at'];
                        
                        return $ticket;
                    }
                } else {
                    error_log("No row found with PDO");
                }
            } else {
                // MySQLi
                error_log("Using MySQLi connection");
                $escapedServices = array();
                foreach ($services as $service) {
                    $trimmed = trim($service);
                    $escaped = $db->real_escape_string($trimmed);
                    $escapedServices[] = "'" . $escaped . "'";
                    error_log("Escaped service: '$trimmed' -> '$escaped'");
                }
                
                $escapedStatuses = array();
                foreach ($validStatuses as $status) {
                    $escaped = $db->real_escape_string($status);
                    $escapedStatuses[] = "'" . $escaped . "'";
                    error_log("Escaped status: '$status' -> '$escaped'");
                }
                
                $serviceList = implode(",", $escapedServices);
                $statusList = implode(",", $escapedStatuses);
                
                error_log("Service list: " . $serviceList);
                error_log("Status list: " . $statusList);
                
                $query = "SELECT * FROM tickets 
                          WHERE service IN ($serviceList) 
                          AND status IN ($statusList)
                          ORDER BY created_at ASC 
                          LIMIT 1";
                
                error_log("Executing MySQLi query: " . $query);
                
                $result = $db->query($query);
                
                if (!$result) {
                    error_log("MySQLi query failed: " . $db->error);
                    throw new Exception("Erreur SQL MySQLi: " . $db->error);
                }
                
                if ($result->num_rows > 0) {
                    $row = $result->fetch_assoc();
                    error_log("Found ticket with MySQLi: " . json_encode($row));
                    
                    // Mettre à jour le statut du ticket
                    $ticket_id = (int)$row['id'];
                    $update_query = "UPDATE tickets SET status = 'serving' WHERE id = $ticket_id";
                    
                    error_log("Updating ticket ID $ticket_id with query: " . $update_query);
                    
                    if (!$db->query($update_query)) {
                        error_log("MySQLi Update failed: " . $db->error);
                        throw new Exception("Erreur update MySQLi: " . $db->error);
                    }
                    
                    error_log("Ticket updated successfully");
                    
                    // Créer et retourner l'objet Ticket
                    if (method_exists('Ticket', 'fromArray')) {
                        error_log("Using Ticket::fromArray");
                        return Ticket::fromArray($row);
                    } else {
                        error_log("Creating stdClass ticket object");
                        $ticket = new stdClass();
                        $ticket->id = $row['id'];
                        $ticket->ticket_number = $row['ticket_number'];
                        $ticket->name = $row['name'];
                        $ticket->phone = $row['phone'];
                        $ticket->status = $row['status'];
                        $ticket->service = $row['service'];
                        $ticket->created_at = $row['created_at'];
                        
                        return $ticket;
                    }
                } else {
                    error_log("No row found with MySQLi");
                }
            }
            
            error_log("No tickets found for services: " . implode(", ", $services));
            return null;
            
        } catch (Exception $e) {
            error_log("serveNextTicketByServices Exception: " . $e->getMessage());
            throw new Exception("Erreur lors de l'appel du ticket: " . $e->getMessage());
        }
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

        /* Services Grid */
        .domains-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 16px;
        }

        .service-item {
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

        .service-item:hover {
            background: #f8f9fa;
            border-color: #6C63FF;
            box-shadow: 0 4px 12px rgba(108,99,255,0.1);
        }

        .service-checkbox {
            width: 18px;
            height: 18px;
            cursor: pointer;
            accent-color: #6C63FF;
        }

        .service-checkbox:checked + .service-label {
            color: #6C63FF;
            font-weight: 600;
        }

        .service-label {
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

            .service-item {
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