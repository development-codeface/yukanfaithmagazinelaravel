<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MagazinePurchase;
use App\Models\MagazineIssue;
use Illuminate\Http\Request;

class MagazinePurchaseController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'search' => 'nullable|string|max:255',
            'search_by' => 'nullable|in:name,all',
            'magazine_id' => 'nullable|integer|exists:magazine_issues,id',
            'delivery_type' => 'nullable|in:digital,print,both',
            'review' => 'nullable|in:pending,reviewed',
            'date_from' => 'nullable|date_format:Y-m-d',
            'date_to' => 'nullable|date_format:Y-m-d'.($request->filled('date_from') ? '|after_or_equal:date_from' : ''),
            'per_page' => 'nullable|integer|in:15,30,50,100',
            'page' => 'nullable|integer|min:1',
        ]);
        $query = MagazinePurchase::with(['user', 'magazine']);
        if (($filters['search_by'] ?? '') === 'name' && ($search = trim($filters['search'] ?? ''))) {
            $query->whereHas('user', fn ($user) => $user->where('name', 'like', '%'.$search.'%'));
        } elseif ($search = trim($filters['search'] ?? '')) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('user', fn ($u) => $u->where(fn ($user) => $user->where('name', 'like', '%'.$search.'%')->orWhere('email', 'like', '%'.$search.'%')))
                    ->orWhere('transaction_id', 'like', '%'.$search.'%');
                if (ctype_digit($search)) $q->orWhere('id', $search);
            });
        }
        if (!empty($filters['magazine_id'])) $query->where('magazine_issue_id', $filters['magazine_id']);
        if (!empty($filters['delivery_type'])) $query->where('delivery_type', $filters['delivery_type']);
        if (!empty($filters['review'])) {
            $query->whereIn('delivery_type', ['print', 'both']);
            $filters['review'] === 'reviewed' ? $query->whereNotNull('print_acknowledged_at') : $query->whereNull('print_acknowledged_at');
        }
        if (!empty($filters['date_from'])) $query->where('created_at', '>=', $filters['date_from'].' 00:00:00');
        if (!empty($filters['date_to'])) $query->where('created_at', '<', \Carbon\Carbon::parse($filters['date_to'])->addDay()->startOfDay());
        $purchases = $query->latest('id')->paginate($filters['per_page'] ?? 30)->withQueryString();
        $magazines = MagazineIssue::orderByDesc('issue_date')->orderByDesc('id')->get(['id', 'title']);
        return view('admin.magazine_purchases.index', compact('purchases', 'magazines', 'filters'));
    }

    public function acknowledge(MagazinePurchase $purchase)
    {
        abort_unless(in_array($purchase->delivery_type, ['print', 'both'], true), 404);
        if (!$purchase->print_acknowledged_at) $purchase->update(['print_acknowledged_at' => now()]);
        return back()->with('success', 'Magazine delivery notification acknowledged.');
    }
}
