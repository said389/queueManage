<?php

class AdminStats extends Page {

    public static $MIN_DATE = 1;
    public static $MAX_DATE = 2145916800;

    protected $dateFrom;
    protected $dateTo;

    public function __construct() {
        Database::lockTables(false);
        $this->dateFrom = self::$MIN_DATE;
        $this->dateTo   = self::$MAX_DATE;
    }

    public function canUse($userLevel) {
        return $userLevel === Page::SYSADMIN_USER;
    }

    public function execute() {
        global $gvTimeZone;

        if (!empty($_POST['from']) && !empty($_POST['to'])) {

            $dateFrom = strtotime($_POST['from'] . " $gvTimeZone");
            $dateTo   = strtotime($_POST['to']   . " $gvTimeZone");

            if ($dateTo && $dateFrom && $dateTo > $dateFrom) {
                $this->dateFrom = $dateFrom;
                $this->dateTo   = $dateTo;
            }
        }
        return true;
    }

    public function getPageTitle() {
        return "Statistiche";
    }

    public function getOutput() {

        $page = new WebPageOutput();
        $page->setHtmlPageTitle("Statistiche");

        $page->setHtmlBodyHeader($this->getDesignCSS());
        $page->setHtmlBodyContent($this->getLayout());

        return $page;
    }


    /** ✅ LAYOUT PRINCIPAL */
    private function getLayout() {
        global $gvPath;

        $timeSpan = $this->getTimeSpanString();

        return <<<HTML
<div class="layout">
    <!-- Sidebar Toggle pour mobile -->
    <button class="mobile-toggle" id="mobileToggle">
        <i class="fas fa-bars"></i>
    </button>
    
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="logo">
               
                <h2>FastQueue</h2>
            </div>
            <span class="admin-badge">Administrateur</span>
        </div>

        <nav class="sidebar-nav">
            <a href="$gvPath/application/adminPage" class="nav-item">
                <i class="fas fa-tachometer-alt"></i>
                <span>Tableau de bord</span>
            </a>
            <a href="$gvPath/application/adminOperatorList" class="nav-item">
                <i class="fas fa-users"></i>
                <span>Opérateurs</span>
            </a>
            <a href="$gvPath/application/adminDeskList" class="nav-item">
                <i class="fas fa-desktop"></i>
                <span>Compteurs</span>
            </a>
            <a href="$gvPath/application/adminTopicalDomainList" class="nav-item">
                <i class="fas fa-folder-tree"></i>
                <span>Domaines thématiques</span>
            </a>
            <a href="$gvPath/application/adminDeviceList" class="nav-item">
                <i class="fas fa-mobile-alt"></i>
                <span>Appareils</span>
            </a>
            <a href="$gvPath/application/adminStats" class="nav-item active">
                <i class="fas fa-chart-line"></i>
                <span>Statistiques</span>
            </a>
            <a href="$gvPath/application/adminSettings" class="nav-item">
                <i class="fas fa-cog"></i>
                <span>Paramètres</span>
            </a>
        </nav>

        <div class="sidebar-footer">
            <a href="$gvPath/application/logoutPage" class="nav-item logout">
                <i class="fas fa-sign-out-alt"></i>
                <span>Déconnexion</span>
            </a>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <div class="content-wrapper">
            <div class="page-header">
                
                <h1 >Statistiques du système</h1>
                <p class="subtitle">Analyse des tickets et performances</p>
            </div>

            <!-- Période -->
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-calendar-alt"></i>
                    <h3>Période : <span class="highlight">$timeSpan</span></h3>
                </div>
                {$this->getForm()}
            </div>

            <!-- Stats par domaine -->
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-chart-bar"></i>
                    <h3>Statistiques par domaine thématique</h3>
                </div>
                {$this->getTdStatsTable()}
            </div>

            <!-- Stats par source -->
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-globe"></i>
                    <h3>Statistiques par source</h3>
                </div>
                {$this->getSourceStatsTable()}
            </div>

            <!-- Stats par opérateur -->
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-user-tie"></i>
                    <h3>Statistiques par opérateur</h3>
                </div>
                {$this->getStatsOperatorTables()}
            </div>
        </div>
    </main>
</div>

<!-- Overlay pour mobile -->
<div class="overlay" id="overlay"></div>

<script>
const mobileToggle = document.getElementById('mobileToggle');
const sidebar = document.getElementById('sidebar');
const overlay = document.getElementById('overlay');

if (mobileToggle) {
    mobileToggle.addEventListener('click', () => {
        sidebar.classList.add('open');
        overlay.classList.add('show');
    });
}

if (overlay) {
    overlay.addEventListener('click', () => {
        sidebar.classList.remove('open');
        overlay.classList.remove('show');
    });
}

function resetDates() {
    document.querySelector('input[name="from"]').value = '';
    document.querySelector('input[name="to"]').value = '';
    document.querySelector('form').submit();
}

function toggleOperator(element) {
    const group = element.closest('.operator-group');
    group.classList.toggle('expanded');
}

// Fermer sidebar sur resize si écran large
window.addEventListener('resize', () => {
    if (window.innerWidth > 768) {
        sidebar.classList.remove('open');
        overlay.classList.remove('show');
    }
});
</script>
HTML;
    }

