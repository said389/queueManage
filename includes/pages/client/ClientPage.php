<?php

/**
 * ClientPage — "Espace Client" kiosk interface.
 *
 * Implements a 4-step wizard:
 *   Step 1 : Language selection + QR code
 *   Step 2 : Service (topical domain) selection
 *   Step 3 : Customer information form
 *   Step 4 : Print ticket & automatic reset
 *
 * GET  /client         → display wizard
 * POST /client         → save ticket (JSON response)
 */
class ClientPage extends Page {

    /** @var array TopicalDomain objects */
    private $topicalDomains = array();

    // -------------------------------------------------------------------------
    // Page contract
    // -------------------------------------------------------------------------

    public function canUse( $userLevel ) {
        return true; // public page — no login required
    }

    public function afterPermissionCheck() {
        try {
            $list = TopicalDomain::fromDatabaseCompleteList();
            $this->topicalDomains = $list ? $list : array();
        } catch ( Exception $e ) {
            $this->topicalDomains = array();
        }
    }

    // -------------------------------------------------------------------------
    // POST: save ticket, return JSON
    // -------------------------------------------------------------------------

    public function execute() {
        if ( $_SERVER['REQUEST_METHOD'] === 'POST'
                && !empty( $_POST['action'] )
                && $_POST['action'] === 'save_ticket' ) {
            return $this->handleSaveTicket();
        }
        return true; // GET → let getOutput() render the page
    }

