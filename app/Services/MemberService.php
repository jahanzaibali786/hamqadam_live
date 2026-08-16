<?php

namespace App\Services;

use App\Models\Member;
use App\Models\Package;

class MemberService
{
     public function store(array $data)
     {
           $collection = collect($data);

           return Member::create($data);
     }
}
