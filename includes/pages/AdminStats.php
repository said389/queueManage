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

    /** ✅ Indispensable */
    public function getPageTitle() {
        return "Statistiche";
    }

    /** ✅ Output principal */
    public function getOutput() {
        global $gvPath;

        $page = new WebPageOutput();
        $page->setHtmlPageTitle($this->getPageTitle());

        $page->setHtmlBodyHeader(
            $this->getDesignCSS() .
            $this->getHeaderAndNav()
        );

        $page->setHtmlBodyContent($this->getPageContent());

        return $page;
    }

    /** ✅ Header + navbar */
    private function getHeaderAndNav() {
        global $gvPath;

        return <<<HTML
<div class="admin-header">
    <h2>Pannello amministrazione FastQueue</h2>
    <span>Statistiche del sistema</span>
</div>

<div class="admin-navbar">
    <a class="nav-item" href="$gvPath/application/adminPage">Dashboard</a>
    <a class="nav-item" href="$gvPath/application/adminOperatorList">Operatori</a>
    <a class="nav-item" href="$gvPath/application/adminDeskList">Sportelli</a>
    <a class="nav-item " href="$gvPath/application/adminTopicalDomainList">Aree tematiche</a>
    <a class="nav-item" href="$gvPath/application/adminDeviceList">Dispositivi</a>
    <a class="nav-item active"  href="$gvPath/application/adminStats">Statistiche</a>
    <a class="nav-item " href="$gvPath/application/adminSettings">Impostazioni</a>
    <a class="nav-item" style="margin-left:auto;" href="$gvPath/application/logoutPage">Logout</a>
</div>
HTML;
    }

    /** ✅ Contenu */
    public function getPageContent() {

        $timeSpan = $this->getTimeSpanString();

        return <<<HTML
<div class="content-wrapper">

    <h3 class="section-title">Imposta il periodo di ricerca</h3>

    {$this->getForm()}

    <h3 class="section-title">Statistiche per il periodo: <span class="highlight">$timeSpan</span></h3>

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

</div>
HTML;
    }

    /** ✅ Intervalle */
    private function getTimeSpanString() {
        if ($this->dateFrom == self::$MIN_DATE && $this->dateTo == self::$MAX_DATE) {
            return "completo";
        }

        global $gvTimeZone;

        $zone = new DateTimeZone($gvTimeZone);

        $from = new DateTime('@'.$this->dateFrom);
        $from->setTimezone($zone);
        $from = $from->format('d-m-Y');

        $to = new DateTime('@'.$this->dateTo);
        $to->setTimezone($zone);
        $to = $to->format('d-m-Y');

        return "dal $from al $to";
    }

    /** ✅ Stat Aree tematiche */
    public function getTdStatsTable() {
        $rows = $this->getTdStatsRows();

        return <<<HTML
<table class="styled-table w100">
    <caption>Statistiche per area tematica</caption>
    <tr>
        <th>Area tematica</th>
        <th>Numero ticket</th>
        <th>Attesa media</th>
        <th>Tempo d'esecuzione medio</th>
    </tr>
    $rows
</table>
HTML;
    }

    private function getTdStatsRows() {

        $rows = "";
        $tdList = TopicalDomain::fromDatabaseCompleteList(false);

        foreach ($tdList as $td) {

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

            $avgWait = $count ? (int)($wait / $count / 60) : 0;
            $avgExec = $count ? (int)($exec / $count / 60) : 0;

            $rows .= <<<HTML
<tr>
    <td>{$td->getCode()}</td>
    <td>$count</td>
    <td>{$avgWait} min</td>
    <td>{$avgExec} min</td>
</tr>
HTML;
        }

        return $rows ?: '<tr><td colspan="4">Nessuna statistica</td></tr>';
    }

    /** ✅ Stat per operatore */
    public function getStatsOperatorTables() {

        $ops = Operator::fromDatabaseCompleteList();
        $html = "";

        foreach ($ops as $op) {

            $rows = $this->getRowsForOperator($op);

            $html .= <<<HTML
<table class="styled-table w100">
    <caption>{$op->getFullName()} ({$op->getCode()})</caption>
    <tr>
        <th>Area tematica</th>
        <th>Ticket serviti</th>
        <th>Percentuale</th>
        <th>Tempo medio</th>
    </tr>
    $rows
</table>
HTML;
        }

        return $html ?: "<p>Nessuna statistica disponibile</p>";
    }

    /** ✅ Rows per operatore */
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

            if (!isset($stats[$code])) {
                $stats[$code] = ['count' => 0, 'exec' => 0];
            }

            $e = $t->getTimeOut() - $t->getTimeExec();

            $stats[$code]['count']++;
            $stats[$code]['exec'] += $e;
            $execSum += $e;
        }

        if (!$total)
            return "<tr><td colspan='4'>Nessuna statistica</td></tr>";

        ksort($stats);

        $rows = "";

        foreach ($stats as $code => $s) {

            $perc = (int)(($s['count'] / $total) * 100);
            $avg  = (int)($s['exec'] / $s['count'] / 60);

            $rows .= <<<HTML
<tr>
    <td>$code</td>
    <td>{$s['count']}</td>
    <td>{$perc} %</td>
    <td>{$avg} min</td>
</tr>
HTML;
        }

        $avgTotal = (int)($execSum / $total / 60);

        $rows .= <<<HTML
