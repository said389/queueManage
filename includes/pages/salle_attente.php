<?php

/**
 * SalleAttentePage - Public Waiting Room Display
 * 
 * Real-time ticket tracking for public waiting room displays
 * 
 * @author said389
 */
class SalleAttentePage extends Page {

    private $device = null;
    private $device_ip = '';
    private $is_ajax = false;

    /**
     * Public page - accessible without authentication
     */
    public function canUse( $userLevel ) {
        return true;
    }

    /**
     * Check and register device by IP address
     */
    public function afterPermissionCheck() {
        // Get the calling device's IP address
        $this->device_ip = $this->getClientIp();
        
        error_log( "🔍 [Salle Attente] Device check - IP: " . $this->device_ip . " | Found: " . ($this->device ? 'YES' : 'NO') );
        
        // Attempt to retrieve device configuration from database
        try {
            $this->device = Device::fromDatabaseByIpAddress( $this->device_ip );
            error_log( "✅ [Salle Attente] Device found - IP: " . $this->device_ip );
        } catch ( Exception $e ) {
            error_log( "❌ [Salle Attente] Device not found - IP: " . $this->device_ip . " | Error: " . $e->getMessage() );
            $this->device = null;
        }
    }

    /**
     * Handle both regular requests and AJAX polling
     */
    public function execute() {
        // Check if this is an AJAX request
        $this->is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
        
        error_log( "🔄 [Salle Attente] Request type: " . ($this->is_ajax ? 'AJAX' : 'Regular') );
        
        if ( $this->is_ajax ) {
            return $this->handleAjaxRequest();
        }
        
        return true;
    }

    /**
     * Handle AJAX requests for real-time updates
     */
    private function handleAjaxRequest() {
        $action = gfPostVar('action', '');
        error_log( "🔄 [Salle Attente] AJAX Action: " . $action );
        
        switch ($action) {
            case 'get_current_ticket':
                return $this->ajaxGetCurrentTicket();
            case 'check_ticket_update':
                return $this->ajaxCheckTicketUpdate();
            default:
                $this->jsonResponse(false, 'Action inconnue', 400);
                return false;
        }
    }

    /**
     * Get the current ticket being displayed
     */
    private function ajaxGetCurrentTicket() {
        try {
            if ( !$this->device ) {
                error_log( "❌ [Salle Attente] Device not authenticated" );
                $this->jsonResponse(false, 'Appareil non reconnu', 403);
                return false;
            }

            $ticket_data = $this->getCurrentDisplayTicket();
            
            error_log( "✅ [Get Current Ticket] Success - Ticket: " . ($ticket_data ? $ticket_data['ticket_number'] : 'None') );
            
            $this->jsonResponse(true, 'Ticket récupéré', 200, array(
                'ticket' => $ticket_data,
                'device_ip' => $this->device_ip,
                'timestamp' => time()
            ));
            return false;

        } catch (Exception $e) {
            error_log( "⚠️ [Salle Attente] Error: " . $e->getMessage() );
            $this->jsonResponse(false, 'Erreur: ' . $e->getMessage(), 500);
            return false;
        }
    }

    /**
     * Check if there's an updated ticket since last check
     */
    private function ajaxCheckTicketUpdate() {
        try {
            $lastTicketId = (int)gfPostVar('last_ticket_id', 0);
            
            if ( !$this->device ) {
                $this->jsonResponse(false, 'Appareil non reconnu', 403);
                return false;
            }

            $ticket_data = $this->getCurrentDisplayTicket();
            $has_update = false;

            if ( $ticket_data && isset($ticket_data['id']) && $ticket_data['id'] !== $lastTicketId ) {
                $has_update = true;
                error_log( "🔔 [Ticket Update] New ticket detected: " . $ticket_data['ticket_number'] );
            }

            $this->jsonResponse(true, 'Vérification effectuée', 200, array(
                'has_update' => $has_update,
                'ticket' => $ticket_data,
                'timestamp' => time()
            ));
            return false;

        } catch (Exception $e) {
            error_log( "⚠️ [Check Update Error] " . $e->getMessage() );
            $this->jsonResponse(false, 'Erreur: ' . $e->getMessage(), 500);
            return false;
        }
    }

