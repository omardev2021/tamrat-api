<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Coupon;

class CouponController extends Controller
{
    public function check(Request $request) {
        $cou = $request->coupon;
        $coupon = Coupon::where('coupon_name',$cou)->first();

        if ($coupon) {
            return response()->json($coupon,201);
        } else {
            return response()->json('coupon is incorrect',404);

        }
    }
}
