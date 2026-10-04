<?php
namespace App\Policies;
use App\Models\User;
class TeamPolicy {
    public function manage(User $actor,User $target): bool { return $actor->business_id===$target->business_id && $actor->isManager() && $target->role!=='owner' && $actor->id!==$target->id; }
}