    private function handleSaveTicket() {
        $fullName = isset( $_POST['full_name'] ) ? trim( $_POST['full_name'] ) : '';
        $phone    = isset( $_POST['phone'] )     ? trim( $_POST['phone'] )     : '';
        $state    = isset( $_POST['state'] )     ? trim( $_POST['state'] )     : 'standard';
        $service  = isset( $_POST['service'] )   ? trim( $_POST['service'] )   : '';
        $language = isset( $_POST['language'] )  ? trim( $_POST['language'] )  : 'fr';

        // --- validation ---
        if ( $fullName === '' || $phone === '' || $service === '' ) {
            $out = new JsonOutput();
            $out->setContent( array(
                'success' => false,
                'error'   => 'Champs obligatoires manquants',
            ) );
            return $out;
        }

        $allowedStates = array( 'standard', 'pregnant', 'pmr' );
        if ( !in_array( $state, $allowedStates, true ) ) {
            $state = 'standard';
        }

        $allowedLangs = array( 'fr', 'ar', 'en', 'it' );
        if ( !in_array( $language, $allowedLangs, true ) ) {
            $language = 'fr';
        }

        // Limit field lengths to match column sizes
        $fullName = substr( $fullName, 0, 100 );
        $phone    = substr( $phone,    0, 20  );
        $service  = substr( $service,  0, 100 );

        try {
            $db = Database::getConnection();

            // Daily ticket counter for this service (tickets created since midnight today)
            $todayStart = mktime( 0, 0, 0, date( 'n' ), date( 'j' ), date( 'Y' ) );
            $stmtCount  = $db->prepare(
                'SELECT COUNT(*) FROM tickets WHERE service = ? AND created_at >= ?'
            );
            $stmtCount->execute( array( $service, $todayStart ) );
            $ticketNumber = (int) $stmtCount->fetchColumn() + 1;

            // Insert ticket
            $stmt = $db->prepare(
                'INSERT INTO tickets
                 (full_name, phone, state, service, language, ticket_number, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute( array(
                $fullName,
                $phone,
                $state,
                $service,
                $language,
                $ticketNumber,
                time(),
            ) );
            $ticketId = (int) $db->lastInsertId();

            $out = new JsonOutput();
            $out->setContent( array(
                'success'       => true,
                'ticket_id'     => $ticketId,
                'ticket_number' => sprintf( '%03d', $ticketNumber ),
                'service'       => htmlspecialchars( $service,  ENT_QUOTES, 'UTF-8' ),
                'full_name'     => htmlspecialchars( $fullName, ENT_QUOTES, 'UTF-8' ),
                'state'         => $state,
            ) );
            return $out;

        } catch ( Exception $e ) {
            $out = new JsonOutput();
            $out->setContent( array(
                'success' => false,
                'error'   => 'Erreur de base de données',
            ) );
            return $out;
        }
    }

    // -------------------------------------------------------------------------
    // GET: render the wizard HTML page
    // -------------------------------------------------------------------------

    public function getOutput() {
        global $gvPath, $gvProtocol, $gvServerName, $gvPort;

        $port      = $gvPort ? ':' . $gvPort : '';
        $clientUrl = $gvProtocol . $gvServerName . $port . $gvPath . '/client';
        $saveUrl   = $gvPath . '/client';

        // Build services array from DB (fallback to sample data when DB is empty)
        $services = array();
        foreach ( $this->topicalDomains as $td ) {
            $services[] = array(
                'code' => htmlspecialchars( $td->getCode(), ENT_QUOTES, 'UTF-8' ),
                'name' => htmlspecialchars( $td->getName(), ENT_QUOTES, 'UTF-8' ),
            );
        }
        if ( empty( $services ) ) {
            $services = array(
                array( 'code' => 'A', 'name' => 'Service Client' ),
                array( 'code' => 'B', 'name' => 'Caisse'         ),
                array( 'code' => 'C', 'name' => 'Facturation'    ),
            );
        }

        $servicesJson   = json_encode( $services,  JSON_UNESCAPED_UNICODE );
        $clientUrlJs    = json_encode( $clientUrl, JSON_UNESCAPED_UNICODE );
        $saveUrlJs      = json_encode( $saveUrl,   JSON_UNESCAPED_UNICODE );
        $gvPathEncoded  = htmlspecialchars( $gvPath, ENT_QUOTES, 'UTF-8' );

        $output = new WebPageOutput();
        $output->setHtmlPageTitle( 'Espace Client — Smart Queue' );
        $output->addHtmlHeader( '<meta name="viewport" content="width=device-width, initial-scale=1.0">' );
        $output->addHtmlHeader( '<script src="https://cdn.tailwindcss.com"></script>' );
        $output->addHtmlHeader(
            '<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"'
            . ' crossorigin="anonymous" referrerpolicy="no-referrer"></script>'
        );
        $output->addHtmlHeader( '<style>
            [dir="rtl"] { direction: rtl; }
            @media print {
                body > *:not(#print-area) { display: none !important; }
                #print-area { display: block !important; }
            }
            #print-area { display: none; }
        </style>' );

        $output->setHtmlBodyContent(
            $this->buildWizardHtml( $servicesJson, $clientUrlJs, $saveUrlJs )
        );
        return $output;
    }

    // -------------------------------------------------------------------------
    // HTML builder
    // -------------------------------------------------------------------------

    private function buildWizardHtml( $servicesJson, $clientUrlJs, $saveUrlJs ) {
        return <<<HTML
<!-- ═══════════════════════════════════════════════════════════════════ -->
<!--  Espace Client — Smart Queue Management                           -->
<!-- ═══════════════════════════════════════════════════════════════════ -->

<!-- Print area (hidden on screen, shown on print) -->
<div id="print-area" class="p-10 text-center font-mono">
    <h1 class="text-3xl font-bold mb-4">Smart Queue Management</h1>
    <div class="border-4 border-black inline-block px-12 py-6 mt-4">
        <p class="text-6xl font-bold" id="print-ticket-num">---</p>
        <p class="text-xl mt-2" id="print-service">---</p>
    </div>
    <p class="mt-6 text-lg" id="print-name">---</p>
    <p class="mt-2 text-sm text-gray-500" id="print-time">---</p>
</div>

<!-- ─── App wrapper ─────────────────────────────────────────────── -->
<div id="app" class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 flex flex-col">

    <!-- Top bar -->
    <header class="bg-indigo-700 text-white py-4 px-6 flex items-center justify-between shadow-lg">
        <div class="flex items-center gap-3">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
            <span class="text-xl font-bold tracking-wide">Smart Queue Management</span>
        </div>
        <!-- Step indicator -->
        <div class="flex gap-2" id="step-dots">
            <span class="w-3 h-3 rounded-full bg-white opacity-100 step-dot" data-step="1"></span>
            <span class="w-3 h-3 rounded-full bg-white opacity-40 step-dot" data-step="2"></span>
            <span class="w-3 h-3 rounded-full bg-white opacity-40 step-dot" data-step="3"></span>
        </div>
    </header>

    <!-- Main content -->
    <main class="flex-1 flex items-center justify-center p-4">

        <!-- ── Step 1: Language selection ──────────────────────────── -->
        <section id="step-1" class="w-full max-w-lg">
            <div class="bg-white rounded-2xl shadow-xl overflow-hidden">
                <!-- QR code banner -->
                <div class="bg-indigo-700 p-6 text-center text-white">
                    <p class="text-sm font-medium mb-3 opacity-80" data-i18n="scan_qr">
                        Scannez pour continuer sur mobile
                    </p>
                    <div id="qrcode" class="inline-block bg-white p-3 rounded-xl shadow"></div>
                </div>

                <!-- Language buttons -->
                <div class="p-8">
                    <h2 class="text-center text-2xl font-bold text-gray-700 mb-2">
                        Espace Client
                    </h2>
                    <p class="text-center text-gray-400 text-sm mb-8">
                        Choose your language / Choisissez votre langue
                    </p>
                    <div class="grid grid-cols-2 gap-4">
                        <button onclick="selectLanguage('ar')"
                                class="lang-btn flex flex-col items-center gap-2 p-5 rounded-xl border-2 border-gray-200
                                       hover:border-indigo-500 hover:bg-indigo-50 transition-all duration-200 group">
                            <span class="text-4xl">🇸🇦</span>
                            <span class="text-xl font-bold text-gray-700 group-hover:text-indigo-600">عربي</span>
                        </button>
                        <button onclick="selectLanguage('fr')"
                                class="lang-btn flex flex-col items-center gap-2 p-5 rounded-xl border-2 border-gray-200
                                       hover:border-indigo-500 hover:bg-indigo-50 transition-all duration-200 group">
                            <span class="text-4xl">🇫🇷</span>
                            <span class="text-xl font-bold text-gray-700 group-hover:text-indigo-600">Français</span>
                        </button>
                        <button onclick="selectLanguage('en')"
                                class="lang-btn flex flex-col items-center gap-2 p-5 rounded-xl border-2 border-gray-200
                                       hover:border-indigo-500 hover:bg-indigo-50 transition-all duration-200 group">
                            <span class="text-4xl">🇬🇧</span>
                            <span class="text-xl font-bold text-gray-700 group-hover:text-indigo-600">English</span>
                        </button>
                        <button onclick="selectLanguage('it')"
                                class="lang-btn flex flex-col items-center gap-2 p-5 rounded-xl border-2 border-gray-200
                                       hover:border-indigo-500 hover:bg-indigo-50 transition-all duration-200 group">
                            <span class="text-4xl">🇮🇹</span>
                            <span class="text-xl font-bold text-gray-700 group-hover:text-indigo-600">Italiano</span>
                        </button>
                    </div>
                </div>
            </div>
        </section>

        <!-- ── Step 2: Service selection ───────────────────────────── -->
        <section id="step-2" class="w-full max-w-lg hidden">
            <div class="bg-white rounded-2xl shadow-xl overflow-hidden">
                <div class="bg-indigo-700 p-5 text-white">
                    <button onclick="goToStep(1)" class="flex items-center gap-2 opacity-80 hover:opacity-100 mb-3 text-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                        <span data-i18n="back">Retour</span>
                    </button>
                    <h2 class="text-xl font-bold" data-i18n="select_service">Sélectionnez un service</h2>
                    <p class="text-sm opacity-70 mt-1" data-i18n="step_2_of_3">Étape 2 de 3</p>
                </div>
                <div class="p-6" id="services-list">
                    <!-- Rendered by JS -->
                </div>
                <div class="px-6 pb-6">
                    <button id="btn-next-step2" onclick="goToStep(3)"
                            class="w-full py-3 rounded-xl bg-indigo-600 text-white font-semibold
                                   hover:bg-indigo-700 transition disabled:opacity-40 disabled:cursor-not-allowed"
                            disabled data-i18n="next">
                        Suivant →
                    </button>
                </div>
            </div>
        </section>

        <!-- ── Step 3: Customer information form ───────────────────── -->
        <section id="step-3" class="w-full max-w-lg hidden">
            <div class="bg-white rounded-2xl shadow-xl overflow-hidden">
                <div class="bg-indigo-700 p-5 text-white">
                    <button onclick="goToStep(2)" class="flex items-center gap-2 opacity-80 hover:opacity-100 mb-3 text-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                        <span data-i18n="back">Retour</span>
                    </button>
                    <h2 class="text-xl font-bold" data-i18n="your_info">Vos informations</h2>
                    <p class="text-sm opacity-70 mt-1" data-i18n="step_3_of_3">Étape 3 de 3</p>
                </div>
                <div class="p-6 space-y-5">
                    <!-- Full name -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1" data-i18n="full_name">
                            Nom complet
                        </label>
                        <input id="input-name" type="text" maxlength="100"
                               class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:outline-none
                                      focus:ring-2 focus:ring-indigo-400 transition"
                               placeholder="Ex: Mehdi Benali" />
                    </div>
                    <!-- Phone -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1" data-i18n="phone">
                            Téléphone
                        </label>
                        <input id="input-phone" type="tel" maxlength="20"
                               class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:outline-none
                                      focus:ring-2 focus:ring-indigo-400 transition"
                               placeholder="Ex: 0612345678" />
                    </div>
                    <!-- State -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2" data-i18n="status">
                            État
                        </label>
                        <div class="space-y-2">
                            <label class="flex items-center gap-3 p-3 rounded-lg border border-gray-200
                                          hover:bg-indigo-50 cursor-pointer has-[:checked]:border-indigo-500
                                          has-[:checked]:bg-indigo-50 transition">
                                <input type="radio" name="state" value="standard" checked class="accent-indigo-600">
                                <span class="text-sm font-medium text-gray-700" data-i18n="standard">Standard</span>
                            </label>
                            <label class="flex items-center gap-3 p-3 rounded-lg border border-gray-200
                                          hover:bg-pink-50 cursor-pointer has-[:checked]:border-pink-400
                                          has-[:checked]:bg-pink-50 transition">
                                <input type="radio" name="state" value="pregnant" class="accent-pink-500">
                                <span class="text-sm font-medium text-gray-700" data-i18n="pregnant">
                                    🤰 Femme enceinte
                                </span>
                            </label>
                            <label class="flex items-center gap-3 p-3 rounded-lg border border-gray-200
                                          hover:bg-amber-50 cursor-pointer has-[:checked]:border-amber-400
                                          has-[:checked]:bg-amber-50 transition">
                                <input type="radio" name="state" value="pmr" class="accent-amber-500">
                                <span class="text-sm font-medium text-gray-700" data-i18n="pmr">
                                    ♿ PMR / Handicap
                                </span>
                            </label>
                        </div>
                    </div>
                </div>
                <div class="px-6 pb-6">
                    <div id="form-error"
                         class="hidden mb-4 p-3 rounded-lg bg-red-50 border border-red-200 text-red-600 text-sm">
                    </div>
                    <button id="btn-print" onclick="submitTicket()"
                            class="w-full py-3 rounded-xl bg-green-600 text-white font-semibold
                                   hover:bg-green-700 transition flex items-center justify-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2
                                     2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2
                                     2 0 00-2 2v4h10z"/>
                        </svg>
                        <span data-i18n="print_ticket">🖨️ Imprimer le ticket</span>
                    </button>
                </div>
            </div>
        </section>

    </main>

    <!-- Persistent language selector (bottom-left, steps 2 & 3) -->
    <div id="lang-selector-bar"
         class="hidden fixed bottom-4 left-4 z-50 bg-white rounded-xl shadow-lg border border-gray-200">
        <button id="lang-toggle"
                onclick="toggleLangMenu()"
                class="flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 rounded-xl">
            <span id="lang-flag">🌐</span>
            <span id="lang-label">Français</span>
            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
        </button>
        <div id="lang-menu" class="hidden absolute bottom-full left-0 mb-2 bg-white rounded-xl shadow-xl
                                   border border-gray-200 overflow-hidden min-w-max">
            <button onclick="changeLang('ar')" class="lang-opt w-full text-left px-4 py-2 flex items-center gap-2
                                                       hover:bg-indigo-50 text-sm">🇸🇦 عربي</button>
            <button onclick="changeLang('fr')" class="lang-opt w-full text-left px-4 py-2 flex items-center gap-2
                                                       hover:bg-indigo-50 text-sm">🇫🇷 Français</button>
            <button onclick="changeLang('en')" class="lang-opt w-full text-left px-4 py-2 flex items-center gap-2
                                                       hover:bg-indigo-50 text-sm">🇬🇧 English</button>
            <button onclick="changeLang('it')" class="lang-opt w-full text-left px-4 py-2 flex items-center gap-2
                                                       hover:bg-indigo-50 text-sm">🇮🇹 Italiano</button>
        </div>
    </div>

</div><!-- /#app -->

<!-- ═══════════════════════════════════════════════════════════════════ -->
<!--  JavaScript                                                       -->
<!-- ═══════════════════════════════════════════════════════════════════ -->
<script>
(function () {
    'use strict';

    // ── Configuration ──────────────────────────────────────────────────
    var SERVICES   = {$servicesJson};
    var CLIENT_URL = {$clientUrlJs};
    var SAVE_URL   = {$saveUrlJs};

    // ── i18n translations ──────────────────────────────────────────────
    var I18N = {
        fr: {
            scan_qr:        'Scannez pour continuer sur mobile',
            select_service: 'Sélectionnez un service',
            step_2_of_3:    'Étape 2 de 3',
            step_3_of_3:    'Étape 3 de 3',
            your_info:      'Vos informations',
            full_name:      'Nom complet',
            phone:          'Téléphone',
            status:         'État',
            standard:       'Standard',
            pregnant:       '🤰 Femme enceinte',
            pmr:            '♿ PMR / Handicap',
            print_ticket:   '🖨️ Imprimer le ticket',
            next:           'Suivant →',
            back:           '← Retour',
            loading:        'Enregistrement…',
            error_fields:   'Veuillez remplir tous les champs.',
            error_server:   'Erreur serveur. Veuillez réessayer.',
            ticket_ready:   'Ticket prêt ! Impression en cours…',
        },
        ar: {
            scan_qr:        'امسح للمتابعة على الهاتف',
            select_service: 'اختر الخدمة',
            step_2_of_3:    'الخطوة 2 من 3',
            step_3_of_3:    'الخطوة 3 من 3',
            your_info:      'معلوماتك',
            full_name:      'الاسم الكامل',
            phone:          'الهاتف',
            status:         'الحالة',
            standard:       'عادي',
            pregnant:       '🤰 حامل',
            pmr:            '♿ ذوو الاحتياجات',
            print_ticket:   '🖨️ طباعة التذكرة',
            next:           'التالي →',
            back:           '← رجوع',
            loading:        'جارٍ الحفظ…',
            error_fields:   'يرجى ملء جميع الحقول.',
            error_server:   'خطأ في الخادم. يرجى المحاولة مجددًا.',
            ticket_ready:   'التذكرة جاهزة! جارٍ الطباعة…',
        },
        en: {
            scan_qr:        'Scan to continue on mobile',
            select_service: 'Select a service',
            step_2_of_3:    'Step 2 of 3',
            step_3_of_3:    'Step 3 of 3',
            your_info:      'Your information',
            full_name:      'Full name',
            phone:          'Phone',
            status:         'Status',
            standard:       'Standard',
            pregnant:       '🤰 Pregnant',
            pmr:            '♿ PRM / Disability',
            print_ticket:   '🖨️ Print ticket',
            next:           'Next →',
            back:           '← Back',
            loading:        'Saving…',
            error_fields:   'Please fill in all required fields.',
            error_server:   'Server error. Please try again.',
            ticket_ready:   'Ticket ready! Printing…',
        },
        it: {
            scan_qr:        'Scansiona per continuare sul cellulare',
            select_service: 'Seleziona un servizio',
            step_2_of_3:    'Passaggio 2 di 3',
            step_3_of_3:    'Passaggio 3 di 3',
            your_info:      'Le tue informazioni',
            full_name:      'Nome completo',
            phone:          'Telefono',
            status:         'Stato',
            standard:       'Standard',
            pregnant:       '🤰 Donna incinta',
            pmr:            '♿ PMR / Disabilità',
            print_ticket:   '🖨️ Stampa biglietto',
            next:           'Avanti →',
            back:           '← Indietro',
            loading:        'Salvataggio…',
            error_fields:   'Compila tutti i campi richiesti.',
            error_server:   'Errore del server. Riprova.',
            ticket_ready:   'Biglietto pronto! Stampa in corso…',
        },
    };

    var LANG_META = {
        fr: { flag: '🇫🇷', label: 'Français', dir: 'ltr' },
        ar: { flag: '🇸🇦', label: 'عربي',     dir: 'rtl' },
        en: { flag: '🇬🇧', label: 'English',  dir: 'ltr' },
        it: { flag: '🇮🇹', label: 'Italiano', dir: 'ltr' },
    };

    // ── State ──────────────────────────────────────────────────────────
    var currentStep     = 1;
    var currentLang     = 'fr';
    var selectedService = null; // { code, name }

    // ── Init ───────────────────────────────────────────────────────────
    function init() {
        generateQrCode();
        renderServices();
    }

    // ── QR Code ────────────────────────────────────────────────────────
    function generateQrCode() {
        new QRCode( document.getElementById('qrcode'), {
            text:          CLIENT_URL,
            width:         140,
            height:        140,
            colorDark:     '#3730a3',
            colorLight:    '#ffffff',
            correctLevel:  QRCode.CorrectLevel.H,
        });
    }

    // ── Services list ──────────────────────────────────────────────────
    function renderServices() {
        var container = document.getElementById('services-list');
        container.innerHTML = '';
        SERVICES.forEach(function (svc) {
            var btn = document.createElement('button');
            btn.className =
                'service-btn w-full text-left px-5 py-4 mb-3 rounded-xl border-2 border-gray-200 ' +
                'hover:border-indigo-500 hover:bg-indigo-50 transition-all duration-200 flex items-center gap-4 group';
            btn.innerHTML =
                '<span class="w-10 h-10 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center font-bold text-lg group-hover:bg-indigo-600 group-hover:text-white transition">' +
                    escHtml(svc.code) +
                '</span>' +
                '<span class="font-medium text-gray-700 group-hover:text-indigo-700">' + escHtml(svc.name) + '</span>';
            btn.addEventListener('click', function () {
                selectService(svc);
                // Highlight selected
                document.querySelectorAll('.service-btn').forEach(function (b) {
                    b.classList.remove('border-indigo-500', 'bg-indigo-50');
                });
                btn.classList.add('border-indigo-500', 'bg-indigo-50');
            });
            container.appendChild(btn);
        });
    }

    function selectService(svc) {
        selectedService = svc;
        document.getElementById('btn-next-step2').disabled = false;
    }

    // ── Step navigation ────────────────────────────────────────────────
    window.goToStep = function (step) {
        document.getElementById('step-' + currentStep).classList.add('hidden');
        currentStep = step;
        document.getElementById('step-' + currentStep).classList.remove('hidden');

        // Update dots
        document.querySelectorAll('.step-dot').forEach(function (dot) {
            var s = parseInt(dot.dataset.step, 10);
            dot.style.opacity = s === currentStep ? '1' : '0.4';
        });

        // Show/hide persistent lang selector
        var bar = document.getElementById('lang-selector-bar');
        if (step === 1) {
            bar.classList.add('hidden');
        } else {
            bar.classList.remove('hidden');
        }
    };

    // ── Language ───────────────────────────────────────────────────────
    window.selectLanguage = function (lang) {
        currentLang = lang;
        applyLanguage(lang);
        goToStep(2);
    };

    window.changeLang = function (lang) {
        currentLang = lang;
        applyLanguage(lang);
        document.getElementById('lang-menu').classList.add('hidden');
    };

    window.toggleLangMenu = function () {
        document.getElementById('lang-menu').classList.toggle('hidden');
    };

    function applyLanguage(lang) {
        var t    = I18N[lang] || I18N.fr;
        var meta = LANG_META[lang] || LANG_META.fr;

        // Update all data-i18n elements
        document.querySelectorAll('[data-i18n]').forEach(function (el) {
            var key = el.dataset.i18n;
            if (t[key] !== undefined) {
                el.textContent = t[key];
            }
        });

        // Persist placeholder on phone input
        var phoneInput = document.getElementById('input-phone');
        if (lang === 'ar') {
            phoneInput.placeholder = 'مثال: 0612345678';
        } else {
            phoneInput.placeholder = 'Ex: 0612345678';
        }

        // Update lang selector button
        document.getElementById('lang-flag').textContent  = meta.flag;
        document.getElementById('lang-label').textContent = meta.label;

        // RTL support
        document.getElementById('app').dir = meta.dir;
    }

    // ── Submit / print ticket ──────────────────────────────────────────
    window.submitTicket = function () {
        var t = I18N[currentLang] || I18N.fr;

        var name  = document.getElementById('input-name').value.trim();
        var phone = document.getElementById('input-phone').value.trim();
        var state = document.querySelector('input[name="state"]:checked');

        var errorDiv = document.getElementById('form-error');

        if (!name || !phone || !state || !selectedService) {
            errorDiv.textContent = t.error_fields;
            errorDiv.classList.remove('hidden');
            return;
        }
        errorDiv.classList.add('hidden');

        var btn = document.getElementById('btn-print');
        btn.disabled   = true;
        btn.textContent = t.loading;

        var body = new URLSearchParams();
        body.append('action',    'save_ticket');
        body.append('full_name', name);
        body.append('phone',     phone);
        body.append('state',     state.value);
        body.append('service',   selectedService.name);
        body.append('language',  currentLang);

        fetch(SAVE_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body:    body.toString(),
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (data.success) {
                preparePrintArea(data, selectedService.name);
                btn.textContent = t.ticket_ready;
                setTimeout(function () {
                    window.print();
                    // Reset after print dialog closes (or after a fixed delay)
                    setTimeout(function () {
                        resetWizard();
                    }, 1500);
                }, 400);
            } else {
                errorDiv.textContent = data.error || t.error_server;
                errorDiv.classList.remove('hidden');
                btn.disabled    = false;
                btn.textContent = t.print_ticket;
            }
        })
        .catch(function () {
            errorDiv.textContent = t.error_server;
            errorDiv.classList.remove('hidden');
            btn.disabled    = false;
            btn.textContent = t.print_ticket;
        });
    };

    function preparePrintArea(data, serviceName) {
        document.getElementById('print-ticket-num').textContent = data.ticket_number;
        document.getElementById('print-service').textContent    = serviceName;
        document.getElementById('print-name').textContent       = data.full_name;
        var now = new Date();
        document.getElementById('print-time').textContent =
            now.toLocaleDateString() + '  ' + now.toLocaleTimeString();
    }

    function resetWizard() {
        // Reset form fields
        document.getElementById('input-name').value  = '';
        document.getElementById('input-phone').value = '';
        var std = document.querySelector('input[name="state"][value="standard"]');
        if (std) { std.checked = true; }
        selectedService = null;

        // Reset service selection highlight
        document.querySelectorAll('.service-btn').forEach(function (b) {
            b.classList.remove('border-indigo-500', 'bg-indigo-50');
        });
        document.getElementById('btn-next-step2').disabled = true;

        // Reset print button
        var btn = document.getElementById('btn-print');
        btn.disabled    = false;
        var t = I18N[currentLang] || I18N.fr;
        btn.textContent = t.print_ticket;

        // Go back to step 1
        goToStep(1);
    }

    // ── Helpers ────────────────────────────────────────────────────────
    function escHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    // Close lang menu on outside click
    document.addEventListener('click', function (e) {
        var menu   = document.getElementById('lang-menu');
        var toggle = document.getElementById('lang-toggle');
        if (!menu || !toggle) { return; }
        if (!menu.contains(e.target) && !toggle.contains(e.target)) {
            menu.classList.add('hidden');
        }
    });

    // ── Bootstrap ──────────────────────────────────────────────────────
    document.addEventListener('DOMContentLoaded', init);
}());
</script>
HTML;
    }
}