    private function getTimeSpanString() {
        if ($this->dateFrom == self::$MIN_DATE && $this->dateTo == self::$MAX_DATE)
            return "Complet";

        global $gvTimeZone;
        $zone = new DateTimeZone($gvTimeZone);

        $from = new DateTime('@'.$this->dateFrom);
        $from->setTimezone($zone);
        $from = $from->format('d/m/Y');

        $to = new DateTime('@'.$this->dateTo);
        $to->setTimezone($zone);
        $to = $to->format('d/m/Y');

        return "Du $from au $to";
    }

    public function getTdStatsTable() {
        $rows = $this->getTdStatsRows();

        return <<<HTML
<div class="table-container">
    <table class="stats-table">
        <thead>
            <tr>
                <th>Domaine thématique</th>
                <th>Billets</th>
                <th>Temps d'attente moyen</th>
                <th>Exécution moyenne</th>
            </tr>
        </thead>
        <tbody>
            $rows
        </tbody>
    </table>
</div>
HTML;
    }

    private function getTdStatsRows() {
        $rows = "";
        $list = TopicalDomain::fromDatabaseCompleteList(false);

        if (empty($list)) {
            return '<tr><td colspan="4" class="empty-row">Aucune donnée disponible</td></tr>';
        }

        foreach ($list as $td) {
            $tickets = TicketStats::fromDatabaseListByCode(
                $td->getCode(),
                $this->dateFrom,
                $this->dateTo
            );

            $wait = 0; 
            $exec = 0; 
            $count = 0;

            foreach ($tickets as $t) {
                if (!$t->getTimeExec()) continue;

                $wait += ($t->getTimeExec() - $t->getTimeIn());
                $exec += ($t->getTimeOut() - $t->getTimeExec());
                $count++;
            }

            $avgWait = $count ? floor($wait / $count / 60) : 0;
            $avgExec = $count ? floor($exec / $count / 60) : 0;
            
            $waitClass = $avgWait > 60 ? 'badge danger' : ($avgWait > 30 ? 'badge warning' : 'badge success');

            $rows .= <<<HTML
<tr>
    <td class="td-name">{$td->getName()}</td>
    <td class="td-number">$count</td>
    <td><span class="$waitClass">{$avgWait} min</span></td>
    <td><span class="badge info">{$avgExec} min</span></td>
</tr>
HTML;
        }

        return $rows;
    }

    public function getSourceStatsTable() {
        $sources = [
            'app' => ['label' => 'Application Mobile', 'icon' => 'fab fa-android', 'color' => '#3DDC84'],
            'totem' => ['label' => 'Bornes', 'icon' => 'fas fa-tv', 'color' => '#6C63FF'],
            'web' => ['label' => 'Site Web', 'icon' => 'fas fa-globe', 'color' => '#00A8FF']
        ];
        
        $count = [];
        $total = 0;

        foreach ($sources as $src => $info) {
            $arr = TicketStats::fromDatabaseListBySource(
                $src,
                $this->dateFrom,
                $this->dateTo
            );
            $count[$src] = count($arr);
            $total += $count[$src];
        }

        $html = '<div class="source-grid">';
        
        foreach ($sources as $src => $info) {
            $percentage = $total ? round(($count[$src] / $total) * 100, 1) : 0;
            $html .= <<<HTML
            <div class="source-card">
                <div class="source-icon" style="background: {$info['color']}">
                    <i class="{$info['icon']}"></i>
                </div>
                <div class="source-details">
                    <div class="source-label">{$info['label']}</div>
                    <div class="source-count">{$count[$src]}</div>
                    <div class="progress">
                        <div class="progress-bar" style="width: {$percentage}%"></div>
                    </div>
                    <div class="source-percent">{$percentage}%</div>
                </div>
            </div>
HTML;
        }
        
        $html .= <<<HTML
        </div>
        <div class="source-total">
            <i class="fas fa-ticket-alt"></i>
            Total des billets : <strong>{$total}</strong>
        </div>
HTML;
        
        return $html;
    }

