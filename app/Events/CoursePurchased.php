<?php

namespace App\Events;

use App\Models\Purchase;

class CoursePurchased
{
    public function __construct(public Purchase $purchase) {}
}
