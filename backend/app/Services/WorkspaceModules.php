<?php
namespace App\Services;
use App\Models\User;
class WorkspaceModules {
 public const SIMPLE=['quotations'];
 public const ADVANCED=['quotations','delivery_notes','products','team','statements','reports'];
 public function catalog(User $user):array {return array_values(array_filter([
  ['id'=>'quotations','name'=>'Quotations','description'=>'Prepare offers, revise them and convert accepted quotes to invoices.','route'=>'/quotations'],
  ['id'=>'delivery_notes','name'=>'Delivery notes','description'=>'Quantity-only delivery documents and recipient records.','route'=>'/delivery-notes'],
  ['id'=>'products','name'=>'Products & services','description'=>'Reusable catalogue with CSV/Excel templates and imports.','route'=>'/products'],
  ['id'=>'statements','name'=>'Client statements','description'=>'Dated client account activity and balances, grouped by currency.','route'=>'/statements'],
  ['id'=>'reports','name'=>'Due & overdue reports','description'=>'Outstanding invoice ageing by client and currency.','route'=>'/reports/ageing'],
  ['id'=>'team','name'=>'Team','description'=>'Manage workspace members within your existing role permissions.','route'=>'/team'],
 ],fn($m)=>$m['id']!=='team'||$user->isManager()));}
 public function preferences(User $user):array {$ids=array_column($this->catalog($user),'id');$mode=$user->workspace_mode??'advanced';$selected=$mode==='simple'?self::SIMPLE:($mode==='advanced'?self::ADVANCED:($user->enabled_modules??[]));return ['mode'=>$mode,'enabled_modules'=>array_values(array_intersect($selected,$ids)),'modules'=>$this->catalog($user),'presets'=>['simple'=>array_values(array_intersect(self::SIMPLE,$ids)),'advanced'=>array_values(array_intersect(self::ADVANCED,$ids))]];}
}
