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
        return "Statistiques";
    }

    public function getOutput() {

        $page = new WebPageOutput();
        $page->setHtmlPageTitle("Statistiques");

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
                <div class="header-content">
                    <h1>Statistiques du système</h1>
                    <p class="subtitle">Analyse complète des tickets et performances</p>
                </div>
            </div>

            <!-- Filtres -->
            <div class="card filter-card">
                <div class="card-header">
                    <i class="fas fa-filter"></i>
                    <h3>Période : <span class="highlight">$timeSpan</span></h3>
                </div>
                {$this->getForm()}
            </div>

            <!-- KPI Cards -->
            <div class="kpi-grid">
                {$this->getKPICards()}
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
                    <i class="fas fa-layer-group"></i>
                    <h3>Distribution par source</h3>
                </div>
                {$this->getSourceStatsTable()}
            </div>

            <!-- Stats par opérateur -->
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-user-tie"></i>
                    <h3>Performance par opérateur</h3>
                </div>
                {$this->getStatsOperatorTables()}
            </div>
        </div>
    </main>
</div>

<!-- Overlay pour mobile -->
<div class="overlay" id="overlay"></div>

<!-- Date Picker Popovers -->
<div class="datepicker-popover" id="datePickerFrom">
    <div class="datepicker-header">
        <button class="datepicker-prev" onclick="prevMonth('from')"><i class="fas fa-chevron-left"></i></button>
        <span class="datepicker-month-year" id="monthYearFrom"></span>
        <button class="datepicker-next" onclick="nextMonth('from')"><i class="fas fa-chevron-right"></i></button>
    </div>
    <div class="datepicker-weekdays">
        <div>Lun</div>
        <div>Mar</div>
        <div>Mer</div>
        <div>Jeu</div>
        <div>Ven</div>
        <div>Sam</div>
        <div>Dim</div>
    </div>
    <div class="datepicker-days" id="daysFrom"></div>
</div>

<div class="datepicker-popover" id="datePickerTo">
    <div class="datepicker-header">
        <button class="datepicker-prev" onclick="prevMonth('to')"><i class="fas fa-chevron-left"></i></button>
        <span class="datepicker-month-year" id="monthYearTo"></span>
        <button class="datepicker-next" onclick="nextMonth('to')"><i class="fas fa-chevron-right"></i></button>
    </div>
    <div class="datepicker-weekdays">
        <div>Lun</div>
        <div>Mar</div>
        <div>Mer</div>
        <div>Jeu</div>
        <div>Ven</div>
        <div>Sam</div>
        <div>Dim</div>
    </div>
    <div class="datepicker-days" id="daysTo"></div>
</div>

<script>
// State
let currentMonthFrom = new Date();
let currentMonthTo = new Date();
let activePickerFrom = false;
let activePickerTo = false;

/* ---- Sidebar mobile ---- */
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

/* ---- Date Picker ---- */
function openDatePicker(type) {
    const picker = type === 'from' ? document.getElementById('datePickerFrom') : document.getElementById('datePickerTo');
    const input = document.querySelector('input[name="' + type + '"]');
    
    if (type === 'from') {
        activePickerFrom = !activePickerFrom;
        activePickerTo = false;
        document.getElementById('datePickerTo').classList.remove('show');
    } else {
        activePickerTo = !activePickerTo;
        activePickerFrom = false;
        document.getElementById('datePickerFrom').classList.remove('show');
    }
    
    if (type === 'from' ? activePickerFrom : activePickerTo) {
        picker.classList.add('show');
        renderCalendar(type);
        
        const rect = input.getBoundingClientRect();
        picker.style.top = (rect.bottom + 10) + 'px';
        picker.style.left = rect.left + 'px';
    } else {
        picker.classList.remove('show');
    }
}

function renderCalendar(type) {
    const currentMonth = type === 'from' ? currentMonthFrom : currentMonthTo;
    const monthYearEl = document.getElementById(type === 'from' ? 'monthYearFrom' : 'monthYearTo');
    const daysEl = document.getElementById(type === 'from' ? 'daysFrom' : 'daysTo');
    const input = document.querySelector('input[name="' + type + '"]');
    
    const months = ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin',
                    'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'];
    
    monthYearEl.textContent = months[currentMonth.getMonth()] + ' ' + currentMonth.getFullYear();
    
    const firstDay = new Date(currentMonth.getFullYear(), currentMonth.getMonth(), 1);
    const lastDay = new Date(currentMonth.getFullYear(), currentMonth.getMonth() + 1, 0);
    const prevLastDay = new Date(currentMonth.getFullYear(), currentMonth.getMonth(), 0);
    
    const firstDayOfWeek = firstDay.getDay() === 0 ? 6 : firstDay.getDay() - 1;
    const lastDateOfMonth = lastDay.getDate();
    const lastDateOfPrevMonth = prevLastDay.getDate();
    
    let html = '';
    
    // Previous month days
    for (let i = firstDayOfWeek; i > 0; i--) {
        html += '<div class="datepicker-day other-month">' + (lastDateOfPrevMonth - i + 1) + '</div>';
    }
    
    // Current month days
    const inputDate = input.value ? new Date(input.value.split('/').reverse().join('-')) : null;
    for (let day = 1; day <= lastDateOfMonth; day++) {
        const date = new Date(currentMonth.getFullYear(), currentMonth.getMonth(), day);
        const dateStr = ('0' + day).slice(-2) + '/' + ('0' + (currentMonth.getMonth() + 1)).slice(-2) + '/' + currentMonth.getFullYear();
        const isSelected = inputDate && inputDate.toDateString() === date.toDateString();
        const isToday = new Date().toDateString() === date.toDateString();
        
        html += '<div class="datepicker-day' + (isSelected ? ' selected' : '') + (isToday ? ' today' : '') + '" onclick="selectDate(\'' + dateStr + '\', \'' + type + '\')">' + day + '</div>';
    }
    
    // Next month days
    const totalCells = 42;
    const filledCells = firstDayOfWeek + lastDateOfMonth;
    for (let day = 1; day <= totalCells - filledCells; day++) {
        html += '<div class="datepicker-day other-month">' + day + '</div>';
    }
    
    daysEl.innerHTML = html;
}

