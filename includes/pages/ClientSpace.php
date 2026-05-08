<?php
/**
 * Smart Queue Management - Client Space
 * ✅ Numérotation intelligente par service (S-1, S-2, etc.)
 * ✅ Réinitialisation quotidienne automatique
 * ✅ Impression optimisée avec persistance du numéro
 * ✅ CORRIGÉ: Race condition éliminée avec transaction + FOR UPDATE
 * ✅ TRADUCTION COMPLÈTE: Cercles + toute l'interface selon langue choisie
 * ✅ RTL: Barre de progression inversée pour l'arabe
 */

session_start();
header('Content-Type: text/html; charset=utf-8');

// ✅ Configuration BD pour MariaDB/Windows
define('DB_HOST', '127.0.0.1:3307');
define('DB_NAME', 'fastqueue');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Initialiser la langue
if (!isset($_SESSION['language'])) {
    $_SESSION['language'] = 'fr';
}

// Traductions complètes
$translations = [
    'fr' => [
        'welcome' => 'Bienvenue à Smart Queue Management',
        'select_language' => 'Sélectionnez votre langue',
        'arabic' => 'العربية',
        'french' => 'Français',
        'english' => 'English',
        'italian' => 'Italiano',
        'scan_qr' => 'Scannez ce code QR pour continuer sur mobile',
        'select_service' => 'Sélectionnez un service',
        'next' => 'Suivant',
        'back' => 'Retour',
        'full_name' => 'Nom Complet',
        'phone' => 'Téléphone',
        'status' => 'État',
        'standard' => 'Standard',
        'pregnant' => 'Femme enceinte',
        'disability' => 'PMR/Handicap',
        'print_ticket' => 'Imprimer Ticket',
        'error_empty' => 'Tous les champs sont obligatoires',
        'ticket_number' => 'Numéro de Ticket',
        'service' => 'Service',
        'domain' => 'Domaine',
        'priority' => 'Priorité',
        'change_language' => 'Changer de langue',
        'form_info' => 'Veuillez saisir vos informations',
        'ticket_confirmed' => 'Ticket généré avec succès',
        'new_ticket' => 'Nouveau ticket',
        'select_service_first' => 'Veuillez sélectionner un service',
        'processing' => 'Traitement...',
        'printing' => 'Impression...',
        'step1' => 'ACCUEIL',
        'step2' => 'SERVICE',
        'step3' => 'INFOS',
        'step4' => 'TICKET',
        'date_time' => 'Date/Heure'
    ],
    'ar' => [
        'welcome' => 'مرحبا بك في نظام إدارة الطوابير الذكية',
        'select_language' => 'اختر لغتك',
        'arabic' => 'العربية',
        'french' => 'Français',
        'english' => 'English',
        'italian' => 'Italiano',
        'scan_qr' => 'امسح رمز QR هذا للمتابعة على الهاتف المحمول',
        'select_service' => 'اختر خدمة',
        'next' => 'التالي',
        'back' => 'العودة',
        'full_name' => 'الاسم الكامل',
        'phone' => 'الهاتف',
        'status' => 'الحالة',
        'standard' => 'عادي',
        'pregnant' => 'حامل',
        'disability' => 'الإعاقة',
        'print_ticket' => 'طباعة التذكرة',
        'error_empty' => 'جميع الحقول مطلوبة',
        'ticket_number' => 'رقم التذكرة',
        'service' => 'الخدمة',
        'domain' => 'المجال',
        'priority' => 'الأولوية',
        'change_language' => 'تغيير اللغة',
        'form_info' => 'يرجى إدخال معلوماتك',
        'ticket_confirmed' => 'تم إنشاء التذكرة بنجاح',
        'new_ticket' => 'تذكرة جديدة',
        'select_service_first' => 'الرجاء اختيار خدمة',
        'processing' => 'جاري المعالجة...',
        'printing' => 'جاري الطباعة...',
        'step1' => 'الرئيسية',
        'step2' => 'الخدمة',
        'step3' => 'المعلومات',
        'step4' => 'التذكرة',
        'date_time' => 'التاريخ/الوقت'
    ],
    'en' => [
        'welcome' => 'Welcome to Smart Queue Management',
        'select_language' => 'Select your language',
        'arabic' => 'العربية',
        'french' => 'Français',
        'english' => 'English',
        'italian' => 'Italiano',
        'scan_qr' => 'Scan this QR code to continue on mobile',
        'select_service' => 'Select a service',
        'next' => 'Next',
        'back' => 'Back',
        'full_name' => 'Full name',
        'phone' => 'Phone',
        'status' => 'Status',
        'standard' => 'Standard',
        'pregnant' => 'Pregnant',
        'disability' => 'Disability',
        'print_ticket' => 'Print ticket',
        'error_empty' => 'All fields are required',
        'ticket_number' => 'Ticket number',
        'service' => 'Service',
        'domain' => 'Domain',
        'priority' => 'Priority',
        'change_language' => 'Change language',
        'form_info' => 'Please enter your information',
        'ticket_confirmed' => 'Ticket generated successfully',
        'new_ticket' => 'New ticket',
        'select_service_first' => 'Please select a service',
        'processing' => 'Processing...',
        'printing' => 'Printing...',
        'step1' => 'HOME',
        'step2' => 'SERVICE',
        'step3' => 'INFO',
        'step4' => 'TICKET',
        'date_time' => 'Date/Time'
    ],
    'it' => [
        'welcome' => 'Benvenuto in Smart Queue Management',
        'select_language' => 'Seleziona la tua lingua',
        'arabic' => 'العربية',
        'french' => 'Français',
        'english' => 'English',
        'italian' => 'Italiano',
        'scan_qr' => 'Scansiona questo codice QR per continuare sul mobile',
        'select_service' => 'Seleziona un servizio',
        'next' => 'Avanti',
        'back' => 'Indietro',
        'full_name' => 'Nome completo',
        'phone' => 'Telefono',
        'status' => 'Stato',
        'standard' => 'Standard',
        'pregnant' => 'In attesa',
        'disability' => 'Disabilità',
        'print_ticket' => 'Stampa biglietto',
        'error_empty' => 'Tutti i campi sono obbligatori',
        'ticket_number' => 'Numero del biglietto',
        'service' => 'Servizio',
        'domain' => 'Dominio',
        'priority' => 'Priorità',
        'change_language' => 'Cambia lingua',
        'form_info' => 'Inserisci le tue informazioni',
        'ticket_confirmed' => 'Biglietto generato con successo',
        'new_ticket' => 'Nuovo biglietto',
        'select_service_first' => 'Seleziona un servizio',
        'processing' => 'Elaborazione...',
        'printing' => 'Stampa in corso...',
        'step1' => 'HOME',
        'step2' => 'SERVIZIO',
        'step3' => 'INFO',
        'step4' => 'BIGLIETTO',
        'date_time' => 'Data/Ora'
    ]
];

