<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';
require_permission('dashboard.view');

$pdo = db();
$user = current_user();

$canSales = user_can('sales.view') || user_can('reports.sales');
$canProfit = user_can('reports.profit');
$canPayments = user_can('reports.payments');
$canExpenses = user_can('expenses.view') || user_can('reports.expenses');
$canInventory = user_can('inventory.view') || user_can('reports.inventory');
$canPurchases = user_can('purchases.view') || user_can('reports.purchases');
$canCustomers = user_can('customers.view') || user_can('reports.customers');
$canCredit = user_can('credit.view') || user_can('reports.customers');
$canUsers = user_can('users.view');
$canAudit = user_can('audit.view');
$canSuppliers = user_can('suppliers.view');

$period = trim((string) ($_GET['period'] ?? 'month'));
$allowed = ['today', 'yesterday', 'week', 'month', 'custom'];
if (!in_array($period, $allowed, true)) {
    $period = 'month';
}

$today = new DateTimeImmutable('today');
$startDate = $today->modify('first day of this month');
$endDateExclusive = $today->modify('+1 day');
$periodLabel = 'This Month';

switch ($period) {
    case 'today':
        $startDate = $today;
        $endDateExclusive = $today->modify('+1 day');
        $periodLabel = 'Today';
        break;
    case 'yesterday':
        $startDate = $today->modify('-1 day');
        $endDateExclusive = $today;
        $periodLabel = 'Yesterday';
        break;
    case 'week':
        $startDate = $today->modify('monday this week');
        $endDateExclusive = $today->modify('+1 day');
        $periodLabel = 'This Week';
        break;
    case 'month':
        break;
    case 'custom':
        $from = DateTimeImmutable::createFromFormat('!Y-m-d', trim((string) ($_GET['from'] ?? '')));
        $to = DateTimeImmutable::createFromFormat('!Y-m-d', trim((string) ($_GET['to'] ?? '')));
        if ($from instanceof DateTimeImmutable && $to instanceof DateTimeImmutable && $from <= $to) {
            $startDate = $from;
            $endDateExclusive = $to->modify('+1 day');
            $periodLabel = $from->format('d M Y') . ' - ' . $to->format('d M Y');
        } else {
            $period = 'month';
        }
        break;
}

$start = $startDate->format('Y-m-d 00:00:00');
$end = $endDateExclusive->format('Y-m-d 00:00:00');
$params = [':start_date' => $start, ':end_date' => $end];

function r_one(PDO $pdo, string $sql, array $params = []): array {
    $s = $pdo->prepare($sql); $s->execute($params); return $s->fetch() ?: [];
}
function r_all(PDO $pdo, string $sql, array $params = []): array {
    $s = $pdo->prepare($sql); $s->execute($params); return $s->fetchAll();
}
function num(mixed $v, int $dp = 2): string { return number_format((float)$v, $dp); }
function kes(mixed $v): string { return 'KSh ' . number_format((float)$v, 2); }

$businessName = (string) setting('business.name', APP_NAME);
$branch = r_one($pdo, 'SELECT name, address, phone, email FROM branches WHERE is_active = 1 ORDER BY id LIMIT 1');

$sections = [];
$summary = [];

