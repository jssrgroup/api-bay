<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

class TransectionList extends ResourceCollection
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        // return [
        //     'pageNumber' => $this->pageNumber,
        //     'count' => $this->count,
        //     'numberPerPage' => $this->numberPerPage,
        //     'transactions' => [
        // 'billerId' => $this->transactions->billerId,
        // 'amount' => $this->transactions->amount,
        // 'datetime' => date("d/m/Y H:i:s", substr($this->transactions->datetime + 25200000, 0, 10)),
        // 'fromAccount' => $this->transactions->fromAccount,
        // 'feeMerchant' => $this->transactions->feeMerchant,
        // 'remark' => $this->transactions->remark,
        // 'trxStatus' => $this->transactions->trxStatus,
        // 'terminalId' => $this->transactions->terminalId,
        // 'trxId' => $this->transactions->trxId
        //     ],
        // ];
        return $this->collection;
    }

    /**
     * Get additional data that should be returned with the resource array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function with($request)
    {
        return [
            'meta' => [
                'key' => 'value',
            ],
        ];
    }
}
