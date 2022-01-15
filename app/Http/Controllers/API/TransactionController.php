<?php

namespace App\Http\Controllers\API;

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

}
