<?php
/**
 * Smart Queue Management - Client Space
 * Adapté à la structure réelle de la table tickets
 * Design Premium Amélioré + Menu Langue Escamotable
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

// Récupérer les domaines
$domaines = [];
$db_error = null;

try {
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
    
    $pdo = new PDO(
        $dsn,
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_TIMEOUT => 5
        ]
    );
    
    // Vérifier que la table existe
    $tableCheck = $pdo->query("SHOW TABLES LIKE 'topical_domain'");
    if ($tableCheck->rowCount() === 0) {
        $db_error = "Table 'topical_domain' introuvable";
    } else {
        // Récupérer les domaines ACTIFS
        $stmt = $pdo->prepare(
            "SELECT td_id as id, td_name as nom, td_description as description 
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

// Traiter les requêtes AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    $action = $_POST['action'] ?? '';
    
    if ($action === 'set_language') {
        $_SESSION['language'] = $_POST['language'];
        echo json_encode(['success' => true]);
        exit;
    }
    
    if ($action === 'set_service') {
        $_SESSION['service'] = $_POST['service'];
        echo json_encode(['success' => true]);
        exit;
    }
    
    if ($action === 'save_ticket') {
        $name = $_POST['name'] ?? '';
        $phone = $_POST['phone'] ?? '';
        $status = $_POST['status'] ?? '';
        $service = $_POST['service'] ?? '';
        
        if (empty($name) || empty($phone) || empty($status) || empty($service)) {
            echo json_encode(['success' => false, 'message' => $t['error_empty']]);
            exit;
        }
        
        if (!preg_match('/^[0-9\s\-\+\(\)]{8,}$/', $phone)) {
            echo json_encode(['success' => false, 'message' => 'Numéro de téléphone invalide']);
            exit;
        }
        
        // Générer un numéro de ticket unique
        $ticket_number = date('YmdHis') . rand(1000, 9999);
        
        try {
            $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
            
            $pdo = new PDO(
                $dsn,
                DB_USER,
                DB_PASS,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
            
            // ✅ INSERTION CORRIGÉE SELON LA STRUCTURE RÉELLE
            $stmt = $pdo->prepare(
                "INSERT INTO tickets (ticket_number, name, phone, status, service, created_at) 
                 VALUES (:ticket_number, :name, :phone, :status, :service, :created_at)"
            );
            
            $stmt->execute([
                'ticket_number' => $ticket_number,
                'name' => $name,
                'phone' => $phone,
                'status' => $status,
                'service' => $service,
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            echo json_encode([
                'success' => true,
                'ticket_number' => $ticket_number,
                'name' => $name,
                'phone' => $phone,
                'status' => $status,
                'service' => $service
            ]);
        } catch (Exception $e) {
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
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto Helvetica Neue', sans-serif;
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
        
        /* ═════════════════════════════════════════════════════════════ */
        /* STEPPER - Indicateur de Progression */
        /* ═════════════════════════════════════════════════════════════ */
        .progress-container {
            margin-bottom: 48px;
        }
        
        .progress-bar {
            width: 100%;
            height: 2px;
            background: var(--border-light);
            border-radius: 1px;
            overflow: hidden;
            margin-bottom: 28px;
            position: relative;
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
            box-shadow: var(--shadow-sm);
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
        
        /* ═════════════════════════════════════════════════════════════ */
        /* CARD - Conteneur Principal */
        /* ═══════════════════���═════════════════════════════════════════ */
        .card {
            background: white;
            border-radius: 20px;
            box-shadow: var(--shadow-xl);
            padding: 56px;
            max-width: 640px;
            width: 100%;
            animation: slideIn 0.6s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(24px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(20px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        @keyframes scaleIn {
            from {
                opacity: 0;
                transform: scale(0.8);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }
        
        .step {
            display: none;
        }
        
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
            letter-spacing: 0.3px;
            line-height: 1.5;
        }
        
        /* ═════════════════════════════════════════════════════════════ */
        /* QR CODE SECTION */
        /* ═════════════════════════════════════════════════════════════ */
        .qr-container {
            text-align: center;
            margin-bottom: 36px;
            padding: 36px;
            background: linear-gradient(135deg, var(--bg-light) 0%, var(--bg-lighter) 100%);
            border: 1px solid var(--border-light);
            border-radius: 16px;
            backdrop-filter: blur(10px);
        }
        
        .qr-container p {
            margin-bottom: 24px;
            font-size: 14px;
            color: var(--text-light);
            font-weight: 500;
        }
        
        #qrcode {
            display: inline-block;
            padding: 16px;
            background: white;
            border-radius: 12px;
            box-shadow: var(--shadow-md);
        }
        
        /* ═════════════════════════════════════════════════════════════ */
        /* LANGUAGE SELECTOR TOGGLE - Menu Escamotable Premium */
        /* ═════════════════════════════════════════════════════════════ */
        .language-selector {
            position: fixed;
            bottom: 32px;
            right: 32px;
            z-index: 1000;
        }
        
        html[dir="rtl"] .language-selector {
            right: auto;
            left: 32px;
        }
        
        /* Bouton Circulaire Flottant */
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
        
        .language-toggle-btn-float:active {
            transform: scale(0.95);
        }
        
        /* Backdrop Semi-transparent */
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
        
        /* Menu Dropdown */
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
        
        html[dir="rtl"] .language-dropdown-menu {
            right: auto;
            left: 0;
        }
        
        .language-dropdown-menu.active {
            display: flex;
        }
        
        /* Items du Menu */
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
            position: relative;
            overflow: hidden;
        }
        
        .language-menu-item::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            width: 0;
            height: 100%;
            background: linear-gradient(90deg, var(--primary) 0%, var(--accent) 100%);
            opacity: 0.1;
            transition: width 0.3s ease;
        }
        
        .language-menu-item:hover {
            border-color: var(--primary);
            background: white;
            box-shadow: var(--shadow-sm);
            transform: translateX(-4px);
        }
        
        html[dir="rtl"] .language-menu-item:hover {
            transform: translateX(4px);
        }
        
        .language-menu-item.active {
            background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
            color: white;
            border-color: transparent;
            box-shadow: 0 4px 12px rgba(90, 103, 216, 0.3);
        }
        
        .language-menu-item.active::before {
            display: none;
        }
        
        /* Indicateur de Sélection */
        .language-menu-indicator {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--text-lighter);
            transition: all 0.3s ease;
            margin-left: auto;
        }
        
        html[dir="rtl"] .language-menu-indicator {
            margin-left: 0;
            margin-right: auto;
        }
        
        .language-menu-item.active .language-menu-indicator {
            background: white;
            box-shadow: 0 0 8px rgba(255, 255, 255, 0.6);
            transform: scale(1.2);
        }
        
        .language-menu-label {
            flex: 1;
        }
        
        .language-menu-code {
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            opacity: 0.7;
            min-width: 24px;
            text-align: right;
        }
        
        html[dir="rtl"] .language-menu-code {
            text-align: left;
        }
        
        .language-menu-item.active .language-menu-code {
            opacity: 1;
        }
        
        /* ═════════════════════════════════════════════════════════════ */
        /* LANGUAGE TOGGLE - Étape 1 */
        /* ═════════════════════════════════════════════════════════════ */
        .language-toggle-wrapper {
            display: flex;
            justify-content: center;
            margin-bottom: 36px;
        }
        
        .language-toggle {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
            width: 100%;
            max-width: 100%;
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
            position: relative;
            overflow: hidden;
            box-shadow: var(--shadow-sm);
        }
        
        .lang-toggle-btn::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
            opacity: 0;
            transition: opacity 0.3s ease;
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
        
        .lang-toggle-btn span {
            position: relative;
            z-index: 1;
        }
        
        /* ═════════════════════════════════════════════════════════════ */
        /* FORM STYLING */
        /* ═════════════════════════════════════════════════════════════ */
        .form-group {
            margin-bottom: 28px;
            animation: slideInRight 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
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
            opacity: 0.95;
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
        input[type="tel"]::placeholder {
            color: var(--text-lighter);
            font-weight: 400;
        }
        
        input[type="text"]:focus,
        input[type="tel"]:focus,
        select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(90, 103, 216, 0.1), var(--shadow-md);
            background: white;
        }
        
        /* ══════════════════════════════════════════════════��══════════ */
        /* SERVICE BUTTONS */
        /* ═════════════════════════════════════════════════════════════ */
        .service-buttons {
            display: grid;
            grid-template-columns: 1fr;
            gap: 14px;
            margin: 32px 0;
        }
        
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
            top: 0;
            left: 0;
            width: 4px;
            height: 0;
            background: linear-gradient(180deg, var(--primary) 0%, var(--accent) 100%);
            transition: height 0.3s ease;
        }
        
        .service-btn:hover {
            border-color: var(--primary);
            background: var(--bg-light);
            box-shadow: var(--shadow-md);
            transform: translateY(-2px);
        }
        
        .service-btn:hover::before {
            height: 100%;
        }
        
        .service-btn.active {
            background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
            color: white;
            border-color: transparent;
            box-shadow: var(--shadow-lg), 0 0 24px rgba(90, 103, 216, 0.3);
        }
        
        .service-btn.active::before {
            display: none;
        }
        
        .service-btn small {
            display: block;
            font-size: 13px;
            font-weight: 400;
            margin-top: 6px;
            opacity: 0.8;
        }
        
        .service-btn.active small {
            opacity: 0.9;
        }
        
        .no-services {
            padding: 40px;
            text-align: center;
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            border-radius: 14px;
            color: #742a2a;
            border: 1px solid #fca5a5;
            font-weight: 600;
            box-shadow: var(--shadow-sm);
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
            box-shadow: var(--shadow-sm);
        }
        
        /* ═════════════════════════════════════════════════════════════ */
        /* BUTTONS */
        /* ═════════════════════════════════════════════════════════════ */
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
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
            color: white;
        }
        
        .btn-primary:hover:not(:disabled) {
            transform: translateY(-3px);
            box-shadow: var(--shadow-lg), 0 0 28px rgba(90, 103, 216, 0.35);
        }
        
        .btn-primary:active:not(:disabled) {
            transform: translateY(-1px);
        }
        
        .btn-primary:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        
        .btn-secondary {
            background: white;
            color: var(--text-dark);
            border: 1.5px solid var(--border-light);
        }
        
        .btn-secondary:hover {
            background: var(--bg-light);
            border-color: var(--primary);
            color: var(--primary);
            box-shadow: var(--shadow-md), 0 0 20px rgba(90, 103, 216, 0.15);
        }
        
        .btn-secondary:active {
            transform: translateY(-1px);
        }
        
        /* ═════════════════════════════════════════════════════════════ */
        /* TICKET PREVIEW */
        /* ═════════════════════════════════════════════════════════════ */
        .ticket-preview {
            background: linear-gradient(135deg, #f0f9ff 0%, #e0e7ff 100%);
            padding: 40px;
            border-radius: 16px;
            text-align: center;
            margin: 36px 0;
            border: 1px solid var(--border-light);
            box-shadow: var(--shadow-md);
        }
        
        .ticket-preview h2 {
            font-size: 28px;
            margin-bottom: 12px;
            color: var(--text-dark);
            font-weight: 800;
        }
        
        .ticket-number {
            font-size: 42px;
            font-weight: 800;
            background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin: 20px 0;
            letter-spacing: 3px;
            font-family: 'Courier New', monospace;
        }
        
        .ticket-info {
            background: white;
            padding: 24px;
            border-radius: 12px;
            margin: 20px 0;
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
        
        .ticket-info p:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }
        
        .ticket-info strong {
            color: var(--text-dark);
            font-weight: 700;
        }
        
        .ticket-info span {
            color: var(--text-light);
            font-weight: 500;
        }
        
        /* ═════════════════════════════════════════════════════════════ */
        /* ERROR MESSAGE */
        /* ═════════════════════════════════════════════════════════════ */
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
            box-shadow: var(--shadow-sm);
        }
        
        /* ═════════════════════════════════════════════════════════════ */
        /* RESPONSIVE DESIGN */
        /* ═════════════════════════════════════════════════════════════ */
        @media print {
            body {
                background: white;
            }
            
            .card {
                box-shadow: none;
                padding: 40px;
            }
            
            .language-selector,
            .button-group,
            .progress-container,
            .language-menu-backdrop {
                display: none;
            }
        }
        
        @media (max-width: 768px) {
            .card {
                padding: 36px 24px;
                border-radius: 16px;
            }
            
            h1 {
                font-size: 28px;
            }
            
            .subtitle {
                font-size: 14px;
            }
            
            .language-toggle {
                grid-template-columns: 1fr 1fr;
            }
            
            .button-group {
                flex-direction: row;
                gap: 12px;
            }
            
            .btn {
                flex: 1;
                padding: 14px 16px;
                font-size: 14px;
            }
            
            .progress-step {
                gap: 6px;
            }
            
            .progress-step-circle {
                width: 36px;
                height: 36px;
                font-size: 13px;
            }
            
            .progress-step-label {
                font-size: 11px;
            }
            
            .language-selector {
                bottom: 20px;
                right: 20px;
            }
            
            html[dir="rtl"] .language-selector {
                right: auto;
                left: 20px;
            }
            
            .language-toggle-btn-float {
                width: 52px;
                height: 52px;
                font-size: 26px;
            }
            
            .language-dropdown-menu {
                min-width: 180px;
            }
        }
        
        @media (max-width: 480px) {
            .card {
                padding: 28px 20px;
            }
            
            h1 {
                font-size: 24px;
            }
            
            .progress-container {
                margin-bottom: 32px;
            }
            
            .progress-step-circle {
                width: 32px;
                height: 32px;
                font-size: 12px;
            }
            
            .language-toggle {
                grid-template-columns: 1fr 1fr;
                gap: 10px;
            }
            
            .lang-toggle-btn {
                padding: 12px 14px;
                font-size: 13px;
            }
            
            .qr-container {
                padding: 24px;
            }
            
            .service-btn {
                padding: 18px 16px;
                font-size: 14px;
            }
            
            .button-group {
                gap: 10px;
                margin-top: 32px;
            }
            
            .btn {
                padding: 13px 14px;
                font-size: 13px;
                min-height: 44px;
            }
            
            .form-group {
                margin-bottom: 20px;
            }
            
            .ticket-preview {
                padding: 28px 20px;
            }
            
            .ticket-number {
                font-size: 32px;
                letter-spacing: 2px;
            }
            
            .ticket-info {
                padding: 16px;
            }
            
            .language-selector {
                bottom: 16px;
                right: 16px;
            }
            
            html[dir="rtl"] .language-selector {
                right: auto;
                left: 16px;
            }
            
            .language-toggle-btn-float {
                width: 48px;
                height: 48px;
                font-size: 22px;
            }
            
            .language-dropdown-menu {
                min-width: 160px;
                bottom: 70px;
            }
            
            .language-menu-item {
                padding: 12px 14px;
                font-size: 13px;
            }
        }
    </style>