function selectDate(dateStr, type) {
    const input = document.querySelector('input[name="' + type + '"]');
    input.value = dateStr;
    
    const picker = type === 'from' ? document.getElementById('datePickerFrom') : document.getElementById('datePickerTo');
    picker.classList.remove('show');
    if (type === 'from') activePickerFrom = false;
    else activePickerTo = false;
}

function prevMonth(type) {
    if (type === 'from') {
        currentMonthFrom = new Date(currentMonthFrom.getFullYear(), currentMonthFrom.getMonth() - 1);
    } else {
        currentMonthTo = new Date(currentMonthTo.getFullYear(), currentMonthTo.getMonth() - 1);
    }
    renderCalendar(type);
}

function nextMonth(type) {
    if (type === 'from') {
        currentMonthFrom = new Date(currentMonthFrom.getFullYear(), currentMonthFrom.getMonth() + 1);
    } else {
        currentMonthTo = new Date(currentMonthTo.getFullYear(), currentMonthTo.getMonth() + 1);
    }
    renderCalendar(type);
}

// Close pickers when clicking outside
document.addEventListener('click', function(e) {
    if (!e.target.closest('.form-group') && !e.target.closest('.datepicker-popover')) {
        document.getElementById('datePickerFrom').classList.remove('show');
        document.getElementById('datePickerTo').classList.remove('show');
        activePickerFrom = false;
        activePickerTo = false;
    }
});

function resetDates() {
    document.querySelector('input[name="from"]').value = '';
    document.querySelector('input[name="to"]').value = '';
    document.querySelector('form').submit();
}

function toggleOperator(element) {
    const card = element.closest('.operator-card');
    card.classList.toggle('expanded');
}

