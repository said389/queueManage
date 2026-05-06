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
     * Main page content — logique originale + design WaitingRoomDisplay
     */
    private function getPageContent() {
        global $gvPath;
        $service_name = 'Tous les services';
        try {
            $service_name = $this->device->getTdCode();
        } catch ( Exception $e ) {
            error_log( "⚠️ [Service Name] " . $e->getMessage() );
        }

        $alertMp3 = $gvPath . '/assets/audio/alert.mp3';
        $alertOgg = $gvPath . '/assets/audio/bell.ogg';

        return <<<HTML
<div class="wr-root">

    <!-- HEADER -->
    <header class="wr-header">
        <div class="wr-header-left">
            <div class="wr-logo-mark"></div>
            <div class="wr-header-text">
                <span class="wr-office-name"><i class="fas fa-rocket"></i> FastQueue</span>
                <span class="wr-tagline">Salle d'attente — {$service_name}</span>
            </div>
        </div>
        <div class="wr-header-right">
            <div class="wr-status-live">
                <span class="wr-live-dot"></span>
                <span>En direct</span>
            </div>
            <div class="wr-clock" id="wrClock">--:--:--</div>
        </div>
    </header>

    <!-- BODY -->
    <div class="wr-body">

        <!-- PANNEAU PRINCIPAL -->
        <div class="wr-hero-panel">
            <p class="wr-hero-label">NUMÉRO APPELÉ</p>

            <div class="wr-hero-box" id="ticketDisplay">
                <div class="wr-hero-idle" id="heroIdle">
                    <span class="wr-idle-icon">◎</span>
                    <p>En attente de ticket…</p>
                </div>
                <div class="wr-hero-content" id="heroContent" style="display:none;">
                    <span class="wr-hero-number" id="heroNumber">—</span>
                    <div class="wr-hero-details" id="heroDetails"></div>
                </div>
            </div>

            <!-- Rings d'animation -->
            <div class="wr-ring wr-ring-1" id="ring1"></div>
            <div class="wr-ring wr-ring-2" id="ring2"></div>
        </div>

        <!-- PANNEAU HISTORIQUE -->
        <div class="wr-history-panel">
            <div class="wr-history-header">
                <span class="wr-history-title">DERNIERS APPELS</span>
                <span class="wr-history-count" id="historyCount">0 ticket(s)</span>
            </div>
            <div class="wr-history-list" id="historyList">
                <div class="wr-history-empty" id="historyEmpty">
                    <span class="wr-empty-icon">◎</span>
                    <p>Aucun ticket appelé pour le moment</p>
                </div>
            </div>
        </div>

    </div>

    <!-- TICKER BAS -->
    <div class="wr-ticker-bar">
        <span class="wr-ticker-label">INFO</span>
        <div class="wr-ticker-track">
            <span class="wr-ticker-content">
                Bienvenue — Veuillez patienter, un agent va vous appeler.
                &nbsp;&nbsp;◆&nbsp;&nbsp;
                Merci de votre patience.
                &nbsp;&nbsp;◆&nbsp;&nbsp;
                Welcome — Please wait, an agent will call you shortly.
                &nbsp;&nbsp;◆&nbsp;&nbsp;
                Bienvenue — Veuillez patienter, un agent va vous appeler.
                &nbsp;&nbsp;◆&nbsp;&nbsp;
                Merci de votre patience.
                &nbsp;&nbsp;◆&nbsp;&nbsp;
                Welcome — Please wait, an agent will call you shortly.
            </span>
        </div>
        <span class="wr-status-dot" id="statusDot"></span>
        <span class="wr-update-time">MàJ : <span id="lastUpdate">--:--:--</span></span>
    </div>

</div>

<!-- Audio -->
<audio id="alertSound" preload="auto">
    <source src="$alertOgg" type="audio/ogg">
    <source src="$alertMp3" type="audio/mpeg">
</audio>

<script>
var API_ENDPOINT  = window.location.pathname;
var POLL_INTERVAL = 2000;
var lastTicketId  = 0;
var pollInterval  = null;
var history       = [];

console.log('Salle Attente - API Endpoint: ' + API_ENDPOINT);

/* ---- Horloge ---- */
function updateClock() {
    var n = new Date();
    var p = function(x){ return String(x).padStart(2,'0'); };
    document.getElementById('wrClock').textContent =
        p(n.getHours()) + ':' + p(n.getMinutes()) + ':' + p(n.getSeconds());
    var el = document.getElementById('lastUpdate');
    if (el) el.textContent = p(n.getHours()) + ':' + p(n.getMinutes()) + ':' + p(n.getSeconds());
}
setInterval(updateClock, 1000);
updateClock();

/* ---- Statut dot ---- */
function setStatus(ok) {
    var d = document.getElementById('statusDot');
    if (d) d.className = 'wr-status-dot ' + (ok ? 'online' : 'offline');
}

function initWaitingRoom() {
    console.log('Initializing waiting room display...');
    getCurrentTicket();
    startPolling();
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
        setStatus(response.ok);
        return response.json();
    })
    .then(function(data) {
        console.log('Data received:', data);
        if (data.success && data.ticket) {
            console.log('Ticket found: ' + data.ticket.ticket_number);
            displayTicket(data.ticket, false);
            lastTicketId = data.ticket.id || 0;
        } else {
            console.log('No ticket in response');
            clearDisplay();
        }
    })
    .catch(function(error) {
        setStatus(false);
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
        setStatus(response.ok);
        return response.json();
    })
    .then(function(data) {
        if (data.success && data.has_update && data.ticket) {
            console.log('New ticket detected: ' + data.ticket.ticket_number);
            displayTicket(data.ticket, true);
            playAlert();
            lastTicketId = data.ticket.id || 0;
        }
    })
    .catch(function(error) {
        setStatus(false);
        console.error('Error checking update: ' + error);
    });
}