    public function getStatsOperatorTables() {
        $ops = Operator::fromDatabaseCompleteList();
        
        if (empty($ops)) {
            return '<div class="empty-state">Aucun opérateur trouvé</div>';
        }
        
        $html = '<div class="operators-list">';

        foreach ($ops as $op) {
            $rows = $this->getRowsForOperator($op);
            
            $html .= <<<HTML
            <div class="operator-card">
                <div class="operator-title" onclick="toggleOperator(this)">
                    <div class="operator-avatar">
                        <i class="fas fa-user-circle"></i>
                    </div>
                    <div class="operator-info">
                        <div class="operator-name">{$op->getFullName()}</div>
                        <div class="operator-code">{$op->getCode()}</div>
                    </div>
                    <i class="fas fa-chevron-down toggle-icon"></i>
                </div>
                <div class="operator-stats">
                    <div class="table-container">
                        <table class="stats-table">
                            <thead>
                                <tr>
                                    <th>Domaine</th>
                                    <th>Billets</th>
                                    <th>%</th>
                                    <th>Temps moyen</th>
                                </tr>
                            </thead>
                            <tbody>
                                $rows
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
HTML;
        }
        
        $html .= '</div>';
        return $html;
    }

    public function getRowsForOperator($op) {
        $stats = [];
        $tickets = TicketStats::fromDatabaseListByOperator(
            $op->getCode(),
            $this->dateFrom,
            $this->dateTo
        );

        $total = count($tickets);
        $execSum = 0;

        foreach ($tickets as $t) {
            $code = $t->getCode();

            if (!isset($stats[$code]))
                $stats[$code] = ['count' => 0, 'exec' => 0];

            $exec = $t->getTimeOut() - $t->getTimeExec();

            $stats[$code]['count']++;
            $stats[$code]['exec'] += $exec;
            $execSum += $exec;
        }

        if (!$total) {
            return '<tr><td colspan="4" class="empty-row">Aucun ticket traité</td></tr>';
        }

        ksort($stats);
        $rows = "";

        foreach ($stats as $code => $s) {
            $perc = round(($s['count'] / $total) * 100, 1);
            $avg = floor($s['exec'] / $s['count'] / 60);

            $rows .= <<<HTML
            <tr>
                <td class="td-name">$code</td>
                <td class="td-number">{$s['count']}</td>
                <td>
                    <div class="percent-bar">
                        <div class="percent-fill" style="width: {$perc}%"></div>
                        <span class="percent-text">{$perc}%</span>
                    </div>
                </td>
                <td><span class="badge info">{$avg} min</span></td>
            </tr>
HTML;
        }

        $avgTotal = $total ? floor($execSum / $total / 60) : 0;

        $rows .= <<<HTML
            <tr class="total-row">
                <td><strong>Total</strong></td>
                <td><strong>{$total}</strong></td>
                <td><strong>100%</strong></td>
                <td><span class="badge primary"><strong>{$avgTotal} min</strong></span></td>
            </tr>
HTML;

        return $rows;
    }

    public function getForm() {
        $fromDate = $this->dateFrom != self::$MIN_DATE ? date('d/m/Y', $this->dateFrom) : '';
        $toDate = $this->dateTo != self::$MAX_DATE ? date('d/m/Y', $this->dateTo) : '';

        return <<<HTML
        <form method="post" class="date-form">
            <div class="form-row">
                <div class="form-group">
                    <label>Du :</label>
                    <input type="text" name="from" class="date-input" placeholder="jj/mm/aaaa" value="$fromDate">
                </div>
                <div class="form-group">
                    <label>Au :</label>
                    <input type="text" name="to" class="date-input" placeholder="jj/mm/aaaa" value="$toDate">
                </div>
                <div class="form-buttons">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-sync-alt"></i> Mettre à jour
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="resetDates()">
                        <i class="fas fa-undo-alt"></i> Réinitialiser
                    </button>
                </div>
            </div>
        </form>
HTML;
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
            background: #f5f7fb;
            color: #1a1a2e;
            overflow-x: hidden;
        }