    /**
     * Get current ticket from display queue
     */
    private function getCurrentDisplayTicket() {
        try {
            $db = $this->getDatabase();
            if ( !$db ) {
                error_log( "❌ [Get Ticket] No database connection" );
                return null;
            }

            if ( $db instanceof PDO ) {
                // Get from display_main
                $query = "SELECT dm_id, dm_ticket, dm_desk FROM display_main ORDER BY dm_id DESC LIMIT 1";
                error_log( "🔍 [Query] Executing: " . $query );
                
                $stmt = $db->prepare( $query );
                if (!$stmt) {
                    error_log( "❌ [Query] Prepare failed: " . json_encode($db->errorInfo()) );
                    return null;
                }
                
                $executed = $stmt->execute();
                if (!$executed) {
                    error_log( "❌ [Query] Execute failed: " . json_encode($stmt->errorInfo()) );
                    return null;
                }
                
                $display = $stmt->fetch(PDO::FETCH_ASSOC);
                error_log( "🔍 [Display Main] Row count: " . $stmt->rowCount() );
                error_log( "🔍 [Display Main] Data: " . json_encode($display) );

                if ( !$display || empty($display['dm_ticket']) ) {
                    error_log( "⚠️ [Display Main] Empty or no record" );
                    return null;
                }

                // Get full ticket details
                $ticket_number = trim($display['dm_ticket']);
                error_log( "🎫 [Looking for] Ticket number: '" . $ticket_number . "'" );
                
                $ticket_query = "SELECT id, ticket_number, name, phone, status, service, created_at FROM tickets WHERE ticket_number = ?";
                error_log( "🔍 [Ticket Query] Looking for: " . $ticket_number );
                
                $ticket_stmt = $db->prepare( $ticket_query );
                if (!$ticket_stmt) {
                    error_log( "❌ [Ticket Query] Prepare failed" );
                    return null;
                }
                
                $ticket_stmt->bindValue(1, $ticket_number, PDO::PARAM_STR);
                $ticket_stmt->execute();
                $ticket = $ticket_stmt->fetch(PDO::FETCH_ASSOC);

                if ( $ticket ) {
                    error_log( "✅ [Ticket Found] " . json_encode($ticket) );
                } else {
                    error_log( "❌ [Ticket Not Found] for number: " . $ticket_number );
                }

                return $ticket;
            }

            return null;

        } catch (Exception $e) {
            error_log( "⚠️ [Salle Attente] Exception: " . $e->getMessage() );
            return null;
        }
    }

    /**
     * Get database connection
     */
    private function getDatabase() {
        try {
            // Use the Database class static method
            $db = Database::getConnection();
            error_log( "✅ [Salle Attente] Database connection obtained via Database class" );
            return $db;
        } catch (Exception $e) {
            error_log( "❌ [Salle Attente] Database connection failed: " . $e->getMessage() );
            return null;
        }
    }

