<?php
namespace App\Services;
use Illuminate\Validation\ValidationException;
class PhoneService {
    public static function normalize(?string $phone): ?string {
        if(!$phone) return null;
        $phone=preg_replace('/[\s()\-]/','',$phone);
        if(preg_match('/^0[789]\d{9}$/',$phone)) $phone='+234'.substr($phone,1);
        if(str_starts_with($phone,'234')) $phone='+'.$phone;
        if(!preg_match('/^\+[1-9]\d{7,14}$/',$phone)) throw ValidationException::withMessages(['phone'=>'Use a valid Nigerian phone or international number, such as +2348012345678.']);
        return $phone;
    }
}