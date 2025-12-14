<?php
namespace App\Services;

use App\Models\Order;
use App\Models\User;
use App\Models\Asset;
use App\Models\Trade;
use App\Events\OrderMatched;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class OrderMatchingService
{
    const COMMISSION_RATE = 0.015;

    /**
     * Create a new order and attempt to match it
     */
    public function createOrder(User $user, array $data): Order
    {
        return DB::transaction(function () use ($user, $data) {
            $symbol = $data['symbol'];
            $side = $data['side'];
            $price = $data['price'];
            $amount = $data['amount'];

            if ($side === 'buy') {
                $this->lockBuyerFunds($user, $price, $amount);
            } else {
                $this->lockSellerAssets($user, $symbol, $amount);
            }

            $order = $user->orders()->create([
                'symbol' => $symbol,
                'side' => $side,
                'price' => $price,
                'amount' => $amount,
                'status' => Order::STATUS_OPEN,
                'locked_value' => $side === 'buy' 
                    ? bcmul($price, $amount, 8) 
                    : $amount,
            ]);

            Log::info('Order created', [
                'order_id' => $order->id,
                'user_id' => $user->id,
                'side' => $side,
                'price' => $price,
                'amount' => $amount,
            ]);

            // Try to match immediately
            $this->attemptMatch($order);

            return $order->fresh();
        });
    }

    /**
     * Lock buyer's USD funds
     */
    protected function lockBuyerFunds(User $user, string $price, string $amount): void
    {
        $totalCost = bcmul($price, $amount, 8);

        $user = User::where('id', $user->id)->lockForUpdate()->first();

        if (bccomp($user->balance, $totalCost, 8) < 0) {
            throw new Exception('Insufficient balance. Required: ' . $totalCost . ', Available: ' . $user->balance);
        }

        $user->decrement('balance', $totalCost);

        Log::info('Buyer funds locked', [
            'user_id' => $user->id,
            'amount' => $totalCost,
            'remaining_balance' => $user->fresh()->balance,
        ]);
    }

    /**
     * Lock seller's assets
     */
    protected function lockSellerAssets(User $user, string $symbol, string $amount): void
    {
        $asset = Asset::where('user_id', $user->id)
            ->where('symbol', $symbol)
            ->lockForUpdate()
            ->first();

        if (!$asset || bccomp($asset->amount, $amount, 8) < 0) {
            throw new Exception('Insufficient ' . $symbol . ' balance. Required: ' . $amount . ', Available: ' . ($asset->amount ?? 0));
        }
        $asset->decrement('amount', $amount);
        $asset->increment('locked_amount', $amount);

        Log::info('Seller assets locked', [
            'user_id' => $user->id,
            'symbol' => $symbol,
            'amount' => $amount,
            'remaining_available' => $asset->fresh()->amount,
        ]);
    }

    /**
     * Attempt to match an order with existing counter orders
     */
    protected function attemptMatch(Order $order): void
    {
        $order = Order::where('id', $order->id)
            ->lockForUpdate()
            ->first();

        if (!$order->isOpen()) {
            return;
        }

        $counterOrder = $this->findMatchingOrder($order);

        if ($counterOrder) {
            $this->executeMatch($order, $counterOrder);
        } else {
            Log::info('No matching order found', ['order_id' => $order->id]);
        }
    }

    /**
     * Find the best matching counter order
     */
    protected function findMatchingOrder(Order $order): ?Order
    {
        $query = Order::where('symbol', $order->symbol)
            ->where('status', Order::STATUS_OPEN)
            ->where('user_id', '!=', $order->user_id)
            ->lockForUpdate();

        if ($order->side === 'buy') {
            $query->where('side', 'sell')
                ->where('price', '<=', $order->price)
                ->where('amount', $order->amount) 
                ->orderBy('price', 'asc')
                ->orderBy('created_at', 'asc'); 
        } else {
            $query->where('side', 'buy')
                ->where('price', '>=', $order->price)
                ->where('amount', $order->amount)
                ->orderBy('price', 'desc') 
                ->orderBy('created_at', 'asc');
        }

        return $query->first();
    }

    /**
     * Execute a matched trade
     */
    protected function executeMatch(Order $newOrder, Order $counterOrder): void
    {
        Log::info('Executing match', [
            'new_order_id' => $newOrder->id,
            'counter_order_id' => $counterOrder->id,
        ]);

        $buyOrder = $newOrder->side === 'buy' ? $newOrder : $counterOrder;
        $sellOrder = $newOrder->side === 'sell' ? $newOrder : $counterOrder;

        $matchPrice = $counterOrder->price;
        $amount = $newOrder->amount;

        $totalValue = bcmul($matchPrice, $amount, 8);
        $commission = bcmul($totalValue, self::COMMISSION_RATE, 8);

        $buyOrder->update(['status' => Order::STATUS_FILLED]);
        $sellOrder->update(['status' => Order::STATUS_FILLED]);

        $this->processBuyerTrade($buyOrder, $amount, $totalValue, $commission);
        $this->processSellerTrade($sellOrder, $amount, $totalValue);
        $trade = $this->recordTrade($buyOrder, $sellOrder, $matchPrice, $amount, $totalValue, $commission);

        broadcast(new OrderMatched($trade, $buyOrder->user_id))->toOthers();
        broadcast(new OrderMatched($trade, $sellOrder->user_id))->toOthers();

        Log::info('Match executed successfully', [
            'trade_id' => $trade->id,
            'price' => $matchPrice,
            'amount' => $amount,
            'total_value' => $totalValue,
            'commission' => $commission,
        ]);
    }

    /**
     * Process buyer side of trade
     */
    protected function processBuyerTrade(Order $buyOrder, string $amount, string $totalValue, string $commission): void
    {
        $buyer = User::where('id', $buyOrder->user_id)->lockForUpdate()->first();

        $buyerAsset = Asset::firstOrCreate(
            [
                'user_id' => $buyer->id,
                'symbol' => $buyOrder->symbol,
            ],
            [
                'amount' => 0,
                'locked_amount' => 0,
            ]
        );

        $buyerAsset->increment('amount', $amount);
        $lockedValue = $buyOrder->locked_value;
        $actualCost = bcadd($totalValue, $commission, 8);

        if (bccomp($lockedValue, $actualCost, 8) > 0) {
            $refund = bcsub($lockedValue, $actualCost, 8);
            $buyer->increment('balance', $refund);

            Log::info('Buyer refunded price difference', [
                'user_id' => $buyer->id,
                'refund' => $refund,
            ]);
        } elseif (bccomp($lockedValue, $actualCost, 8) < 0) {
            $additional = bcsub($actualCost, $lockedValue, 8);
            $buyer->decrement('balance', $additional);

            Log::warning('Buyer charged additional amount', [
                'user_id' => $buyer->id,
                'additional' => $additional,
            ]);
        }

        Log::info('Buyer trade processed', [
            'user_id' => $buyer->id,
            'received_amount' => $amount,
            'commission_paid' => $commission,
        ]);
    }

    /**
     * Process seller side of trade
     */
    protected function processSellerTrade(Order $sellOrder, string $amount, string $totalValue): void
    {
        $seller = User::where('id', $sellOrder->user_id)->lockForUpdate()->first();

        $sellerAsset = Asset::where('user_id', $seller->id)
            ->where('symbol', $sellOrder->symbol)
            ->lockForUpdate()
            ->first();
        $sellerAsset->decrement('locked_amount', $amount);
        $seller->increment('balance', $totalValue);
        Log::info('Seller trade processed', [
            'user_id' => $seller->id,
            'asset_released' => $amount,
            'usd_received' => $totalValue,
        ]);
    }

    /**
     * Record trade in database
     */
    protected function recordTrade(
        Order $buyOrder,
        Order $sellOrder,
        string $price,
        string $amount,
        string $totalValue,
        string $commission
    ): Trade {
        return Trade::create([
            'buy_order_id' => $buyOrder->id,
            'sell_order_id' => $sellOrder->id,
            'buyer_id' => $buyOrder->user_id,
            'seller_id' => $sellOrder->user_id,
            'symbol' => $buyOrder->symbol,
            'price' => $price,
            'amount' => $amount,
            'total_value' => $totalValue,
            'commission' => $commission,
            'executed_at' => now(),
        ]);
    }

    /**
     * Cancel an open order
     */
    public function cancelOrder(Order $order): Order
    {
        return DB::transaction(function () use ($order) {
            // Lock and reload order
            $order = Order::where('id', $order->id)
                ->lockForUpdate()
                ->first();

            // Validate order can be cancelled
            if (!$order->isOpen()) {
                throw new Exception('Only open orders can be cancelled. Current status: ' . $order->status);
            }

            // Release locked funds/assets
            if ($order->side === 'buy') {
                $this->releaseBuyerFunds($order);
            } else {
                $this->releaseSellerAssets($order);
            }

            // Update order status
            $order->update(['status' => Order::STATUS_CANCELLED]);

            Log::info('Order cancelled', [
                'order_id' => $order->id,
                'user_id' => $order->user_id,
            ]);

            return $order;
        });
    }

    /**
     * Release buyer's locked USD funds
     */
    protected function releaseBuyerFunds(Order $order): void
    {
        $user = User::where('id', $order->user_id)->lockForUpdate()->first();
        $user->increment('balance', $order->locked_value);

        Log::info('Buyer funds released', [
            'user_id' => $user->id,
            'amount' => $order->locked_value,
        ]);
    }

    /**
     * Release seller's locked assets
     */
    protected function releaseSellerAssets(Order $order): void
    {
        $asset = Asset::where('user_id', $order->user_id)
            ->where('symbol', $order->symbol)
            ->lockForUpdate()
            ->first();

        if ($asset) {
            $asset->decrement('locked_amount', $order->locked_value);
            $asset->increment('amount', $order->locked_value);

            Log::info('Seller assets released', [
                'user_id' => $order->user_id,
                'symbol' => $order->symbol,
                'amount' => $order->locked_value,
            ]);
        }
    }

}