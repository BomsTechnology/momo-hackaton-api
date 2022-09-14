<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TransactionResource;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Illuminate\Support\Str;

class TransactionController extends Controller
{
    private $referenceId;
    private $apiKey;
    private $accessToken;
    private $baseUrl = 'https://sandbox.momodeveloper.mtn.com';

    public function index()
    {
        return TransactionResource::collection(Transaction::latest()->get());
    }

    public function byUser($user)
    {
        return TransactionResource::collection(Transaction::where('sender', $user)->orWhere('receiver', $user)->orderBy('id', 'desc')->get());
    }

    public function initialiaze(string $type)
    {
        $this->referenceId = Str::uuid()->toString();
        try {
            // Initialize transaction
            $response = Http::withHeaders([
                'Ocp-Apim-Subscription-Key' => $type === 'DISBURSEMENT' ? env('MOMO_PRIMARY_KEY_DISBURSEMENT') : env('MOMO_PRIMARY_KEY_COLLECTION'),
                'X-Reference-Id' => $this->referenceId,
                'Content-Type' => 'application/json'
            ])->post("$this->baseUrl/v1_0/apiuser", [
                "providerCallbackHost" => "to.com"
            ]);

            // Create ApiKey
            $response = Http::withHeaders([
                'Ocp-Apim-Subscription-Key' => $type === 'DISBURSEMENT' ? env('MOMO_PRIMARY_KEY_DISBURSEMENT') : env('MOMO_PRIMARY_KEY_COLLECTION'),
            ])->post("$this->baseUrl/v1_0/apiuser/{$this->referenceId}/apikey");
            $bodyResponse = json_decode($response->body());
            $this->apiKey = $bodyResponse->apiKey;

            // Create Acess Token
            $response = Http::withHeaders([
                'Ocp-Apim-Subscription-Key' => $type === 'DISBURSEMENT' ? env('MOMO_PRIMARY_KEY_DISBURSEMENT') : env('MOMO_PRIMARY_KEY_COLLECTION'),
                'Authorization' => "Basic " . base64_encode("$this->referenceId:$this->apiKey"),
            ])->post($type === 'DISBURSEMENT' ? "$this->baseUrl/disbursement/token/" : "$this->baseUrl/collection/token/");
            $bodyResponse = json_decode($response->body());
            $this->accessToken = $bodyResponse->access_token;
        } catch (HttpException $ex) {
            return $ex;
        }
    }

    public function collect(Request $request)
    {
        $fields = $request->validate([
            'amount' => 'required|integer',
            'currency' => 'required|string',
            'payerMessage' => 'required|string',
            'sender' => 'required|integer',
            'receiver' => 'required|integer',
        ]);

        // initialiaze
        $this->initialiaze('COLLECTION');

        // Collect Money
        $uuid = Str::uuid()->toString();
        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer $this->accessToken",
                'Ocp-Apim-Subscription-Key' => env('MOMO_PRIMARY_KEY_COLLECTION'),
                'X-Reference-Id' => $uuid,
                'X-Target-Environment' => 'sandbox',
                'Content-Type' => 'application/json'
            ])->post("$this->baseUrl/collection/v1_0/requesttopay", [
                "amount" => $fields['amount'],
                "currency" => $fields['currency'],
                "externalId" => $uuid,
                "payer" => [
                    "partyIdType" => "MSISDN",
                    "partyId" => $fields['sender'],
                ],
                "payerMessage" => $fields['payerMessage'],
                "payeeNote" => "From :{$fields['sender']} - To {$fields['receiver']}",
            ]);
            if ($response->status() == 202) {
                $response = [
                    'status' => true,
                    'message' => 'Collection doing successfully',
                ];
                return response($response, 201);
            } else {
                return response(['status' => false, 'message' => 'An error occurred during the transaction'], 401);
            }
        } catch (HttpException $ex) {
            return $ex;
        }
    }

    public function deposit(Request $request)
    {
        $fields = $request->validate([
            'amount' => 'required|integer',
            'currency' => 'required|string',
            'payerMessage' => 'required|string',
            'sender' => 'required|integer',
            'receiver' => 'required|integer',
        ]);
        // initialiaze
        $this->initialiaze('DISBURSEMENT');
        // Collect Money
        $uuid = Str::uuid()->toString();
        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer $this->accessToken",
                'Ocp-Apim-Subscription-Key' => env('MOMO_PRIMARY_KEY_DISBURSEMENT'),
                'X-Reference-Id' => $uuid,
                'X-Target-Environment' => 'sandbox',
                'Content-Type' => 'application/json'
            ])->post("$this->baseUrl/disbursement/v2_0/deposit", [
                "amount" => $fields['amount'],
                "currency" => $fields['currency'],
                "externalId" => $uuid,
                "payee" => [
                    "partyIdType" => "MSISDN",
                    "partyId" => $fields['receiver'],
                ],
                "payerMessage" => $fields['payerMessage'],
                "payeeNote" => "From :{$fields['sender']} - To {$fields['receiver']}",
            ]);
            if ($response->status() == 202) {
                $sender = User::where('phone', $fields['sender'])->first();
                $receiver = User::where('phone', $fields['receiver'])->first();
                Transaction::create([
                    'amount' => $fields['amount'],
                    'currency' => $fields['currency'],
                    'externalId' => $uuid,
                    'payerMessage' => $fields['payerMessage'],
                    'payeeNote' => "From :{$fields['sender']} - To {$fields['receiver']}",
                    "sender" => $sender->id,
                    "receiver" => $receiver->id,
                ]);
                $response = [
                    'status' => true,
                    'message' => 'Deposit doing successfully',
                ];
                return response($response, 201);
            } else {
                return response(['status' => false, 'message' => 'An error occurred during the transaction'], 401);
            }
        } catch (HttpException $ex) {
            return $ex;
        }
    }
}
