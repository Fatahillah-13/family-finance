<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionCardResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'occurred_at' => $this->occurred_at?->format('Y-m-d H:i'),
            'amount' => (int) $this->amount,
            'description' => $this->description,

            'category' => $this->whenLoaded('category', fn() => $this->category && [
                'id' => $this->category->id,
                'name' => $this->category->name,
            ]),

            'account' => $this->whenLoaded('account', fn() => $this->account && [
                'id' => $this->account->id,
                'name' => $this->account->name,
            ]),

            'from_account' => $this->whenLoaded('fromAccount', fn() => $this->fromAccount && [
                'id' => $this->fromAccount->id,
                'name' => $this->fromAccount->name,
            ]),

            'to_account' => $this->whenLoaded('toAccount', fn() => $this->toAccount && [
                'id' => $this->toAccount->id,
                'name' => $this->toAccount->name,
            ]),
        ];
    }
}
