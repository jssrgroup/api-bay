<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Api\BaseController;
use App\Http\Resources\Qrcode as QrcodeResource;
use App\Models\MemberLive;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MemberLiveController extends BaseController
{
        /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $memberLives = MemberLive::all();
    
        return response()->json($memberLives, 200);
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
        $memberLive = MemberLive::find($id);
  
        if (is_null($memberLive)) {
            return response()->json('Qrcode not found.',404);
        }

        return response()->json($memberLive, 200);
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
