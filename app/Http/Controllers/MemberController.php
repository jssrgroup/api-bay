<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Api\BaseController;
use App\Http\Resources\Qrcode as QrcodeResource;
use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MemberController extends BaseController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $members = Member::all();

        return response()->json($members, 200);
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
            'INDX' => 'required',
            'FULL_NAME' => 'required',
            'MOBILE' => 'required',
            'ON_PAYMENT_CUSTOMER' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors());
        }

        $member = Member::create($input);

        return $this->sendResponse($member, 'Member created successfully.');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $member = Member::find($id);

        if (is_null($member)) {
            return response()->json('Member not found.',404);
        }
        return response()->json($member, 200);
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

        if ($validator->fails()) {
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