</head>
<body>
    <div class="container-main">
        <div class="card">
            <!-- STEPPER - Indicateur de Progression -->
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
                
                <!-- Language Toggle -->
                <div class="language-toggle-wrapper">
                    <div class="language-toggle">
                        <button class="lang-toggle-btn active" onclick="selectLanguage('ar')">
                            <span>🇸🇦 <?php echo $t['arabic']; ?></span>
                        </button>
                        <button class="lang-toggle-btn active" onclick="selectLanguage('fr')">
                            <span>🇫🇷 <?php echo $t['french']; ?></span>
                        </button>
                        <button class="lang-toggle-btn active" onclick="selectLanguage('en')">
                            <span>🇬🇧 <?php echo $t['english']; ?></span>
                        </button>
                        <button class="lang-toggle-btn active" onclick="selectLanguage('it')">
                            <span>🇮🇹 <?php echo $t['italian']; ?></span>
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
                    <div class="db-error">
                        ⚠️ <?php echo htmlspecialchars($db_error); ?>
                    </div>
                <?php endif; ?>
                
                <div class="service-buttons" id="serviceContainer">
                    <?php if (count($domaines) > 0): ?>
                        <?php foreach ($domaines as $domaine): ?>
                            <button class="service-btn" 
                                    onclick="selectService('<?php echo htmlspecialchars($domaine['nom']); ?>', this)">
                                📌 <?php echo htmlspecialchars($domaine['nom']); ?>
                                <?php if (!empty($domaine['description'])): ?>
                                    <small><?php echo htmlspecialchars($domaine['description']); ?></small>
                                <?php endif; ?>
                            </button>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="no-services">
                            <p>❌ Aucun service disponible</p>
                            <p style="font-size: 12px; margin-top: 12px;">Veuillez contacter l'administrateur</p>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="button-group">
                    <button class="btn btn-secondary" onclick="goToStep(1)">
                        ← <?php echo $t['back']; ?>
                    </button>
                    <button class="btn btn-primary" onclick="goToStep(3)" <?php echo count($domaines) === 0 ? 'disabled' : ''; ?>>
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
                    <input type="tel" id="phone" placeholder="+33 6 12 34 56 78" autocomplete="tel">
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
                    <button class="btn btn-primary" onclick="submitForm()">📋 <?php echo $t['print_ticket']; ?></button>
                </div>
            </div>
            
            <!-- ÉTAPE 4 : Ticket Confirmation -->
            <div class="step" id="step4">
                <h1><?php echo $t['ticket_confirmed']; ?></h1>
                <p class="subtitle"><?php echo $t['ticket_number']; ?></p>
                
                <div class="ticket-preview">
                    <div class="ticket-number" id="ticketNumber">----</div>
                    
                    <div class="ticket-info">
                        <p>
                            <strong><?php echo $t['full_name']; ?>:</strong>
                            <span id="ticketName">-</span>
                        </p>
                        <p>
                            <strong><?php echo $t['phone']; ?>:</strong>
                            <span id="ticketPhone">-</span>
                        </p>
                        <p>
                            <strong><?php echo $t['status']; ?>:</strong>
                            <span id="ticketStatus">-</span>
                        </p>
                        <p>
                            <strong><?php echo $t['domain']; ?>:</strong>
                            <span id="ticketService">-</span>
                        </p>
                        <p>
                            <strong>Date/Heure:</strong>
                            <span id="ticketDate" style="direction: ltr;"></span>
                        </p>
                    </div>
                </div>
                
                <div class="button-group">
                    <button class="btn btn-primary" onclick="printTicket()">🖨️ <?php echo $t['print_ticket']; ?></button>
                    <button class="btn btn-secondary" onclick="resetForm()">➕ <?php echo $t['next']; ?></button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- SÉLECTEUR DE LANGUE - Menu Escamotable Premium -->
    <div class="language-menu-backdrop" id="languageMenuBackdrop" onclick="closeLanguageMenu()"></div>
    
    <div class="language-selector" id="languageSelector">
        <button class="language-toggle-btn-float" id="languageToggleBtn" onclick="toggleLanguageMenu()">
            🌐
        </button>
        
        <div class="language-dropdown-menu" id="languageDropdownMenu">
            <button class="language-menu-item active" onclick="selectLanguageFromMenu('ar')">
                <div class="language-menu-label">العربية</div>
                <div class="language-menu-code">AR</div>
                <div class="language-menu-indicator"></div>
            </button>
            
            <button class="language-menu-item" onclick="selectLanguageFromMenu('fr')">
                <div class="language-menu-label">Français</div>
                <div class="language-menu-code">FR</div>
                <div class="language-menu-indicator"></div>
            </button>
            
            <button class="language-menu-item" onclick="selectLanguageFromMenu('en')">
                <div class="language-menu-label">English</div>
                <div class="language-menu-code">EN</div>
                <div class="language-menu-indicator"></div>
            </button>
            
            <button class="language-menu-item" onclick="selectLanguageFromMenu('it')">
                <div class="language-menu-label">Italiano</div>
                <div class="language-menu-code">IT</div>
                <div class="language-menu-indicator"></div>
            </button>
        </div>
    </div>
    
    <script>
        let currentStep = 1;
        let selectedService = '';
        let currentLanguage = '<?php echo $lang; ?>';
        
        console.log('🚀 Domaines au chargement:', <?php echo json_encode($domaines); ?>);
        
        // ═══════════════════════════════════════════════════════════════
        // MENU DE LANGUE - Fonctions Escamotables
        // ════════════════════════════════════════════════��══════════════
        
        /**
         * Basculer l'ouverture/fermeture du menu
         */
        function toggleLanguageMenu() {
            const menu = document.getElementById('languageDropdownMenu');
            const backdrop = document.getElementById('languageMenuBackdrop');
            
            const isOpen = menu.classList.contains('active');
            
            if (isOpen) {
                closeLanguageMenu();
            } else {
                menu.classList.add('active');
                backdrop.classList.add('active');
            }
        }
        
        /**
         * Fermer le menu de langue
         */
        function closeLanguageMenu() {
            const menu = document.getElementById('languageDropdownMenu');
            const backdrop = document.getElementById('languageMenuBackdrop');
            
            menu.classList.remove('active');
            backdrop.classList.remove('active');
        }
        
        /**
         * Sélectionner une langue depuis le menu
         */
        function selectLanguageFromMenu(lang) {
            const langCodes = ['ar', 'fr', 'en', 'it'];
            if (!langCodes.includes(lang)) return;
            
            // Mettre à jour les indicateurs visuels
            const menuItems = document.querySelectorAll('.language-menu-item');
            menuItems.forEach(item => item.classList.remove('active'));
            event.target.closest('.language-menu-item').classList.add('active');
            
            // Mettre à jour les boutons en haut
            const toggleButtons = document.querySelectorAll('.lang-toggle-btn');
            toggleButtons.forEach(btn => btn.classList.remove('active'));
            const langMap = { 'ar': 0, 'fr': 1, 'en': 2, 'it': 3 };
            if (langMap[lang] !== undefined) {
                toggleButtons[langMap[lang]].classList.add('active');
            }
            
            currentLanguage = lang;
            closeLanguageMenu();
            changeLanguage(lang);
        }
        
        /**
         * Changer la langue (requête AJAX)
         */
        function changeLanguage(lang) {
            const langCodes = ['ar', 'fr', 'en', 'it'];
            if (!langCodes.includes(lang)) return;
            
            fetch(window.location.href, {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=set_language&language=' + lang
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) location.reload();
            })
            .catch(error => console.error('Erreur:', error));
        }
        
        // Fermer le menu au clic sur le backdrop
        document.addEventListener('click', function(event) {
            const selector = document.getElementById('languageSelector');
            const menu = document.getElementById('languageDropdownMenu');
            
            if (selector && menu.classList.contains('active')) {
                if (!selector.contains(event.target)) {
                    closeLanguageMenu();
                }
            }
        });
        
        // Fermer le menu avec la touche Escape
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeLanguageMenu();
            }
        });
        
        // ═══════════════════════════════════════════════════════════════
        // AUTRES FONCTIONS
        // ═══════════════════════════════════════════════════════════════
        
        function generateQRCode() {
            const qrContainer = document.getElementById('qrcode');
            qrContainer.innerHTML = '';
            const mobileUrl = window.location.href.split('?')[0];
            new QRCode(qrContainer, {
                text: mobileUrl,
                width: 200,
                height: 200,
                colorDark: '#5a67d8',
                colorLight: '#ffffff',
                correctLevel: QRCode.CorrectLevel.H
            });
        }
        
        function updateProgress(stepNumber) {
            const totalSteps = 4;
            const percentage = (stepNumber / totalSteps) * 100;
            
            document.getElementById('progressFill').style.width = percentage + '%';
            
            for (let i = 1; i <= totalSteps; i++) {
                const progressStep = document.getElementById('progressStep' + i);
                if (i < stepNumber) {
                    progressStep.classList.add('completed');
                    progressStep.classList.remove('active');
                } else if (i === stepNumber) {
                    progressStep.classList.add('active');
                    progressStep.classList.remove('completed');
                } else {
                    progressStep.classList.remove('active', 'completed');
                }
            }
        }
        
        function selectLanguage(lang) {
            const buttons = document.querySelectorAll('.lang-toggle-btn');
            buttons.forEach(btn => btn.classList.remove('active'));
            event.target.closest('.lang-toggle-btn').classList.add('active');
            
            const menuItems = document.querySelectorAll('.language-menu-item');
            menuItems.forEach(item => item.classList.remove('active'));
            const langMap = { 'ar': 0, 'fr': 1, 'en': 2, 'it': 3 };
            if (langMap[lang] !== undefined) {
                menuItems[langMap[lang]].classList.add('active');
            }
            
            currentLanguage = lang;
            changeLanguage(lang);
        }
        
        function selectService(service, element) {
            const buttons = document.querySelectorAll('.service-btn');
            buttons.forEach(btn => btn.classList.remove('active'));
            element.classList.add('active');
            
            selectedService = service;
            
            console.log('✅ Service sélectionné:', service);
            
            fetch(window.location.href, {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=set_service&service=' + encodeURIComponent(service)
            })
            .catch(error => console.error('Erreur:', error));
        }
        
        function goToStep(stepNumber) {
            document.getElementById('step' + currentStep).classList.remove('active');
            document.getElementById('step' + stepNumber).classList.add('active');
            currentStep = stepNumber;
            updateProgress(stepNumber);
        }
        
        function submitForm() {
            const name = document.getElementById('fullName').value.trim();
            const phone = document.getElementById('phone').value.trim();
            const status = document.getElementById('status').value.trim();
            const errorMessage = document.getElementById('errorMessage');
            
            if (!name || !phone || !status || !selectedService) {
                errorMessage.textContent = '⚠️ Veuillez remplir tous les champs obligatoires';
                errorMessage.style.display = 'block';
                errorMessage.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                return;
            }
            
            errorMessage.style.display = 'none';
            
            const formData = new FormData();
            formData.append('action', 'save_ticket');
            formData.append('name', name);
            formData.append('phone', phone);
            formData.append('status', status);
            formData.append('service', selectedService);
            
            // Disable button during submission
            const submitBtn = event.target;
            submitBtn.disabled = true;
            submitBtn.style.opacity = '0.6';
            
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                console.log('✅ Ticket créé:', data);
                if (data.success) {
                    displayTicket(data);
                    goToStep(4);
                } else {
                    errorMessage.textContent = data.message || '❌ Erreur lors de la création du ticket';
                    errorMessage.style.display = 'block';
                    submitBtn.disabled = false;
                    submitBtn.style.opacity = '1';
                }
            })
            .catch(error => {
                console.error('❌ Erreur:', error);
                errorMessage.textContent = '⚠️ Une erreur est survenue. Veuillez réessayer.';
                errorMessage.style.display = 'block';
                submitBtn.disabled = false;
                submitBtn.style.opacity = '1';
            });
        }
        
        function displayTicket(data) {
            // Animate ticket number display
            const ticketNumber = document.getElementById('ticketNumber');
            ticketNumber.style.animation = 'none';
            setTimeout(() => {
                ticketNumber.textContent = data.ticket_number;
                ticketNumber.style.animation = 'slideInRight 0.6s ease-out';
            }, 10);
            
            document.getElementById('ticketName').textContent = data.name;
            document.getElementById('ticketPhone').textContent = data.phone;
            document.getElementById('ticketStatus').textContent = data.status;
            document.getElementById('ticketService').textContent = data.service;
            
            const now = new Date();
            const dateStr = now.toLocaleDateString('fr-FR') + ' à ' + now.toLocaleTimeString('fr-FR');
            document.getElementById('ticketDate').textContent = dateStr;
        }
        
        function printTicket() {
            const printBtn = event.target;
            printBtn.textContent = '✓ Impression...';
            printBtn.disabled = true;
            
            window.print();
            
            setTimeout(() => {
                printBtn.textContent = '🖨️ <?php echo $t['print_ticket']; ?>';
                printBtn.disabled = false;
            }, 1500);
        }
        
        function resetForm() {
            // Smooth reset with animation
            const card = document.querySelector('.card');
            card.style.animation = 'slideIn 0.5s ease-out';
            
            document.getElementById('fullName').value = '';
            document.getElementById('phone').value = '';
            document.getElementById('status').value = '';
            document.getElementById('errorMessage').style.display = 'none';
            document.querySelectorAll('.service-btn').forEach(btn => btn.classList.remove('active'));
            selectedService = '';
            goToStep(1);
            generateQRCode();
        }
        
        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            generateQRCode();
            updateProgress(1);
            
            // Initialiser le menu avec la bonne langue active
            const langMap = { 'ar': 0, 'fr': 1, 'en': 2, 'it': 3 };
            const langIndex = langMap[currentLanguage];
            if (langIndex !== undefined) {
                const menuItems = document.querySelectorAll('.language-menu-item');
                menuItems.forEach((item, index) => {
                    if (index === langIndex) {
                        item.classList.add('active');
                    } else {
                        item.classList.remove('active');
                    }
                });
            }
            
            // Add keyboard navigation
            document.addEventListener('keypress', function(event) {
                if (event.key === 'Enter') {
                    const activeStep = document.querySelector('.step.active');
                    if (activeStep.id === 'step3') {
                        const submitBtn = activeStep.querySelector('.btn-primary');
                        submitBtn && submitBtn.click();
                    }
                }
            });
        });
    </script>
</body>
</html>