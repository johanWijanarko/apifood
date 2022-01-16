<?php

namespace App\Http\Controllers\API;

use Midtrans\Snap;
use Midtrans\Config;
use App\Models\Transactions;
use Illuminate\Http\Request;
use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class TransactionController extends Controller
{
    public function all(Request $request)
    {
        $id = $request->input('id');
        $limit = $request->input('limit', 6);
        $food_id = $request->input('food_id');
        $status = $request->input('status');


        if($id)
        {
            $transaction = Transactions::widh(['user', 'food'])->find($id);

                if($transaction)
                {
                    return ResponseFormatter::success(
                        $transaction,
                        'Data transaksi berhasil diambil'
                    );
                }else{
                    return ResponseFormatter::error(
                        null,
                        'Data transaksi berhasil diambil',
                        404
                    );
                }

                $transaction = Transactions::widh(['user', 'food'])->where('user_id', Auth::user()->id);

                if($food_id)
                {
                    $transaction->where('food_id', $food_id);
                }

                if($status)
                {
                    $transaction->where('status', $status);
                }

                return ResponseFormatter::success(
                    $transaction->paginate($limit),
                    'Data list berhasil di ambil',
                );
        }
    }

    public function update(Request $request, $id)
    {
        $transaction = Transactions::findOrFail($id);
        $transaction->update($request->all());
        return ResponseFormatter::success($transaction,'Data transaksi berhasil diupdate' );
    }

    public function checkout(Request $request)
    {
        // validasi input 
        $request->validate([
            'food_id' => 'required|exist:food_id',
            'user_id' => 'required|exist:user_id',
            'quantity' => 'required',
            'total' => 'required',
            'status' => 'required',

        ]);

        $transaction = Transactions::create([
            'food_id' => $request->food_id,
            'user_id' => $request->user_id,
            'quantity' => $request->quantity,
            'total' => $request->total,
            'status,' => $request->status,
            'payment_url,' => '',
        ]);

        // konfigurasi midtrans 
        Config::$serverKey = config('service.midtrans.serverKey');
        Config::$isProduction = config('service.midtrans.isProduction');
        Config::$isSanitized = config('service.midtrans.isSanitized');
        Config::$is3ds = config('service.midtrans.is3ds');

        // call transaction
        $transaction = Transactions::with(['food', 'user'])->find($transaction->id);

        // create transaction in midtrans by documentation 
        $midtrans = [
            'transaction_details' => [
                'order_id'=> $transaction->id,
                'gross_amount'=> (int) $transaction->total,
            ], 
            'customer_details' => [
                'first_name'=> $transaction->user->name,
                'email'=> (int) $transaction->user->email,
            ],
            'enabled_payments' =>[ 'gopay', 'bank_transver'],
            'vtweb' => []
        ];

        // call midtrans

        try{
            $paymentUrl = Snap::createTransaction($midtrans)->redirect_url;

            $transaction->payment_url = $paymentUrl;
            $transaction->save();

            return ResponseFormatter::success($transaction, 'Transaksi berhasil');
        }
        catch(Exception $e){
            return ResponseFormatter::error($e->getMessage(), 'Transaksi gagal');
        }
    }
}
