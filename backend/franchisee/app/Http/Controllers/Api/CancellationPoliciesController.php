<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CancellationPolicy;
use Illuminate\Support\Facades\DB;

class CancellationPoliciesController extends Controller
{
    public function index()
    {
        $policies = DB::table('cancellation_policies')->get();
        return response()->json($policies);
    }
}
