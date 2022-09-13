<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
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
            'amount' => $this->amount,
            'currency' => $this->currency,
            'payerMessage' => $this->payerMessage,
            'payeeNote' => $this->payeeNote,
            'sender' => User::find($this->sender),
            'sender' => User::find($this->receiver),
            'date' => $this->created_at,
        ];
    }
}
