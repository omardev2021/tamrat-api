<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Otp;
use Illuminate\Support\Facades\Mail;
use Mailjet\Resources;


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


    public function sendSMS($phone,$msg) {

        $url = "http://sms.connectsaudi.com/sendurl.aspx";
        $user = "Tamrattr";
        $pwd = "jmbm44p6";
        $senderid = "Advance Dig"; // Include whitespace in the senderid value
        $countryCode = "966";
        $mobileno = $phone;
        $msgtext = $msg;


        $senderid = urlencode($senderid);
        $msgtext = urlencode($msgtext);
        $fullUrl = "$url?user=$user&pwd=$pwd&senderid=$senderid&CountryCode=$countryCode&mobileno=$mobileno&msgtext=$msgtext";

        $ch = curl_init($fullUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $curl_scraped_page = curl_exec($ch);
        curl_close($ch);

    }



    public function send_sms(Request $request)
    {
      

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

    }


    public function send_email(Request $request)
    {
     
        $this->validate($request, [
            'email' => 'required|email|exists:users,email', // 1MB Max
        ]);
        // Insert OTP record into the database
        $otp = Otp::create([
            'email' => $request->email,
            'otp' => rand(1000, 9999), // Generate a random OTP
        ]);

        $data = [
            'otp' => $otp->otp,

        ];
        $mj = new \Mailjet\Client('0f2691fc2f219fbd06b37f24c25ba639', '18133e418df9bfe5df35ab7b3f5d5416');
        $body = [
            'FromEmail' => "info@tamratdates.com",
            'FromName' => "تمرات",
            'Subject' => "رمز التحقق",
            'Text-part' => "رمز التحقق الخاص بك في تمرات",
            'Html-part' => "<h3>رمز التحقق الخاص بك في تمرات هو ".$otp->otp."</h3>",
            'Recipients' => [
                [
                    'Email' => $request->email
                ]
            ]
        ];
        $response = $mj->post(Resources::$Email, ['body' => $body]);

    
        return response()->json(['message' => 'otp sent'], 200);
    
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


}
