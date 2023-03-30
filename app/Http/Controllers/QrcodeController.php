<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Api\BaseController;
use App\Http\Resources\Qrcode as QrcodeResource;
use App\Models\Qrcode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class QrcodeController extends BaseController
{
        /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $qrcodes = Qrcode::all();
    
        return $this->sendResponse(QrcodeResource::collection($qrcodes), 'QrCode retrieved successfully.');
    }
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $input = $request->all();
   
        $validator = Validator::make($input, [
            'trxId' => 'required',
            'qrcodeContent' => 'required',
            'qrcode' => 'required',
        ]);
   
        if($validator->fails()){
            return $this->sendError('Validation Error.', $validator->errors());       
        }
   
        $qrcode = Qrcode::create($input);
   
        return $this->sendResponse(new QrcodeResource($qrcode), 'Qrcode created successfully.');
    } 
   
    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $qrcode = Qrcode::find($id);
  
        if (is_null($qrcode)) {
            return $this->sendError('Qrcode not found.');
        }
   
        return $this->sendResponse(new QrcodeResource($qrcode), 'Qrcode retrieved successfully.');
    }
    
    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Qrcode $qrcode)
    {
        $input = $request->all();
   
        $validator = Validator::make($input, [
            'trxId' => 'required',
            'qrcodeContent' => 'required',
            'qrcode' => 'required',
        ]);
   
        if($validator->fails()){
            return $this->sendError('Validation Error.', $validator->errors());       
        }
   
        $qrcode->trxId = $input['trxId'];
        $qrcode->qrcodeContent = $input['qrcodeContent'];
        $qrcode->qrcode = $input['qrcode'];
        $qrcode->save();
   
        return $this->sendResponse(new QrcodeResource($qrcode), 'Qrcode updated successfully.');
    }
   
    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Qrcode $qrcode)
    {
        $qrcode->delete();
   
        return $this->sendResponse([], 'Qrcode deleted successfully.');
    }
}
