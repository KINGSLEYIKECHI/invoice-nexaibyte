<?php
namespace App\Services;
use App\Models\Product;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\{DB,Validator};
use Illuminate\Validation\{Rule,ValidationException};
class ProductImportService {
 public const HEADERS=['sku','name','description','unit','unit_price','currency','is_active'];
 private function fail(string $message):never {throw ValidationException::withMessages(['file'=>$message]);}
 private function xml(string $s):\DOMDocument {
  if(stripos($s,'<!DOCTYPE')!==false||stripos($s,'<!ENTITY')!==false)$this->fail('XML entities and document types are not supported.');
  $doc=new \DOMDocument();$old=libxml_use_internal_errors(true);try{if(!$doc->loadXML($s,LIBXML_NONET))$this->fail('Invalid Excel XML.');}finally{libxml_clear_errors();libxml_use_internal_errors($old);}return $doc;
 }
 private function xlsx(string $path):array {
  if(!class_exists(\ZipArchive::class))$this->fail('Enable the PHP zip extension to import XLSX, or use CSV.');$zip=new \ZipArchive();if($zip->open($path)!==true)$this->fail('Invalid XLSX archive.');
  try {
   if($zip->numFiles>1000)$this->fail('Excel workbook contains too many files.');$size=0;for($n=0;$n<$zip->numFiles;$n++){$stat=$zip->statIndex($n);$size+=$stat['size'];if($size>20*1024*1024||$stat['size']>10*1024*1024)$this->fail('Excel workbook expands beyond the supported size.');if(preg_match('/vbaProject|externalLinks/i',$stat['name']))$this->fail('Macros and external workbook links are not supported.');}
   $read=function($name)use($zip){$s=$zip->getFromName($name);if($s===false)$this->fail('Required Excel worksheet information is missing.');return $this->xml($s);};
   $workbook=$read('xl/workbook.xml');$x=new \DOMXPath($workbook);$sheet=$x->query('//*[local-name()="sheets"]/*[local-name()="sheet"]')->item(0);if(!$sheet)$this->fail('Workbook has no worksheet.');$relId=$sheet->getAttributeNS('http://schemas.openxmlformats.org/officeDocument/2006/relationships','id');
   $rels=new \DOMXPath($read('xl/_rels/workbook.xml.rels'));$target=null;foreach($rels->query('//*[local-name()="Relationship"]') as $rel){if($rel->getAttribute('Id')===$relId){if($rel->getAttribute('TargetMode')==='External')$this->fail('External worksheets are not supported.');$target=ltrim($rel->getAttribute('Target'),'/');}}
   if(!$target)$this->fail('Worksheet relationship is missing.');if(!str_starts_with($target,'xl/'))$target='xl/'.$target;if(!preg_match('~^xl/worksheets/[A-Za-z0-9_.-]+\.xml$~D',$target))$this->fail('Unsupported worksheet path.');
   $strings=[];if($zip->locateName('xl/sharedStrings.xml')!==false){$sx=new \DOMXPath($read('xl/sharedStrings.xml'));foreach($sx->query('//*[local-name()="si"]') as $si){$text='';foreach($sx->query('.//*[local-name()="t"]',$si) as $t)$text.=$t->textContent;$strings[]=$text;}}
   $sx=new \DOMXPath($read($target));if($sx->query('//*[local-name()="f"]')->length)$this->fail('Replace formulas with values before importing.');$rows=[];
   foreach($sx->query('//*[local-name()="sheetData"]/*[local-name()="row"]') as $row){if(count($rows)>500)$this->fail('Limit each import to 500 product rows.');$values=array_fill(0,7,'');$nonempty=false;foreach($sx->query('./*[local-name()="c"]',$row) as $cell){if(!preg_match('/^([A-Z]+)[1-9][0-9]*$/D',$cell->getAttribute('r'),$m))$this->fail('Invalid Excel cell reference.');$col=0;foreach(str_split($m[1]) as $ch)$col=$col*26+ord($ch)-64;$col--;if($col>6){if(trim($cell->textContent)!=='')$this->fail('Use only the seven template columns.');continue;}$type=$cell->getAttribute('t');$value=$sx->query('./*[local-name()="v"]',$cell)->item(0)?->textContent??'';if($type==='s'){$value=$strings[(int)$value]??'';}elseif($type==='inlineStr'){$value='';foreach($sx->query('.//*[local-name()="t"]',$cell) as $t)$value.=$t->textContent;}elseif($type==='e')$this->fail('Excel error cells are not supported.');$values[$col]=trim($value);$nonempty=$nonempty||$values[$col]!=='';}if($nonempty)$rows[]=$values;
   }return $rows;
  }finally{$zip->close();}
 }
 private function read(UploadedFile $file):array {
  $ext=strtolower($file->getClientOriginalExtension());if($ext==='xlsx')return $this->xlsx($file->getRealPath());if($ext!=='csv')$this->fail('Upload a UTF-8 CSV or .xlsx file. Legacy .xls is not supported.');
  $f=fopen($file->getRealPath(),'rb');$rows=[];try{while(($row=fgetcsv($f,0,',','"',''))!==false){if(count($rows)>500)$this->fail('Limit each import to 500 product rows.');if(count($row)===1&&($row[0]===null||trim($row[0])===''))continue;$rows[]=array_map(fn($v)=>trim((string)$v),$row);}}finally{fclose($f);}return $rows;
 }
 public function preview(UploadedFile $file,bool $updates=false):array {
  $rows=$this->read($file);$header=array_shift($rows)??[];if($header)$header[0]=ltrim($header[0],"\xEF\xBB\xBF");if($header!==self::HEADERS)$this->fail('Use the template column headers, in the same order.');if(!$rows)$this->fail('Add at least one product row.');$errors=[];$data=[];$seen=[];$existing=Product::pluck('id','sku');$currencies=app(CurrencyService::class);$creates=0;$changes=0;
  foreach($rows as $n=>$row){$line=$n+2;if(count($row)!==7){$errors[]=['row'=>$line,'message'=>'Expected seven columns.'];continue;}$d=array_combine(self::HEADERS,$row);$d['sku']=strtoupper($d['sku']);$v=Validator::make($d,['sku'=>'required|string|max:80|regex:/^[A-Za-z0-9][A-Za-z0-9._-]*$/','name'=>'required|string|max:150','description'=>'nullable|string|max:2000','unit'=>'required|string|max:30','unit_price'=>'required|string|max:30','currency'=>['required',Rule::in($currencies->codes())],'is_active'=>['required',Rule::in(['1','0','true','false'])]]);
   if($v->fails()){$errors[]=['row'=>$line,'message'=>implode(' ',$v->errors()->all())];continue;}if(isset($seen[strtolower($d['sku'])])){$errors[]=['row'=>$line,'message'=>'Duplicate SKU in this file.'];continue;}$seen[strtolower($d['sku'])]=true;
   if($existing->has($d['sku'])&&!$updates){$errors[]=['row'=>$line,'message'=>'SKU already exists. Enable updates to replace its catalogue fields.'];continue;}
   try{$minor=$currencies->minor($d['unit_price'],$d['currency']);}catch(ValidationException $e){$errors[]=['row'=>$line,'message'=>implode(' ',array_merge(...array_values($e->errors())))];continue;}
   foreach(['name','description','unit'] as $key)if(preg_match('/^[=+@]/',$d[$key])){$errors[]=['row'=>$line,'message'=>'Formula-like text is not supported.'];continue 2;}
   unset($d['unit_price']);$d['unit_price_minor']=$minor;$d['is_active']=in_array($d['is_active'],['1','true'],true);$data[]=$d;if($existing->has($d['sku']))$changes++;else $creates++;
  }return ['rows'=>$data,'errors'=>$errors,'create_count'=>$creates,'update_count'=>$changes,'can_import'=>!$errors];
 }
 public function import(UploadedFile $file,bool $updates=false):array {return DB::transaction(function()use($file,$updates){
  // Serialize imports for this tenant; confirmation reparses the file and rechecks current SKUs.
  $b=\App\Models\Business::whereKey(auth()->user()->business_id)->lockForUpdate()->firstOrFail();$result=$this->preview($file,$updates);if($result['errors'])throw ValidationException::withMessages(['file'=>array_map(fn($e)=>'Row '.$e['row'].': '.$e['message'],$result['errors'])]);foreach($result['rows'] as $d){Product::updateOrCreate(['business_id'=>$b->id,'sku'=>$d['sku']],$d);}return ['created'=>$result['create_count'],'updated'=>$result['update_count']];
 },5);}
 public function template(string $format,string $currency):array {
  $digits=app(CurrencyService::class)->precision($currency);$rows=[self::HEADERS,['PROD-001','Example product','Replace this example with your product','pcs',number_format(100,$digits,'.',''),$currency,'1']];
  if($format==='csv'){$f=fopen('php://temp','r+');foreach($rows as $row)fputcsv($f,$row,',','"','');rewind($f);$bytes=stream_get_contents($f);fclose($f);return [$bytes,'text/csv; charset=UTF-8'];}
  if($format!=='xlsx')abort(404);if(!class_exists(\ZipArchive::class))$this->fail('Enable the PHP zip extension or download the CSV template.');$tmp=tempnam(sys_get_temp_dir(),'products-');$z=new \ZipArchive();try{if($z->open($tmp,\ZipArchive::OVERWRITE)!==true)$this->fail('Unable to generate Excel template.');
   $z->addFromString('[Content_Types].xml','<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/></Types>');
   $z->addFromString('_rels/.rels','<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
   $z->addFromString('xl/workbook.xml','<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Products" sheetId="1" r:id="rId1"/></sheets></workbook>');
   $z->addFromString('xl/_rels/workbook.xml.rels','<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/></Relationships>');
   $xml='<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><cols><col min="1" max="1" width="16" customWidth="1"/><col min="2" max="2" width="28" customWidth="1"/><col min="3" max="3" width="48" customWidth="1"/><col min="4" max="7" width="18" customWidth="1"/></cols><sheetData>';foreach($rows as $n=>$row){$xml.='<row r="'.($n+1).'">';foreach($row as $i=>$value)$xml.='<c r="'.chr(65+$i).($n+1).'" t="inlineStr"><is><t>'.htmlspecialchars($value,ENT_XML1|ENT_QUOTES,'UTF-8').'</t></is></c>';$xml.='</row>';}$xml.='</sheetData></worksheet>';$z->addFromString('xl/worksheets/sheet1.xml',$xml);$z->close();return [file_get_contents($tmp),'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'];
  }finally{if(is_file($tmp))unlink($tmp);}
 }
}
