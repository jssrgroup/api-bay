<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Api\BaseController;
use App\Http\Resources\Transection as TransectionResource;
use App\Models\Callback;
use App\firebaseRDB;
use App\Models\Log;
use App\Models\Qrcode;
use App\Models\Settlement;
use SimpleSoftwareIO\QrCode\Facades\QrCode as GQrcode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Libern\QRCodeReader\QRCodeReader;
use Zxing\QrReader;
// use Carbon\Carbon;

class KmtController extends BaseController
{

    public function callback(Request $request)
    {
        $input = $request->all();

        $callback = [
            'trxId' => $input['trxId'],
            'terminalId' => $input['terminalId'],
            'data' => json_encode($input)
        ];

        $db = new firebaseRDB(env('FIREBASE_DATABASE_URL', false));
        $insert = $db->insert("callback/{$callback['terminalId']}", $callback);

        $cb = Callback::create($callback);

        $res = [
            'trxId' => $input['trxId'],
            'terminalId' => $input['terminalId'],
            // 'datetime' => Carbon::createFromFormat($input['datetime'])->format('Y-m-d H:i:s'), 
            'datetime' => str_replace('Z', '', str_replace('T', ' ', $input['datetime'])),
            'amount' => $input['amount'],
            'feeMerchant' => $input['feeMerchant'],
            'fromAccount' => $input['fromAccount'],
            'trxStatus' => $input['trxStatus'],
            'channel' => $input['channel'],
            'billerId' => $input['billerId'],
        ];

        Settlement::create($res);

        $data = [
            'message' => 'Successful reception',
            'returnCode' => 10000,

        ];
        $stringA = '';
        foreach ($data as $key => $value) {
            $stringA .= "$key=$value&";
        }

        $stringA = substr($stringA, 0, -1);
        $stringB = hash("sha256", utf8_encode($stringA));
        openssl_public_encrypt($stringB, $encrypted_message, env('PUBLIC_KEY', false), OPENSSL_PKCS1_PADDING);
        $data['sign'] = base64_encode($encrypted_message);

        return response()->json($data);
    }