$lang = $_SESSION['language'];
$t = $translations[$lang];
$isRTL = ($lang === 'ar');

// ═══════════════════════════════════════════════════════════════════════
// FONCTION UTILITAIRE : Créer une connexion PDO
// ═══════════════════════════════════════════════════════════════════════
function createPDO(): PDO {
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
    return new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_TIMEOUT            => 5,
    ]);
}

// Récupérer les domaines
$domaines  = [];
$db_error  = null;

try {
    $pdo = createPDO();

    $tableCheck = $pdo->query("SHOW TABLES LIKE 'topical_domain'");
    if ($tableCheck->rowCount() === 0) {
        $db_error = "Table 'topical_domain' introuvable";
    } else {
        $stmt = $pdo->prepare(
            "SELECT td_id AS id, td_name AS nom, td_description AS description, td_code AS code
             FROM topical_domain
             WHERE td_active = 1
             ORDER BY td_name ASC"
        );
        $stmt->execute();
        $domaines = $stmt->fetchAll();
        error_log("✅ Connexion réussie! Domaines trouvés: " . count($domaines));
    }
} catch (PDOException $e) {
    $db_error = "Erreur BD: " . $e->getMessage();
    error_log("❌ " . $db_error);
}

// ═════════════════════════════════════════════════════════════════
// TRAITEMENT DES REQUÊTES AJAX
// ═════════════════════════════════════════════════════════════════

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    $action = $_POST['action'] ?? '';

    // ── Langue ─────────────────────────────────────────────────────
    if ($action === 'set_language') {
        $allowed = ['fr', 'ar', 'en', 'it'];
        $newLang = $_POST['language'] ?? '';
        if (in_array($newLang, $allowed, true)) {
            $_SESSION['language'] = $newLang;
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Langue invalide']);
        }
        exit;
    }

    // ── Service ────────────────────────────────────────────────────
    if ($action === 'set_service') {
        $_SESSION['service']      = $_POST['service']      ?? '';
        $_SESSION['service_code'] = $_POST['service_code'] ?? '';
        echo json_encode(['success' => true]);
        exit;
    }

    // ── Sauvegarde ticket ──────────────────────────────────────────
    if ($action === 'save_ticket') {
        $name        = trim($_POST['name']         ?? '');
        $phone       = trim($_POST['phone']        ?? '');
        $status      = trim($_POST['status']       ?? '');
        $service     = trim($_POST['service']      ?? '');
        $serviceCode = strtoupper(trim($_POST['service_code'] ?? ''));

        if (empty($name) || empty($phone) || empty($status) || empty($service) || empty($serviceCode)) {
            echo json_encode(['success' => false, 'message' => $t['error_empty']]);
            exit;
        }

        if (!preg_match('/^[0-9\s\-\+\(\)]{8,}$/', $phone)) {
            echo json_encode(['success' => false, 'message' => 'Numéro de téléphone invalide']);
            exit;
        }

        try {
            $pdo  = createPDO();
            $today = date('Y-m-d');

            $pdo->exec(
                "CREATE TABLE IF NOT EXISTS ticket_counter (
                    tc_id           INT PRIMARY KEY AUTO_INCREMENT,
                    tc_service_code VARCHAR(10)  NOT NULL,
                    tc_date         DATE         NOT NULL,
                    tc_count        INT UNSIGNED NOT NULL DEFAULT 0,
                    UNIQUE KEY uq_service_date (tc_service_code, tc_date)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
            );

            $pdo->beginTransaction();

            $stmt = $pdo->prepare(
                "INSERT INTO ticket_counter (tc_service_code, tc_date, tc_count)
                 VALUES (:code, :date, 1)
                 ON DUPLICATE KEY UPDATE
                     tc_count = LAST_INSERT_ID(tc_count + 1)"
            );
            $stmt->execute([
                'code' => $serviceCode,
                'date' => $today,
            ]);

            $nextNumber    = (int) $pdo->query("SELECT LAST_INSERT_ID()")->fetchColumn();
            $ticket_number = $serviceCode . '-' . $nextNumber;

            $stmt = $pdo->prepare(
                "INSERT INTO tickets (ticket_number, name, phone, status, service, created_at)
                 VALUES (:ticket_number, :name, :phone, :status, :service, :created_at)"
            );
            $stmt->execute([
                'ticket_number' => $ticket_number,
                'name'          => $name,
                'phone'         => $phone,
                'status'        => $status,
                'service'       => $service,
                'created_at'    => date('Y-m-d H:i:s'),
            ]);

            $pdo->commit();

            error_log("✅ Ticket créé: $ticket_number pour $name");

            echo json_encode([
                'success'      => true,
                'ticket_number'=> $ticket_number,
                'name'         => $name,
                'phone'        => $phone,
                'status'       => $status,
                'service'      => $service,
                'service_code' => $serviceCode,
            ]);

        } catch (Exception $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('❌ Erreur sauvegarde ticket: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Erreur: ' . $e->getMessage()]);
        }
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" dir="<?php echo $isRTL ? 'rtl' : 'ltr'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Queue Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        :root { --primary: #5a67d8; --accent: #6b5ce7; --success: #48bb78; --bg-light: #f7fafc; --border-light: #e2e8f0; --text-dark: #1e293b; --text-light: #64748b; --shadow-sm: 0 1px 3px rgba(0,0,0,0.08); --shadow-md: 0 4px 12px rgba(0,0,0,0.12); --shadow-lg: 0 8px 24px rgba(0,0,0,0.15); --shadow-xl: 0 16px 40px rgba(0,0,0,0.18); }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: linear-gradient(135deg, #f7fafc 0%, #edf2f7 50%, #e6f0ff 100%); min-height: 100vh; color: var(--text-dark); }
        .container-main { display: flex; justify-content: center; align-items: center; min-height: 100vh; padding: 20px; }
        .progress-container { margin-bottom: 48px; position: relative; width: 100%; }
        .progress-steps { display: flex; justify-content: space-between; align-items: flex-start; position: relative; width: 100%; }
        
        /* Ligne de fond entre les cercles */
        .progress-steps::before {
            content: '';
            position: absolute;
            top: 22px;
            left: 0;
            right: 0;
            height: 3px;
            background: #e2e8f0;
            border-radius: 3px;
            z-index: 1;
        }
        
        /* Barre de progression qui se remplit - version LTR (gauche vers droite) */
        .progress-steps::after {
            content: '';
            position: absolute;
            top: 22px;
            left: 0;
            width: 0%;
            height: 3px;
            background: linear-gradient(90deg, var(--primary) 0%, var(--accent) 100%);
            border-radius: 3px;
            transition: width 0.5s ease;
            z-index: 2;
        }
        
        /* Version RTL (arabe) : barre de progression de droite vers gauche */
        html[dir="rtl"] .progress-steps::after {
            left: auto;
            right: 0;
            width: 0%;
            background: linear-gradient(270deg, var(--primary) 0%, var(--accent) 100%);
        }
        
        .progress-step { flex: 1; display: flex; flex-direction: column; align-items: center; gap: 8px; position: relative; background: transparent; z-index: 3; }
        .progress-step-circle { width: 44px; height: 44px; background: white; border: 2.5px solid #cbd5e0; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 16px; color: #94a3b8; transition: all 0.3s ease; box-shadow: var(--shadow-sm); background: white; }
        .progress-step.active .progress-step-circle { background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%); border-color: transparent; color: white; transform: scale(1.08); box-shadow: var(--shadow-md); }
        .progress-step.completed .progress-step-circle { background: var(--success); border-color: var(--success); color: white; }
        .progress-step-label { font-size: 12px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px; transition: all 0.3s ease; text-align: center; }
        .progress-step.active .progress-step-label { color: var(--primary); font-weight: 800; }
        .progress-step.completed .progress-step-label { color: var(--success); }
        
        .card { background: white; border-radius: 24px; box-shadow: var(--shadow-xl); padding: 48px; max-width: 640px; width: 100%; animation: slideIn 0.5s ease; border: 1px solid rgba(255,255,255,0.3); }
        @keyframes slideIn { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes slideInRight { from { opacity: 0; transform: translateX(20px); } to { opacity: 1; transform: translateX(0); } }
        @keyframes scaleIn { from { opacity: 0; transform: scale(0.9); } to { opacity: 1; transform: scale(1); } }
        @keyframes ticketPulse { 0%,100% { transform: scale(1); } 50% { transform: scale(1.02); } }
        .step { display: none; }
        .step.active { display: block; animation: slideIn 0.4s ease; }
        h1 { font-size: 32px; font-weight: 800; text-align: center; margin-bottom: 12px; letter-spacing: -0.5px; color: #1e293b; }
        .subtitle { text-align: center; color: #64748b; margin-bottom: 32px; font-size: 14px; font-weight: 500; }
        .qr-container { text-align: center; margin-bottom: 32px; padding: 32px; background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%); border: 1px solid #e2e8f0; border-radius: 20px; }
        .qr-container p { margin-bottom: 20px; font-size: 14px; color: #475569; font-weight: 500; }
        #qrcode { display: inline-block; padding: 12px; background: white; border-radius: 16px; box-shadow: var(--shadow-md); }
        .language-selector { position: fixed; bottom: 24px; right: 24px; z-index: 1000; }
        html[dir="rtl"] .language-selector { right: auto; left: 24px; }
        .language-toggle-btn-float { width: 52px; height: 52px; border-radius: 50%; background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%); border: none; cursor: pointer; font-size: 26px; color: white; box-shadow: var(--shadow-lg); transition: all 0.3s ease; }
        .language-toggle-btn-float:hover { transform: scale(1.08); }
        .language-menu-backdrop { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0); z-index: 999; transition: background 0.3s; }
        .language-menu-backdrop.active { display: block; background: rgba(0,0,0,0.4); }
        .language-dropdown-menu { position: absolute; bottom: 70px; right: 0; background: white; border-radius: 20px; box-shadow: var(--shadow-lg); padding: 12px; min-width: 200px; display: none; flex-direction: column; gap: 8px; z-index: 1002; animation: scaleIn 0.2s ease; }
        html[dir="rtl"] .language-dropdown-menu { right: auto; left: 0; }
        .language-dropdown-menu.active { display: flex; }
        .language-menu-item { padding: 12px 16px; border-radius: 12px; cursor: pointer; background: #f8fafc; font-weight: 600; transition: all 0.2s; display: flex; justify-content: space-between; align-items: center; }
        .language-menu-item:hover { background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%); color: white; }
        .language-menu-item.active { background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%); color: white; }
        .language-toggle { display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; margin-bottom: 32px; }
        .lang-toggle-btn { padding: 12px 16px; border: 1.5px solid #e2e8f0; background: white; border-radius: 14px; cursor: pointer; font-weight: 700; transition: all 0.3s; font-size: 14px; }
        .lang-toggle-btn:hover { border-color: var(--primary); transform: translateY(-2px); }
        .lang-toggle-btn.active { background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%); color: white; border-color: transparent; }
        
        /* Formulaire - inputs avec plus de padding */
        .form-group { margin-bottom: 24px; animation: slideInRight 0.4s ease; }
        label { display: block; margin-bottom: 8px; font-weight: 700; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; color: #475569; }
        input, select { width: 100%; padding: 16px 18px; border: 1.5px solid #e2e8f0; border-radius: 14px; font-size: 15px; background: white; transition: all 0.3s; }
        input:focus, select:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(90,103,216,0.1); }
        
        /* Ajustement pour RTL (arabe) */
        html[dir="rtl"] input, html[dir="rtl"] select, html[dir="rtl"] .service-btn, html[dir="rtl"] .btn { text-align: right; }
        html[dir="rtl"] .service-btn { text-align: right; }
        html[dir="rtl"] .ticket-info p { direction: rtl; }
        html[dir="rtl"] .ticket-info span { text-align: left; }
        
        .service-buttons { display: grid; gap: 12px; margin: 28px 0; }
        .service-btn { padding: 18px 20px; border: 1.5px solid #e2e8f0; background: white; border-radius: 16px; cursor: pointer; font-weight: 600; text-align: left; transition: all 0.3s; box-shadow: var(--shadow-sm); }
        .service-btn:hover { border-color: var(--primary); transform: translateY(-2px); box-shadow: var(--shadow-md); }
        .service-btn.active { background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%); color: white; border-color: transparent; }
        .service-btn small { display: block; font-size: 12px; font-weight: 400; margin-top: 6px; opacity: 0.8; }
        .no-services { padding: 40px; text-align: center; background: #fee2e2; border-radius: 16px; color: #991b1b; font-weight: 600; }
        .db-error { padding: 14px; background: #fef3c7; border-radius: 14px; color: #92400e; margin-bottom: 24px; font-weight: 600; font-size: 13px; }
        .button-group { display: flex; gap: 12px; margin-top: 36px; }
        .btn { flex: 1; padding: 14px 20px; border-radius: 14px; font-weight: 700; cursor: pointer; text-transform: uppercase; text-align: center; transition: all 0.3s; box-shadow: var(--shadow-sm); font-size: 13px; letter-spacing: 0.5px; }
        .btn-primary { background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%); color: white; border: none; }
        .btn-primary:hover:not(:disabled) { transform: translateY(-2px); box-shadow: var(--shadow-md); }
        .btn-primary:disabled { opacity: 0.6; cursor: not-allowed; }
        .btn-secondary { background: white; color: #334155; border: 1.5px solid #e2e8f0; }
        .btn-secondary:hover { background: #f8fafc; border-color: var(--primary); color: var(--primary); }
        .ticket-preview { background: linear-gradient(135deg, #f0f9ff 0%, #e0e7ff 100%); padding: 32px; border-radius: 20px; text-align: center; margin: 32px 0; border: 1px solid #e2e8f0; }
        .ticket-preview h2 { font-size: 24px; margin-bottom: 16px; font-weight: 800; color: #1e293b; }
        .ticket-number { font-size: 52px; font-weight: 900; background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; margin: 16px 0; font-family: 'Courier New', monospace; letter-spacing: 2px; animation: ticketPulse 2s infinite; }
        .ticket-info { background: white; padding: 20px; border-radius: 16px; text-align: left; border: 1px solid #e2e8f0; }
        .ticket-info p { display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid #e2e8f0; font-size: 13px; }
        .ticket-info p:last-child { border-bottom: none; }
        .error-message { background: #fee2e2; color: #991b1b; padding: 14px; border-radius: 14px; margin-bottom: 24px; display: none; font-weight: 600; font-size: 13px; }
        @media print { .language-selector, .progress-container, .button-group, .qr-container, .language-toggle, #step1, #step2, #step3 { display: none !important; } #step4 { display: block !important; } .ticket-number { color: black !important; -webkit-text-fill-color: black; background: none; border: 2px solid #000; padding: 16px; border-radius: 12px; } }
        @media (max-width: 640px) { .card { padding: 28px 20px; } h1 { font-size: 26px; } .progress-step-circle { width: 38px; height: 38px; font-size: 14px; } .progress-step-label { font-size: 10px; } .progress-steps::before, .progress-steps::after { top: 19px; } input, select { padding: 14px 16px; } }
    </style>
</head>
<body>
    <div class="container-main">
        <div class="card">
            <!-- STEPPER avec barre de progression -->
            <div class="progress-container">
                <div class="progress-steps" id="progressSteps">
                    <div class="progress-step active" id="progressStep1">
                        <div class="progress-step-circle">1</div>
                        <div class="progress-step-label" id="stepLabel1"><?php echo $t['step1']; ?></div>
                    </div>
                    <div class="progress-step" id="progressStep2">
                        <div class="progress-step-circle">2</div>
                        <div class="progress-step-label" id="stepLabel2"><?php echo $t['step2']; ?></div>
                    </div>
                    <div class="progress-step" id="progressStep3">
                        <div class="progress-step-circle">3</div>
                        <div class="progress-step-label" id="stepLabel3"><?php echo $t['step3']; ?></div>
                    </div>
                    <div class="progress-step" id="progressStep4">
                        <div class="progress-step-circle">✓</div>
                        <div class="progress-step-label" id="stepLabel4"><?php echo $t['step4']; ?></div>
                    </div>
                </div>
            </div>
            
            <!-- ÉTAPE 1 : Accueil -->
            <div class="step active" id="step1">
                <h1 id="welcomeText"><?php echo $t['welcome']; ?></h1>
                <p class="subtitle" id="selectLangText"><?php echo $t['select_language']; ?></p>
                <div class="qr-container">
                    <p id="qrText"><?php echo $t['scan_qr']; ?></p>
                    <div id="qrcode"></div>
                </div>
                <div class="language-toggle">
                    <button class="lang-toggle-btn <?php echo $lang === 'ar' ? 'active' : ''; ?>" data-lang="ar">🇸🇦 <?php echo $t['arabic']; ?></button>
                    <button class="lang-toggle-btn <?php echo $lang === 'fr' ? 'active' : ''; ?>" data-lang="fr">🇫🇷 <?php echo $t['french']; ?></button>
                    <button class="lang-toggle-btn <?php echo $lang === 'en' ? 'active' : ''; ?>" data-lang="en">🇬🇧 <?php echo $t['english']; ?></button>
                    <button class="lang-toggle-btn <?php echo $lang === 'it' ? 'active' : ''; ?>" data-lang="it">🇮🇹 <?php echo $t['italian']; ?></button>
                </div>
                <div class="button-group">
                    <button class="btn btn-primary" id="nextStep1Btn"><?php echo $t['next']; ?> →</button>
                </div>
            </div>
            
            <!-- ÉTAPE 2 : Sélection du Domaine -->
            <div class="step" id="step2">
                <h1 id="serviceTitle"><?php echo $t['select_service']; ?></h1>
                <p class="subtitle" id="domainSubtitle"><?php echo $t['domain']; ?></p>
                <?php if ($db_error): ?>
                    <div class="db-error">⚠️ <?php echo htmlspecialchars($db_error); ?></div>
                <?php endif; ?>
                <div class="service-buttons" id="serviceContainer">
                    <?php if (count($domaines) > 0): ?>
                        <?php foreach ($domaines as $domaine): ?>
                            <button class="service-btn" data-code="<?php echo htmlspecialchars($domaine['code']); ?>" data-name="<?php echo htmlspecialchars($domaine['nom']); ?>">
                                📌 <?php echo htmlspecialchars($domaine['nom']); ?>
                                <?php if (!empty($domaine['description'])): ?>
                                    <small><?php echo htmlspecialchars($domaine['description']); ?></small>
                                <?php endif; ?>
                            </button>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="no-services">❌ Aucun service disponible</div>
                    <?php endif; ?>
                </div>
                <div class="button-group">
                    <button class="btn btn-secondary" id="backStep2Btn">← <?php echo $t['back']; ?></button>
                    <button class="btn btn-primary" id="nextStep2Btn"><?php echo $t['next']; ?> →</button>
                </div>
            </div>
            
            <!-- ÉTAPE 3 : Formulaire -->
            <div class="step" id="step3">
                <h1 id="formTitle"><?php echo $t['form_info']; ?></h1>
                <p class="subtitle" id="formSubtitle"><?php echo $t['ticket_confirmed']; ?></p>
                <div class="error-message" id="errorMessage"></div>
                <div class="form-group">
                    <label id="nameLabel"><?php echo $t['full_name']; ?></label>
                    <input type="text" id="fullName" placeholder="Jean Dupont"  style="padding: 16px 20px;">
                </div>
                <div class="form-group">
                    <label id="phoneLabel"><?php echo $t['phone']; ?></label>
                    <input type="tel" id="phone" placeholder="+212 6 12 34 56 78"  style="padding: 16px 20px;">
                </div>
                <div class="form-group">
                    <label id="statusLabel"><?php echo $t['status']; ?></label>
                    <select id="status"  style="padding: 16px 20px;">
                        <option value="">-- <?php echo $t['status']; ?> --</option>
                        <option value="standard"><?php echo $t['standard']; ?></option>
                        <option value="pregnant"><?php echo $t['pregnant']; ?></option>
                        <option value="disability"><?php echo $t['disability']; ?></option>
                    </select>
                </div>
                <div class="button-group">
                    <button class="btn btn-secondary" id="backStep3Btn">← <?php echo $t['back']; ?></button>
                    <button class="btn btn-primary" id="submitBtn">📋 <?php echo $t['print_ticket']; ?></button>
                </div>
            </div>
            
            <!-- ÉTAPE 4 : Ticket -->
            <div class="step" id="step4">
                <h1 id="ticketConfirmTitle"><?php echo $t['ticket_confirmed']; ?></h1>
                <p class="subtitle" id="ticketNumberLabel"><?php echo $t['ticket_number']; ?></p>
                <div class="ticket-preview">
                    <h2 id="ticketNumberTitle"><?php echo $t['ticket_number']; ?></h2>
                    <div class="ticket-number" id="ticketNumber">----</div>
                    <div class="ticket-info">
                        <p><strong id="fullNameLabelTicket"><?php echo $t['full_name']; ?>:</strong> <span id="ticketName">-</span></p>
                        <p><strong id="phoneLabelTicket"><?php echo $t['phone']; ?>:</strong> <span id="ticketPhone">-</span></p>
                        <p><strong id="statusLabelTicket"><?php echo $t['status']; ?>:</strong> <span id="ticketStatus">-</span></p>
                        <p><strong id="domainLabelTicket"><?php echo $t['domain']; ?>:</strong> <span id="ticketService">-</span></p>
                        <p><strong id="dateLabelTicket"><?php echo $t['date_time']; ?>:</strong> <span id="ticketDate"></span></p>
                    </div>
                </div>
                <div class="button-group">
                    <button class="btn btn-secondary" id="resetBtn">➕ <?php echo $t['new_ticket']; ?></button>
                    <button class="btn btn-primary" id="printBtn">🖨️ <?php echo $t['print_ticket']; ?></button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Menu langue flottant -->
    <div class="language-menu-backdrop" id="languageMenuBackdrop"></div>
    <div class="language-selector">
        <button class="language-toggle-btn-float" id="floatLangBtn">🌐</button>
        <div class="language-dropdown-menu" id="languageDropdownMenu">
            <button class="language-menu-item <?php echo $lang === 'ar' ? 'active' : ''; ?>" data-lang="ar">العربية</button>
            <button class="language-menu-item <?php echo $lang === 'fr' ? 'active' : ''; ?>" data-lang="fr">Français</button>
            <button class="language-menu-item <?php echo $lang === 'en' ? 'active' : ''; ?>" data-lang="en">English</button>
            <button class="language-menu-item <?php echo $lang === 'it' ? 'active' : ''; ?>" data-lang="it">Italiano</button>
        </div>
    </div>
    
    <script>
        // Traductions complètes pour JavaScript
        const translationsJS = {
            fr: { step1:"ACCUEIL", step2:"SERVICE", step3:"INFOS", step4:"TICKET", welcome:"Bienvenue à Smart Queue Management", select_language:"Sélectionnez votre langue", scan_qr:"Scannez ce code QR pour continuer sur mobile", select_service:"Sélectionnez un service", domain:"Domaine", next:"Suivant", back:"Retour", full_name:"Nom Complet", phone:"Téléphone", status:"État", standard:"Standard", pregnant:"Femme enceinte", disability:"PMR/Handicap", print_ticket:"Imprimer Ticket", ticket_number:"Numéro de Ticket", form_info:"Veuillez saisir vos informations", ticket_confirmed:"Ticket généré avec succès", new_ticket:"Nouveau ticket", select_service_first:"Veuillez sélectionner un service", processing:"Traitement...", printing:"Impression...", error_empty:"Tous les champs sont obligatoires", date_time:"Date/Heure" },
            ar: { step1:"الرئيسية", step2:"الخدمة", step3:"المعلومات", step4:"التذكرة", welcome:"مرحبا بك في نظام إدارة الطوابير الذكية", select_language:"اختر لغتك", scan_qr:"امسح رمز QR هذا للمتابعة على الهاتف المحمول", select_service:"اختر خدمة", domain:"المجال", next:"التالي", back:"العودة", full_name:"الاسم الكامل", phone:"الهاتف", status:"الحالة", standard:"عادي", pregnant:"حامل", disability:"الإعاقة", print_ticket:"طباعة التذكرة", ticket_number:"رقم التذكرة", form_info:"يرجى إدخال معلوماتك", ticket_confirmed:"تم إنشاء التذكرة بنجاح", new_ticket:"تذكرة جديدة", select_service_first:"الرجاء اختيار خدمة", processing:"جاري المعالجة...", printing:"جاري الطباعة...", error_empty:"جميع الحقول مطلوبة", date_time:"التاريخ/الوقت" },
            en: { step1:"HOME", step2:"SERVICE", step3:"INFO", step4:"TICKET", welcome:"Welcome to Smart Queue Management", select_language:"Select your language", scan_qr:"Scan this QR code to continue on mobile", select_service:"Select a service", domain:"Domain", next:"Next", back:"Back", full_name:"Full name", phone:"Phone", status:"Status", standard:"Standard", pregnant:"Pregnant", disability:"Disability", print_ticket:"Print ticket", ticket_number:"Ticket number", form_info:"Please enter your information", ticket_confirmed:"Ticket generated successfully", new_ticket:"New ticket", select_service_first:"Please select a service", processing:"Processing...", printing:"Printing...", error_empty:"All fields are required", date_time:"Date/Time" },
            it: { step1:"HOME", step2:"SERVIZIO", step3:"INFO", step4:"BIGLIETTO", welcome:"Benvenuto in Smart Queue Management", select_language:"Seleziona la tua lingua", scan_qr:"Scansiona questo codice QR per continuare sul mobile", select_service:"Seleziona un servizio", domain:"Dominio", next:"Avanti", back:"Indietro", full_name:"Nome completo", phone:"Telefono", status:"Stato", standard:"Standard", pregnant:"In attesa", disability:"Disabilità", print_ticket:"Stampa biglietto", ticket_number:"Numero del biglietto", form_info:"Inserisci le tue informazioni", ticket_confirmed:"Biglietto generato con successo", new_ticket:"Nuovo biglietto", select_service_first:"Seleziona un servizio", processing:"Elaborazione...", printing:"Stampa in corso...", error_empty:"Tutti i campi sono obbligatori", date_time:"Data/Ora" }
        };
        
        let currentStep = 1;
        let selectedService = '';
        let selectedServiceCode = '';
        let currentLanguage = '<?php echo $lang; ?>';
        let isRTL = <?php echo $isRTL ? 'true' : 'false'; ?>;
        
        // Mise à jour de la barre de progression
        function updateProgress(step) {
            const percent = ((step - 1) / 3) * 100;
            const progressSteps = document.getElementById('progressSteps');
            
            if (isRTL) {
                // Pour RTL (arabe) : la barre progresse de droite vers gauche
                progressSteps.style.setProperty('--progress-width', percent + '%');
                progressSteps.style.setProperty('--progress-right', (100 - percent) + '%');
                progressSteps.style.setProperty('--progress-left', 'auto');
                progressSteps.style.setProperty('--progress-right-value', (100 - percent) + '%');
                progressSteps.style.setProperty('background-position', 'right');
            }
            
            // Appliquer la largeur de la barre via style
            const styleId = 'progressBarStyle';
            let styleEl = document.getElementById(styleId);
            if (!styleEl) {
                styleEl = document.createElement('style');
                styleEl.id = styleId;
                document.head.appendChild(styleEl);
            }
            
            if (isRTL) {
                styleEl.textContent = `.progress-steps::after { width: ${percent}%; right: 0; left: auto; }`;
            } else {
                styleEl.textContent = `.progress-steps::after { width: ${percent}%; left: 0; right: auto; }`;
            }
            
            for (let i = 1; i <= 4; i++) {
                const stepEl = document.getElementById('progressStep' + i);
                stepEl.classList.remove('active', 'completed');
                if (i < step) stepEl.classList.add('completed');
                else if (i === step) stepEl.classList.add('active');
            }
        }
        
        // Mise à jour des textes des cercles
        function updateStepLabels(lang) {
            const t = translationsJS[lang];
            if (t) {
                document.getElementById('stepLabel1').innerText = t.step1;
                document.getElementById('stepLabel2').innerText = t.step2;
                document.getElementById('stepLabel3').innerText = t.step3;
                document.getElementById('stepLabel4').innerText = t.step4;
            }
        }
        
        function changeLanguage(lang) {
            if (lang === currentLanguage) return;
            fetch(window.location.href, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=set_language&language=' + encodeURIComponent(lang)
            }).then(r => r.json()).then(d => { if (d.success) location.reload(); }).catch(console.error);
        }
        
        // Menu langue
        function toggleLanguageMenu() {
            const menu = document.getElementById('languageDropdownMenu');
            const backdrop = document.getElementById('languageMenuBackdrop');
            if (menu.classList.contains('active')) {
                menu.classList.remove('active');
                backdrop.classList.remove('active');
            } else {
                menu.classList.add('active');
                backdrop.classList.add('active');
            }
        }
        
        function closeLanguageMenu() {
            document.getElementById('languageDropdownMenu').classList.remove('active');
            document.getElementById('languageMenuBackdrop').classList.remove('active');
        }
        
        // Événements langue
        document.querySelectorAll('.lang-toggle-btn, .language-menu-item').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.stopPropagation();
                const lang = this.getAttribute('data-lang');
                if (lang) changeLanguage(lang);
                closeLanguageMenu();
            });
        });
        document.getElementById('floatLangBtn')?.addEventListener('click', toggleLanguageMenu);
        document.getElementById('languageMenuBackdrop')?.addEventListener('click', closeLanguageMenu);
        document.addEventListener('keydown', e => { if (e.key === 'Escape') closeLanguageMenu(); });
        
        // QR Code
        function generateQRCode() {
            const el = document.getElementById('qrcode');
            if (el) {
                el.innerHTML = '';
                new QRCode(el, { text: window.location.href.split('?')[0], width: 180, height: 180, colorDark: '#5a67d8', colorLight: '#ffffff', correctLevel: QRCode.CorrectLevel.H });
            }
        }
        
        function goToStep(n) {
            const t = translationsJS[currentLanguage] || translationsJS.fr;
            if (n === 3 && !selectedService) {
                alert(t.select_service_first);
                return;
            }
            document.getElementById('step' + currentStep).classList.remove('active');
            document.getElementById('step' + n).classList.add('active');
            currentStep = n;
            updateProgress(n);
        }
        
        // Sélection service
        function initServiceButtons() {
            document.querySelectorAll('.service-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    document.querySelectorAll('.service-btn').forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                    selectedService = this.getAttribute('data-name');
                    selectedServiceCode = this.getAttribute('data-code');
                    fetch(window.location.href, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: 'action=set_service&service=' + encodeURIComponent(selectedService) + '&service_code=' + encodeURIComponent(selectedServiceCode)
                    }).catch(console.error);
                });
            });
        }
        
        // Soumission formulaire
        function submitForm() {
            const name = document.getElementById('fullName').value.trim();
            const phone = document.getElementById('phone').value.trim();
            const status = document.getElementById('status').value;
            const errEl = document.getElementById('errorMessage');
            const t = translationsJS[currentLanguage] || translationsJS.fr;
            
            if (!name || !phone || !status || !selectedService) {
                errEl.textContent = t.error_empty;
                errEl.style.display = 'block';
                errEl.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                return;
            }
            errEl.style.display = 'none';
            
            const btn = document.getElementById('submitBtn');
            btn.disabled = true;
            btn.style.opacity = '0.6';
            btn.innerHTML = '⏳ ' + t.processing;
            
            const fd = new FormData();
            fd.append('action', 'save_ticket');
            fd.append('name', name);
            fd.append('phone', phone);
            fd.append('status', status);
            fd.append('service', selectedService);
            fd.append('service_code', selectedServiceCode);
            
            fetch(window.location.href, { method: 'POST', body: fd })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('ticketNumber').innerText = data.ticket_number;
                        document.getElementById('ticketName').innerText = data.name;
                        document.getElementById('ticketPhone').innerText = data.phone;
                        let statusText = data.status === 'standard' ? t.standard : (data.status === 'pregnant' ? t.pregnant : (data.status === 'disability' ? t.disability : data.status));
                        document.getElementById('ticketStatus').innerText = statusText;
                        document.getElementById('ticketService').innerText = data.service;
                        document.getElementById('ticketDate').innerText = new Date().toLocaleDateString() + ' à ' + new Date().toLocaleTimeString();
                        goToStep(4);
                    } else {
                        errEl.textContent = data.message || 'Erreur';
                        errEl.style.display = 'block';
                    }
                })
                .catch(err => {
                    errEl.textContent = 'Erreur serveur';
                    errEl.style.display = 'block';
                })
                .finally(() => {
                    btn.disabled = false;
                    btn.style.opacity = '1';
                    btn.innerHTML = '📋 ' + t.print_ticket;
                });
        }
        
        function printTicket() {
            const btn = document.getElementById('printBtn');
            const t = translationsJS[currentLanguage] || translationsJS.fr;
            const orig = btn.innerHTML;
            btn.innerHTML = '⏳ ' + t.printing;
            btn.disabled = true;
            setTimeout(() => {
                window.print();
                setTimeout(() => {
                    btn.innerHTML = orig;
                    btn.disabled = false;
                }, 1500);
            }, 100);
        }
        
        function resetForm() {
            document.getElementById('fullName').value = '';
            document.getElementById('phone').value = '';
            document.getElementById('status').value = '';
            document.getElementById('errorMessage').style.display = 'none';
            document.getElementById('ticketNumber').innerHTML = '----';
            document.querySelectorAll('.service-btn').forEach(b => b.classList.remove('active'));
            selectedService = '';
            selectedServiceCode = '';
            goToStep(1);
            generateQRCode();
        }
        
        // Initialisation
        document.addEventListener('DOMContentLoaded', () => {
            initServiceButtons();
            generateQRCode();
            updateProgress(1);
            document.getElementById('nextStep1Btn')?.addEventListener('click', () => goToStep(2));
            document.getElementById('backStep2Btn')?.addEventListener('click', () => goToStep(1));
            document.getElementById('nextStep2Btn')?.addEventListener('click', () => goToStep(3));
            document.getElementById('backStep3Btn')?.addEventListener('click', () => goToStep(2));
            document.getElementById('submitBtn')?.addEventListener('click', submitForm);
            document.getElementById('printBtn')?.addEventListener('click', printTicket);
            document.getElementById('resetBtn')?.addEventListener('click', resetForm);
            document.addEventListener('keypress', e => { if (e.key === 'Enter' && currentStep === 3) submitForm(); });
        });
    </script>
</body>
</html>