window.addEventListener('resize', () => {
    if (window.innerWidth > 768) {
        sidebar.classList.remove('open');
        overlay.classList.remove('show');
    }
});

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    renderCalendar('from');
    renderCalendar('to');
});
</script>
HTML;
    }

    private function getKPICards() {
        $totalTickets = 0;
        $totalWait = 0;
        $totalExec = 0;
        $count = 0;

        // Récupérer tous les tickets
        $tickets = TicketStats::fromDatabaseCompleteList();
        
        if (empty($tickets)) {
            return <<<HTML
            <div class="kpi-card empty">
                <i class="fas fa-inbox"></i>
                <p>Aucune donnée disponible</p>
            </div>
HTML;
        }

        // Calculer les statistiques globales
        foreach ($tickets as $t) {
            if (!$t->getTimeExec()) continue;
            
            // Vérifier si le ticket est dans la plage de dates
            if ($t->getTimeExec() < $this->dateFrom || $t->getTimeExec() > $this->dateTo) {
                continue;
            }
            
            $totalTickets++;
            $totalWait += ($t->getTimeExec() - $t->getTimeIn());
            $totalExec += ($t->getTimeOut() - $t->getTimeExec());
            $count++;
        }

        $avgWait = $count ? floor($totalWait / $count / 60) : 0;
        $avgExec = $count ? floor($totalExec / $count / 60) : 0;

        return <<<HTML
            <div class="kpi-card">
                <div class="kpi-icon"><i class="fas fa-ticket-alt"></i></div>
                <div class="kpi-content">
                    <div class="kpi-label">Total des tickets</div>
                    <div class="kpi-value">$totalTickets</div>
                </div>
            </div>
            <div class="kpi-card">
                <div class="kpi-icon"><i class="fas fa-hourglass-start"></i></div>
                <div class="kpi-content">
                    <div class="kpi-label">Attente moyenne</div>
                    <div class="kpi-value">$avgWait <span class="kpi-unit">min</span></div>
                </div>
            </div>
            <div class="kpi-card">
                <div class="kpi-icon"><i class="fas fa-stopwatch"></i></div>
                <div class="kpi-content">
                    <div class="kpi-label">Traitement moyen</div>
                    <div class="kpi-value">$avgExec <span class="kpi-unit">min</span></div>
                </div>
            </div>
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
                <th style="text-align: center;">Billets</th>
                <th style="text-align: center;">Temps d'attente</th>
                <th style="text-align: center;">Exécution</th>
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
    <td style="text-align: center;"><span class="badge info">$count</span></td>
    <td style="text-align: center;"><span class="$waitClass">$avgWait min</span></td>
    <td style="text-align: center;"><span class="badge primary">$avgExec min</span></td>
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
                        <div class="progress-bar" style="background: {$info['color']}; width: {$percentage}%"></div>
                    </div>
                    <div class="source-percent">{$percentage}%</div>
                </div>
            </div>
HTML;
        }
        
        $html .= <<<HTML
        </div>
        <div class="source-total">
            <i class="fas fa-chart-pie"></i>
            Total des billets : <strong>{$total}</strong>
        </div>
