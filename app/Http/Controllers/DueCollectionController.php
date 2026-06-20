<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\Customer;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class DueCollectionController extends Controller
{
    public function index(Request $request)
    {
        $shopId = auth()->user()->shop_id ?? 1;
        
        $query = Sale::with(['customer', 'user', 'payments.user'])
            ->where('shop_id', $shopId)
            ->where('due_amount', '>', 0);
        
        // Apply customer filter
        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }
        
        // Apply date filters
        if ($request->filled('start_date')) {
            $query->whereDate('sale_date', '>=', $request->start_date);
        }
        
        if ($request->filled('end_date')) {
            $query->whereDate('sale_date', '<=', $request->end_date);
        }
        
        $dueSales = $query->orderBy('created_at', 'desc')->get();
        
        // Get all customers for filter
        $customers = Customer::where('shop_id', $shopId)
            ->orderBy('name')
            ->get();
        
        // Calculate total due
        $totalDue = $dueSales->sum('due_amount');
        
        return Inertia::render('DueCollection/Index', [
            'customers' => $customers,
            'due_sales' => $dueSales,
            'total_due' => $totalDue,
            'customer_id' => $request->customer_id,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
        ]);
    }
    
    public function collectPayment(Request $request)
    {
        // Log the incoming request for debugging
        Log::info('Payment collection request received', $request->all());
        
        $request->validate([
            'sale_id' => 'required|exists:sales,id',
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|in:cash,card,mobile_banking,bank_transfer',
            'payment_date' => 'required|date',
            'transaction_id' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);
        
        try {
            DB::beginTransaction();
            
            $sale = Sale::findOrFail($request->sale_id);
            $user = auth()->user();
            $shopId = $user->shop_id ?? 1;
            
            // Log sale information
            Log::info('Sale found', [
                'sale_id' => $sale->id,
                'current_due' => $sale->due_amount,
                'current_paid' => $sale->paid_amount
            ]);
            
            // Check if amount exceeds due
            if ($request->amount > $sale->due_amount) {
                return redirect()->back()->with('error', 'Amount cannot exceed due amount.');
            }
            
            // Create payment record with polymorphic relationship
            $payment = Payment::create([
                'shop_id' => $shopId,
                'sale_id' => $sale->id,
                'payable_id' => $sale->id,
                'payable_type' => 'App\Models\Sale', // Full namespace with quotes
                'user_id' => $user->id,
                'amount' => $request->amount,
                'payment_method' => $request->payment_method,
                'transaction_id' => $request->transaction_id,
                'payment_date' => $request->payment_date,
                'notes' => $request->notes,
            ]);
            
            Log::info('Payment created', ['payment_id' => $payment->id, 'amount' => $payment->amount]);
            
            // Update sale payment
            $newPaidAmount = $sale->paid_amount + $request->amount;
            $newDueAmount = $sale->due_amount - $request->amount;
            
            $paymentStatus = 'partial';
            if ($newDueAmount <= 0) {
                $paymentStatus = 'paid';
                $newDueAmount = 0;
            }
            
            $sale->update([
                'paid_amount' => $newPaidAmount,
                'due_amount' => $newDueAmount,
                'payment_status' => $paymentStatus,
            ]);
            
            Log::info('Sale updated', [
                'sale_id' => $sale->id,
                'new_paid' => $newPaidAmount,
                'new_due' => $newDueAmount,
                'status' => $paymentStatus
            ]);
            
            DB::commit();
            
            // Get updated due sales
            $dueSales = Sale::with(['customer', 'user', 'payments.user'])
                ->where('shop_id', $shopId)
                ->where('due_amount', '>', 0)
                ->orderBy('created_at', 'desc')
                ->get();
            
            $totalDue = $dueSales->sum('due_amount');
            
            return redirect()->back()->with([
                'success' => 'Payment of ' . number_format($request->amount, 2) . ' collected successfully!',
                'due_sales' => $dueSales,
                'total_due' => $totalDue,
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Payment collection failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->back()->with('error', 'Failed to collect payment: ' . $e->getMessage());
        }
    }
}