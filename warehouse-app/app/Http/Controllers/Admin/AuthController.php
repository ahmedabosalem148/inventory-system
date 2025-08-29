<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'pin' => 'required|string|size:6|regex:/^[0-9]{6}$/'
        ]);

        // Use simple PIN: 123456 for admin
        if ($request->pin !== '123456') {
            return back()->with('error', 'PIN غير صحيح');
        }

        session(['admin' => true]);
        
        return redirect('/admin/dashboard');
    }

    public function logout(Request $request)
    {
        $request->session()->forget('admin');
        $request->session()->flush();
        
        return redirect('/');
    }
}
