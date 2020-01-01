<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use App\User;
use Alert;
use DB;

class UserController extends Controller
{
    public function editPassword()
    {
        $v = DB::connection('sqlite')->table('config')->where('name', 'filter bot')->select('value')->first();
        return \view('setting', \compact('v'));
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

    public function toggleSwitch($filter = true)
    {
        try {
            $val = 'false';
            if ($filter == 'true') {
                $val = 'true';
            }
            $a = DB::connection('sqlite')
                ->table('config')
                ->where('name', 'filter bot')
                ->update(['value' => $val]);
            return response()->json(["message" => $val], 200);
        } catch (Exception $e) {
            Log::warning("{can't toggle switch. message($e) ".date('d-M-Y H:i:s')."}");
        }
    }
}
