<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\Order;

use MyFatoorah\Library\PaymentMyfatoorahApiV2;

class MyFatoorahController extends Controller {

    public $mfObj;

//-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     * create MyFatoorah object
     */
    public function __construct() {
        $this->mfObj = new PaymentMyfatoorahApiV2(config('myfatoorah.api_key'), config('myfatoorah.country_iso'), config('myfatoorah.test_mode'));
    }

//-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Create MyFatoorah invoice
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request) {
        try {
            $paymentMethodId = 0; // 0 for MyFatoorah invoice or 1 for Knet in test mode
            $order = Order::where('id',$request->orderId)->first();
        //    return $order;
            $curlData = $this->getPayLoadData($order);
            $data     = $this->mfObj->getInvoiceURL($curlData, $paymentMethodId);

            $response = ['IsSuccess' => 'true', 'Message' => 'Invoice created successfully.', 'Data' => $data];
        } catch (\Exception $e) {
            $response = ['IsSuccess' => 'false', 'Message' => $e->getMessage()];
        }
        return response()->json($response);
    }

//-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     * 
     * @param int|string $orderId
     * @return array
     */
    private function getPayLoadData($order) {
        $callbackURL = route('myfatoorah.callback');

        return [
            'CustomerName'       => $order->name,
            'InvoiceValue'       => $order->totalPrice,
            'DisplayCurrencyIso' => 'SAR',
            'CustomerEmail'      => $order->email,
            'CallBackUrl'        => 'http://localhost:3000/success',
            'ErrorUrl'           => 'http://localhost:3000/fail',
            'MobileCountryCode'  => '+966',
            'CustomerMobile'     => $order->phone,
            'Language'           => 'en',
            'CustomerReference'  => $order->id,
            'SourceInfo'         => 'Laravel ' . app()::VERSION . ' - MyFatoorah Package ' . MYFATOORAH_LARAVEL_PACKAGE_VERSION
        ];
    }

//-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Get MyFatoorah payment information
     * 
     * @return \Illuminate\Http\Response
     */
    public function callback() {
        try {
            $paymentId = request('paymentId');
            $data      = $this->mfObj->getPaymentStatus($paymentId, 'PaymentId');

            if ($data->InvoiceStatus == 'Paid') {
                $msg = 'Invoice is paid.';
            } else if ($data->InvoiceStatus == 'Failed') {
                $msg = 'Invoice is not paid due to ' . $data->InvoiceError;
            } else if ($data->InvoiceStatus == 'Expired') {
                $msg = 'Invoice is expired.';
            }

            $response = ['IsSuccess' => 'true', 'Message' => $msg, 'Data' => $data];
        } catch (\Exception $e) {
            $response = ['IsSuccess' => 'false', 'Message' => $e->getMessage()];
        }
        return response()->json($response);
    }

//-----------------------------------------------------------------------------------------------------------------------------------------
}
