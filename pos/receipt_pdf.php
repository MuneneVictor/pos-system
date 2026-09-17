<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config/bootstrap.php';
require_auth();

$id = (int)($_GET['id'] ?? 0);
$st = db()->prepare('SELECT s.*,u.full_name cashier,c.name customer_name,c.phone customer_phone,b.name branch_name,b.address branch_address,b.phone branch_phone FROM sales s INNER JOIN users u ON u.id=s.created_by INNER JOIN branches b ON b.id=s.branch_id LEFT JOIN customers c ON c.id=s.customer_id WHERE s.id=:id AND s.status IN("completed","voided")');
$st->execute([':id'=>$id]);
$sale = $st->fetch();
if (!$sale) { http_response_code(404); exit('Receipt not found.'); }

$it = db()->prepare('SELECT * FROM sale_items WHERE sale_id=:id ORDER BY id');
$it->execute([':id'=>$id]);
$items = $it->fetchAll();

$pay = db()->prepare('SELECT sp.*,pm.name method_name,pm.method_type FROM sale_payments sp INNER JOIN payment_methods pm ON pm.id=sp.payment_method_id WHERE sp.sale_id=:id ORDER BY sp.id');
$pay->execute([':id'=>$id]);
$payments = $pay->fetchAll();

$businessName = (string)setting('business.name','Wambo wa Carpets');

function pdf_escape(string $s): string {
    $s = iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $s) ?: $s;
    return str_replace(['\\','(',')',"\r","\n"], ['\\\\','\\(','\\)',' ',' '], $s);
}
function pdf_text(array &$ops, string $text, float $x, float $y, float $size=10, bool $bold=false): void {
    $ops[] = sprintf('BT /%s %.2f Tf %.2f %.2f Td (%s) Tj ET', $bold?'F2':'F1', $size, $x, $y, pdf_escape($text));
}
function pdf_line(array &$ops, float $x1,float $y1,float $x2,float $y2): void {
    $ops[] = sprintf('0.80 G %.2f %.2f m %.2f %.2f l S',$x1,$y1,$x2,$y2);
}

$pages=[]; $ops=[]; $y=790;
$newPage = function() use (&$pages,&$ops,&$y) {
    if ($ops) $pages[] = implode("\n",$ops);
    $ops=[]; $y=790;
};
$ensure = function(float $need=30) use (&$y,&$newPage) {
    if ($y-$need < 45) $newPage();
};

pdf_text($ops,$businessName,40,$y,18,true); $y-=20;
pdf_text($ops,$sale['branch_address'] ?: $sale['branch_name'],40,$y,9);
if ($sale['branch_phone']) { $y-=13; pdf_text($ops,$sale['branch_phone'],40,$y,9); }
$y-=22; pdf_line($ops,40,$y,555,$y); $y-=22;
pdf_text($ops,'RECEIPT',40,$y,14,true);
pdf_text($ops,(string)$sale['sale_no'],420,$y,11,true); $y-=20;
pdf_text($ops,'Date: '.format_datetime($sale['sale_date']),40,$y,9);
pdf_text($ops,'Cashier: '.$sale['cashier'],300,$y,9); $y-=16;
pdf_text($ops,'Customer: '.($sale['customer_name'] ?: 'Walk-in customer'),40,$y,9); $y-=20;
if ($sale['status']==='voided') { pdf_text($ops,'VOIDED',245,$y,18,true); $y-=25; }

pdf_line($ops,40,$y,555,$y); $y-=18;
pdf_text($ops,'Item',40,$y,9,true); pdf_text($ops,'Qty',310,$y,9,true); pdf_text($ops,'Price',390,$y,9,true); pdf_text($ops,'Total',485,$y,9,true); $y-=12;
pdf_line($ops,40,$y,555,$y); $y-=17;

foreach ($items as $i) {
    $ensure(36);
    $name=(string)$i['product_name_snapshot'];
    if (strlen($name)>43) $name=substr($name,0,40).'...';
    pdf_text($ops,$name,40,$y,9,true);
    pdf_text($ops,format_quantity($i['quantity']).' '.$i['unit_snapshot'],310,$y,8);
    pdf_text($ops,money($i['unit_price']),390,$y,8);
    pdf_text($ops,money($i['line_total']),485,$y,8);
    $y-=13;
    $detail=(string)$i['sku_snapshot'];
    if ((float)$i['discount_amount']>0) $detail.=' | Discount '.money($i['discount_amount']);
    pdf_text($ops,$detail,40,$y,7); $y-=17;
}
pdf_line($ops,300,$y,555,$y); $y-=18;
pdf_text($ops,'Subtotal',350,$y,9); pdf_text($ops,money($sale['subtotal']),485,$y,9,true); $y-=16;
pdf_text($ops,'Discount',350,$y,9); pdf_text($ops,'-'.money($sale['discount_amount']),485,$y,9,true); $y-=19;
pdf_text($ops,'TOTAL',350,$y,11,true); pdf_text($ops,money($sale['total_amount']),485,$y,11,true); $y-=25;

pdf_text($ops,'Payment',40,$y,10,true); $y-=17;
foreach ($payments as $p) {
    $ensure(20);
    $label=$p['method_name'].($p['reference_no'] ? ' | '.$p['reference_no'] : '');
    pdf_text($ops,$label,40,$y,8); pdf_text($ops,money($p['amount']),485,$y,8,true); $y-=15;
}
if ((float)$sale['balance_due']>0) {
    pdf_text($ops,'Outstanding balance',40,$y,9,true); pdf_text($ops,money($sale['balance_due']),485,$y,9,true); $y-=20;
}
$y-=10; pdf_line($ops,40,$y,555,$y); $y-=22;
pdf_text($ops,'Thank you for shopping with Wambo wa Carpets.',150,$y,10,true); $y-=16;
pdf_text($ops,'Receipt generated '.date('d M Y H:i'),205,$y,7);

if ($ops) $pages[] = implode("\n",$ops);

$objects=[1=>'<< /Type /Catalog /Pages 2 0 R >>',3=>'<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',4=>'<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>'];
$kids=[]; $obj=5;
foreach($pages as $content){
    $pageObj=$obj++; $contentObj=$obj++; $kids[]=$pageObj.' 0 R';
    $objects[$pageObj]='<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595.28 841.89] /Resources << /Font << /F1 3 0 R /F2 4 0 R >> >> /Contents '.$contentObj.' 0 R >>';
    $objects[$contentObj]='<< /Length '.strlen($content)." >>\nstream\n".$content."\nendstream";
}
$objects[2]='<< /Type /Pages /Kids ['.implode(' ',$kids).'] /Count '.count($kids).' >>';
ksort($objects);
$pdf="%PDF-1.4\n"; $offset=[0];
foreach($objects as $oid=>$body){$offset[$oid]=strlen($pdf);$pdf.=$oid." 0 obj\n".$body."\nendobj\n";}
$xref=strlen($pdf); $max=max(array_keys($objects));
$pdf.="xref\n0 ".($max+1)."\n0000000000 65535 f \n";
for($i=1;$i<=$max;$i++) $pdf.=sprintf("%010d 00000 n \n",$offset[$i]??0);
$pdf.="trailer\n<< /Size ".($max+1)." /Root 1 0 R >>\nstartxref\n$xref\n%%EOF";

$filename='receipt-'.preg_replace('/[^A-Za-z0-9_-]/','-',(string)$sale['sale_no']).'.pdf';
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="'.$filename.'"');
header('Content-Length: '.strlen($pdf));
header('Cache-Control: private, no-store, max-age=0');
echo $pdf;
exit;
