<?php

namespace App\Services;

use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    public function createItem(array $data): InventoryItem
    {
        return InventoryItem::create($data);
    }

    public function updateItem(InventoryItem $item, array $data): InventoryItem
    {
        $item->update($data);

        return $item;
    }

    public function deleteItem(InventoryItem $item): bool
    {
        return $item->delete();
    }

    public function getItems(array $filters = []): LengthAwarePaginator
    {
        $query = InventoryItem::query()->orderBy('name');

        if (! empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        if (! empty($filters['low_stock'])) {
            $query->lowStock();
        }

        if (! empty($filters['search'])) {
            $query->where('name', 'like', '%'.$filters['search'].'%');
        }

        return $query->paginate($filters['per_page'] ?? 15);
    }

    public function recordPurchase(InventoryItem $item, array $data): InventoryTransaction
    {
        return DB::transaction(function () use ($item, $data) {
            $transaction = InventoryTransaction::create([
                'inventory_item_id' => $item->id,
                'type' => 'purchase',
                'quantity' => $data['quantity'],
                'unit_price' => $data['unit_price'] ?? $item->unit_price,
                'total_amount' => ($data['unit_price'] ?? $item->unit_price) * $data['quantity'],
                'supplier' => $data['supplier'] ?? null,
                'transaction_date' => $data['transaction_date'] ?? now(),
                'notes' => $data['notes'] ?? null,
                'created_by' => Auth::id(),
            ]);

            $item->increment('quantity', $data['quantity']);

            if (! empty($data['unit_price'])) {
                $item->update(['unit_price' => $data['unit_price']]);
            }

            return $transaction;
        });
    }

    public function recordUsage(InventoryItem $item, array $data): InventoryTransaction
    {
        return DB::transaction(function () use ($item, $data) {
            // Re-read under a row lock. The guard previously tested
            // `$item->quantity`, which is the model bound by the route at request
            // time, not the current committed value — and `decrement()` is atomic
            // at the database level, so the check and the write could disagree:
            //
            //   stock 7, two concurrent requests for 5 units
            //   t0 both read quantity = 7, both pass `7 < 5`
            //   t1 A decrements -> 2
            //   t2 B decrements -> -3
            //
            // The ledger then recorded 10 units consumed from a stock of 7, and a
            // transaction inside DB::transaction() does not help: the defect is a
            // stale read, not a partial write. The controller's `max:` rule reads
            // the same stale model, so it did not catch it either.
            $quantity = (int) $data['quantity'];
            $locked = InventoryItem::whereKey($item->id)->lockForUpdate()->firstOrFail();

            if ($locked->quantity < $quantity) {
                throw new \Exception('Insufficient stock available.');
            }

            $transaction = InventoryTransaction::create([
                'inventory_item_id' => $locked->id,
                'type' => 'usage',
                'quantity' => $quantity,
                'unit_price' => $locked->unit_price,
                'total_amount' => $locked->unit_price * $quantity,
                'purpose' => $data['purpose'] ?? null,
                'transaction_date' => $data['transaction_date'] ?? now(),
                'notes' => $data['notes'] ?? null,
                'created_by' => Auth::id(),
            ]);

            $locked->decrement('quantity', $quantity);

            return $transaction;
        });
    }

    public function recordAdjustment(InventoryItem $item, array $data): InventoryTransaction
    {
        return DB::transaction(function () use ($item, $data) {
            $oldQuantity = $item->quantity;
            $newQuantity = $data['new_quantity'];
            $difference = $newQuantity - $oldQuantity;

            $transaction = InventoryTransaction::create([
                'inventory_item_id' => $item->id,
                'type' => 'adjustment',
                'quantity' => abs($difference),
                'unit_price' => $item->unit_price,
                'total_amount' => 0,
                'notes' => ($data['notes'] ?? 'Stock adjustment')." (Old: {$oldQuantity}, New: {$newQuantity})",
                'transaction_date' => now(),
                'created_by' => Auth::id(),
            ]);

            $item->update(['quantity' => $newQuantity]);

            return $transaction;
        });
    }

    public function getLowStockItems(?int $threshold = null): Collection
    {
        $query = InventoryItem::query();

        if ($threshold !== null) {
            $query->where('quantity', '<', $threshold);
        } else {
            $query->lowStock();
        }

        return $query->orderBy('quantity')->get();
    }

    public function getTransactionHistory(InventoryItem $item, int $limit = 50): Collection
    {
        return $item->transactions()
            ->with('creator')
            ->orderBy('transaction_date', 'desc')
            ->limit($limit)
            ->get();
    }

    public function getInventoryReport(): array
    {
        $items = InventoryItem::all();

        $totalItems = $items->count();
        $totalValue = $items->sum('total_value');
        $lowStockCount = $items->filter(fn ($item) => $item->isLowStock())->count();

        $byCategory = $items->groupBy('category')->map(function ($items) {
            return [
                'count' => $items->count(),
                'total_quantity' => $items->sum('quantity'),
                'total_value' => $items->sum('total_value'),
            ];
        });

        $recentTransactions = InventoryTransaction::with(['item', 'creator'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return [
            'total_items' => $totalItems,
            'total_value' => $totalValue,
            'low_stock_count' => $lowStockCount,
            'by_category' => $byCategory,
            'recent_transactions' => $recentTransactions,
        ];
    }

    public function getCategories(): array
    {
        return InventoryItem::distinct('category')
            ->whereNotNull('category')
            ->pluck('category')
            ->toArray();
    }
}
