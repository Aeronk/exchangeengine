# Limit-Order Exchange Mini Engine

## Setup Instructions

1. Clone repository
2. Copy .env.example to .env
3. Configure database and Pusher credentials
4. Run:
```bash
   composer install
   npm install
   php artisan key:generate
   php artisan migrate
   npm run dev
   php artisan serve
```

## Testing

### Create Test Users with Balance
```bash
php artisan tinker
$user1 = User::factory()->create(['balance' => 10000]);
$user2 = User::factory()->create(['balance' => 10000]);
Asset::create(['user_id' => $user2->id, 'symbol' => 'BTC', 'amount' => 1]);
```

### Test Order Matching
1. Login as User 1
2. Place BUY order: BTC @ 50000, amount 0.1
3. Login as User 2
4. Place SELL order: BTC @ 50000, amount 0.1
5. Both should receive real-time notification
6. Check balances updated correctly

## Features Implemented
- ✅ Order creation with balance locking
- ✅ Full-match order matching engine
- ✅ 1.5% commission on trades
- ✅ Race condition prevention
- ✅ Real-time updates via Pusher
- ✅ Order cancellation
- ✅ Orderbook display