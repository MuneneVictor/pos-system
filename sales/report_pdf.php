<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/config/bootstrap.php';
require_permission('sales.view');

$q=trim((string)($_GET['q']??''));
$status=(string)($_GET['status']??'completed');
$from=trim((string)($_GET['from']??''));
$to=trim((string)($_GET['to']??''));
$where=['1=1'];$params=[];
if($q!==''){$where[]='(s.sale_no LIKE :q OR c.name LIKE :q OR u.full_name LIKE :q)';$params[':q']='%'.$q.'%';}
if(in_array($status,['completed','held','voided'],true)){$where[]='s.status=:status';$params[':status']=$status;}
if($from!==''){$where[]='s.sale_date>=:from';$params[':from']=$from.' 00:00:00';}
if($to!==''){$where[]='s.sale_date<DATE_ADD(:to,INTERVAL 1 DAY)';$params[':to']=$to.' 00:00:00';}
$ws=implode(' AND ',$where);

$sum=db()->prepare("SELECT COALESCE(SUM(s.total_amount),0) sales,COALESCE(SUM(s.gross_profit),0) profit,COALESCE(SUM(s.amount_paid),0) paid,COALESCE(SUM(s.balance_due),0) balance,COUNT(*) transactions FROM sales s LEFT JOIN customers c ON c.id=s.customer_id INNER JOIN users u ON u.id=s.created_by WHERE $ws");
$sum->execute($params);$summary=$sum->fetch()?:[];

$st=db()->prepare("SELECT s.*,c.name customer_name,u.full_name cashier FROM sales s LEFT JOIN customers c ON c.id=s.customer_id INNER JOIN users u ON u.id=s.created_by WHERE $ws ORDER BY s.sale_date DESC,s.id DESC");
$st->execute($params);$rows=$st->fetchAll();

$business=(string)setting('business.name','Wambo wa Carpets');
$period=$from!==''||$to!=='' ? (($from?:'Beginning').' to '.($to?:'Present')) : 'All time';
$statusLabel=in_array($status,['completed','held','voided'],true)?ucfirst($status):'All statuses';

function pe(string $s):string{$s=iconv('UTF-8','Windows-1252//TRANSLIT//IGNORE',$s)?:$s;return str_replace(['\\','(',')',"\r","\n"],['\\\\','\\(','\\)',' ',' '],$s);}
function pt(array &$o,string $t,float $x,float $y,float $z=8,bool $b=false,array $rgb=[0.08,0.12,0.10]):void{$o[]=sprintf('BT /%s %.2f Tf %.3f %.3f %.3f rg %.2f %.2f Td (%s) Tj ET',$b?'F2':'F1',$z,$rgb[0],$rgb[1],$rgb[2],$x,$y,pe($t));}
function pl(array &$o,float $x1,float $y1,float $x2,float $y2):void{$o[]=sprintf('0.82 G %.2f %.2f m %.2f %.2f l S',$x1,$y1,$x2,$y2);}
function fit(string $s,int $n):string{return strlen($s)>$n?substr($s,0,$n-3).'...':$s;}

$pages=[];$ops=[];$y=545;
$new=function()use(&$pages,&$ops,&$y){if($ops)$pages[]=implode("\n",$ops);$ops=[];$y=800;};
$header=function()use(&$ops,&$y,$business,$period,$statusLabel,$q){
    $ops[]='0.055 0.29 0.16 rg 0 500 841.89 95 re f';
    pt($ops,$business,42,557,22,true,[1,1,1]);
    pt($ops,'SALES PERFORMANCE REPORT',42,530,15,true,[0.88,0.96,0.90]);
    pt($ops,'Period: '.$period,42,509,9,false,[1,1,1]);
    pt($ops,'Status: '.$statusLabel,340,509,9,false,[1,1,1]);
    if($q!=='')pt($ops,'Search: '.fit($q,55),545,509,8,false,[1,1,1]);
    $y=474;
};
$header();