<tr>
    <td><b>TOTALE</b></td>
    <td><b>$total</b></td>
    <td><b>100 %</b></td>
    <td><b>{$avgTotal} min</b></td>
</tr>
HTML;

        return $rows;
    }

    /** ✅ ✅ ✅ FONCTION MANQUANTE — CAUSE DE TON ERREUR */
    public function getSourceStatsTable() {

        $src = ['app','totem','web'];
        $count = [];
        $total = 0;

        foreach ($src as $s) {
            $tickets = TicketStats::fromDatabaseListBySource(
                $s,
                $this->dateFrom,
                $this->dateTo
            );
            $count[$s] = count($tickets);
            $total += $count[$s];
        }

        $appP   = $total ? (int)(($count['app']   / $total) * 100) : 0;
        $totemP = $total ? (int)(($count['totem'] / $total) * 100) : 0;
        $webP   = $total ? (int)(($count['web']   / $total) * 100) : 0;

        return <<<HTML
<table class="styled-table w100">
    <caption>Statistiche per sorgente</caption>
    <tr>
        <th>Fonte</th>
        <th>Conteggio</th>
        <th>Percentuale</th>
    </tr>
    <tr>
        <td>App</td><td>{$count['app']}</td><td>{$appP}%</td>
    </tr>
    <tr>
        <td>Totem</td><td>{$count['totem']}</td><td>{$totemP}%</td>
    </tr>
    <tr>
        <td>Web</td><td>{$count['web']}</td><td>{$webP}%</td>
    </tr>
</table>
HTML;
    }

    /** ✅ Formulaire */
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

    /** ✅ CSS */
    private function getDesignCSS() {
        return <<<CSS
<style>

*{margin:0;padding:0;box-sizing:border-box;}
body{background:hsl(210,5%,85%);font-family:'Segoe UI',Tahoma;}

.admin-header{
    background:linear-gradient(135deg,hsl(354,82%,70%),hsl(354,62%,78%));
    padding:22px 40px;color:#fff;
    box-shadow:0 4px 12px rgba(0,0,0,0.15);
}

.admin-navbar{
    background:#fff;padding:12px 30px;
    display:flex;gap:18px;border-bottom:1px solid rgba(0,0,0,0.10);
}

.nav-item{
    padding:8px 18px;border-radius:30px;
    color:hsl(354,82%,70%);font-weight:600;
    text-decoration:none;transition:.3s;
}
.nav-item:hover{background:hsl(354,82%,90%);}
.nav-item.active{background:hsl(354,82%,70%);color:#fff;}

.content-wrapper{padding:40px;}
.section-title{font-size:22px;font-weight:700;margin-bottom:18px;}
.highlight{color:hsl(354,82%,60%);font-weight:bold;}

.styled-table{
    width:100%;background:#fff;border-collapse:collapse;
    border-radius:16px;overflow:hidden;
    box-shadow:0 4px 18px rgba(0,0,0,0.08);
    margin-bottom:30px;
}
.styled-table th{
    background:hsl(354,82%,70%);
    color:#fff;padding:12px;text-align:left;
}
.styled-table td{
    padding:12px;border-bottom:1px solid #eee;
}
.styled-table tr:hover{background:hsl(354,82%,95%);}

.stats-form input[type=text]{
    padding:8px;width:100%;
}

.btn-primary{
    padding:10px 22px;border-radius:30px;
    border:none;background:hsl(354,82%,70%);
    color:#fff;font-weight:600;cursor:pointer;
}
.btn-primary:hover{background:hsl(354,82%,60%);}

.stats-block{margin-bottom:40px;}

</style>
CSS;
    }
}