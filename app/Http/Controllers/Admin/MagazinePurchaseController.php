<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MagazinePurchase;

class MagazinePurchaseController extends Controller
{
    public function index()
    {
        $purchases = MagazinePurchase::with(['user', 'magazine'])->latest()->paginate(30);
        return view('admin.magazine_purchases.index', compact('purchases'));
    }

    public function acknowledge(MagazinePurchase $purchase)
    {
        abort_unless(in_array($purchase->delivery_type, ['print', 'both'], true), 404);
        if (!$purchase->print_acknowledged_at) $purchase->update(['print_acknowledged_at' => now()]);
        return back()->with('success', 'Magazine delivery notification acknowledged.');
    }
}
