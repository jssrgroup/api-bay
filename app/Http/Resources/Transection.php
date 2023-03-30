<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class Transection extends JsonResource
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
            'billerId' => $this->billerId,
            'amount' => $this->amount,
            'datetime' => date("d/m/Y H:i:s", substr($this->datetime + 25200000, 0, 10)),
            'fromAccount' => $this->fromAccount,
            'feeMerchant' => $this->feeMerchant,
            'remark' => $this->remark,
            'trxStatus' => $this->trxStatus,
            'terminalId' => $this->terminalId,
            'trxId' => $this->trxId
        ];
    }
}