function displayTicket(ticket, animate) {
    if (!ticket || !ticket.ticket_number) { clearDisplay(); return; }

    /* -- Héro -- */
    var heroIdle    = document.getElementById('heroIdle');
    var heroContent = document.getElementById('heroContent');
    var heroNum     = document.getElementById('heroNumber');
    var heroDetails = document.getElementById('heroDetails');
    var r1          = document.getElementById('ring1');
    var r2          = document.getElementById('ring2');

    heroIdle.style.display    = 'none';
    heroContent.style.display = 'flex';
    heroNum.textContent = escapeHtml(ticket.ticket_number);

    heroDetails.innerHTML =
        '<div class="wr-detail-item"><i class="fas fa-user"></i><span>' + escapeHtml(ticket.name || 'Client') + '</span></div>' +
        '<div class="wr-detail-item"><i class="fas fa-briefcase"></i><span>' + escapeHtml(ticket.service || 'Service') + '</span></div>' +
        '<div class="wr-detail-item"><i class="fas fa-arrow-right"></i><span>Rendez-vous à votre comptoir</span></div>';

    if (animate) {
        heroNum.classList.remove('wr-pop');
        void heroNum.offsetWidth;
        heroNum.classList.add('wr-pop');

        r1.classList.remove('wr-ring-burst');
        r2.classList.remove('wr-ring-burst');
        void r1.offsetWidth;
        r1.classList.add('wr-ring-burst');
        setTimeout(function(){ void r2.offsetWidth; r2.classList.add('wr-ring-burst'); }, 180);
    }

    /* -- Historique -- */
    history.unshift(ticket);
    if (history.length > 12) history = history.slice(0, 12);
    renderHistory();
}

function clearDisplay() {
    var heroIdle    = document.getElementById('heroIdle');
    var heroContent = document.getElementById('heroContent');
    if (heroIdle)    heroIdle.style.display    = 'flex';
    if (heroContent) heroContent.style.display = 'none';
}

function renderHistory() {
    var list  = document.getElementById('historyList');
    var empty = document.getElementById('historyEmpty');
    var count = document.getElementById('historyCount');

    var old = list.querySelectorAll('.wr-hist-item');
    old.forEach(function(el){ el.remove(); });

    if (history.length === 0) {
        empty.style.display = 'flex';
        count.textContent   = '0 ticket(s)';
        return;
    }
    empty.style.display = 'none';
    count.textContent   = history.length + ' ticket(s)';

    history.forEach(function(t, i) {
        var item = document.createElement('div');
        item.className = 'wr-hist-item' + (i === 0 ? ' wr-hist-latest' : '');
        item.innerHTML =
            '<div class="wr-hist-badge">' + (i === 0 ? '★' : (i + 1)) + '</div>' +
            '<div class="wr-hist-info">' +
                '<span class="wr-hist-ticket">' + escapeHtml(t.ticket_number) + '</span>' +
                '<span class="wr-hist-service">' + escapeHtml(t.service || '') + '</span>' +
            '</div>' +
            (i === 0 ? '<div class="wr-hist-now">ACTUEL</div>' : '');
        list.appendChild(item);
    });
}