pt($ops,'FILTERED SALES SUMMARY',42,$y,13,true);$y-=28;
$summaryItems=[
    'Sales Total'=>money($summary['sales']??0),
    'Gross Profit'=>money($summary['profit']??0),
    'Transactions'=>(string)($summary['transactions']??0),
    'Amount Paid'=>money($summary['paid']??0),
    'Outstanding Balance'=>money($summary['balance']??0)
];
$x=42;
foreach($summaryItems as$k=>$v){
    $ops[]=sprintf('0.955 0.975 0.960 rg %.2f %.2f 145 58 re f',$x,$y-40);
    pt($ops,$k,$x+10,$y-8,8);
    pt($ops,(string)$v,$x+10,$y-29,12,true);
    $x+=154;
}
$y-=72;
pt($ops,'SALES DETAILS',42,$y,13,true);$y-=22;

$drawHead=function()use(&$ops,&$y){
    $ops[]='0.055 0.29 0.16 rg 42 '.($y-15).' 757 28 re f';
    foreach([['Sale',48],['Date',145],['Customer',245],['Cashier',365],['Total',485],['Paid',575],['Balance',665],['Payment',755]] as[$h,$x])pt($ops,$h,$x,$y-5,8,true,[1,1,1]);
    $y-=34;
};
$drawHead();
foreach($rows as$r){
    if($y<55){$new();$header();pt($ops,'SALES DETAILS - CONTINUED',42,$y,12,true);$y-=22;$drawHead();}
    pt($ops,fit((string)$r['sale_no'],15),48,$y,8.2,true);
    pt($ops,date('d M Y H:i',strtotime($r['sale_date'])),145,$y,7.8);
    pt($ops,fit((string)($r['customer_name']?:'Walk-in'),20),245,$y,7.8);
    pt($ops,fit((string)$r['cashier'],18),365,$y,7.8);
    pt($ops,money($r['total_amount']),485,$y,7.8);
    pt($ops,money($r['amount_paid']),575,$y,7.8);
    pt($ops,money($r['balance_due']),665,$y,7.8);
    pt($ops,ucfirst((string)$r['payment_status']),755,$y,7.3);
    $y-=24;pl($ops,42,$y+8,799,$y+8);
}
if(!$rows){pt($ops,'No sales found for the selected filters.',40,$y,9);}
if($ops)$pages[]=implode("\n",$ops);

$objects=[1=>'<< /Type /Catalog /Pages 2 0 R >>',3=>'<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',4=>'<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>'];
$kids=[];$obj=5;
foreach($pages as$content){$po=$obj++;$co=$obj++;$kids[]=$po.' 0 R';$objects[$po]='<< /Type /Page /Parent 2 0 R /MediaBox [0 0 841.89 595.28] /Resources << /Font << /F1 3 0 R /F2 4 0 R >> >> /Contents '.$co.' 0 R >>';$objects[$co]='<< /Length '.strlen($content)." >>\nstream\n".$content."\nendstream";}
$objects[2]='<< /Type /Pages /Kids ['.implode(' ',$kids).'] /Count '.count($kids).' >>';ksort($objects);
$pdf="%PDF-1.4\n";$off=[0];foreach($objects as$id=>$body){$off[$id]=strlen($pdf);$pdf.=$id." 0 obj\n".$body."\nendobj\n";}$xref=strlen($pdf);$max=max(array_keys($objects));$pdf.="xref\n0 ".($max+1)."\n0000000000 65535 f \n";for($i=1;$i<=$max;$i++)$pdf.=sprintf("%010d 00000 n \n",$off[$i]??0);$pdf.="trailer\n<< /Size ".($max+1)." /Root 1 0 R >>\nstartxref\n$xref\n%%EOF";

header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="sales-report-'.date('Ymd-His').'.pdf"');
header('Content-Length: '.strlen($pdf));
header('Cache-Control: private, no-store, max-age=0');
echo $pdf;exit;
