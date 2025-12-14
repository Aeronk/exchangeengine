<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
     public function show(Request $request)
    {
        $user = $request->user();
        
        return response()->json([
            'balance' => $user->balance,
            'assets' => $user->assets()->get(),
        ]);
    }
}
