<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class Qrcode extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'trxId' => $this->trxId,
            'qrcodeContent' => $this->qrcodeContent,
            'qrcode' => $this->qrcode,
            'amount' => $this->amount,
            'reference1' => $this->reference1,
            'reference2' => $this->reference2,
            'remark' => $this->remark,
            'created_at' => $this->created_at->format('d/m/Y H:i:s'),
            'updated_at' => $this->updated_at->format('d/m/Y H:i:s'),
        ];
    }
}