function playAlert() {
    try {
        var audio = document.getElementById('alertSound');
        if (audio) { audio.currentTime = 0; audio.play(); }
    } catch (error) {
        console.warn('Audio error: ' + error);
    }
}

function escapeHtml(text) {
    var map = { '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;' };
    return String(text).replace(/[&<>"']/g, function(m){ return map[m]; });
}

document.addEventListener('DOMContentLoaded', function() { initWaitingRoom(); });
window.addEventListener('beforeunload', function() { if (pollInterval) clearInterval(pollInterval); });
</script>
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
    <link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@700;900&family=Barlow:wght@400;500&display=swap" rel="stylesheet">
    <style>
        *{margin:0;padding:0;box-sizing:border-box;}
        body{font-family:'Barlow',sans-serif;background:#0a0c14;display:flex;align-items:center;justify-content:center;min-height:100vh;padding:20px;}
        body::before{content:'';position:fixed;inset:0;background-image:linear-gradient(rgba(0,210,255,.025) 1px,transparent 1px),linear-gradient(90deg,rgba(0,210,255,.025) 1px,transparent 1px);background-size:60px 60px;pointer-events:none;}
        .error-container{text-align:center;background:rgba(255,255,255,.04);border:1px solid rgba(0,210,255,.15);padding:50px 40px;border-radius:20px;box-shadow:0 24px 60px rgba(0,0,0,.5);max-width:500px;position:relative;z-index:1;}
        .error-icon{font-size:72px;color:#e74c3c;margin-bottom:24px;text-shadow:0 0 30px rgba(231,76,60,.4);}
        h1{font-family:'Barlow Condensed',sans-serif;font-size:28px;font-weight:700;color:#fff;margin-bottom:12px;letter-spacing:2px;}
        .error-text{font-size:14px;color:rgba(255,255,255,.45);line-height:1.6;margin-bottom:24px;}
        .ip-badge{background:rgba(0,210,255,.1);padding:16px;border-radius:10px;font-family:'Barlow Condensed',monospace;color:#00d2ff;border:2px solid rgba(0,210,255,.3);font-weight:700;font-size:18px;letter-spacing:2px;word-break:break-all;text-shadow:0 0 10px rgba(0,210,255,.3);}
        .footer-text{margin-top:24px;font-size:12px;color:rgba(255,255,255,.2);letter-spacing:1px;}
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-icon"><i class="fas fa-exclamation-circle"></i></div>
        <h1>APPAREIL NON RECONNU</h1>
        <div class="error-text">
            <p>Cette interface n'a pas pu identifier votre appareil.</p>
            <p style="margin-top:12px;">Veuillez enregistrer l'adresse IP suivante dans la gestion des appareils :</p>
        </div>
        <div class="ip-badge">{$ip}</div>
        <div class="footer-text">La page se rafraîchira automatiquement dans 10 secondes.</div>
    </div>
    <script>setTimeout(function(){ location.reload(); }, 10000);</script>
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
    <link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@700;900&family=Barlow:wght@400;500&display=swap" rel="stylesheet">
    <style>
        *{margin:0;padding:0;box-sizing:border-box;}
        body{font-family:'Barlow',sans-serif;background:#0a0c14;display:flex;align-items:center;justify-content:center;min-height:100vh;padding:20px;}
        body::before{content:'';position:fixed;inset:0;background-image:linear-gradient(rgba(0,210,255,.025) 1px,transparent 1px),linear-gradient(90deg,rgba(0,210,255,.025) 1px,transparent 1px);background-size:60px 60px;pointer-events:none;}
        .error-container{text-align:center;background:rgba(255,255,255,.04);border:1px solid rgba(255,193,7,.2);padding:50px 40px;border-radius:20px;box-shadow:0 24px 60px rgba(0,0,0,.5);max-width:500px;position:relative;z-index:1;}
        .error-icon{font-size:72px;color:#ffc107;margin-bottom:24px;text-shadow:0 0 30px rgba(255,193,7,.4);}
        h1{font-family:'Barlow Condensed',sans-serif;font-size:28px;font-weight:700;color:#fff;margin-bottom:12px;letter-spacing:2px;}
        .error-text{font-size:14px;color:rgba(255,255,255,.45);line-height:1.6;}
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-icon"><i class="fas fa-cog"></i></div>
        <h1>ERREUR DE CONFIGURATION</h1>
        <div class="error-text">
            <p>Cet appareil est configuré pour l'affichage de comptoir, pas pour la salle d'attente.</p>
            <p style="margin-top:12px;">Veuillez reconfigurer l'appareil depuis l'interface d'administration.</p>
        </div>
    </div>
</body>
</html>
HTML;
    }

    /**
     * CSS / fonts — design WaitingRoomDisplay
     */
    private function getDesignCSS() {
        return <<<CSS
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@400;600;700;800;900&family=Barlow:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
/* ==============================
   RESET & BASE
   ============================== */
*, *::before, *::after { margin:0; padding:0; box-sizing:border-box; }
html, body {
    height:100%; width:100%;
    overflow:hidden;
    background:#0a0c14;
    font-family:'Barlow', sans-serif;
    color:#fff;
}
body::before {
    content:'';
    position:fixed; inset:0;
    background-image:
        linear-gradient(rgba(0,210,255,.025) 1px, transparent 1px),
        linear-gradient(90deg, rgba(0,210,255,.025) 1px, transparent 1px);
    background-size:60px 60px;
    pointer-events:none;
    z-index:0;
}

/* ==============================
   ROOT LAYOUT
   ============================== */
.wr-root {
    display:grid;
    grid-template-rows:70px 1fr 50px;
    height:100vh; width:100vw;
    position:relative; z-index:1;
}

/* ==============================
   HEADER
   ============================== */
.wr-header {
    display:flex;
    align-items:center;
    justify-content:space-between;
    padding:0 32px;
    background:rgba(255,255,255,.03);
    border-bottom:1px solid rgba(0,210,255,.12);
    backdrop-filter:blur(8px);
    position:relative; z-index:10;
}
.wr-header-left { display:flex; align-items:center; gap:16px; }
.wr-header-right { display:flex; align-items:center; gap:24px; }

.wr-logo-mark {
    width:36px; height:36px;
    background:linear-gradient(135deg,#00d2ff,#0066ff);
    border-radius:8px;
    position:relative; flex-shrink:0;
}
.wr-logo-mark::after {
    content:''; position:absolute; inset:7px;
    background:#0a0c14; border-radius:3px;
}

.wr-header-text { display:flex; flex-direction:column; line-height:1.2; }
.wr-office-name {
    font-family:'Barlow Condensed',sans-serif;
    font-size:18px; font-weight:700; color:#fff;
    display:flex; align-items:center; gap:8px;
}
.wr-office-name i { color:#00d2ff; }
.wr-tagline {
    font-size:11px; color:rgba(0,210,255,.55);
    letter-spacing:1px; text-transform:uppercase;
}

.wr-status-live {
    display:flex; align-items:center; gap:8px;
    background:rgba(40,167,69,.15);
    border:1px solid rgba(40,167,69,.3);
    padding:6px 14px; border-radius:20px;
    font-size:12px; font-weight:600; color:#2ecc71;
    letter-spacing:1px;
}
.wr-live-dot {
    width:8px; height:8px; background:#2ecc71;
    border-radius:50%;
    animation:livePulse 2s ease-in-out infinite;
}
@keyframes livePulse { 0%,100%{opacity:1} 50%{opacity:.4} }

.wr-clock {
    font-family:'Barlow Condensed',sans-serif;
    font-size:34px; font-weight:800;
    letter-spacing:4px; color:#00d2ff;
    text-shadow:0 0 20px rgba(0,210,255,.4);
}

/* ==============================
   BODY
   ============================== */
.wr-body {
    display:grid;
    grid-template-columns:1fr 380px;
    overflow:hidden;
}

/* ==============================
   HERO PANEL
   ============================== */
.wr-hero-panel {
    display:flex; flex-direction:column;
    align-items:center; justify-content:center;
    position:relative; overflow:hidden; gap:24px;
    background:radial-gradient(ellipse at 50% 55%,rgba(0,102,255,.13) 0%,transparent 68%);
    border-right:1px solid rgba(0,210,255,.09);
    padding:40px;
}

.wr-hero-label {
    font-family:'Barlow Condensed',sans-serif;
    font-size:13px; font-weight:700;
    letter-spacing:5px; color:rgba(0,210,255,.5);
    text-transform:uppercase;
}

.wr-hero-box {
    width:min(420px,80%); min-height:220px;
    display:flex; align-items:center; justify-content:center;
    background:linear-gradient(135deg,rgba(0,102,255,.15),rgba(0,210,255,.07));
    border:2px solid rgba(0,210,255,.22);
    border-radius:24px;
    box-shadow:0 0 0 1px rgba(0,210,255,.06),
               0 40px 80px rgba(0,0,0,.45),
               inset 0 1px 0 rgba(255,255,255,.05);
    position:relative; z-index:2;
    padding:32px 24px;
    transition:opacity .3s ease;
}

/* État idle */
.wr-hero-idle {
    display:flex; flex-direction:column;
    align-items:center; justify-content:center;
    gap:16px; color:rgba(255,255,255,.25); text-align:center;
}
.wr-idle-icon {
    font-size:40px; opacity:.4; display:block;
    animation:idlePulse 3s ease-in-out infinite;
}
.wr-hero-idle p { font-size:14px; letter-spacing:1px; }
@keyframes idlePulse { 0%,100%{opacity:.25} 50%{opacity:.6} }

/* État ticket */
.wr-hero-content {
    flex-direction:column; align-items:center;
    justify-content:center; gap:20px; text-align:center;
}

.wr-hero-number {
    font-family:'Barlow Condensed',sans-serif;
    font-size:clamp(72px,12vw,120px);
    font-weight:900; line-height:1;
    color:#fff; letter-spacing:-2px;
    text-shadow:0 0 60px rgba(0,210,255,.5);
}

.wr-hero-details {
    display:flex; flex-direction:column; gap:10px;
}
.wr-detail-item {
    display:flex; align-items:center; justify-content:center;
    gap:10px; font-size:14px; color:rgba(255,255,255,.55);
}
.wr-detail-item i { color:#00d2ff; width:16px; }

/* Rings */
.wr-ring {
    position:absolute; border-radius:50%;
    border:2px solid rgba(0,210,255,.15);
    opacity:0; pointer-events:none; z-index:1;
}
.wr-ring-1{width:320px;height:320px;}
.wr-ring-2{width:480px;height:480px;}

.wr-ring-burst { animation:ringBurst 1.2s ease-out forwards; }
.wr-ring-2.wr-ring-burst { animation-delay:.12s; }
@keyframes ringBurst {
    0%  {transform:scale(.4);opacity:.7}
    100%{transform:scale(1.35);opacity:0}
}

@keyframes popIn {
    0%  {transform:scale(.55);opacity:0}
    60% {transform:scale(1.12)}
    80% {transform:scale(.97)}
    100%{transform:scale(1);opacity:1}
}
.wr-pop { animation:popIn .5s cubic-bezier(.34,1.56,.64,1) forwards; }

/* ==============================
   HISTORY PANEL
   ============================== */
.wr-history-panel {
    display:flex; flex-direction:column;
    background:rgba(255,255,255,.02);
    overflow:hidden;
}

.wr-history-header {
    display:flex; align-items:center; justify-content:space-between;
    padding:20px 22px 14px;
    border-bottom:1px solid rgba(255,255,255,.06);
    flex-shrink:0;
}
.wr-history-title {
    font-family:'Barlow Condensed',sans-serif;
    font-size:12px; font-weight:700;
    letter-spacing:4px; color:rgba(255,255,255,.35);
}
.wr-history-count { font-size:11px; color:rgba(0,210,255,.5); letter-spacing:1px; }

.wr-history-list {
    flex:1; overflow-y:auto;
    padding:12px 14px;
    display:flex; flex-direction:column; gap:8px;
    scrollbar-width:thin;
    scrollbar-color:rgba(0,210,255,.2) transparent;
}
.wr-history-list::-webkit-scrollbar{width:4px;}
.wr-history-list::-webkit-scrollbar-thumb{background:rgba(0,210,255,.2);border-radius:2px;}

.wr-history-empty {
    flex:1; display:flex; flex-direction:column;
    align-items:center; justify-content:center;
    gap:12px; color:rgba(255,255,255,.2); text-align:center;
}
.wr-empty-icon{font-size:36px;opacity:.3;display:block;}
.wr-history-empty p{font-size:13px;line-height:1.5;}

/* Items historique */
.wr-hist-item {
    display:flex; align-items:center; gap:12px;
    padding:12px 14px; border-radius:12px;
    background:rgba(255,255,255,.03);
    border:1px solid rgba(255,255,255,.06);
    animation:slideIn .3s ease forwards;
}
@keyframes slideIn {
    from{transform:translateX(16px);opacity:0}
    to  {transform:translateX(0);opacity:1}
}
.wr-hist-latest {
    background:linear-gradient(135deg,rgba(0,102,255,.2),rgba(0,210,255,.1));
    border-color:rgba(0,210,255,.3);
    box-shadow:0 4px 20px rgba(0,102,255,.14);
}
.wr-hist-badge {
    width:32px; height:32px; border-radius:8px;
    background:rgba(255,255,255,.06);
    display:flex; align-items:center; justify-content:center;
    font-family:'Barlow Condensed',sans-serif;
    font-size:14px; font-weight:700; color:rgba(255,255,255,.4);
    flex-shrink:0;
}
.wr-hist-latest .wr-hist-badge{background:rgba(0,210,255,.2);color:#00d2ff;}
.wr-hist-info {
    flex:1; display:flex; flex-direction:column; line-height:1.3;
}
.wr-hist-ticket {
    font-family:'Barlow Condensed',sans-serif;
    font-size:26px; font-weight:800; color:#fff;
}
.wr-hist-latest .wr-hist-ticket{color:#00d2ff;}
.wr-hist-service{font-size:11px;color:rgba(255,255,255,.32);letter-spacing:1px;}
.wr-hist-now {
    font-family:'Barlow Condensed',sans-serif;
    font-size:10px; font-weight:700;
    letter-spacing:2px; color:#00d2ff;
    background:rgba(0,210,255,.12);
    padding:4px 8px; border-radius:6px; white-space:nowrap;
}

/* ==============================
   TICKER
   ============================== */
.wr-ticker-bar {
    display:flex; align-items:center;
    border-top:1px solid rgba(0,210,255,.1);
    background:rgba(0,0,0,.45);
    backdrop-filter:blur(8px);
    overflow:hidden;
    position:relative; z-index:10;
    gap:0;
}
.wr-ticker-label {
    flex-shrink:0; padding:0 18px;
    font-family:'Barlow Condensed',sans-serif;
    font-size:11px; font-weight:800; letter-spacing:3px;
    color:#0a0c14; background:#00d2ff;
    height:100%; display:flex; align-items:center;
}
.wr-ticker-track {
    flex:1; overflow:hidden; height:100%;
    display:flex; align-items:center; padding-left:18px;
}
.wr-ticker-content {
    white-space:nowrap; font-size:13px;
    color:rgba(255,255,255,.45); letter-spacing:.5px;
    animation:ticker 35s linear infinite;
}
@keyframes ticker{from{transform:translateX(0)}to{transform:translateX(-50%)}}

.wr-status-dot {
    width:8px; height:8px; border-radius:50%;
    background:#6c757d; flex-shrink:0; margin:0 12px;
    transition:background .4s,box-shadow .4s;
}
.wr-status-dot.online  {background:#28a745;box-shadow:0 0 6px rgba(40,167,69,.6);}
.wr-status-dot.offline {background:#e74c3c;box-shadow:0 0 6px rgba(231,76,60,.6);animation:blink 1s step-end infinite;}
@keyframes blink{50%{opacity:0}}

.wr-update-time {
    font-size:11px; color:rgba(255,255,255,.25);
    white-space:nowrap; padding-right:16px;
    flex-shrink:0; letter-spacing:.5px;
}

/* ==============================
   RESPONSIVE
   ============================== */
@media(max-width:900px){
    .wr-body{grid-template-columns:1fr;grid-template-rows:1fr 220px;}
    .wr-history-panel{border-top:1px solid rgba(0,210,255,.09);}
    .wr-hero-number{font-size:80px;}
    .wr-hero-box{width:260px;min-height:160px;}
    .wr-ring-1{width:220px;height:220px;}
    .wr-ring-2{width:340px;height:340px;}
}
@media(max-width:600px){
    .wr-header{padding:0 16px;}
    .wr-status-live{display:none;}
    .wr-clock{font-size:24px;letter-spacing:2px;}
    .wr-hero-panel{padding:20px;}
    .wr-hero-number{font-size:60px;}
}
</style>
CSS;
    }
}
?>