if ($canSales) {
    $s = r_one($pdo, 'SELECT COALESCE(SUM(total_amount),0) revenue, COUNT(*) transactions,
        COALESCE(SUM(discount_amount),0) discounts, COALESCE(SUM(amount_paid),0) received,
        COALESCE(SUM(balance_due),0) balance
        FROM sales WHERE status="completed" AND sale_date>=:start_date AND sale_date<:end_date', $params);
    $items = r_one($pdo, 'SELECT COALESCE(SUM(si.quantity),0) qty
        FROM sale_items si INNER JOIN sales s ON s.id=si.sale_id
        WHERE s.status="completed" AND s.sale_date>=:start_date AND s.sale_date<:end_date', $params);
    $summary['Sales Revenue'] = kes($s['revenue'] ?? 0);
    $summary['Completed Sales'] = (string)($s['transactions'] ?? 0);
    $summary['Items Sold'] = num($items['qty'] ?? 0, 3);
    $summary['Amount Received'] = kes($s['received'] ?? 0);
    $summary['Sales Balance'] = kes($s['balance'] ?? 0);
    $summary['Discounts'] = kes($s['discounts'] ?? 0);

    $rows = r_all($pdo, 'SELECT s.sale_no, COALESCE(c.name,"Walk-in") customer, u.full_name cashier,
        s.payment_status, s.total_amount, s.amount_paid, s.balance_due, s.sale_date
        FROM sales s LEFT JOIN customers c ON c.id=s.customer_id INNER JOIN users u ON u.id=s.created_by
        WHERE s.status="completed" AND s.sale_date>=:start_date AND s.sale_date<:end_date
        ORDER BY s.sale_date DESC', $params);
    $sections[] = ['Sales', ['Sale','Customer','Cashier','Payment','Total','Paid','Balance','Date'],
        array_map(fn($r)=>[$r['sale_no'],$r['customer'],$r['cashier'],ucfirst($r['payment_status']),kes($r['total_amount']),kes($r['amount_paid']),kes($r['balance_due']),date('d M Y',strtotime($r['sale_date']))],$rows)];
}

$grossProfit = 0.0;
if ($canProfit) {
    $p = r_one($pdo, 'SELECT COALESCE(SUM(cogs_amount),0) cogs, COALESCE(SUM(gross_profit),0) gross_profit
        FROM sales WHERE status="completed" AND sale_date>=:start_date AND sale_date<:end_date', $params);
    $grossProfit = (float)($p['gross_profit'] ?? 0);
    $summary['COGS'] = kes($p['cogs'] ?? 0);
    $summary['Gross Profit'] = kes($grossProfit);
}

$expenseTotal = 0.0;
if ($canExpenses) {
    $e = r_one($pdo, 'SELECT COALESCE(SUM(amount),0) total, COUNT(*) count
        FROM expenses WHERE status="active" AND expense_date>=:start_date AND expense_date<:end_date', $params);
    $expenseTotal = (float)($e['total'] ?? 0);
    $summary['Expenses'] = kes($expenseTotal);
    $summary['Expense Entries'] = (string)($e['count'] ?? 0);
    $rows = r_all($pdo, 'SELECT e.expense_date, ec.name category, e.description, pm.name payment_method, e.amount, u.full_name
        FROM expenses e INNER JOIN expense_categories ec ON ec.id=e.expense_category_id
        INNER JOIN payment_methods pm ON pm.id=e.payment_method_id INNER JOIN users u ON u.id=e.created_by
        WHERE e.status="active" AND e.expense_date>=:start_date AND e.expense_date<:end_date ORDER BY e.expense_date DESC', $params);
    $sections[] = ['Expenses', ['Date','Category','Description','Payment','Amount','Recorded By'],
        array_map(fn($r)=>[date('d M Y',strtotime($r['expense_date'])),$r['category'],$r['description'],$r['payment_method'],kes($r['amount']),$r['full_name']],$rows)];
}
if ($canProfit && $canExpenses) {
    $summary['Net Profit'] = kes($grossProfit - $expenseTotal);
}

if ($canPayments) {
    $rows = r_all($pdo, 'SELECT pm.name, pm.method_type, COUNT(*) transactions, COALESCE(SUM(sp.amount),0) total
        FROM sale_payments sp INNER JOIN sales s ON s.id=sp.sale_id INNER JOIN payment_methods pm ON pm.id=sp.payment_method_id
        WHERE s.status="completed" AND sp.paid_at>=:start_date AND sp.paid_at<:end_date
        GROUP BY pm.id,pm.name,pm.method_type ORDER BY total DESC', $params);
    $sections[] = ['Sales Payment Methods', ['Method','Type','Transactions','Amount'],
        array_map(fn($r)=>[$r['name'],ucfirst($r['method_type']),$r['transactions'],kes($r['total'])],$rows)];
}

if (user_can('reports.sales')) {
    $rows = r_all($pdo, 'SELECT si.product_name_snapshot product, si.unit_snapshot unit_name,
        COALESCE(SUM(si.quantity),0) quantity, COALESCE(SUM(si.line_total),0) revenue
        FROM sale_items si INNER JOIN sales s ON s.id=si.sale_id
        WHERE s.status="completed" AND s.sale_date>=:start_date AND s.sale_date<:end_date
        GROUP BY si.product_id,si.product_name_snapshot,si.unit_snapshot ORDER BY revenue DESC LIMIT 20', $params);
    $sections[] = ['Top Products', ['Product','Unit','Qty Sold','Revenue'],
        array_map(fn($r)=>[$r['product'],$r['unit_name'],num($r['quantity'],3),kes($r['revenue'])],$rows)];
}

if ($canPurchases) {
    $p = r_one($pdo, 'SELECT COALESCE(SUM(total_amount),0) total, COUNT(*) count, COALESCE(SUM(amount_paid),0) paid,
        COALESCE(SUM(balance_due),0) balance FROM purchases
        WHERE status<>"cancelled" AND purchase_date>=:start_date AND purchase_date<:end_date', $params);
    $summary['Purchases'] = kes($p['total'] ?? 0);
    $summary['Purchase Count'] = (string)($p['count'] ?? 0);
    $summary['Supplier Balance'] = kes($p['balance'] ?? 0);
    $rows = r_all($pdo, 'SELECT p.purchase_no,s.name supplier,p.status,p.payment_status,p.total_amount,p.amount_paid,p.balance_due,p.purchase_date
        FROM purchases p INNER JOIN suppliers s ON s.id=p.supplier_id
        WHERE p.status<>"cancelled" AND p.purchase_date>=:start_date AND p.purchase_date<:end_date ORDER BY p.purchase_date DESC', $params);
    $sections[] = ['Purchases', ['Purchase','Supplier','Status','Payment','Total','Paid','Balance','Date'],
        array_map(fn($r)=>[$r['purchase_no'],$r['supplier'],ucwords(str_replace('_',' ',$r['status'])),ucfirst($r['payment_status']),kes($r['total_amount']),kes($r['amount_paid']),kes($r['balance_due']),date('d M Y',strtotime($r['purchase_date']))],$rows)];
}

if ($canInventory) {
    $inv = r_one($pdo, 'SELECT COUNT(*) products, COALESCE(SUM(current_stock*average_cost),0) stock_value,
        COALESCE(SUM(CASE WHEN track_stock=1 AND current_stock<=minimum_stock THEN 1 ELSE 0 END),0) low_stock,
        COALESCE(SUM(CASE WHEN track_stock=1 AND current_stock<=0 THEN 1 ELSE 0 END),0) out_stock
        FROM products WHERE status="active"');
    $summary['Stock Value'] = kes($inv['stock_value'] ?? 0);
    $summary['Active Products'] = (string)($inv['products'] ?? 0);
    $summary['Low Stock'] = (string)($inv['low_stock'] ?? 0);
    $summary['Out of Stock'] = (string)($inv['out_stock'] ?? 0);
    $rows = r_all($pdo, 'SELECT p.name,u.short_name,p.current_stock,p.minimum_stock,p.average_cost,
        (p.current_stock*p.average_cost) stock_value FROM products p INNER JOIN units u ON u.id=p.unit_id
        WHERE p.status="active" ORDER BY p.name');
    $sections[] = ['Inventory Snapshot', ['Product','Unit','Stock','Minimum','Avg Cost','Stock Value'],
        array_map(fn($r)=>[$r['name'],$r['short_name'],num($r['current_stock'],3),num($r['minimum_stock'],3),kes($r['average_cost']),kes($r['stock_value'])],$rows)];

    $rows = r_all($pdo, 'SELECT sm.created_at,p.name product,sm.movement_type,sm.reference_no,sm.quantity_change,sm.stock_before,sm.stock_after,u.full_name
        FROM stock_movements sm INNER JOIN products p ON p.id=sm.product_id INNER JOIN users u ON u.id=sm.created_by
        WHERE sm.created_at>=:start_date AND sm.created_at<:end_date ORDER BY sm.created_at DESC LIMIT 100', $params);
    $sections[] = ['Stock Movements', ['Date','Product','Movement','Reference','Qty Change','Before','After','User'],
        array_map(fn($r)=>[date('d M Y',strtotime($r['created_at'])),$r['product'],ucwords(str_replace('_',' ',$r['movement_type'])),$r['reference_no'] ?: '-',num($r['quantity_change'],3),num($r['stock_before'],3),num($r['stock_after'],3),$r['full_name']],$rows)];
}

if ($canCustomers || $canCredit) {
    $c = r_one($pdo, 'SELECT COUNT(*) customers, COALESCE(SUM(CASE WHEN account_balance>0 THEN account_balance ELSE 0 END),0) debt,
        COALESCE(SUM(CASE WHEN account_balance>0 THEN 1 ELSE 0 END),0) debtors FROM customers WHERE status="active"');
    $summary['Active Customers'] = (string)($c['customers'] ?? 0);
    $summary['Outstanding Customer Debt'] = kes($c['debt'] ?? 0);
    $summary['Debtors'] = (string)($c['debtors'] ?? 0);
}
if ($canCredit) {
    $rows = r_all($pdo, 'SELECT name,phone,email,credit_limit,account_balance FROM customers
        WHERE status="active" AND account_balance>0 ORDER BY account_balance DESC');
    $sections[] = ['Customer Credit / Debtors', ['Customer','Phone','Email','Credit Limit','Balance'],
        array_map(fn($r)=>[$r['name'],$r['phone'] ?: '-',$r['email'] ?: '-',kes($r['credit_limit']),kes($r['account_balance'])],$rows)];
}

if ($canSuppliers) {
    $summary['Active Suppliers'] = (string)$pdo->query('SELECT COUNT(*) FROM suppliers WHERE status="active"')->fetchColumn();
}
if ($canUsers) {
    $summary['Active Staff'] = (string)$pdo->query('SELECT COUNT(*) FROM users WHERE status="active"')->fetchColumn();
}

if ($canAudit) {
    $rows = r_all($pdo, 'SELECT a.created_at,COALESCE(u.full_name,"System") full_name,a.module,a.action,a.description
        FROM audit_logs a LEFT JOIN users u ON u.id=a.user_id
        WHERE a.created_at>=:start_date AND a.created_at<:end_date ORDER BY a.created_at DESC LIMIT 100', $params);
    $sections[] = ['System Activity', ['Date','User','Module','Action','Description'],
        array_map(fn($r)=>[date('d M Y H:i',strtotime($r['created_at'])),$r['full_name'],$r['module'],$r['action'],$r['description']],$rows)];
}

/*
 * Small dependency-free PDF writer.
 * It produces a real A4 PDF, using a green/neutral visual language matching the POS UI.
 */
final class SimplePdf {
    private array $pages = [];
    private array $ops = [];
    private float $y = 800;
    private const W = 595.28;
    private const H = 841.89;

    public function __construct() { $this->newPage(); }
    private function esc(string $s): string {
        $s = iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $s) ?: $s;
        return str_replace(['\\','(',')',"\r","\n"], ['\\\\','\\(','\\)',' ',' '], $s);
    }
    private function newPage(): void {
        if ($this->ops) $this->pages[] = implode("\n",$this->ops);
        $this->ops=[]; $this->y=800;
        $this->rect(0,0,self::W,self::H,[1,1,1],true);
    }
    private function ensure(float $need=30): void { if ($this->y-$need<45) $this->newPage(); }
    public function rect(float $x,float $y,float $w,float $h,array $rgb,bool $fill=true): void {
        $this->ops[] = sprintf('%.3f %.3f %.3f rg %.2f %.2f %.2f %.2f re %s',$rgb[0],$rgb[1],$rgb[2],$x,$y,$w,$h,$fill?'f':'S');
    }
    public function text(string $t,float $x,float $y,float $size=9,bool $bold=false,array $rgb=[0.12,0.16,0.20]): void {
        $font=$bold?'F2':'F1';
        $this->ops[] = sprintf('BT /%s %.2f Tf %.3f %.3f %.3f rg %.2f %.2f Td (%s) Tj ET',$font,$size,$rgb[0],$rgb[1],$rgb[2],$x,$y,$this->esc($t));
    }
    private function fit(string $s,int $chars): string {
        if (mb_strlen($s)<= $chars) return $s;
        return mb_substr($s,0,max(1,$chars-3)).'...';
    }
    public function header(string $business,string $period,string $generated): void {
        $this->rect(0,748,self::W,94,[0.055,0.29,0.16],true);
        $this->text($business,38,805,18,true,[1,1,1]);
        $this->text('SYSTEM OVERALL REPORT',38,782,11,true,[0.82,0.94,0.86]);
        $this->text('Reporting period: '.$period,38,762,9,false,[1,1,1]);
        $this->text('Generated: '.$generated,380,762,8,false,[1,1,1]);
        $this->y=728;
    }
    public function sectionTitle(string $title): void {
        $this->ensure(35);
        $this->rect(36,$this->y-3,523,23,[0.93,0.97,0.94],true);
        $this->text($title,44,$this->y+4,11,true,[0.055,0.29,0.16]);
        $this->y-=34;
    }
    public function keyValues(array $items): void {
        $pairs=array_chunk($items,2,true);
        foreach($pairs as $pair){
            $this->ensure(34); $x=36;
            foreach($pair as $k=>$v){
                $this->rect($x,$this->y-17,252,29,[0.975,0.98,0.98],true);
                $this->text((string)$k,$x+8,$this->y+2,7,false,[0.38,0.43,0.47]);
                $this->text($this->fit((string)$v,30),$x+8,$this->y-11,10,true,[0.08,0.18,0.12]);
                $x+=271;
            }
            $this->y-=37;
        }
    }
    public function table(array $headers,array $rows): void {
        if(!$rows){ $this->text('No records for this period.',40,$this->y,9,false,[0.45,0.48,0.50]); $this->y-=22; return; }
        $n=count($headers); $usable=523; $cw=$usable/$n;
        $drawHead=function()use($headers,$n,$cw){
            $this->rect(36,$this->y-12,523,22,[0.055,0.29,0.16],true);
            foreach($headers as $i=>$h) $this->text($this->fit((string)$h,max(8,(int)(58/$n))),39+$i*$cw,$this->y-3,6.8,true,[1,1,1]);
            $this->y-=25;
        };
        $drawHead();
        foreach($rows as $ri=>$row){
            if($this->y<55){ $this->newPage(); $drawHead(); }
            if($ri%2===0) $this->rect(36,$this->y-11,523,20,[0.97,0.98,0.97],true);
            foreach(array_values($row) as $i=>$v){
                if($i >= $n) break;
                $chars=max(7,(int)(72/$n));
                $this->text($this->fit((string)$v,$chars),39+$i*$cw,$this->y-3,6.4,false,[0.16,0.19,0.21]);
            }
            $this->y-=20;
        }
        $this->y-=8;
    }
    public function output(): string {
        if($this->ops) {$this->pages[]=implode("\n",$this->ops); $this->ops=[];}
        $objects=[]; $objects[1]='<< /Type /Catalog /Pages 2 0 R >>';
        $kids=[]; $obj=5;
        foreach($this->pages as $content){
            $pageObj=$obj++; $contentObj=$obj++;
            $kids[]=$pageObj.' 0 R';
            $objects[$pageObj]='<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595.28 841.89] /Resources << /Font << /F1 3 0 R /F2 4 0 R >> >> /Contents '.$contentObj.' 0 R >>';
            $objects[$contentObj]='<< /Length '.strlen($content)." >>\nstream\n".$content."\nendstream";
        }
        $objects[2]='<< /Type /Pages /Kids ['.implode(' ',$kids).'] /Count '.count($kids).' >>';
        $objects[3]='<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>';
        $objects[4]='<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>';
        ksort($objects);
        $pdf="%PDF-1.4\n"; $offset=[0];
        foreach($objects as $id=>$body){$offset[$id]=strlen($pdf);$pdf.=$id." 0 obj\n".$body."\nendobj\n";}
        $xref=strlen($pdf); $max=max(array_keys($objects));
        $pdf.="xref\n0 ".($max+1)."\n0000000000 65535 f \n";
        for($i=1;$i<=$max;$i++) $pdf.=sprintf("%010d 00000 n \n",$offset[$i]??0);
        $pdf.="trailer\n<< /Size ".($max+1)." /Root 1 0 R >>\nstartxref\n".$xref."\n%%EOF";
        return $pdf;
    }
}

$pdf = new SimplePdf();
$pdf->header($businessName, $periodLabel, date('d M Y H:i'));
$pdf->sectionTitle('Executive Summary');
$pdf->keyValues($summary);

foreach ($sections as [$title,$headers,$rows]) {
    $pdf->sectionTitle($title);
    $pdf->table($headers,$rows);
}

$filename = 'system-report-' . $startDate->format('Ymd') . '-to-' . $endDateExclusive->modify('-1 day')->format('Ymd') . '.pdf';
$data = $pdf->output();

header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . strlen($data));
header('Cache-Control: private, no-store, max-age=0');
echo $data;
exit;