HTML;
        
        return $html;
    }

    public function getStatsOperatorTables() {
        $ops = Operator::fromDatabaseCompleteList();
        
        if (empty($ops)) {
            return '<div class="empty-state"><i class="fas fa-inbox"></i><p>Aucun opérateur trouvé</p></div>';
        }
        
        $html = '<div class="operators-list">';

        foreach ($ops as $op) {
            $rows = $this->getRowsForOperator($op);
            
            $html .= <<<HTML
            <div class="operator-card">
                <div class="operator-title" onclick="toggleOperator(this)">
                    <div class="operator-avatar">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="operator-info">
                        <div class="operator-name">{$op->getFullName()}</div>
                        <div class="operator-code">Code: {$op->getCode()}</div>
                    </div>
                    <i class="fas fa-chevron-down toggle-icon"></i>
                </div>
                <div class="operator-stats">
                    <div class="table-container">
                        <table class="stats-table">
                            <thead>
                                <tr>
                                    <th>Domaine</th>
                                    <th style="text-align: center;">Billets</th>
                                    <th style="text-align: center;">Part</th>
                                    <th style="text-align: center;">Temps moyen</th>
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
                <td style="text-align: center;"><span class="badge info">{$s['count']}</span></td>
                <td>
                    <div class="percent-bar">
                        <div class="percent-fill" style="width: {$perc}%"></div>
                        <span class="percent-text">{$perc}%</span>
                    </div>
                </td>
                <td style="text-align: center;"><span class="badge primary">{$avg} min</span></td>
            </tr>
HTML;
        }

        $avgTotal = $total ? floor($execSum / $total / 60) : 0;

        $rows .= <<<HTML
            <tr class="total-row">
                <td><strong>Total</strong></td>
                <td style="text-align: center;"><strong>$total</strong></td>
                <td><strong>100%</strong></td>
                <td style="text-align: center;"><span class="badge primary"><strong>{$avgTotal} min</strong></span></td>
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
                    <label><i class="fas fa-calendar-alt"></i> Du :</label>
                    <div class="date-input-wrapper">
                        <input type="text" name="from" class="date-input" placeholder="jj/mm/aaaa" value="$fromDate" readonly onclick="openDatePicker('from')">
                        <i class="fas fa-calendar-alt picker-icon" onclick="openDatePicker('from')"></i>
                    </div>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-calendar-alt"></i> Au :</label>
                    <div class="date-input-wrapper">
                        <input type="text" name="to" class="date-input" placeholder="jj/mm/aaaa" value="$toDate" readonly onclick="openDatePicker('to')">
                        <i class="fas fa-calendar-alt picker-icon" onclick="openDatePicker('to')"></i>
                    </div>
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
            background: linear-gradient(135deg, #f5f7fb 0%, #eef2f9 100%);
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
            background: linear-gradient(135deg, #f5f7fb 0%, #eef2f9 100%);
        }

        .content-wrapper {
            padding: 40px 45px;
            max-width: 1600px;
            margin: 0 auto;
        }

        /* Page Header */
        .page-header {
            margin-bottom: 40px;
        }

        .header-content h1 {
            font-size: 32px;
            font-weight: 800;
            color: #1a1a2e;
            margin-bottom: 8px;
        }

        .subtitle {
            color: #666;
            font-size: 15px;
            font-weight: 500;
        }

        /* Cards */
        .card {
            background: white;
            border-radius: 18px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.06);
            border: 1px solid rgba(0,0,0,0.03);
            transition: all 0.3s ease;
        }

        .card:hover {
            box-shadow: 0 8px 24px rgba(0,0,0,0.1);
        }

        .filter-card {
            background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
        }

        .card-header {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 25px;
            padding-bottom: 18px;
            border-bottom: 2px solid #f0f0f0;
        }

        .card-header i {
            font-size: 24px;
            color: #6C63FF;
            width: 32px;
            text-align: center;
        }

        .card-header h3 {
            font-size: 18px;
            font-weight: 700;
            color: #1a1a2e;
            margin: 0;
        }

        .highlight {
            color: #6C63FF;
            font-weight: 800;
        }

        /* KPI Grid */
        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .kpi-card {
            background: white;
            border-radius: 16px;
            padding: 24px;
            display: flex;
            align-items: center;
            gap: 20px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.06);
            border: 1px solid rgba(0,0,0,0.03);
            transition: all 0.3s ease;
        }

        .kpi-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.1);
        }

        .kpi-card.empty {
            justify-content: center;
            text-align: center;
            color: #999;
            grid-column: 1 / -1;
            flex-direction: column;
            gap: 12px;
        }

        .kpi-card.empty i {
            font-size: 48px;
            color: #ddd;
        }

        .kpi-icon {
            width: 64px;
            height: 64px;
            background: linear-gradient(135deg, #6C63FF, #8B82FF);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            color: white;
            flex-shrink: 0;
        }

        .kpi-content {
            flex: 1;
        }

        .kpi-label {
            font-size: 13px;
            color: #999;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .kpi-value {
            font-size: 28px;
            font-weight: 800;
            color: #1a1a2e;
        }

        .kpi-unit {
            font-size: 14px;
            color: #999;
            font-weight: 500;
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
            min-width: 200px;
            position: relative;
        }

        .form-group label {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 10px;
            font-weight: 600;
            font-size: 13px;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .form-group label i {
            color: #6C63FF;
        }

        .date-input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .date-input {
            width: 100%;
            padding: 12px 16px 12px 16px;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            font-size: 14px;
            font-family: inherit;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .date-input:focus {
            outline: none;
            border-color: #6C63FF;
            box-shadow: 0 0 0 4px rgba(108,99,255,0.1);
        }

        .picker-icon {
            position: absolute;
            right: 14px;
            color: #6C63FF;
            cursor: pointer;
            font-size: 16px;
            pointer-events: none;
        }

        .form-buttons {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
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
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #6C63FF, #8B82FF);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(108,99,255,0.4);
        }

        .btn-secondary {
            background: #f0f0f0;
            color: #666;
        }

        .btn-secondary:hover {
            background: #e0e0e0;
        }

        /* Date Picker Popover */
        .datepicker-popover {
            position: fixed;
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.15);
            z-index: 2000;
            padding: 20px;
            width: 320px;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.3s ease;
        }

        .datepicker-popover.show {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .datepicker-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
        }

        .datepicker-month-year {
            font-weight: 700;
            font-size: 16px;
            color: #1a1a2e;
            flex: 1;
            text-align: center;
        }

        .datepicker-prev,
        .datepicker-next {
            background: #f0f0f0;
            border: none;
            width: 32px;
            height: 32px;
            border-radius: 8px;
            cursor: pointer;
            color: #666;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .datepicker-prev:hover,
        .datepicker-next:hover {
            background: #6C63FF;
            color: white;
        }

        .datepicker-weekdays {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 8px;
            margin-bottom: 10px;
            text-align: center;
            font-weight: 600;
            font-size: 12px;
            color: #999;
        }

        .datepicker-days {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 8px;
        }

        .datepicker-day {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.2s ease;
            user-select: none;
        }

        .datepicker-day:not(.other-month) {
            color: #1a1a2e;
        }

        .datepicker-day:not(.other-month):hover {
            background: #f0f0f0;
        }

        .datepicker-day.other-month {
            color: #ddd;
            cursor: default;
        }

        .datepicker-day.today {
            border: 2px solid #6C63FF;
            font-weight: 700;
        }

        .datepicker-day.selected {
            background: linear-gradient(135deg, #6C63FF, #8B82FF);
            color: white;
            font-weight: 700;
        }

        /* Tables */
        .table-container {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            border-radius: 12px;
            background: #f8f9fa;
        }

        .stats-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 600px;
        }

        .stats-table thead th {
            text-align: left;
            padding: 16px;
            background: #f0f0f0;
            font-weight: 700;
            font-size: 12px;
            color: #666;
            border-bottom: 2px solid #e0e0e0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stats-table tbody td {
            padding: 16px;
            border-bottom: 1px solid #eee;
            font-size: 14px;
        }

        .stats-table tbody tr:hover {
            background: #f8f9fa;
        }

        .td-name {
            font-weight: 700;
            color: #1a1a2e;
        }

        .total-row {
            background: #f0f0f0;
            font-weight: 700;
        }

        .total-row td {
            border-bottom: 2px solid #e0e0e0;
        }

        .empty-row {
            text-align: center;
            padding: 40px 20px;
            color: #999;
        }

        /* Badges */
        .badge {
            display: inline-block;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
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
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }

        .source-card {
            display: flex;
            gap: 18px;
            padding: 24px;
            background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
            border-radius: 16px;
            border: 1px solid rgba(0,0,0,0.03);
            transition: all 0.3s ease;
        }

        .source-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.1);
        }

        .source-icon {
            width: 60px;
            height: 60px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            color: white;
            flex-shrink: 0;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        .source-details {
            flex: 1;
        }

        .source-label {
            font-size: 12px;
            color: #999;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
        }

        .source-count {
            font-size: 32px;
            font-weight: 800;
            color: #1a1a2e;
            margin-bottom: 12px;
        }

        .progress {
            background: #e0e0e0;
            border-radius: 10px;
            height: 6px;
            overflow: hidden;
            margin-bottom: 10px;
        }

        .progress-bar {
            height: 100%;
            border-radius: 10px;
            transition: width 0.5s ease;
        }

        .source-percent {
            font-size: 13px;
            font-weight: 700;
            color: #666;
        }

        .source-total {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 8px;
            padding-top: 20px;
            border-top: 2px solid #f0f0f0;
            font-size: 15px;
            color: #666;
        }

        .source-total strong {
            color: #6C63FF;
            font-size: 20px;
        }

        /* Operators List */
        .operators-list {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .operator-card {
            border: 1px solid rgba(0,0,0,0.03);
            border-radius: 16px;
            overflow: hidden;
            background: white;
            transition: all 0.3s ease;
        }

        .operator-title {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 22px;
            background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
            cursor: pointer;
            transition: all 0.3s ease;
            border-bottom: 1px solid transparent;
        }

        .operator-title:hover {
            background: #f8f9fa;
        }

        .operator-card.expanded .operator-title {
            border-bottom: 1px solid #e0e0e0;
        }

        .operator-avatar {
            width: 52px;
            height: 52px;
            background: linear-gradient(135deg, #6C63FF, #8B82FF);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
            flex-shrink: 0;
            box-shadow: 0 4px 12px rgba(108,99,255,0.2);
        }

        .operator-info {
            flex: 1;
        }

        .operator-name {
            font-weight: 700;
            font-size: 15px;
            color: #1a1a2e;
        }

        .operator-code {
            font-size: 12px;
            color: #999;
            margin-top: 2px;
        }

        .toggle-icon {
            color: #999;
            font-size: 18px;
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

        .operator-stats .table-container {
            margin: 0;
            border-radius: 0;
        }

        /* Percent Bar */
        .percent-bar {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .percent-fill {
            flex: 1;
            height: 6px;
            background: linear-gradient(90deg, #6C63FF, #8B82FF);
            border-radius: 3px;
            min-width: 60px;
        }

        .percent-text {
            font-size: 12px;
            font-weight: 700;
            color: #6C63FF;
            min-width: 40px;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }

        .empty-state i {
            font-size: 48px;
            margin-bottom: 15px;
            color: #ddd;
        }

        /* Responsive */
        @media (max-width: 1200px) {
            .content-wrapper {
                padding: 30px 30px;
            }

            .kpi-grid {
                grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            }
        }

        @media (max-width: 1024px) {
            .content-wrapper {
                padding: 25px 20px;
            }
        }

        @media (max-width: 768px) {
            .mobile-toggle {
                display: flex;
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
                padding: 80px 16px 20px 16px;
            }

            .header-content h1 {
                font-size: 24px;
            }

            .form-row {
                flex-direction: column;
                align-items: stretch;
            }

            .form-group {
                min-width: 100%;
            }

            .form-buttons {
                flex-direction: column;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }

            .kpi-grid {
                grid-template-columns: 1fr;
            }

            .source-grid {
                grid-template-columns: 1fr;
            }

            .card {
                padding: 20px;
                margin-bottom: 20px;
            }

            .card-header {
                margin-bottom: 20px;
            }

            .datepicker-popover {
                width: calc(100vw - 40px);
                max-width: 320px;
            }
        }

        @media (max-width: 480px) {
            .content-wrapper {
                padding: 70px 12px 15px 12px;
            }

            .header-content h1 {
                font-size: 20px;
            }

            .stats-table {
                min-width: 400px;
                font-size: 12px;
            }

            .stats-table thead th,
            .stats-table tbody td {
                padding: 12px;
            }

            .operator-title {
                padding: 16px;
            }

            .operator-avatar {
                width: 44px;
                height: 44px;
                font-size: 20px;
            }

            .operator-name {
                font-size: 14px;
            }

            .kpi-card {
                padding: 16px;
                gap: 12px;
            }

            .kpi-icon {
                width: 48px;
                height: 48px;
                font-size: 24px;
            }

            .kpi-value {
                font-size: 22px;
            }

            .source-card {
                padding: 16px;
                gap: 12px;
            }

            .source-icon {
                width: 48px;
                height: 48px;
                font-size: 22px;
            }

            .source-count {
                font-size: 24px;
            }

            .datepicker-day {
                width: 36px;
                height: 36px;
                font-size: 12px;
            }

            .datepicker-popover {
                width: calc(100vw - 30px);
                padding: 15px;
            }
        }

        /* Print */
        @media print {
            .sidebar,
            .mobile-toggle,
            .overlay,
            .form-buttons,
            .filter-card {
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