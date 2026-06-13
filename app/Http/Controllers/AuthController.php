<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Otp;
use Illuminate\Support\Facades\Mail;
use Mailjet\Resources;
use Mailjet\Client;

class AuthController extends Controller
{

    public function register(Request $request)
{
    // $request->validate([
    //     'name' => 'required|string',
    //     'email' => 'required|email|unique:users',
    //     'password' => 'required|string|min:6',
    // ]);

    $user = User::create([
        'name' => $request->name,
        'email' => $request->email,
        'password' => bcrypt('1234'),
        'phone' => $request->phone
    ]);

           // Insert OTP record into the database
           $otp = Otp::create([
            'phone' => $user->phone,
            'otp' => rand(1000, 9999), // Generate a random OTP
        ]);

        $msgtext = "رمز التحقق الخاص بك في تمرات هو ".$otp->otp;

        $this->sendSMS($user->phone,$msgtext);


        return response()->json(['message' => 'otp sent'], 200);
}


    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');
    
        if (Auth::attempt($credentials)) {
            $user = Auth::user();
            $token = $user->createToken('MyApp')->accessToken;
    
            return response()->json([
                'user'=>$user,
                'token' => $user->createToken('API TOKEN FOR ' . $user->name)->plainTextToken
            
            ], 200);
        } else {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }
    }


    public function sendSMS($phone, $msg) {
        $sid   = env('TWILIO_SID');
        $token = env('TWILIO_TOKEN');
        $from  = env('TWILIO_WHATSAPP_FROM', 'whatsapp:+14155238886');

        $phone = preg_replace('/^0+/', '', $phone);
        if (!str_starts_with($phone, '966')) {
            $phone = '966' . $phone;
        }
        $to = 'whatsapp:+' . $phone;

        $url  = 'https://api.twilio.com/2010-04-01/Accounts/' . $sid . '/Messages.json';
        $data = http_build_query(['From' => $from, 'To' => $to, 'Body' => $msg]);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_USERPWD, $sid . ':' . $token);
        curl_exec($ch);
        curl_close($ch);
    }



    public function send_sms(Request $request)
    {

        try{
            $this->validate($request, [
                'phoneNumber' => 'required|min:3|exists:users,phone',
            ]);
    
    
            // Insert OTP record into the database
            $otp = Otp::create([
                'phone' => $request->phoneNumber,
                'otp' => rand(1000, 9999), // Generate a random OTP
            ]);
    
            $msgtext = "رمز التحقق الخاص بك في تمرات هو ".$otp->otp;
    
            $this->sendSMS($request->phoneNumber,$msgtext);
    
    
            return response()->json(['message' => 'otp sent'], 200);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to send OTP'], 500);


        }
      

      

    }


    public function send_email(Request $request)
    {
        try {
            $this->validate($request, [
                'email' => 'required|email|exists:users,email',
            ]);
    
            $otp = Otp::create([
                'email' => $request->email,
                'otp' => rand(1000, 9999),
            ]);
    
            $this->send_email_helper($request->email, $otp->otp);
    
            return response()->json(['message' => 'OTP sent'], 200);
        } catch (\Exception $e) {
            // Log the exception if needed
            \Log::error($e->getMessage());
    
            // Return a response with an error message
            return response()->json(['error' => 'Failed to send OTP'], 500);
        }
    }
    

    private function send_email_helper($email, $otp) {
        // Look up user phone by email and send OTP via WhatsApp
        $user = \App\Models\User::where('email', $email)->first();
        if ($user && $user->phone) {
            $msg = 'رمز التحقق الخاص بك في تمرات هو ' . $otp;
            $this->sendSMS($user->phone, $msg);
        } else {
            throw new \Exception('User phone not found for WhatsApp delivery');
        }
    }

    public function login_user(Request $request) {
        $otp = $request->otp;
        $email = $request->email;
        $phone = $request->phoneNumber;
        if($request->email) {
        $last = Otp::where('email', $email)->latest()->first();

        if ($request->otp == $last->otp) {
            $user = User::where('email',$email)->first();
            return response()->json([
                'user'=>$user,
                'token' => $user->createToken('API TOKEN FOR ' . $user->name)->plainTextToken
            
            ], 200);
        }
            
        return response()->json([
            'message'=>'otp not found',
        
        ], 404);
    }

    if($request->phoneNumber) {
        $last = Otp::where('phone', $phone)->latest()->first();

        if ($request->otp == $last->otp) {
            $user = User::where('phone',$phone)->first();
            return response()->json([
                'user'=>$user,
                'token' => $user->createToken('API TOKEN FOR ' . $user->name)->plainTextToken
            
            ], 200);
        }
            
        return response()->json([
            'message'=>'otp not found2',
        
        ], 404);
    }

    }



    public function update_user_data(Request $request) {
        try {
            if ($request->mode === 'email') {
                $user = User::where('email', $request->email)->first();
                if (!$user) return response()->json(['message' => 'User not found'], 404);
                $user->name  = $request->name;
                $user->phone = preg_replace('/^0+/', '', $request->phone ?? '');
                $user->profile = 'complete';
                $user->save();
            } else {
                $user = User::where('phone', preg_replace('/^0+/', '', $request->phone ?? ''))->first();
                if (!$user) return response()->json(['message' => 'User not found'], 404);
                $user->name  = $request->name;
                $user->email = $request->email;
                $user->profile = 'complete';
                $user->save();
            }
            return response()->json([
                'user'  => $user,
                'token' => $user->createToken('API TOKEN FOR ' . $user->name)->plainTextToken,
            ], 200);
        } catch (\Exception $e) {
            \Log::error($e->getMessage());
            return response()->json(['error' => 'Update failed'], 500);
        }
    }

}
