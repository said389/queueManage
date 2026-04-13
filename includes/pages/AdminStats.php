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


    /** ✅ LAYOUT COMPLET avec SIDEBAR VIOLETTE */
    private function getLayout() {
        global $gvPath;

        $timeSpan = $this->getTimeSpanString();

        return <<<HTML
<div class="layout">

    <!-- ✅ SIDEBAR VIOLETTE -->
    <aside class="sidebar">

        <div class="sidebar-header">
            <div class="logo-circle">FQ</div>
            <h3 class="brand">FastQueue Admin</h3>
        </div>

        <nav class="menu">

            <a class="menu-item" href="$gvPath/application/adminPage"><span>🏠</span> Dashboard</a>
            <a class="menu-item" href="$gvPath/application/adminOperatorList"><span>👤</span> Operatori</a>
            <a class="menu-item" href="$gvPath/application/adminDeskList"><span>🖥️</span> Sportelli</a>
            <a class="menu-item" href="$gvPath/application/adminTopicalDomainList"><span>📂</span> Aree Tematiche</a>
            <a class="menu-item" href="$gvPath/application/adminDeviceList"><span>📱</span> Dispositivi</a>
            <a class="menu-item active" href="$gvPath/application/adminStats"><span>📈</span> Statistiche</a>

        </nav>

        <!-- ✅ BAS -->
        <div class="menu-bottom">
            <a class="menu-item" href="$gvPath/application/adminSettings"><span>⚙️</span> Impostazioni</a>
            <a class="menu-item" href="$gvPath/application/logoutPage"><span>🚪</span> Logout</a>
        </div>

    </aside>


    <!-- ✅ CONTENU PRINCIPAL -->
    <main class="content">

        <h2 class="page-title">Statistiche del Sistema</h2>

        <h3 class="section-title">Imposta il periodo</h3>

        {$this->getForm()}

        <h3 class="section-title">Statistiche periodo: <span class="highlight">$timeSpan</span></h3>

        <div class="stats-block">
            {$this->getTdStatsTable()}
        </div>

        <div class="stats-block">
            {$this->getSourceStatsTable()}
        </div>

        <h3 class="section-title">Statistiche per operatore</h3>

        <div class="stats-block">
            {$this->getStatsOperatorTables()}
        </div>

    </main>

</div>
HTML;
    }



    /** ✅ TEXT PERIOD */
    private function getTimeSpanString() {
        if ($this->dateFrom == self::$MIN_DATE && $this->dateTo == self::$MAX_DATE)
            return "completo";

        global $gvTimeZone;
        $zone = new DateTimeZone($gvTimeZone);

        $from = new DateTime('@'.$this->dateFrom); $from->setTimezone($zone); $from = $from->format('d-m-Y');
        $to   = new DateTime('@'.$this->dateTo);   $to->setTimezone($zone);   $to   = $to->format('d-m-Y');

        return "dal $from al $to";
    }



    /** ✅ TABLE TD */
    public function getTdStatsTable() {

        $rows = $this->getTdStatsRows();

        return <<<HTML
<table class="styled-table">
    <caption>Statistiche per area tematica</caption>
    <tr>
        <th>Area</th>
        <th>Ticket</th>
        <th>Attesa media</th>
        <th>Esecuzione media</th>
    </tr>
    $rows
</table>
HTML;
    }



    private function getTdStatsRows() {

        $rows = "";
        $list = TopicalDomain::fromDatabaseCompleteList(false);

        foreach ($list as $td) {

            $tickets = TicketStats::fromDatabaseListByCode(
                $td->getCode(),
                $this->dateFrom, $this->dateTo
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

            $avgWait = $count ? (int)($wait / $count / 60) : 0;
            $avgExec = $count ? (int)($exec / $count / 60) : 0;

            $rows .= <<<HTML
<tr>
    <td>{$td->getName()}</td>
    <td>$count</td>
    <td>{$avgWait} min</td>
    <td>{$avgExec} min</td>
</tr>
HTML;
        }

        return $rows ?: '<tr><td colspan="4">Nessuna statistica</td></tr>';
    }



    /** ✅ SOURCE TABLE */
    public function getSourceStatsTable() {

        $sources = ['app','totem','web'];
        $count = [];
        $total = 0;

        foreach ($sources as $src) {
            $arr = TicketStats::fromDatabaseListBySource($src, $this->dateFrom, $this->dateTo);
            $count[$src] = count($arr);
            $total += $count[$src];
        }

        $pApp   = $total ? (int)(($count['app']   / $total) * 100) : 0;
        $pTotem = $total ? (int)(($count['totem'] / $total) * 100) : 0;
        $pWeb   = $total ? (int)(($count['web']   / $total) * 100) : 0;

        return <<<HTML
<table class="styled-table">
    <caption>Statistiche per sorgente</caption>
    <tr>
        <th>Fonte</th>
        <th>Totale</th>
        <th>%</th>
    </tr>
    <tr><td>App</td><td>{$count['app']}</td><td>{$pApp}%</td></tr>
    <tr><td>Totem</td><td>{$count['totem']}</td><td>{$pTotem}%</td></tr>
    <tr><td>Web</td><td>{$count['web']}</td><td>{$pWeb}%</td></tr>
</table>
HTML;
    }



    /** ✅ OPERATOR TABLES */
    public function getStatsOperatorTables() {

        $ops = Operator::fromDatabaseCompleteList();
        $html = "";

        foreach ($ops as $op) {

            $rows = $this->getRowsForOperator($op);

            $html .= <<<HTML
<table class="styled-table">
    <caption>{$op->getFullName()} ({$op->getCode()})</caption>
    <tr>
        <th>Area</th>
        <th>Ticket</th>
        <th>%</th>
        <th>Tempo medio</th>
    </tr>
    $rows
</table>
HTML;
        }

        return $html ?: "<p>Nessuna statistica disponibile</p>";
    }



    public function getRowsForOperator($op) {

        $stats = [];
        $tickets = TicketStats::fromDatabaseListByOperator(
            $op->getCode(), $this->dateFrom, $this->dateTo
        );

        $total = count($tickets);
        $execSum = 0;

        foreach ($tickets as $t) {
            $code = $t->getCode();
            if (!isset($stats[$code]))
                $stats[$code] = ['count'=>0,'exec'=>0];

            $exec = $t->getTimeOut() - $t->getTimeExec();
            $stats[$code]['count']++;
            $stats[$code]['exec'] += $exec;
            $execSum += $exec;
        }

        if (!$total)
            return "<tr><td colspan='4'>Nessun dato</td></tr>";

        ksort($stats);

        $rows = "";

        foreach ($stats as $code => $s) {

            $perc = (int)(($s['count'] / $total) * 100);
            $avg  = (int)($s['exec'] / $s['count'] / 60);

            $rows .= <<<HTML
<tr>
    <td>$code</td>
    <td>{$s['count']}</td>
    <td>{$perc}%</td>
    <td>{$avg} min</td>
</tr>
HTML;
        }

        $avgTotal = (int)($execSum / $total / 60);

        $rows .= <<<HTML
<tr>
    <td><b>TOTALE</b></td>
    <td><b>$total</b></td>
    <td><b>100%</b></td>
    <td><b>{$avgTotal} min</b></td>
</tr>
HTML;

        return $rows;
    }



    /** ✅ FORMULAIRE */
    public function getForm() {

        return <<<HTML
<form method="post">
    <table class="styled-table stats-form">
        <tr>
            <th>Inizio periodo</th>
            <th>Fine periodo</th>
            <th></th>
        </tr>
        <tr>
            <td><input type="text" name="from" class="datepicker"></td>
            <td><input type="text" name="to" class="datepicker"></td>
            <td><input type="submit" class="btn-primary" value="Aggiorna"></td>
        </tr>
    </table>
</form>
HTML;
    }



    /** ✅ CSS VIOLET PREMIUM */
    private function getDesignCSS() {
        return <<<CSS
<style>

/* GLOBAL */
body { margin:0; background:#F0ECFF; font-family:'Segoe UI',sans-serif; }
.layout { display:flex; height:100vh; }

/* ✅ SIDEBAR */
.sidebar {
    width:250px;
    background:linear-gradient(180deg,#6C63FF,#8978FF,#CAB8FF);
    color:white;
    padding:25px 0;
    display:flex;
    flex-direction:column;
    border-radius:0 25px 25px 0;
    box-shadow:3px 0 15px rgba(0,0,0,0.08);
}
.logo-circle {
    width:60px;height:60px;background:white;border-radius:50%;
    display:flex;align-items:center;justify-content:center;
    margin:0 auto 10px auto;color:#6C63FF;font-weight:800;font-size:26px;
}
.sidebar-header { text-align:center;margin-bottom:35px; }
.brand { opacity:.85;font-size:17px; }

/* ✅ MENU */
.menu { display:flex;flex-direction:column; }
.menu-item {
    padding:12px 25px;color:white;text-decoration:none;
    display:flex;gap:12px;align-items:center;
    font-size:15px;transition:0.25s;opacity:.85;
}
.menu-item:hover { opacity:1;background:rgba(255,255,255,0.15); }
.menu-item.active { background:rgba(255,255,255,0.25);font-weight:bold; }

/* ✅ MENU BAS */
.menu-bottom { margin-top:auto;display:flex;flex-direction:column; }

/* ✅ CONTENU */
.content { flex:1;padding:45px;overflow-y:auto; }
.page-title { font-size:28px;margin-bottom:25px; }

/* ✅ TABLES */
.styled-table {
    width:100%;border-collapse:collapse;
    background:white;padding:10px;border-radius:15px;
    box-shadow:0 4px 18px rgba(0,0,0,0.08);
}
.styled-table th {
    background:#6C63FF;color:white;padding:12px;border-radius:6px;
}
.styled-table td {
    padding:12px;border-bottom:1px solid #eee;
}
.styled-table tr:hover {
    background:#F3EEFF;
}

.caption { font-weight:bold;margin-bottom:10px; }

/* ✅ STAT BLOCK */
.stats-block {
    margin-top:30px;
}

/* ✅ FORM */
.btn-primary {
    background:#6C63FF;color:white;padding:10px 22px;
    border-radius:30px;font-weight:600;border:none;
}
.btn-primary:hover { background:#5149E8; }

.highlight { color:#6C63FF;font-weight:bold; }

</style>
CSS;
    }

}