    public function qrCode(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|string',
            'reference1' => 'required|string',
            'reference2' => 'required|string',
            'remark' => 'required|string',
            'terminalId' => 'required|string',
        ]);
        if ($validator->fails()) {
            return $this->sendError('Validation error.', $validator->errors(), 422);
        }

        $data = [
            'amount' => $validator->validated()['amount'],
            'billerId' => env('BILLER_ID', false),
            'bizMchId' => env('BIZMCH_ID', false),
            'channel' => env('CHANNEL', false),
            'reference1' => $validator->validated()['reference1'],
            'reference2' => $validator->validated()['reference2'],
            'remark' => $validator->validated()['remark'],
            'terminalId' => $validator->validated()['terminalId'],
        ];
        $stringA = '';
        foreach ($data as $key => $value) {
            $stringA .= "$key=$value&";
        }

        $stringA = substr($stringA, 0, -1);
        $stringB = hash("sha256", utf8_encode($stringA));
        openssl_public_encrypt($stringB, $encrypted_message, env('PUBLIC_KEY', false), OPENSSL_PKCS1_PADDING);
        $data['sign'] = base64_encode($encrypted_message);

        $curl = curl_init();

        $header = array(
            'API-Key: ' . env('API_KEY', false),
            'X-Client-Transaction-ID: ' . Str::uuid(),
            'Content-Type: application/json',
        );

        curl_setopt_array($curl, array(
            CURLOPT_URL => env('BAY_URL', false) . 'trans/precreate', //'https://payment.jssr.co.th/KrungsriAPI/apix/qrcode.php', //
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30000,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => $header,
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        $responseArr = json_decode($response, true);

        // return response()->json($responseArr, 500);

        if ($err || $response == "File not found.\n") {
            return $this->sendError($response, 'Curl error.', 500);
        } else {
            if (isset($responseArr['returnCode'])) {
                if ($responseArr['returnCode'] == '10000') {
                    $payload = [
                        'header' => $header,
                        'body' => $data,
                    ];
                    $log = [
                        'trxId' => $responseArr['trxId'],
                        'payload' => json_encode($payload),
                    ];
                    Log::create($log);

                    $res = [
                        'trxId' => $responseArr['trxId'],
                        'terminalId' => $data['terminalId'],
                        'qrcodeContent' => $responseArr['qrcodeContent'], //$responseArr['qrcodeContent'], //
                        'qrcode' => base64_encode(GQrcode::size(200)->format('png')->generate($responseArr['qrcodeContent'])),
                        'amount' => $data['amount'],
                        'reference1' => $data['reference1'],
                        'reference2' => $data['reference2'],
                        'remark' => $data['remark'],
                    ];

                    Qrcode::create($res);

                    return $this->sendResponse($res, $responseArr['message']);
                } else {
                    return $this->sendError($responseArr['message'], $responseArr, 500);
                }
            } else {
                return $this->sendError('response error', $responseArr, 500);
            }
        }
    }

    public function transection(Request $request, $id)
    {
        $input['trxId'] = $id;
        $validator = Validator::make($input, [
            'trxId' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $data = [
            'bizMchId' => env('BIZMCH_ID', false),
            'trxId' => $validator->validated()['trxId'],
        ];
        $stringA = '';
        foreach ($data as $key => $value) {
            $stringA .= "$key=$value&";
        }

        $stringA = substr($stringA, 0, -1);
        $stringB = hash("sha256", utf8_encode($stringA));
        openssl_public_encrypt($stringB, $encrypted_message, env('PUBLIC_KEY', false), OPENSSL_PKCS1_PADDING);
        $data['sign'] = base64_encode($encrypted_message);

        $curl = curl_init();

        $header = array(
            'API-Key: ' . env('API_KEY', false),
            'X-Client-Transaction-ID: ' . Str::uuid(),
            'Content-Type: application/json',
        );

        curl_setopt_array($curl, array(
            CURLOPT_URL => env('BAY_URL', false) . 'trans/detail',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30000,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => $header,
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            return response()->json($err, 422);
        } else {
            $data = json_decode($response);
            return $this->sendResponse(new TransectionResource($data->transaction), 'Transection retrived successfully.');
        }
    }

    public function transectionList(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'numberPerPage' => 'required',
            'pageNumber' => 'required',
            // 'timeStart' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $data = [
            'billerId' => env('BILLER_ID', false),
            'bizMchId' => env('BIZMCH_ID', false),
            'numberPerPage' => $validator->validated()['numberPerPage'],
            'pageNumber' => $validator->validated()['pageNumber'],
            // 'timeStart' => $validator->validated()['timeStart'],
        ];
        $stringA = '';
        foreach ($data as $key => $value) {
            $stringA .= "$key=$value&";
        }

        $stringA = substr($stringA, 0, -1);
        $stringB = hash("sha256", utf8_encode($stringA));
        openssl_public_encrypt($stringB, $encrypted_message, env('PUBLIC_KEY', false), OPENSSL_PKCS1_PADDING);
        $data['sign'] = base64_encode($encrypted_message);

        $curl = curl_init();


        $header = array(
            'API-Key: ' . env('API_KEY', false),
            'X-Client-Transaction-ID: ' . Str::uuid(),
            'Content-Type: application/json',
        );

        curl_setopt_array($curl, array(
            CURLOPT_URL => env('BAY_URL', false) . 'trans/list',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30000,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => $header,
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            return response()->json($err, 422);
        } else {
            $data = json_decode($response);
            return $this->sendResponse(TransectionResource::collection($data->data->transactions), 'Transection list retrived successfully.');
        }
    }

    public function settleList(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'numberPerPage' => 'required',
            'pageNumber' => 'required',
            // 'timeStart' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $data = [
            'bizMchId' => env('BIZMCH_ID', false),
            'numberPerPage' => $validator->validated()['numberPerPage'],
            'pageNumber' => $validator->validated()['pageNumber'],
            // 'timeStart' => $validator->validated()['timeStart'],
        ];
        $stringA = '';
        foreach ($data as $key => $value) {
            $stringA .= "$key=$value&";
        }

        $stringA = substr($stringA, 0, -1);
        $stringB = hash("sha256", utf8_encode($stringA));
        openssl_public_encrypt($stringB, $encrypted_message, env('PUBLIC_KEY', false), OPENSSL_PKCS1_PADDING);
        $data['sign'] = base64_encode($encrypted_message);

        $curl = curl_init();


        $header = array(
            'API-Key: ' . env('API_KEY', false),
            'X-Client-Transaction-ID: ' . Str::uuid(),
            'Content-Type: application/json',
        );

        curl_setopt_array($curl, array(
            CURLOPT_URL => env('BAY_URL', false) . 'trans/settle/list',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30000,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => $header,
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            return response()->json($err, 422);
        } else {
            $data = json_decode($response);
            return $this->sendResponse($data, 'Settle list retrived successfully.');
        }
    }

    public function verify(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'trxQr' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $data = [
            'bizMchId' => env('BIZMCH_ID', false),
            'trxQr' => $validator->validated()['trxQr'],
        ];
        $stringA = '';
        foreach ($data as $key => $value) {
            $stringA .= "$key=$value&";
        }

        $stringA = substr($stringA, 0, -1);
        $stringB = hash("sha256", utf8_encode($stringA));
        openssl_public_encrypt($stringB, $encrypted_message, env('PUBLIC_KEY', false), OPENSSL_PKCS1_PADDING);
        $data['sign'] = base64_encode($encrypted_message);

        $curl = curl_init();


        $header = array(
            'API-Key: ' . env('API_KEY', false),
            'X-Client-Transaction-ID: ' . Str::uuid(),
            'Content-Type: application/json',
        );

        curl_setopt_array($curl, array(
            CURLOPT_URL => env('BAY_URL', false) . 'trans/verify',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30000,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => $header,
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            return response()->json($err, 422);
        } else {
            $data = json_decode($response);
            return $this->sendResponse($data, 'Verify check successfully.');
        }
    }

    public function qrCodes(Request $request)
    {
        $qrCodes = Qrcode::all();

        return $this->sendResponse($qrCodes, 'qrcode list retrived successfully.');
    }

    public function settles(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'from' => 'required',
            'to' => 'required',
            // 'timeStart' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }
        $from = date($validator->validated()['from'] . ' 00:00:00');
        $to = date($validator->validated()['to'] . ' 23:59:59');

        $settles = Settlement::whereBetween('datetime', [$from, $to])
            ->rightJoin('qrcodes', 'settlements.trxId', '=', 'qrcodes.trxId')
            ->rightJoin('members', 'settlements.trxId', '=', 'members.TRX_ID')
            ->orderBy('datetime', 'asc')
            ->get();
        // $settles = Settlement::all();

        return $this->sendResponse($settles, 'settles list retrived successfully.');
    }

    public function getSign(Request $request)
    {
        $data = $request->data;

        openssl_public_encrypt($data, $encrypted_message, env('PUBLIC_KEY', false), OPENSSL_PKCS1_PADDING);

        return response()->json(["status" => 200, "data" => [
            "data" => $data,
            "sign" => base64_encode($encrypted_message)
        ]]);
    }

    public function readQrCode(Request $request)
    {
        $requestData = $request->all();
        // return $requestData;
        $validator = Validator::make($requestData, [
            'attachment' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        if ($request->has('attachment')) {
            $QRCodeReader = new QRCodeReader();
            $qrcode_text = $QRCodeReader->decode($request->attachment);
            $response["data"] = $qrcode_text;
            $response["status"] = "successs";
            $response["message"] = "Success! image(s) uploaded";
            return response()->json($response);
            
            $data = [
                'bizMchId' => env('BIZMCH_ID', false),
                'trxQr' => $qrcode_text,
            ];
            $stringA = '';
            foreach ($data as $key => $value) {
                $stringA .= "$key=$value&";
            }

            $stringA = substr($stringA, 0, -1);
            $stringB = hash("sha256", utf8_encode($stringA));
            openssl_public_encrypt($stringB, $encrypted_message, env('PUBLIC_KEY', false), OPENSSL_PKCS1_PADDING);
            $data['sign'] = base64_encode($encrypted_message);

            $curl = curl_init();


            $header = array(
                'API-Key: ' . env('API_KEY', false),
                'X-Client-Transaction-ID: ' . Str::uuid(),
                'Content-Type: application/json',
            );

            curl_setopt_array($curl, array(
                CURLOPT_URL => env('BAY_URL', false) . 'trans/verify',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30000,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_POSTFIELDS => json_encode($data),
                CURLOPT_HTTPHEADER => $header,
            ));

            $response = curl_exec($curl);
            $err = curl_error($curl);

            curl_close($curl);

            if ($err) {
                return response()->json($err, 422);
            } else {
                $data = json_decode($response);
                return $this->sendResponse($data, 'Verify check successfully.');
            }
        } else {
            // $response["status"] = "failed";
            // $response["message"] = "Failed! image(s) not uploaded";
            return $this->sendResponse([], 'Failed! image(s) not uploaded.');
        }
    }

    public function readQrCode2(Request $request)
    {
        $requestData = $request->all();
        // return $requestData;
        $validator = Validator::make($requestData, [
            'attachment' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        if ($request->has('attachment')) {
            // $QRCodeReader = new QRCodeReader();//$request->file('attachment')
            // $qrcode_text = $QRCodeReader->decode($request->attachment);
            $qr = new QrReader(__DIR__.'/../../../public/images/qrcode1.png');
            $qrcode_text = $qr->decode();
            $response["data"] = $qrcode_text;
            $response["qr"] = __DIR__.'/../../../public/images/qrcode1.png';//$request->file('attachment');
            $response["status"] = "successs";
            $response["message"] = "Success! image(s) uploaded";
            return response()->json($response);

            $data = [
                'bizMchId' => env('BIZMCH_ID', false),
                'trxQr' => $qrcode_text,
            ];
            $stringA = '';
            foreach ($data as $key => $value) {
                $stringA .= "$key=$value&";
            }

            $stringA = substr($stringA, 0, -1);
            $stringB = hash("sha256", utf8_encode($stringA));
            openssl_public_encrypt($stringB, $encrypted_message, env('PUBLIC_KEY', false), OPENSSL_PKCS1_PADDING);
            $data['sign'] = base64_encode($encrypted_message);

            $curl = curl_init();


            $header = array(
                'API-Key: ' . env('API_KEY', false),
                'X-Client-Transaction-ID: ' . Str::uuid(),
                'Content-Type: application/json',
            );

            curl_setopt_array($curl, array(
                CURLOPT_URL => env('BAY_URL', false) . 'trans/verify',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30000,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_POSTFIELDS => json_encode($data),
                CURLOPT_HTTPHEADER => $header,
            ));

            $response = curl_exec($curl);
            $err = curl_error($curl);

            curl_close($curl);

            if ($err) {
                return response()->json($err, 422);
            } else {
                $data = json_decode($response);
                return $this->sendResponse($data, 'Verify check successfully.');
            }
        } else {
            // $response["status"] = "failed";
            // $response["message"] = "Failed! image(s) not uploaded";
            return $this->sendResponse([], 'Failed! image(s) not uploaded.');
        }
    }
}
