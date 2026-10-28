<?php

namespace App\Models;

use Illuminate\Support\Str;

class Payment
{
    public function create(Purchase $purchase): array
    {
        $id = 'pay_'.Str::uuid();

        return ['id' => $id, 'url' => config('app.url').'/api/payments/'.$id];
    }
}
