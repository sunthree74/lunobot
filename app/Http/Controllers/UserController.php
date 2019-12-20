<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use App\User;
use Alert;

class UserController extends Controller
{
    public function editPassword()
    {
        return \view('setting');
    }
    public function changePassword(Request $request)
    {
        $u = User::findOrFail(Auth::user()->id);

        if ($request->new_password == $request->confirm_password) {
            if (Hash::check($request->old_password, $u->password)) {
                $u->password = Hash::make($request->new_password);
                $u->save();
                Alert::success('Password has changed', 'Yess..');

                return redirect()->back();
            }else {
                Alert::error('Wrong Old Password', 'Ooohhh...');

                return redirect()->back();
            }
        } else {
            Alert::error('Wrong Password Confirmation', 'Ooohhh...');

            return redirect()->back();
        }
    }
}
