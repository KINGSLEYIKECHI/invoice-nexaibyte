<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use App\Models\{Business,User,Client,Invoice,Payment};
use App\Services\{InvoiceTotalsService,InvoiceNumberService};
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
class DemoTenantSeeder extends Seeder {
    public function run(): void {
        DB::transaction(function() {
            $tenants=[
                ['name'=>'Bright Spark Electricals','slug'=>'bright-spark','email'=>'billing@brightspark.test','phone'=>'+2348030000001','address'=>'12 Zik Avenue, Awka, Anambra','brand_color'=>'#0F766E','invoice_prefix'=>'INV','users'=>[['Chika Eze','owner@brightspark.test','owner'],['Ngozi Okafor','admin@brightspark.test','admin'],['Tunde Bello','staff@brightspark.test','staff']],
                'clients'=>[['Ada Obi','Obi Stores','ada@example.com','+2348012345678'],['Emeka Nwosu','Nwosu Pharmacy','emeka@example.com','+2348098765432'],['Funke Adeyemi','Adeyemi Hotels','funke@example.com','+2347011122233'],['Ifeanyi Okeke','Okeke Logistics','ify@example.com','+2348155566677']],
                'invoices'=>[[0,'paid',[['Shop wiring',1,8500000],['Distribution board',1,2200000]]],[1,'sent',[['Solar panel install (3kW)',1,21000000]]],[2,'partially_paid',[['Generator changeover switch',2,1800000],['Labour',1,1500000]]],[3,'overdue',[['Warehouse lighting',12,450000]]],[0,'draft',[['Maintenance retainer (monthly)',1,3000000]]]]],
                ['name'=>'KemTech Solutions','slug'=>'kemtech','email'=>'hello@kemtech.test','phone'=>'+2348030000002','address'=>'5 Upper Iweka Road, Onitsha, Anambra','brand_color'=>'#1D4ED8','invoice_prefix'=>'KT','users'=>[['Kem Ugwu','owner@kemtech.test','owner']],
                'clients'=>[['Blessing Udo','Udo Foods','blessing@example.com','+2348033344455']],
                'invoices'=>[[0,'sent',[['Website design',1,12000000],['Hosting (1 year)',1,1500000]]]]]
            ];
            foreach($tenants as $data) {
                if(Business::where('slug',$data['slug'])->exists()) continue;
                $users=$data['users'];$clients=$data['clients'];$invoices=$data['invoices'];unset($data['users'],$data['clients'],$data['invoices']);
                $b=Business::create(array_merge($data,['default_tax_percent'=>7.5,'payment_instructions'=>'Bank: Demo Bank | Acct: 0123456789 | Name: '.$data['name']]));
                $owner=null;
                foreach($users as [$name,$email,$role]) { $u=User::create(['business_id'=>$b->id,'name'=>$name,'email'=>$email,'role'=>$role,'password'=>'password']); $owner??=$u; }
                $created=[];
                foreach($clients as [$name,$company,$email,$phone]) $created[]=Client::withoutGlobalScopes()->create(compact('name','company','email','phone')+['business_id'=>$b->id]);
                foreach($invoices as [$index,$status,$lines]) {
                    $items=array_map(fn($line)=>['description'=>$line[0],'quantity'=>$line[1],'unit_price_kobo'=>$line[2]],$lines);
                    $result=app(InvoiceTotalsService::class)->calculate($items,0,7.5);$items=$result['items'];unset($result['items']);
                    $i=Invoice::withoutGlobalScopes()->create($result+['business_id'=>$b->id,'client_id'=>$created[$index]->id,'created_by'=>$owner->id,'number'=>app(InvoiceNumberService::class)->next($b->id),'status'=>$status,'tax_percent'=>7.5,'issue_date'=>today()->subDays(10),'due_date'=>$status==='overdue'?today()->subDays(4):today()->addDays(14),'sent_at'=>$status==='draft'?null:now()->subDays(10),'public_token'=>(string)Str::uuid(),'notes'=>'Thank you for your business.','terms'=>'Payment is due by the date shown.']);
                    foreach($items as $item) $i->items()->withoutGlobalScopes()->create($item+['business_id'=>$b->id]);
                    if(in_array($status,['paid','partially_paid'])) Payment::withoutGlobalScopes()->create(['business_id'=>$b->id,'invoice_id'=>$i->id,'amount_kobo'=>$status==='paid'?$i->total_kobo:2000000,'method'=>'bank_transfer','reference'=>'DEMO-'.$i->number,'paid_on'=>today()->subDays(2),'recorded_by'=>$owner->id]);
                    $i->recalculateStatus();
                }
            }
        });
    }
}