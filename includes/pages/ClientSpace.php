<?php
/**
 * Smart Queue Management - Client Space
 * Adapté à la structure réelle de la table tickets
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
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --accent: #8b5cf6;
            --bg-light: #f8fafc;
            --border-light: #e2e8f0;
            --text-dark: #1e293b;
            --text-light: #64748b;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: var(--text-dark);
            line-height: 1.6;
        }
        
        .container-main {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }
        
        /* Progress Bar */
        .progress-container {
            margin-bottom: 32px;
        }
        
        .progress-bar {
            width: 100%;
            height: 4px;
            background: var(--border-light);
            border-radius: 2px;
            overflow: hidden;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--primary) 0%, var(--accent) 100%);
            border-radius: 2px;
            transition: width 0.5s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .progress-steps {
            display: flex;
            justify-content: space-between;
            margin-top: 12px;
            gap: 12px;
        }
        
        .progress-step {
            flex: 1;
            height: 32px;
            background: var(--bg-light);
            border: 2px solid var(--border-light);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 600;
            color: var(--text-light);
            transition: all 0.3s ease;
        }
        
        .progress-step.active {
            background: var(--primary);
            border-color: var(--primary);
            color: white;
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
        }
        
        .progress-step.completed {
            background: var(--primary);
            border-color: var(--primary);
            color: white;
        }
        
        .card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
            padding: 48px;
            max-width: 600px;
            width: 100%;
            animation: slideIn 0.5s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(20px);
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
        
        .step {
            display: none;
        }
        
        .step.active {
            display: block;
            animation: slideIn 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        h1 {
            font-size: 32px;
            font-weight: 700;
            color: var(--text-dark);
            text-align: center;
            margin-bottom: 12px;
            letter-spacing: -0.5px;
        }
        
        .subtitle {
            text-align: center;
            color: var(--text-light);
            margin-bottom: 32px;
            font-size: 15px;
            font-weight: 500;
            letter-spacing: 0.3px;
        }
        
        .qr-container {
            text-align: center;
            margin-bottom: 32px;
            padding: 28px;
            background: var(--bg-light);
            border: 1px solid var(--border-light);
            border-radius: 12px;
            backdrop-filter: blur(10px);
        }
        
        .qr-container p {
            margin-bottom: 16px;
            font-size: 14px;
            color: var(--text-light);
            font-weight: 500;
        }
        
        #qrcode {
            display: inline-block;
            padding: 12px;
            background: white;
            border-radius: 8px;
        }
        
        /* Language Toggle */
        .language-toggle-wrapper {
            display: flex;
            justify-content: center;
            margin-bottom: 32px;
        }
        
        .language-toggle {
            display: flex;
            background: var(--bg-light);
            border: 1px solid var(--border-light);
            border-radius: 10px;
            padding: 4px;
            gap: 4px;
        }
        
        .lang-toggle-btn {
            padding: 10px 16px;
            border: none;
            background: transparent;
            border-radius: 8px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            color: var(--text-light);
            transition: all 0.25s ease;
            white-space: nowrap;
            position: relative;
        }
        
        .lang-toggle-btn:hover {
            color: var(--text-dark);
        }
        
        .lang-toggle-btn.active {
            background: white;
            color: var(--primary);
            box-shadow: 0 2px 8px rgba(99, 102, 241, 0.2);
        }
        
        .language-buttons {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
            margin: 28px 0;
        }
        
        .lang-btn {
            padding: 16px;
            border: 1px solid var(--border-light);
            background: white;
            border-radius: 10px;
            cursor: pointer;
            font-size: 15px;
            font-weight: 600;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            color: var(--text-dark);
            position: relative;
            overflow: hidden;
        }
        
        .lang-btn::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .lang-btn:hover {
            border-color: var(--primary);
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.15);
        }
        
        .lang-btn.active {
            background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
            color: white;
            border-color: transparent;
            box-shadow: 0 8px 24px rgba(99, 102, 241, 0.25);
        }
        
        .lang-btn span {
            position: relative;
            z-index: 1;
        }
        
        /* Form Group Styling */
        .form-group {
            margin-bottom: 24px;
            animation: slideInRight 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .form-group:nth-child(2) { animation-delay: 0.1s; }
        .form-group:nth-child(3) { animation-delay: 0.2s; }
        .form-group:nth-child(4) { animation-delay: 0.3s; }
        
        label {
            display: block;
            margin-bottom: 10px;
            font-weight: 600;
            color: var(--text-dark);
            font-size: 14px;
            letter-spacing: 0.3px;
            text-transform: uppercase;
            opacity: 0.8;
        }
        
        input[type="text"],
        input[type="tel"],
        select {
            width: 100%;
            padding: 14px 16px;
            border: 1px solid var(--border-light);
            border-radius: 10px;
            font-size: 15px;
            background: white;
            color: var(--text-dark);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            font-family: inherit;
        }
        
        input[type="text"]::placeholder,
        input[type="tel"]::placeholder {
            color: var(--text-light);
        }
        
        input[type="text"]:focus,
        input[type="tel"]:focus,
        select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
            background: linear-gradient(white, white) padding-box,
                        linear-gradient(135deg, var(--primary), var(--accent)) border-box;
            border: 1px solid transparent;
        }
        
        .service-buttons {
            display: grid;
            grid-template-columns: 1fr;
            gap: 12px;
            margin: 28px 0;
        }
        
        .service-btn {
            padding: 20px 20px;
            border: 1px solid var(--border-light);
            background: white;
            border-radius: 10px;
            cursor: pointer;
            font-size: 15px;
            font-weight: 600;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            color: var(--text-dark);
            text-align: left;
            position: relative;
            overflow: hidden;
        }
        
        .service-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 0;
            background: var(--primary);
            transition: height 0.3s ease;
        }
        
        .service-btn:hover {
            border-color: var(--primary);
            background: var(--bg-light);
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.1);
        }
        
        .service-btn:hover::before {
            height: 100%;
        }
        
        .service-btn.active {
            background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
            color: white;
            border-color: transparent;
            box-shadow: 0 8px 24px rgba(99, 102, 241, 0.25);
        }
        
        .service-btn.active::before {
            display: none;
        }
        
        .no-services {
            padding: 32px;
            text-align: center;
            background: #fee2e2;
            border-radius: 12px;
            color: #991b1b;
            border: 1px solid #fecaca;
            font-weight: 500;
        }
        
        .db-error {
            padding: 16px;
            background: #fef3c7;
            border: 1px solid #fcd34d;
            border-radius: 10px;
            color: #92400e;
            margin-bottom: 20px;
            font-size: 14px;
            font-weight: 500;
        }
        
        .button-group {
            display: flex;
            gap: 12px;
            margin-top: 32px;
            justify-content: space-between;
        }
        
        .btn {
            flex: 1;
            padding: 16px 20px;
            border: none;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            letter-spacing: 0.3px;
            position: relative;
            overflow: hidden;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.2);
        }
        
        .btn-primary:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(99, 102, 241, 0.3);
        }
        
        .btn-primary:active:not(:disabled) {
            transform: translateY(0);
        }
        
        .btn-primary:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .btn-secondary {
            background: var(--bg-light);
            color: var(--text-dark);
            border: 1px solid var(--border-light);
        }
        
        .btn-secondary:hover {
            background: white;
            border-color: var(--primary);
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.1);
        }
        
        .language-selector {
            position: fixed;
            bottom: 24px;
            left: 24px;
            z-index: 1000;
            animation: slideIn 0.5s ease-out 0.3s backwards;
        }
        
        html[dir="rtl"] .language-selector {
            left: auto;
            right: 24px;
        }
        
        .lang-dropdown {
            display: flex;
            gap: 8px;
            background: white;
            padding: 8px 12px;
            border-radius: 12px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.12);
            border: 1px solid var(--border-light);
            backdrop-filter: blur(10px);
        }
        
        .lang-dropdown button {
            padding: 8px 12px;
            border: 1px solid var(--border-light);
            background: transparent;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 13px;
            transition: all 0.25s ease;
            color: var(--text-light);
        }
        
        .lang-dropdown button:hover {
            border-color: var(--primary);
            color: var(--primary);
        }
        
        .lang-dropdown button.active {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }
        
        .ticket-preview {
            background: linear-gradient(135deg, #f0f9ff 0%, #e0e7ff 100%);
            padding: 32px;
            border-radius: 12px;
            text-align: center;
            margin: 28px 0;
            border: 1px solid var(--border-light);
        }
        
        .ticket-preview h2 {
            font-size: 28px;
            margin-bottom: 8px;
            color: var(--text-dark);
            font-weight: 700;
        }
        
        .ticket-number {
            font-size: 36px;
            font-weight: 700;
            background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin: 16px 0;
            letter-spacing: 2px;
        }
        
        .ticket-info {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin: 16px 0;
            text-align: left;
            border: 1px solid var(--border-light);
        }
        
        .ticket-info p {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid var(--border-light);
            font-size: 14px;
        }
        
        .ticket-info p:last-child {
            border-bottom: none;
        }
        
        .ticket-info strong {
            color: var(--text-dark);
            font-weight: 600;
        }
        
        .ticket-info span {
            color: var(--text-light);
        }
        
        .error-message {
            background: #fee2e2;
            color: #991b1b;
            padding: 16px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: none;
            border: 1px solid #fecaca;
            font-weight: 500;
        }
        
        @media print {
            body {
                background: white;
            }
            
            .card {
                box-shadow: none;
            }
            
            .language-selector,
            .button-group,
            .progress-container,
            .language-toggle-wrapper {
                display: none;
            }
        }
        
        @media (max-width: 768px) {
            .card {
                padding: 28px 24px;
            }
            
            h1 {
                font-size: 26px;
            }
            
            .language-buttons,
            .service-buttons {
                grid-template-columns: 1fr;
            }
            
            .button-group {
                flex-direction: column;
                gap: 10px;
            }
            
            .btn {
                width: 100%;
            }
            
            .progress-steps {
                flex-direction: column;
            }
            
            .language-toggle {
                flex-wrap: wrap;
            }
        }
        
        @media (max-width: 480px) {
            .card {
                padding: 20px 16px;
            }
            
            h1 {
                font-size: 22px;
            }
            
            .lang-btn {
                padding: 14px 12px;
                font-size: 14px;
            }
            
            .language-toggle-wrapper {
                margin-bottom: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container-main">
        <div class="card">
            <!-- Progress Bar -->
            <div class="progress-container">
                <div class="progress-bar">
                    <div class="progress-fill" id="progressFill" style="width: 25%;"></div>
                </div>
                <div class="progress-steps">
                    <div class="progress-step active" id="progressStep1">1</div>
                    <div class="progress-step" id="progressStep2">2</div>
                    <div class="progress-step" id="progressStep3">3</div>
                    <div class="progress-step" id="progressStep4">✓</div>
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
                        <button class="lang-toggle-btn active" onclick="selectLanguage('ar')">🇸🇦 <?php echo $t['arabic']; ?></button>
                        <button class="lang-toggle-btn active" onclick="selectLanguage('fr')">🇫🇷 <?php echo $t['french']; ?></button>
                        <button class="lang-toggle-btn active" onclick="selectLanguage('en')">🇬🇧 <?php echo $t['english']; ?></button>
                        <button class="lang-toggle-btn active" onclick="selectLanguage('it')">🇮🇹 <?php echo $t['italian']; ?></button>
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
                                    <br><small style="opacity: 0.7; font-weight: normal;"><?php echo htmlspecialchars($domaine['description']); ?></small>
                                <?php endif; ?>
                            </button>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="no-services">
                            <p>❌ Aucun service disponible</p>
                            <p style="font-size: 12px; margin-top: 10px;">Veuillez contacter l'administrateur</p>
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
    
    <!-- Sélecteur de Langue -->
    <div class="language-selector" id="languageSelector">
        <div class="lang-dropdown">
            <button onclick="changeLanguage('ar')">العربية</button>
            <button onclick="changeLanguage('fr')">FR</button>
            <button onclick="changeLanguage('en')">EN</button>
            <button onclick="changeLanguage('it')">IT</button>
        </div>
    </div>
    
    <script>
        let currentStep = 1;
        let selectedService = '';
        
        console.log('🚀 Domaines au chargement:', <?php echo json_encode($domaines); ?>);
        
        function generateQRCode() {
            const qrContainer = document.getElementById('qrcode');
            qrContainer.innerHTML = '';
            const mobileUrl = window.location.href.split('?')[0];
            new QRCode(qrContainer, {
                text: mobileUrl,
                width: 200,
                height: 200,
                colorDark: '#667eea',
                colorLight: '#ffffff',
                correctLevel: QRCode.CorrectLevel.H
            });
        }
        
        function changeLanguage(lang) {
            const langCodes = ['ar', 'fr', 'en', 'it'];
            if (!langCodes.includes(lang)) return;
            
            const langButtons = document.querySelectorAll('.lang-dropdown button');
            langButtons.forEach(btn => btn.classList.remove('active'));
            event.target.classList.add('active');
            
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
            
            const langButtons = document.querySelectorAll('.lang-dropdown button');
            langButtons.forEach(btn => btn.classList.remove('active'));
            const langMap = { 'ar': 0, 'fr': 1, 'en': 2, 'it': 3 };
            if (langMap[lang] !== undefined) {
                langButtons[langMap[lang]].classList.add('active');
            }
            
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
            submitBtn.style.opacity = '0.5';
            
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