    /**
     * Get real client IP address
     */
    private function getClientIp() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            return trim($ips[0]);
        }
        return $_SERVER['REMOTE_ADDR'];
    }

    /**
     * JSON response helper
     */
    private function jsonResponse($success, $message, $code = 200, $data = array()) {
        header('Content-Type: application/json; charset=UTF-8');
        http_response_code($code);
        echo json_encode(array_merge(
            array('success' => $success, 'message' => $message, 'code' => $code),
            $data
        ));
        exit;
    }

    /**
     * Escape HTML for safe output
     */
    private function escapeHtml($text) {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Generate the complete HTML page output
     */
    public function getOutput() {
        global $gvPath;

        // Check if device is recognized
        if ( !$this->device ) {
            $page = new WebPageOutput();
            $page->setHtmlPageTitle('Salle d\'attente - Appareil non reconnu');
            $page->addHtmlHeader('<meta http-equiv="refresh" content="10">');
            $page->setHtmlBodyContent($this->getUnknownDeviceContent());
            return $page;
        }

        // Check if device is configured for waiting room (desk_number = 0)
        try {
            $desk_number = (int)$this->device->getDeskNumber();
            if ( $desk_number !== 0 ) {
                $page = new WebPageOutput();
                $page->setHtmlPageTitle('Salle d\'attente - Erreur de configuration');
                $page->setHtmlBodyContent($this->getWrongConfigContent());
                return $page;
            }
        } catch ( Exception $e ) {
            error_log( "⚠️ [Device Check] " . $e->getMessage() );
        }

        $page = new WebPageOutput();
        $page->setHtmlPageTitle('Salle d\'attente - FastQueue');
        $page->addHtmlHeader($this->getDesignCSS());
        $page->setHtmlBodyContent($this->getPageContent());
        $page->importJquery();
        return $page;
    }

    /**
     * Main page content with real-time updates
     */
    private function getPageContent() {
        global $gvPath;
        $service_name = 'Tous les services';
        try {
            $service_name = $this->device->getTdCode();
        } catch ( Exception $e ) {
            error_log( "⚠️ [Service Name] " . $e->getMessage() );
        }
        
        return <<<HTML
<div class="waiting-room-container">
    <div class="header-section">
        <div class="logo-area">
            <h1><i class="fas fa-rocket"></i> FastQueue</h1>
            <p class="subtitle">Salle d'attente - {$service_name}</p>
        </div>
        <div class="status-indicator">
            <span class="pulse"></span>
            <span>En direct</span>
        </div>
    </div>

    <div class="main-display">
        <div class="ticket-display-area" id="ticketDisplay">
            <div class="no-ticket-message">
                <i class="fas fa-inbox"></i>
                <p>En attente de ticket...</p>
            </div>
        </div>

        <div class="information-panel">
            <div class="info-box">
                <div class="info-icon"><i class="fas fa-info-circle"></i></div>
                <div class="info-content">
                    <h3>Instructions</h3>
                    <p>Surveillez cette interface pour connaître le numéro de votre ticket.</p>
                    <p>Vous serez appelé dès que ce numéro s'affichera.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="footer-section">
        <p class="update-time">Dernière mise à jour : <span id="lastUpdate">--:--:--</span></p>
    </div>
</div>

<audio id="alertSound" preload="auto">
    <source src="{$gvPath}/assets/audio/alert.mp3" type="audio/mpeg">
</audio>

<script>
var API_ENDPOINT = window.location.pathname;
var POLL_INTERVAL = 2000;
var lastTicketId = 0;
var pollInterval = null;

console.log('Salle Attente - API Endpoint: ' + API_ENDPOINT);

function initWaitingRoom() {
    console.log('Initializing waiting room display...');
    getCurrentTicket();
    startPolling();
    updateTimestamp();
    setInterval(updateTimestamp, 1000);
}

function getCurrentTicket() {
    console.log('GET_CURRENT_TICKET - Fetching...');
    fetch(API_ENDPOINT, {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: 'action=get_current_ticket'
    })
    .then(function(response) {
        console.log('Response status: ' + response.status);
        return response.json();
    })
    .then(function(data) {
        console.log('Data received:', data);
        if (data.success && data.ticket) {
            console.log('Ticket found: ' + data.ticket.ticket_number);
            displayTicket(data.ticket);
            lastTicketId = data.ticket.id || 0;
        } else {
            console.log('No ticket in response');
            clearDisplay();
        }
    })
    .catch(function(error) {
        console.error('Error fetching ticket: ' + error);
        clearDisplay();
    });
}

function startPolling() {
    console.log('Starting polling every ' + POLL_INTERVAL + ' ms...');
    if (pollInterval) clearInterval(pollInterval);
    
    pollInterval = setInterval(function() {
        checkTicketUpdate();
    }, POLL_INTERVAL);
}

function checkTicketUpdate() {
    fetch(API_ENDPOINT, {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: 'action=check_ticket_update&last_ticket_id=' + lastTicketId
    })
    .then(function(response) {
        return response.json();
    })
    .then(function(data) {
        if (data.success && data.has_update && data.ticket) {
            console.log('New ticket detected: ' + data.ticket.ticket_number);
            displayTicket(data.ticket);
            playAlert();
            lastTicketId = data.ticket.id || 0;
        }
    })
    .catch(function(error) {
        console.error('Error checking update: ' + error);
    });
}

function displayTicket(ticket) {
    if (!ticket || !ticket.ticket_number) {
        clearDisplay();
        return;
    }

    var html = '<div class="ticket-content">' +
        '<div class="ticket-number-display">' + escapeHtml(ticket.ticket_number) + '</div>' +
        '<div class="ticket-details-display">' +
        '<div class="detail-item">' +
        '<i class="fas fa-user"></i>' +
        '<span>' + escapeHtml(ticket.name || 'Client') + '</span>' +
        '</div>' +
        '<div class="detail-item">' +
        '<i class="fas fa-briefcase"></i>' +
        '<span>' + escapeHtml(ticket.service || 'Service') + '</span>' +
        '</div>' +
        '<div class="detail-item">' +
        '<i class="fas fa-chair"></i>' +
        '<span>Rendez-vous a votre compteur</span>' +
        '</div>' +
        '</div>' +
        '</div>';

    var display = document.getElementById('ticketDisplay');
    if (display) {
        display.style.opacity = '0';
        setTimeout(function() {
            display.innerHTML = html;
            display.style.opacity = '1';
        }, 300);
    }
}

function clearDisplay() {
    var html = '<div class="no-ticket-message">' +
        '<i class="fas fa-inbox"></i>' +
        '<p>En attente de ticket...</p>' +
        '</div>';

    var display = document.getElementById('ticketDisplay');
    if (display) {
        if (display.innerHTML.indexOf('no-ticket-message') === -1) {
            display.style.opacity = '0';
            setTimeout(function() {
                display.innerHTML = html;
                display.style.opacity = '1';
            }, 300);
        }
    }
}

function playAlert() {
    try {
        var audio = document.getElementById('alertSound');
        if (audio) {
            audio.currentTime = 0;
            if (audio.play) {
                audio.play();
            }
        }
    } catch (error) {
        console.warn('Audio error: ' + error);
    }
}

function updateTimestamp() {
    var now = new Date();
    var h = (now.getHours() < 10 ? '0' : '') + now.getHours();
    var m = (now.getMinutes() < 10 ? '0' : '') + now.getMinutes();
    var s = (now.getSeconds() < 10 ? '0' : '') + now.getSeconds();
    var element = document.getElementById('lastUpdate');
    if (element) {
        element.textContent = h + ':' + m + ':' + s;
    }
}

function escapeHtml(text) {
    var map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return String(text).replace(/[&<>"']/g, function(m) {
        return map[m];
    });
}

document.addEventListener('DOMContentLoaded', function() {
    initWaitingRoom();
});

window.addEventListener('beforeunload', function() {
    if (pollInterval) {
        clearInterval(pollInterval);
    }
});
</script>

<style>
* { margin: 0; padding: 0; box-sizing: border-box; }

body {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
    color: #fff;
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.waiting-room-container {
    width: 100%;
    max-width: 1200px;
    background: white;
    border-radius: 20px;
    box-shadow: 0 24px 60px rgba(0,0,0,0.3);
    overflow: hidden;
    display: flex;
    flex-direction: column;
    min-height: 100vh;
    color: #1a1a2e;
}

.header-section {
    background: linear-gradient(135deg, #6C63FF, #8B82FF);
    padding: 40px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 20px;
    color: white;
}

.logo-area h1 {
    font-size: 48px;
    font-weight: 800;
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    gap: 16px;
}

.logo-area i { font-size: 52px; }
.subtitle { font-size: 18px; font-weight: 500; opacity: 0.95; }

.status-indicator {
    display: flex;
    align-items: center;
    gap: 10px;
    background: rgba(255,255,255,0.2);
    padding: 12px 20px;
    border-radius: 30px;
    font-weight: 600;
    font-size: 14px;
}

.pulse {
    display: inline-block;
    width: 10px;
    height: 10px;
    background: #2ecc71;
    border-radius: 50%;
    animation: pulse-animation 2s infinite;
}

@keyframes pulse-animation {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}

.main-display {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 60px 40px;
    gap: 40px;
}

.ticket-display-area {
    width: 100%;
    max-width: 600px;
    aspect-ratio: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #f8f9fa, #fff);
    border-radius: 20px;
    border: 3px solid #e0e0e0;
    position: relative;
    overflow: hidden;
    opacity: 1;
    transition: opacity 0.3s ease;
}

.no-ticket-message {
    text-align: center;
    color: #999;
}

.no-ticket-message i {
    font-size: 64px;
    margin-bottom: 20px;
    opacity: 0.5;
}

.no-ticket-message p {
    font-size: 20px;
    font-weight: 500;
}

.ticket-content {
    text-align: center;
    animation: slideIn 0.5s ease;
}

@keyframes slideIn {
    from { opacity: 0; transform: scale(0.8); }
    to { opacity: 1; transform: scale(1); }
}

.ticket-number-display {
    font-size: 120px;
    font-weight: 800;
    background: linear-gradient(135deg, #6C63FF, #8B82FF);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    line-height: 1;
    margin-bottom: 20px;
}

.ticket-details-display {
    color: #1a1a2e;
    font-size: 18px;
    margin-top: 20px;
}

.detail-item {
    margin: 8px 0;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
}

.detail-item i {
    color: #6C63FF;
    width: 24px;
    font-size: 18px;
}

.information-panel {
    width: 100%;
    display: flex;
    justify-content: center;
    gap: 20px;
}

.info-box {
    background: #f8f9fa;
    border-left: 4px solid #6C63FF;
    padding: 20px;
    border-radius: 12px;
    max-width: 500px;
    display: flex;
    gap: 16px;
}

.info-icon {
    flex-shrink: 0;
    width: 40px;
    height: 40px;
    background: rgba(108, 99, 255, 0.1);
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #6C63FF;
    font-size: 20px;
}

.info-content h3 {
    font-size: 14px;
    font-weight: 700;
    color: #1a1a2e;
    margin-bottom: 8px;
}

.info-content p {
    font-size: 13px;
    color: #666;
    line-height: 1.6;
    margin-bottom: 6px;
}

.info-content p:last-child { margin-bottom: 0; }

.footer-section {
    background: #f8f9fa;
    padding: 20px 40px;
    text-align: center;
    border-top: 1px solid #eee;
}

.update-time {
    font-size: 12px;
    color: #999;
}

@media (max-width: 768px) {
    .waiting-room-container { min-height: auto; }
    .header-section { padding: 30px 20px; }
    .logo-area h1 { font-size: 32px; }
    .logo-area i { font-size: 36px; }
    .main-display { padding: 40px 20px; gap: 30px; }
    .ticket-number-display { font-size: 72px; }
    .info-box { max-width: 100%; }
    .footer-section { padding: 16px 20px; }
}

@media (max-width: 480px) {
    .header-section { padding: 20px; flex-direction: column; text-align: center; }
    .status-indicator { align-self: center; }
    .logo-area h1 { font-size: 28px; }
    .logo-area i { font-size: 28px; }
    .main-display { padding: 30px 16px; }
    .ticket-display-area { min-height: 300px; }
    .ticket-number-display { font-size: 56px; }
}
</style>
HTML;
    }

    /**
     * Error message for unrecognized devices
     */
    private function getUnknownDeviceContent() {
        $ip = $this->escapeHtml($_SERVER['REMOTE_ADDR']);
        
        return <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appareil non reconnu</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; 
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            display: flex; 
            align-items: center; 
            justify-content: center; 
            min-height: 100vh;
            padding: 20px;
        }
        .error-container { 
            text-align: center; 
            background: white; 
            padding: 50px 40px;
            border-radius: 16px; 
            box-shadow: 0 24px 60px rgba(0,0,0,0.3);
            max-width: 500px;
        }
        .error-icon { 
            font-size: 72px; 
            color: #ff6b6b; 
            margin-bottom: 24px; 
        }
        h1 { 
            font-size: 28px; 
            color: #1a1a2e; 
            margin-bottom: 12px;
            font-weight: 700;
        }
        .error-text {
            font-size: 14px;
            color: #666;
            line-height: 1.6;
            margin-bottom: 24px;
        }
        .ip-badge { 
            background: #f8f9fa; 
            padding: 16px;
            border-radius: 8px; 
            font-family: 'Courier New', monospace;
            color: #1a1a2e;
            border: 2px solid #6C63FF;
            font-weight: 600;
            font-size: 16px;
            word-break: break-all;
        }
        .footer-text {
            margin-top: 24px;
            font-size: 12px;
            color: #999;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-icon"><i class="fas fa-exclamation-circle"></i></div>
        <h1>Appareil non reconnu</h1>
        <div class="error-text">
            <p>Cette interface n'a pas pu identifier votre appareil.</p>
            <p style="margin-top: 12px;">Veuillez enregistrer l'adresse IP suivante dans la gestion des appareils :</p>
        </div>
        <div class="ip-badge">{$ip}</div>
        <div class="footer-text">
            <p>La page se rafraichira automatiquement dans 10 secondes.</p>
        </div>
    </div>
    <script>
        setTimeout(function() {
            location.reload();
        }, 10000);
    </script>
</body>
</html>
HTML;
    }

    /**
     * Error message for wrong device configuration
     */
    private function getWrongConfigContent() {
        return <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Erreur de configuration</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; 
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            display: flex; 
            align-items: center; 
            justify-content: center; 
            min-height: 100vh;
            padding: 20px;
        }
        .error-container { 
            text-align: center; 
            background: white; 
            padding: 50px 40px;
            border-radius: 16px; 
            box-shadow: 0 24px 60px rgba(0,0,0,0.3);
            max-width: 500px;
        }
        .error-icon { 
            font-size: 72px; 
            color: #ffc107; 
            margin-bottom: 24px; 
        }
        h1 { 
            font-size: 28px; 
            color: #1a1a2e; 
            margin-bottom: 12px;
            font-weight: 700;
        }
        .error-text {
            font-size: 14px;
            color: #666;
            line-height: 1.6;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-icon"><i class="fas fa-cog"></i></div>
        <h1>Erreur de configuration</h1>
        <div class="error-text">
            <p>Cet appareil est configure pour l'affichage de compteur, pas pour la salle d'attente.</p>
            <p style="margin-top: 12px;">Veuillez reconfigurer l'appareil depuis l'interface d'administration.</p>
        </div>
    </div>
</body>
</html>
HTML;
    }

    /**
     * CSS styling for waiting room
     */
    private function getDesignCSS() {
        return '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"><link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">';
    }
}

?>