        /* Layout */
        .layout {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            width: 280px;
            background: linear-gradient(180deg, #1a1a2e 0%, #16213e 100%);
            color: #fff;
            display: flex;
            flex-direction: column;
            position: fixed;
            left: 0;
            top: 0;
            height: 100vh;
            z-index: 1000;
            transition: transform 0.3s ease;
            overflow-y: auto;
        }

        .sidebar-header {
            padding: 30px 25px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 15px;
        }

        .logo i {
            font-size: 28px;
            color: #6C63FF;
        }

        .logo h2 {
            font-size: 22px;
            font-weight: 700;
        }

        .admin-badge {
            background: rgba(108, 99, 255, 0.2);
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            color: #6C63FF;
            display: inline-block;
        }

        .sidebar-nav {
            flex: 1;
            padding: 20px 0;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 25px;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            transition: all 0.3s ease;
            font-size: 14px;
            font-weight: 500;
        }

        .nav-item i {
            width: 20px;
            font-size: 18px;
        }

        .nav-item:hover {
            background: rgba(108, 99, 255, 0.1);
            color: #fff;
        }

        .nav-item.active {
            background: linear-gradient(90deg, #6C63FF, rgba(108, 99, 255, 0.1));
            color: #fff;
            border-right: 3px solid #6C63FF;
        }

        .sidebar-footer {
            padding: 20px 0;
            border-top: 1px solid rgba(255,255,255,0.1);
        }

        .logout {
            color: #ff6b6b;
        }

        .logout:hover {
            background: rgba(255, 107, 107, 0.1);
        }

        /* Mobile Toggle */
        .mobile-toggle {
            display: none;
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1001;
            background: #6C63FF;
            border: none;
            width: 45px;
            height: 45px;
            border-radius: 12px;
            color: white;
            font-size: 20px;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        /* Overlay */
        .overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 999;
        }

        .overlay.show {
            display: block;
        }

        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 280px;
            min-height: 100vh;
            background: #f5f7fb;
        }

        .content-wrapper {
            padding: 30px 40px;
            max-width: 1400px;
            margin: 0 auto;
        }

        /* Page Header */
        .page-header {
            margin-bottom: 30px;
        }

        .page-header h1 {
            font-size: 28px;
            font-weight: 700;
            color: #1a1a2e;
            margin-bottom: 8px;
        }

        .subtitle {
            color: #666;
            font-size: 14px;
        }

        /* Cards */
        .card {
            background: white;
            border-radius: 16px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .card-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }

        .card-header i {
            font-size: 22px;
            color: #6C63FF;
        }

        .card-header h3 {
            font-size: 18px;
            font-weight: 600;
            color: #1a1a2e;
        }

        .highlight {
            color: #6C63FF;
            font-weight: 700;
        }

        /* Form */
        .date-form {
            width: 100%;
        }

        .form-row {
            display: flex;
            gap: 20px;
            align-items: flex-end;
            flex-wrap: wrap;
        }

        .form-group {
            flex: 1;
            min-width: 180px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            font-size: 13px;
            color: #666;
        }

        .date-input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            font-size: 14px;
            font-family: inherit;
            transition: all 0.3s ease;
        }

        .date-input:focus {
            outline: none;
            border-color: #6C63FF;
            box-shadow: 0 0 0 3px rgba(108,99,255,0.1);
        }

        .form-buttons {
            display: flex;
            gap: 12px;
        }

        .btn {
            padding: 12px 24px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
            font-family: inherit;
        }

