<?php
namespace App\Policies;
use App\Models\{User,Client};
class ClientPolicy {
    public function delete(User $user,Client $client): bool { return $user->business_id===$client->business_id && $user->isManager(); }
}