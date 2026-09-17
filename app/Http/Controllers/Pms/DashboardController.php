<?php

namespace App\Http\Controllers\Pms;

use App\Http\Controllers\Controller;
use App\Models\Property;

class DashboardController extends Controller
{
    public function index()
    {
        $property = Property::first();

        return view('pms.dashboard', [
            'property' => $property,
        ]);
    }
}