        .btn-primary {
            background: linear-gradient(135deg, #6C63FF, #8B82FF);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(108,99,255,0.4);
        }

        .btn-secondary {
            background: #f0f0f0;
            color: #666;
        }

        .btn-secondary:hover {
            background: #e0e0e0;
        }

        /* Tables */
        .table-container {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        .stats-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 500px;
        }

        .stats-table thead th {
            text-align: left;
            padding: 15px;
            background: #f8f9fa;
            font-weight: 600;
            font-size: 13px;
            color: #666;
            border-bottom: 2px solid #e0e0e0;
        }

        .stats-table tbody td {
            padding: 15px;
            border-bottom: 1px solid #f0f0f0;
            font-size: 14px;
        }

        .stats-table tbody tr:hover {
            background: #f8f9fa;
        }

        .td-name {
            font-weight: 600;
            color: #1a1a2e;
        }

        .td-number {
            font-weight: 700;
            color: #6C63FF;
        }

        .total-row {
            background: #f8f9fa;
            font-weight: 600;
        }

        .empty-row {
            text-align: center;
            padding: 40px;
            color: #999;
        }

        /* Badges */
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge.success {
            background: #d4edda;
            color: #155724;
        }

        .badge.warning {
            background: #fff3cd;
            color: #856404;
        }

        .badge.danger {
            background: #f8d7da;
            color: #721c24;
        }

        .badge.info {
            background: #d1ecf1;
            color: #0c5460;
        }

        .badge.primary {
            background: linear-gradient(135deg, #6C63FF, #8B82FF);
            color: white;
        }

        /* Source Grid */
        .source-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .source-card {
            display: flex;
            gap: 15px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 16px;
            transition: transform 0.3s ease;
        }

        .source-card:hover {
            transform: translateY(-3px);
        }

        .source-icon {
            width: 55px;
            height: 55px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            color: white;
            flex-shrink: 0;
        }

        .source-details {
            flex: 1;
        }

        .source-label {
            font-size: 13px;
            color: #666;
            margin-bottom: 5px;
        }

        .source-count {
            font-size: 28px;
            font-weight: 800;
            color: #1a1a2e;
            margin-bottom: 10px;
        }

        .progress {
            background: #e0e0e0;
            border-radius: 10px;
            height: 6px;
            overflow: hidden;
            margin-bottom: 8px;
        }

        .progress-bar {
            background: linear-gradient(90deg, #6C63FF, #8B82FF);
            height: 100%;
            border-radius: 10px;
            transition: width 0.3s ease;
        }

        .source-percent {
            font-size: 12px;
            font-weight: 600;
            color: #6C63FF;
        }

        .source-total {
            text-align: right;
            padding-top: 15px;
            border-top: 1px solid #e0e0e0;
            font-size: 14px;
        }

        .source-total strong {
            color: #6C63FF;
            font-size: 18px;
        }

        /* Operators List */
        .operators-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .operator-card {
            border: 1px solid #e0e0e0;
            border-radius: 16px;
            overflow: hidden;
            background: white;
        }

        .operator-title {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 20px;
            background: #f8f9fa;
            cursor: pointer;
            transition: background 0.3s ease;
        }

        .operator-title:hover {
            background: #f0f0f0;
        }

        .operator-avatar {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #6C63FF, #8B82FF);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
            flex-shrink: 0;
        }

        .operator-info {
            flex: 1;
        }

        .operator-name {
            font-weight: 700;
            font-size: 16px;
            color: #1a1a2e;
        }

        .operator-code {
            font-size: 12px;
            color: #666;
        }

        .toggle-icon {
            color: #999;
            transition: transform 0.3s ease;
        }

        .operator-card.expanded .toggle-icon {
            transform: rotate(180deg);
        }

        .operator-stats {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease;
        }

        .operator-card.expanded .operator-stats {
            max-height: 2000px;
        }

        /* Percent Bar */
        .percent-bar {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .percent-fill {
            width: 80px;
            height: 6px;
            background: linear-gradient(90deg, #6C63FF, #8B82FF);
            border-radius: 3px;
        }

        .percent-text {
            font-size: 12px;
            font-weight: 600;
            color: #6C63FF;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .content-wrapper {
                padding: 20px 25px;
            }
        }

        @media (max-width: 768px) {
            .mobile-toggle {
                display: block;
            }

            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.open {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
            }

            .content-wrapper {
                padding: 80px 15px 20px 15px;
            }

            .form-row {
                flex-direction: column;
                align-items: stretch;
            }

            .form-buttons {
                flex-direction: column;
            }

            .btn {
                width: 100%;
            }

            .source-grid {
                grid-template-columns: 1fr;
            }

            .card {
                padding: 15px;
            }

            .page-header h1 {
                font-size: 24px;
            }
        }

        @media (max-width: 480px) {
            .content-wrapper {
                padding: 70px 12px 15px 12px;
            }

            .stats-table {
                min-width: 400px;
            }

            .operator-title {
                padding: 15px;
            }

            .operator-avatar {
                width: 40px;
                height: 40px;
                font-size: 20px;
            }

            .operator-name {
                font-size: 14px;
            }
        }

        /* Print */
        @media print {
            .sidebar,
            .mobile-toggle,
            .overlay,
            .form-buttons {
                display: none;
            }

            .main-content {
                margin-left: 0;
            }

            .content-wrapper {
                padding: 0;
            }

            .card {
                break-inside: avoid;
                box-shadow: none;
                border: 1px solid #ddd;
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
?>