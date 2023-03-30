<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Api\BaseController;
use App\Http\Resources\Transection as TransectionResource;
use App\Models\Callback;
use App\firebaseRDB;
use App\Models\Log;
use App\Models\Qrcode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

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
            return $this->sendError('Validation error.', $validator->errors(),422);
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
                        'qrcode' => base64_encode(QrCode::size(200)->format('png')->generate($responseArr['qrcodeContent'])),
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
            'timeStart' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $data = [
            'billerId' => env('BILLER_ID', false),
            'bizMchId' => env('BIZMCH_ID', false),
            'numberPerPage' => $validator->validated()['numberPerPage'],
            'pageNumber' => $validator->validated()['pageNumber'],
            'timeStart' => $validator->validated()['timeStart'],
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
            'timeStart' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $data = [
            'bizMchId' => env('BIZMCH_ID', false),
            'numberPerPage' => $validator->validated()['numberPerPage'],
            'pageNumber' => $validator->validated()['pageNumber'],
            'timeStart' => $validator->validated()['timeStart'],
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

    public function getSign(Request $request)
    {
        $data = $request->data;

        openssl_public_encrypt($data, $encrypted_message, env('PUBLIC_KEY', false), OPENSSL_PKCS1_PADDING);

        return response()->json(["status" => 200, "data" => [
            "data" => $data,
            "sign" => base64_encode($encrypted_message)
        ]]);
    }
}
