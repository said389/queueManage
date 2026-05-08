<?php
/**
 * Smart Queue Management - Client Space
 * ✅ Numérotation intelligente par service (S-1, S-2, etc.)
 * ✅ Réinitialisation quotidienne automatique
 * ✅ Impression optimisée avec persistance du numéro
 * ✅ CORRIGÉ: Race condition éliminée avec transaction + FOR UPDATE
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

// Traductions
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
    ],
    'en' => [
        'welcome' => 'Welcome to Smart Queue Management',
        'select_language' => 'Select Your Language',
        'arabic' => 'العربية',
        'french' => 'Français',
        'english' => 'English',
        'italian' => 'Italiano',
        'scan_qr' => 'Scan this QR code to continue on mobile',
        'select_service' => 'Select a Service',
        'next' => 'Next',
        'back' => 'Back',
        'full_name' => 'Full Name',
        'phone' => 'Phone',
        'status' => 'Status',
        'standard' => 'Standard',
        'pregnant' => 'Pregnant Woman',
        'disability' => 'Person with Disability',
        'print_ticket' => 'Print Ticket',
        'error_empty' => 'All fields are required',
        'ticket_number' => 'Ticket Number',
        'service' => 'Service',
        'domain' => 'Domain',
        'priority' => 'Priority',
        'change_language' => 'Change Language',
        'form_info' => 'Please enter your information',
        'ticket_confirmed' => 'Ticket generated successfully',
    ],
    'it' => [
        'welcome' => 'Benvenuto in Smart Queue Management',
        'select_language' => 'Seleziona la tua lingua',
        'arabic' => 'العربية',
        'french' => 'Français',
        'english' => 'English',
        'italian' => 'Italiano',
        'scan_qr' => 'Scansiona questo codice QR per continuare su mobile',
        'select_service' => 'Seleziona un servizio',
        'next' => 'Avanti',
        'back' => 'Indietro',
        'full_name' => 'Nome Completo',
        'phone' => 'Telefono',
        'status' => 'Stato',
        'standard' => 'Standard',
        'pregnant' => 'Donna Incinta',
        'disability' => 'Persona Disabile',
        'print_ticket' => 'Stampa Biglietto',
        'error_empty' => 'Tutti i campi sono obbligatori',
        'ticket_number' => 'Numero di Biglietto',
        'service' => 'Servizio',
        'domain' => 'Dominio',
        'priority' => 'Priorità',
        'change_language' => 'Cambia Lingua',
        'form_info' => 'Inserisci le tue informazioni',
        'ticket_confirmed' => 'Biglietto generato con successo',
    ]
];

$lang = $_SESSION['language'];
$t = $translations[$lang];

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

        // Validation des champs
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

            // ─────────────────────────────────────────────────────────────
            // 1️⃣  Créer la table ticket_counter si elle n'existe pas encore
            //     (unicité sur service_code + date pour la réinitialisation
            //      quotidienne automatique)
            // ─────────────────────────────────────────────────────────────
            $pdo->exec(
                "CREATE TABLE IF NOT EXISTS ticket_counter (
                    tc_id           INT PRIMARY KEY AUTO_INCREMENT,
                    tc_service_code VARCHAR(10)  NOT NULL,
                    tc_date         DATE         NOT NULL,
                    tc_count        INT UNSIGNED NOT NULL DEFAULT 0,
                    UNIQUE KEY uq_service_date (tc_service_code, tc_date)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
            );

            // ─────────────────────────────────────────────────────────────
            // 2️⃣  Générer le numéro de ticket SANS race condition
            //
            //     Stratégie :
            //     a) Démarrer une transaction InnoDB
            //     b) INSERT ... ON DUPLICATE KEY UPDATE  →  incrémente
            //        atomiquement le compteur
            //     c) Lire la valeur résultante avec LAST_INSERT_ID()
            //        (MariaDB/MySQL renvoie la nouvelle valeur via
            //         ON DUPLICATE KEY UPDATE tc_count = LAST_INSERT_ID(tc_count + 1))
            //     d) Construire le numéro, insérer dans tickets
            //     e) COMMIT
            //
            //     → Aucune lecture préalable du MAX, donc zéro doublon
            //       même avec des requêtes simultanées.
            // ─────────────────────────────────────────────────────────────
            $pdo->beginTransaction();

            // Incrément atomique : si la ligne n'existe pas → tc_count = 1
            //                       si elle existe         → tc_count += 1
            // LAST_INSERT_ID(expr) stocke expr dans le contexte de connexion
            // et le rend disponible via SELECT LAST_INSERT_ID()
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

            // Récupérer la valeur atomiquement allouée à cette connexion
            $nextNumber    = (int) $pdo->query("SELECT LAST_INSERT_ID()")->fetchColumn();
            $ticket_number = $serviceCode . '-' . $nextNumber;

            // Insérer le ticket
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
<html lang="<?php echo $lang; ?>" dir="<?php echo $lang === 'ar' ? 'rtl' : 'ltr'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Queue Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        :root {
            --primary: #5a67d8;
            --primary-dark: #4c51bf;
            --primary-light: #667eea;
            --accent: #6b5ce7;
            --accent-dark: #5a4fd1;
            --success: #48bb78;
            --bg-light: #f7fafc;
            --bg-lighter: #edf2f7;
            --bg-dark: #2d3748;
            --border-light: #cbd5e0;
            --border-med: #a0aec0;
            --text-dark: #1a202c;
            --text-light: #718096;
            --text-lighter: #a0aec0;
            --shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.08);
            --shadow-md: 0 4px 12px rgba(0, 0, 0, 0.12);
            --shadow-lg: 0 8px 24px rgba(0, 0, 0, 0.15);
            --shadow-xl: 0 16px 40px rgba(0, 0, 0, 0.18);
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Helvetica Neue', sans-serif;
            background: linear-gradient(135deg, #f7fafc 0%, #edf2f7 50%, #e6f0ff 100%);
            min-height: 100vh;
            color: var(--text-dark);
            line-height: 1.6;
            overflow-x: hidden;
        }
        
        .container-main {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }
        
        /* ═══════════════════════════════════════════════════ */
        /* STEPPER                                            */
        /* ═══════════════════════════════════════════════════ */
        .progress-container { margin-bottom: 48px; }
        
        .progress-bar {
            width: 100%;
            height: 2px;
            background: var(--border-light);
            border-radius: 1px;
            overflow: hidden;
            margin-bottom: 28px;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--primary) 0%, var(--accent) 100%);
            border-radius: 1px;
            transition: width 0.6s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 0 12px rgba(90, 103, 216, 0.4);
        }
        
        .progress-steps {
            display: flex;
            justify-content: space-between;
            gap: 16px;
        }
        
        .progress-step {
            flex: 1;
            position: relative;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
        }
        
        .progress-step-circle {
            width: 40px;
            height: 40px;
            background: white;
            border: 2px solid var(--border-light);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            font-weight: 700;
            color: var(--text-light);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: var(--shadow-sm);
            position: relative;
            z-index: 2;
        }
        
        .progress-step.active .progress-step-circle {
            background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
            border-color: transparent;
            color: white;
            box-shadow: var(--shadow-md), 0 0 20px rgba(90, 103, 216, 0.3);
            transform: scale(1.1);
        }
        
        .progress-step.completed .progress-step-circle {
            background: var(--success);
            border-color: var(--success);
            color: white;
        }
        
        .progress-step-label {
            font-size: 12px;
            font-weight: 600;
            color: var(--text-light);
            text-align: center;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            opacity: 0.8;
            transition: all 0.3s ease;
        }
        
        .progress-step.active .progress-step-label,
        .progress-step.completed .progress-step-label {
            opacity: 1;
            color: var(--text-dark);
            font-weight: 700;
        }
        
        .progress-step:not(:last-child)::after {
            content: '';
            position: absolute;
            top: 20px;
            left: 50%;
            right: -50%;
            height: 2px;
            background: var(--border-light);
            z-index: 1;
            transition: background 0.3s ease;
        }
        
        .progress-step.completed:not(:last-child)::after {
            background: var(--success);
        }
        
        /* ═══════════════════════════════════════════════════ */
        /* CARD                                               */
        /* ═══════════════════════════════════════════════════ */
        .card {
            background: white;
            border-radius: 20px;
            box-shadow: var(--shadow-xl);
            padding: 56px;
            max-width: 640px;
            width: 100%;
            animation: slideIn 0.6s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid rgba(255, 255, 255, 0.8);
        }
        
        @keyframes slideIn {
            from { opacity: 0; transform: translateY(24px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        
        @keyframes slideInRight {
            from { opacity: 0; transform: translateX(20px); }
            to   { opacity: 1; transform: translateX(0); }
        }
        
        @keyframes scaleIn {
            from { opacity: 0; transform: scale(0.8); }
            to   { opacity: 1; transform: scale(1); }
        }
        
        @keyframes ticketPulse {
            0%, 100% { transform: scale(1); }
            50%       { transform: scale(1.02); }
        }
        
        .step { display: none; }
        .step.active {
            display: block;
            animation: slideIn 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        h1 {
            font-size: 36px;
            font-weight: 800;
            color: var(--text-dark);
            text-align: center;
            margin-bottom: 12px;
            letter-spacing: -0.8px;
        }
        
        .subtitle {
            text-align: center;
            color: var(--text-light);
            margin-bottom: 36px;
            font-size: 15px;
            font-weight: 500;
        }
        
        /* ═══════════════════════════════════════════════════ */
        /* QR CODE                                            */
        /* ═══════════════════════════════════════════════════ */
        .qr-container {
            text-align: center;
            margin-bottom: 36px;
            padding: 36px;
            background: linear-gradient(135deg, var(--bg-light) 0%, var(--bg-lighter) 100%);
            border: 1px solid var(--border-light);
            border-radius: 16px;
        }
        
        .qr-container p { margin-bottom: 24px; font-size: 14px; color: var(--text-light); font-weight: 500; }
        
        #qrcode {
            display: inline-block;
            padding: 16px;
            background: white;
            border-radius: 12px;
            box-shadow: var(--shadow-md);
        }
        
        /* ═══════════════════════════════════════════════════ */
        /* FLOATING LANGUAGE SELECTOR                         */
        /* ═══════════════════════════════════════════════════ */
        .language-selector {
            position: fixed;
            bottom: 32px;
            right: 32px;
            z-index: 1000;
        }
        
        html[dir="rtl"] .language-selector { right: auto; left: 32px; }
        
        .language-toggle-btn-float {
            width: 56px;
            height: 56px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            box-shadow: var(--shadow-lg), 0 0 24px rgba(90, 103, 216, 0.4);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            z-index: 1001;
            color: white;
            font-weight: 700;
        }
        
        .language-toggle-btn-float:hover {
            transform: scale(1.1) translateY(-4px);
            box-shadow: var(--shadow-xl), 0 0 32px rgba(90, 103, 216, 0.5);
        }
        
        .language-menu-backdrop {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0);
            z-index: 999;
            transition: background 0.3s ease;
        }
        
        .language-menu-backdrop.active {
            display: block;
            background: rgba(0, 0, 0, 0.4);
        }
        
        .language-dropdown-menu {
            position: absolute;
            bottom: 80px;
            right: 0;
            background: white;
            border-radius: 16px;
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--border-light);
            padding: 12px;
            min-width: 200px;
            display: none;
            flex-direction: column;
            gap: 8px;
            animation: scaleIn 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 1002;
        }
        
        html[dir="rtl"] .language-dropdown-menu { right: auto; left: 0; }
        .language-dropdown-menu.active { display: flex; }
        
        .language-menu-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 16px;
            border-radius: 12px;
            cursor: pointer;
            border: 1px solid transparent;
            background: var(--bg-light);
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            color: var(--text-dark);
            font-weight: 600;
            font-size: 14px;
        }
        
        .language-menu-item:hover {
            border-color: var(--primary);
            background: white;
            box-shadow: var(--shadow-sm);
            transform: translateX(-4px);
        }
        
        html[dir="rtl"] .language-menu-item:hover { transform: translateX(4px); }
        
        .language-menu-item.active {
            background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
            color: white;
            border-color: transparent;
            box-shadow: 0 4px 12px rgba(90, 103, 216, 0.3);
        }
        
        .language-menu-indicator {
            width: 8px; height: 8px;
            border-radius: 50%;
            background: var(--text-lighter);
            transition: all 0.3s ease;
            margin-left: auto;
        }
        
        html[dir="rtl"] .language-menu-indicator { margin-left: 0; margin-right: auto; }
        
        .language-menu-item.active .language-menu-indicator {
            background: white;
            box-shadow: 0 0 8px rgba(255, 255, 255, 0.6);
            transform: scale(1.2);
        }
        
        .language-menu-label { flex: 1; }
        
        .language-menu-code {
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            opacity: 0.7;
            min-width: 24px;
            text-align: right;
        }
        
        html[dir="rtl"] .language-menu-code { text-align: left; }
        .language-menu-item.active .language-menu-code { opacity: 1; }
        
        /* ═══════════════════════════════════════════════════ */
        /* LANGUAGE TOGGLE STEP 1                             */
        /* ═══════════════════════════════════════════════════ */
        .language-toggle-wrapper { display: flex; justify-content: center; margin-bottom: 36px; }
        
        .language-toggle {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
            width: 100%;
        }
        
        .lang-toggle-btn {
            padding: 14px 16px;
            border: 1px solid var(--border-light);
            background: white;
            border-radius: 12px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 700;
            color: var(--text-dark);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: var(--shadow-sm);
        }
        
        .lang-toggle-btn:hover {
            border-color: var(--primary);
            box-shadow: var(--shadow-md);
            transform: translateY(-2px);
        }
        
        .lang-toggle-btn.active {
            background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
            color: white;
            border-color: transparent;
            box-shadow: var(--shadow-md), 0 0 24px rgba(90, 103, 216, 0.3);
        }
        
        /* ═══════════════════════════════════════════════════ */
        /* FORMS                                              */
        /* ═══════════════════════════════════════════════════ */
        .form-group { margin-bottom: 28px; animation: slideInRight 0.4s cubic-bezier(0.4, 0, 0.2, 1); }
        .form-group:nth-child(2) { animation-delay: 0.1s; }
        .form-group:nth-child(3) { animation-delay: 0.2s; }
        .form-group:nth-child(4) { animation-delay: 0.3s; }
        
        label {
            display: block;
            margin-bottom: 10px;
            font-weight: 700;
            color: var(--text-dark);
            font-size: 13px;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }
        
        input[type="text"],
        input[type="tel"],
        select {
            width: 100%;
            padding: 14px 16px;
            border: 1px solid var(--border-light);
            border-radius: 12px;
            font-size: 15px;
            background: white;
            color: var(--text-dark);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            font-family: inherit;
            font-weight: 500;
        }
        
        input[type="text"]::placeholder,
        input[type="tel"]::placeholder { color: var(--text-lighter); font-weight: 400; }
        
        input[type="text"]:focus,
        input[type="tel"]:focus,
        select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(90, 103, 216, 0.1), var(--shadow-md);
        }
        
        /* ═══════════════════════════════════════════════════ */
        /* SERVICE BUTTONS                                    */
        /* ═══════════════════════════════════════════════════ */
        .service-buttons { display: grid; grid-template-columns: 1fr; gap: 14px; margin: 32px 0; }
        
        .service-btn {
            padding: 20px 22px;
            border: 1px solid var(--border-light);
            background: white;
            border-radius: 14px;
            cursor: pointer;
            font-size: 15px;
            font-weight: 600;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            color: var(--text-dark);
            text-align: left;
            position: relative;
            overflow: hidden;
            box-shadow: var(--shadow-sm);
        }
        
        .service-btn::before {
            content: '';
            position: absolute;
            top: 0; left: 0;
            width: 4px; height: 0;
            background: linear-gradient(180deg, var(--primary) 0%, var(--accent) 100%);
            transition: height 0.3s ease;
        }
        
        .service-btn:hover { border-color: var(--primary); background: var(--bg-light); box-shadow: var(--shadow-md); transform: translateY(-2px); }
        .service-btn:hover::before { height: 100%; }
        
        .service-btn.active {
            background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
            color: white;
            border-color: transparent;
            box-shadow: var(--shadow-lg), 0 0 24px rgba(90, 103, 216, 0.3);
        }
        
        .service-btn.active::before { display: none; }
        .service-btn small { display: block; font-size: 13px; font-weight: 400; margin-top: 6px; opacity: 0.8; }
        .service-btn.active small { opacity: 0.9; }
        
        .no-services {
            padding: 40px;
            text-align: center;
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            border-radius: 14px;
            color: #742a2a;
            border: 1px solid #fca5a5;
            font-weight: 600;
        }
        
        .db-error {
            padding: 16px;
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            border: 1px solid #fcd34d;
            border-radius: 12px;
            color: #78350f;
            margin-bottom: 24px;
            font-size: 14px;
            font-weight: 600;
        }
        
        /* ═══════════════════════════════════════════════════ */
        /* BUTTONS                                            */
        /* ═══════════════════════════════════════════════════ */
        .button-group {
            display: flex;
            gap: 14px;
            margin-top: 40px;
            justify-content: space-between;
        }
        
        .btn {
            flex: 1;
            padding: 16px 24px;
            border: none;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            letter-spacing: 0.5px;
            position: relative;
            overflow: hidden;
            box-shadow: var(--shadow-md);
            text-transform: uppercase;
            min-height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .btn-primary { background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%); color: white; }
        .btn-primary:hover:not(:disabled) { transform: translateY(-3px); box-shadow: var(--shadow-lg), 0 0 28px rgba(90, 103, 216, 0.35); }
        .btn-primary:active:not(:disabled) { transform: translateY(-1px); }
        .btn-primary:disabled { opacity: 0.6; cursor: not-allowed; transform: none; }
        
        .btn-secondary { background: white; color: var(--text-dark); border: 1.5px solid var(--border-light); }
        .btn-secondary:hover { background: var(--bg-light); border-color: var(--primary); color: var(--primary); }
        
        /* ═══════════════════════════════════════════════════ */
        /* TICKET PREVIEW                                     */
        /* ═══════════════════════════════════════════════════ */
        .ticket-preview {
            background: linear-gradient(135deg, #f0f9ff 0%, #e0e7ff 100%);
            padding: 40px;
            border-radius: 16px;
            text-align: center;
            margin: 36px 0;
            border: 1px solid var(--border-light);
            box-shadow: var(--shadow-md);
            display: flex;
            flex-direction: column;
            gap: 24px;
        }
        
        .ticket-preview h2 { font-size: 28px; margin: 0; color: var(--text-dark); font-weight: 800; }
        
        .ticket-number {
            font-size: 56px;
            font-weight: 900;
            background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin: 20px 0;
            letter-spacing: 4px;
            font-family: 'Courier New', monospace;
            animation: ticketPulse 2s ease-in-out infinite;
            min-height: 70px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .ticket-info {
            background: white;
            padding: 24px;
            border-radius: 12px;
            text-align: left;
            border: 1px solid var(--border-light);
            box-shadow: var(--shadow-sm);
        }
        
        .ticket-info p {
            display: flex;
            justify-content: space-between;
            padding: 14px 0;
            border-bottom: 1px solid var(--border-light);
            font-size: 14px;
        }
        
        .ticket-info p:last-child { border-bottom: none; padding-bottom: 0; }
        .ticket-info strong { color: var(--text-dark); font-weight: 700; }
        .ticket-info span { color: var(--text-light); font-weight: 500; text-align: right; }
        
        /* ═══════════════════════════════════════════════════ */
        /* ERROR MESSAGE                                      */
        /* ═══════════════════════════════════════════════════ */
        .error-message {
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            color: #742a2a;
            padding: 16px;
            border-radius: 12px;
            margin-bottom: 24px;
            display: none;
            border: 1px solid #fca5a5;
            font-weight: 600;
            font-size: 14px;
        }
        
        /* ═══════════════════════════════════════════════════ */
        /* PRINT                                              */
        /* ═══════════════════════════════════════════════════ */
        @media print {
            *, html, body { margin: 0; padding: 0; box-sizing: border-box; }
            
            body { background: white !important; font-size: 16px; }
            
            .container-main {
                display: flex;
                justify-content: center;
                align-items: center;
                min-height: 100vh;
                padding: 0;
            }
            
            .card {
                box-shadow: none;
                padding: 40px;
                max-width: 100%;
                border: none;
                background: white;
                border-radius: 0;
                animation: none;
            }
            
            .language-selector,
            .language-menu-backdrop,
            .button-group,
            .progress-container,
            .qr-container,
            .language-toggle-wrapper,
            .error-message,
            #step1, #step2, #step3 { display: none !important; }
            
            #step4 { display: block !important; }
            
            #step4 h1 { font-size: 24px; margin-bottom: 20px; text-align: center; }
            .subtitle { display: none; }
            
            .ticket-preview {
                background: white !important;
                padding: 40px;
                border-radius: 0;
                box-shadow: none !important;
                margin: 0;
                border: 3px dashed #333;
                min-height: 500px;
                justify-content: center;
            }
            
            .ticket-preview h2 { font-size: 20px; text-align: center; }
            
            .ticket-number {
                font-size: 72px;
                font-weight: 900;
                color: #000 !important;
                -webkit-text-fill-color: unset;
                background: none !important;
                background-clip: unset !important;
                -webkit-background-clip: unset !important;
                margin: 30px 0;
                letter-spacing: 6px;
                font-family: 'Courier New', monospace;
                animation: none !important;
                min-height: 90px;
                border: 2px solid #000;
                padding: 20px;
                border-radius: 8px;
            }
            
            .ticket-info {
                background: white !important;
                padding: 20px;
                border: 1px solid #333;
                border-radius: 4px;
                box-shadow: none !important;
            }
            
            .ticket-info p { font-size: 13px; padding: 10px 0; border-color: #ddd; }
            .ticket-info strong { color: #000; font-weight: 700; }
            .ticket-info span { color: #000; font-weight: 500; text-align: right; }
            
            @page { size: A4; margin: 20mm; }
        }
        
        /* ═══════════════════════════════════════════════════ */
        /* RESPONSIVE                                         */
        /* ═══════════════════════════════════════════════════ */
        @media (max-width: 768px) {
            .card { padding: 36px 24px; border-radius: 16px; }
            h1 { font-size: 28px; }
            .button-group { flex-direction: row; gap: 12px; }
            .btn { flex: 1; padding: 14px 16px; font-size: 14px; }
            .progress-step-circle { width: 36px; height: 36px; font-size: 13px; }
            .progress-step-label { font-size: 11px; }
            .language-selector { bottom: 20px; right: 20px; }
            html[dir="rtl"] .language-selector { right: auto; left: 20px; }
            .language-toggle-btn-float { width: 52px; height: 52px; font-size: 26px; }
        }
        
        @media (max-width: 480px) {
            .card { padding: 28px 20px; }
            h1 { font-size: 24px; }
            .progress-step-circle { width: 32px; height: 32px; font-size: 12px; }
            .language-toggle { grid-template-columns: 1fr 1fr; gap: 10px; }
            .lang-toggle-btn { padding: 12px 14px; font-size: 13px; }
            .service-btn { padding: 18px 16px; font-size: 14px; }
            .btn { padding: 13px 14px; font-size: 13px; min-height: 44px; }
            .ticket-number { font-size: 42px; letter-spacing: 2px; }
            .language-selector { bottom: 16px; right: 16px; }
            html[dir="rtl"] .language-selector { right: auto; left: 16px; }
            .language-toggle-btn-float { width: 48px; height: 48px; font-size: 22px; }
            .language-dropdown-menu { min-width: 160px; bottom: 70px; }
            .language-menu-item { padding: 12px 14px; font-size: 13px; }
        }
    </style>
</head>
<body>
    <div class="container-main">
        <div class="card">
            <!-- STEPPER -->
            <div class="progress-container">
                <div class="progress-bar">
                    <div class="progress-fill" id="progressFill" style="width: 25%;"></div>
                </div>
                <div class="progress-steps">
                    <div class="progress-step active" id="progressStep1">
                        <div class="progress-step-circle">1</div>
                        <div class="progress-step-label">Accueil</div>
                    </div>
                    <div class="progress-step" id="progressStep2">
                        <div class="progress-step-circle">2</div>
                        <div class="progress-step-label">Service</div>
                    </div>
                    <div class="progress-step" id="progressStep3">
                        <div class="progress-step-circle">3</div>
                        <div class="progress-step-label">Infos</div>
                    </div>
                    <div class="progress-step" id="progressStep4">
                        <div class="progress-step-circle">✓</div>
                        <div class="progress-step-label">Ticket</div>
                    </div>
                </div>
            </div>
            
            <!-- ÉTAPE 1 : Accueil -->
            <div class="step active" id="step1">
                <h1><?php echo $t['welcome']; ?></h1>
                <p class="subtitle"><?php echo $t['select_language']; ?></p>
                
                <div class="qr-container">
                    <p><?php echo $t['scan_qr']; ?></p>
                    <div id="qrcode"></div>
                </div>
                
                <div class="language-toggle-wrapper">
                    <div class="language-toggle">
                        <button class="lang-toggle-btn <?php echo $lang === 'ar' ? 'active' : ''; ?>" onclick="selectLanguage('ar', this)">
                            🇸🇦 <?php echo $t['arabic']; ?>
                        </button>
                        <button class="lang-toggle-btn <?php echo $lang === 'fr' ? 'active' : ''; ?>" onclick="selectLanguage('fr', this)">
                            🇫🇷 <?php echo $t['french']; ?>
                        </button>
                        <button class="lang-toggle-btn <?php echo $lang === 'en' ? 'active' : ''; ?>" onclick="selectLanguage('en', this)">
                            🇬🇧 <?php echo $t['english']; ?>
                        </button>
                        <button class="lang-toggle-btn <?php echo $lang === 'it' ? 'active' : ''; ?>" onclick="selectLanguage('it', this)">
                            🇮🇹 <?php echo $t['italian']; ?>
                        </button>
                    </div>
                </div>
                
                <div class="button-group">
                    <button class="btn btn-primary" onclick="goToStep(2)">
                        <?php echo $t['next']; ?> →
                    </button>
                </div>
            </div>
            
            <!-- ÉTAPE 2 : Sélection du Domaine -->
            <div class="step" id="step2">
                <h1><?php echo $t['select_service']; ?></h1>
                <p class="subtitle"><?php echo $t['domain']; ?></p>
                
                <?php if ($db_error): ?>
                    <div class="db-error">⚠️ <?php echo htmlspecialchars($db_error); ?></div>
                <?php endif; ?>
                
                <div class="service-buttons" id="serviceContainer">
                    <?php if (count($domaines) > 0): ?>
                        <?php foreach ($domaines as $domaine): ?>
                            <button class="service-btn"
                                    onclick="selectService(<?php echo htmlspecialchars(json_encode($domaine['nom']), ENT_QUOTES); ?>, <?php echo htmlspecialchars(json_encode($domaine['code']), ENT_QUOTES); ?>, this)">
                                📌 <?php echo htmlspecialchars($domaine['nom']); ?>
                                <?php if (!empty($domaine['description'])): ?>
                                    <small><?php echo htmlspecialchars($domaine['description']); ?></small>
                                <?php endif; ?>
                            </button>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="no-services">
                            <p>❌ Aucun service disponible</p>
                            <p style="font-size:12px;margin-top:12px;">Veuillez contacter l'administrateur</p>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="button-group">
                    <button class="btn btn-secondary" onclick="goToStep(1)">← <?php echo $t['back']; ?></button>
                    <button class="btn btn-primary" id="nextToStep3" onclick="goToStep(3)" <?php echo count($domaines) === 0 ? 'disabled' : ''; ?>>
                        <?php echo $t['next']; ?> →
                    </button>
                </div>
            </div>
            
            <!-- ÉTAPE 3 : Formulaire -->
            <div class="step" id="step3">
                <h1><?php echo $t['form_info']; ?></h1>
                <p class="subtitle"><?php echo $t['ticket_confirmed']; ?></p>
                <div class="error-message" id="errorMessage"></div>
                
                <div class="form-group">
                    <label for="fullName"><?php echo $t['full_name']; ?></label>
                    <input type="text" id="fullName" placeholder="Jean Dupont" autocomplete="name">
                </div>
                
                <div class="form-group">
                    <label for="phone"><?php echo $t['phone']; ?></label>
                    <input type="tel" id="phone" placeholder="+212 6 12 34 56 78" autocomplete="tel">
                </div>
                
                <div class="form-group">
                    <label for="status"><?php echo $t['status']; ?></label>
                    <select id="status">
                        <option value="">-- <?php echo $t['status']; ?> --</option>
                        <option value="standard"><?php echo $t['standard']; ?></option>
                        <option value="pregnant"><?php echo $t['pregnant']; ?></option>
                        <option value="disability"><?php echo $t['disability']; ?></option>
                    </select>
                </div>
                
                <div class="button-group">
                    <button class="btn btn-secondary" onclick="goToStep(2)">← <?php echo $t['back']; ?></button>
                    <button class="btn btn-primary" id="submitBtn" onclick="submitForm(event)">📋 <?php echo $t['print_ticket']; ?></button>
                </div>
            </div>
            
            <!-- ÉTAPE 4 : Ticket Confirmation -->
            <div class="step" id="step4">
                <h1><?php echo $t['ticket_confirmed']; ?></h1>
                <p class="subtitle"><?php echo $t['ticket_number']; ?></p>
                
                <div class="ticket-preview">
                    <h2><?php echo $t['ticket_number']; ?></h2>
                    <div class="ticket-number" id="ticketNumber">----</div>
                    
                    <div class="ticket-info">
                        <p><strong><?php echo $t['full_name']; ?>:</strong> <span id="ticketName">-</span></p>
                        <p><strong><?php echo $t['phone']; ?>:</strong> <span id="ticketPhone">-</span></p>
                        <p><strong><?php echo $t['status']; ?>:</strong> <span id="ticketStatus">-</span></p>
                        <p><strong><?php echo $t['domain']; ?>:</strong> <span id="ticketService">-</span></p>
                        <p><strong>Date/Heure:</strong> <span id="ticketDate" style="direction:ltr;"></span></p>
                    </div>
                </div>
                
                <div class="button-group">
                    <button class="btn btn-secondary" onclick="goToStep(1)">← <?php echo $t['back']; ?></button>
                    <button class="btn btn-primary" onclick="printTicket(event)">🖨️ <?php echo $t['print_ticket']; ?></button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- FLOATING LANGUAGE MENU -->
    <div class="language-menu-backdrop" id="languageMenuBackdrop" onclick="closeLanguageMenu()"></div>
    
    <div class="language-selector" id="languageSelector">
        <button class="language-toggle-btn-float" onclick="toggleLanguageMenu()">🌐</button>
        
        <div class="language-dropdown-menu" id="languageDropdownMenu">
            <button class="language-menu-item <?php echo $lang === 'ar' ? 'active' : ''; ?>" onclick="selectLanguageFromMenu('ar', this)">
                <div class="language-menu-label">العربية</div>
                <div class="language-menu-code">AR</div>
                <div class="language-menu-indicator"></div>
            </button>
            <button class="language-menu-item <?php echo $lang === 'fr' ? 'active' : ''; ?>" onclick="selectLanguageFromMenu('fr', this)">
                <div class="language-menu-label">Français</div>
                <div class="language-menu-code">FR</div>
                <div class="language-menu-indicator"></div>
            </button>
            <button class="language-menu-item <?php echo $lang === 'en' ? 'active' : ''; ?>" onclick="selectLanguageFromMenu('en', this)">
                <div class="language-menu-label">English</div>
                <div class="language-menu-code">EN</div>
                <div class="language-menu-indicator"></div>
            </button>
            <button class="language-menu-item <?php echo $lang === 'it' ? 'active' : ''; ?>" onclick="selectLanguageFromMenu('it', this)">
                <div class="language-menu-label">Italiano</div>
                <div class="language-menu-code">IT</div>
                <div class="language-menu-indicator"></div>
            </button>
        </div>
    </div>
    
    <script>
        let currentStep        = 1;
        let selectedService    = '';
        let selectedServiceCode = '';
        let currentLanguage    = '<?php echo $lang; ?>';

        // ── Menu Langue ─────────────────────────────────────────────
        function toggleLanguageMenu() {
            const menu     = document.getElementById('languageDropdownMenu');
            const backdrop = document.getElementById('languageMenuBackdrop');
            const isOpen   = menu.classList.contains('active');
            isOpen ? closeLanguageMenu() : (menu.classList.add('active'), backdrop.classList.add('active'));
        }

        function closeLanguageMenu() {
            document.getElementById('languageDropdownMenu').classList.remove('active');
            document.getElementById('languageMenuBackdrop').classList.remove('active');
        }

        function selectLanguageFromMenu(lang, el) {
            document.querySelectorAll('.language-menu-item').forEach(i => i.classList.remove('active'));
            el.classList.add('active');
            currentLanguage = lang;
            closeLanguageMenu();
            changeLanguage(lang);
        }

        function selectLanguage(lang, el) {
            document.querySelectorAll('.lang-toggle-btn').forEach(b => b.classList.remove('active'));
            el.classList.add('active');
            currentLanguage = lang;
            changeLanguage(lang);
        }

        function changeLanguage(lang) {
            fetch(window.location.href, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=set_language&language=' + encodeURIComponent(lang)
            })
            .then(r => r.json())
            .then(d => { if (d.success) location.reload(); })
            .catch(console.error);
        }

        document.addEventListener('keydown', e => { if (e.key === 'Escape') closeLanguageMenu(); });

        // ── QR Code ─────────────────────────────────────────────────
        function generateQRCode() {
            const el = document.getElementById('qrcode');
            el.innerHTML = '';
            new QRCode(el, {
                text: window.location.href.split('?')[0],
                width: 200, height: 200,
                colorDark: '#5a67d8', colorLight: '#ffffff',
                correctLevel: QRCode.CorrectLevel.H
            });
        }

        // ── Stepper ──────────────────────────────────────────────────
        function updateProgress(step) {
            document.getElementById('progressFill').style.width = (step / 4 * 100) + '%';
            for (let i = 1; i <= 4; i++) {
                const el = document.getElementById('progressStep' + i);
                el.classList.remove('active', 'completed');
                if (i < step) el.classList.add('completed');
                else if (i === step) el.classList.add('active');
            }
        }

        function goToStep(n) {
            // Empêcher de quitter l'étape 2 sans avoir choisi un service
            if (n === 3 && !selectedService) {
                alert('Veuillez sélectionner un service.');
                return;
            }
            document.getElementById('step' + currentStep).classList.remove('active');
            document.getElementById('step' + n).classList.add('active');
            currentStep = n;
            updateProgress(n);
        }

        // ── Service ──────────────────────────────────────────────────
        function selectService(service, serviceCode, el) {
            document.querySelectorAll('.service-btn').forEach(b => b.classList.remove('active'));
            el.classList.add('active');
            selectedService     = service;
            selectedServiceCode = serviceCode;

            fetch(window.location.href, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=set_service&service=' + encodeURIComponent(service)
                    + '&service_code=' + encodeURIComponent(serviceCode)
            }).catch(console.error);
        }

        // ── Formulaire ───────────────────────────────────────────────
        function submitForm(evt) {
            const name       = document.getElementById('fullName').value.trim();
            const phone      = document.getElementById('phone').value.trim();
            const status     = document.getElementById('status').value.trim();
            const errEl      = document.getElementById('errorMessage');

            if (!name || !phone || !status || !selectedService) {
                errEl.textContent = '⚠️ Veuillez remplir tous les champs obligatoires';
                errEl.style.display = 'block';
                errEl.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                return;
            }

            errEl.style.display = 'none';

            const btn = document.getElementById('submitBtn');
            btn.disabled = true;
            btn.style.opacity = '0.6';
            btn.textContent = '⏳ Traitement...';

            const fd = new FormData();
            fd.append('action',       'save_ticket');
            fd.append('name',         name);
            fd.append('phone',        phone);
            fd.append('status',       status);
            fd.append('service',      selectedService);
            fd.append('service_code', selectedServiceCode);

            fetch(window.location.href, { method: 'POST', body: fd })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        displayTicket(data);
                        goToStep(4);
                    } else {
                        errEl.textContent = data.message || '❌ Erreur lors de la création du ticket';
                        errEl.style.display = 'block';
                    }
                })
                .catch(err => {
                    console.error(err);
                    errEl.textContent = '⚠️ Une erreur est survenue. Veuillez réessayer.';
                    errEl.style.display = 'block';
                })
                .finally(() => {
                    btn.disabled = false;
                    btn.style.opacity = '1';
                    btn.textContent = '📋 <?php echo $t['print_ticket']; ?>';
                });
        }

        function displayTicket(data) {
            const tn = document.getElementById('ticketNumber');
            tn.style.animation = 'none';
            tn.offsetHeight; // reflow
            tn.textContent = data.ticket_number;
            tn.style.animation = 'slideInRight 0.6s ease-out';

            document.getElementById('ticketName').textContent    = data.name;
            document.getElementById('ticketPhone').textContent   = data.phone;
            document.getElementById('ticketStatus').textContent  = data.status;
            document.getElementById('ticketService').textContent = data.service;

            const now = new Date();
            document.getElementById('ticketDate').textContent =
                now.toLocaleDateString('fr-FR') + ' à ' + now.toLocaleTimeString('fr-FR');
        }

        // ── Impression ───────────────────────────────────────────────
        function printTicket(evt) {
            const btn = evt.currentTarget;
            const orig = btn.textContent;
            btn.textContent = '⏳ Impression...';
            btn.disabled = true;
            setTimeout(() => {
                window.print();
                setTimeout(() => {
                    btn.textContent = orig;
                    btn.disabled = false;
                }, 1500);
            }, 100);
        }

        // ── Reset ────────────────────────────────────────────────────
        function resetForm() {
            document.getElementById('fullName').value  = '';
            document.getElementById('phone').value     = '';
            document.getElementById('status').value    = '';
            document.getElementById('errorMessage').style.display = 'none';
            document.getElementById('ticketNumber').textContent = '----';
            document.querySelectorAll('.service-btn').forEach(b => b.classList.remove('active'));
            selectedService      = '';
            selectedServiceCode  = '';
            goToStep(1);
            generateQRCode();
        }

        // ── Init ─────────────────────────────────────────────────────
        document.addEventListener('DOMContentLoaded', () => {
            generateQRCode();
            updateProgress(1);

            // Validation à la touche Entrée sur l'étape 3
            document.addEventListener('keypress', e => {
                if (e.key === 'Enter' && currentStep === 3) {
                    submitForm(e);
                }
            });
        });
    </script>
</body>
</html>