# Limit-Order Exchange Mini Engine

A real-time cryptocurrency exchange engine built with Laravel 11 and Vue.js 3, featuring limit order matching, atomic balance management, and live notifications via Pusher.

##  Features

- ✅ **User Balance Management** - USD balance tracking with atomic operations
- ✅ **Asset Management** - BTC and ETH with locked amounts for open orders
- ✅ **Limit Order Matching** - Full-match engine with price-time priority
- ✅ **Commission System** - 1.5% commission on all trades
- ✅ **Race Condition Prevention** - Database locks and transactions throughout
- ✅ **Real-time Updates** - Instant notifications via Pusher when orders match
- ✅ **Order Cancellation** - Cancel open orders with automatic fund release
- ✅ **Authentication** - Secure token-based auth with Laravel Sanctum
- ✅ **Responsive UI** - Modern Tailwind CSS interface

## 🛠 Tech Stack

- **Backend**: Laravel 12
- **Frontend**: Vue.js 3 (Composition API)
- **Database**: MySQL / PostgreSQL
- **Real-time**: Pusher via Laravel Broadcasting
- **Styling**: Tailwind CSS
- **Authentication**: Laravel Sanctum

## Installation

### Prerequisites

- PHP 8.2+
- Composer
- Node.js 18+
- MySQL or PostgreSQL
- Pusher account (free tier works)

### Step 1: Clone Repository

```bash
git clone https://github.com/Aeronk/exchangeengine.git
cd exchange-engine
```

### Step 2: Install Dependencies

```bash
composer install
npm install
```

### Step 3: Environment Configuration

```bash
cp .env.example .env
php artisan key:generate
```

Update `.env` with your credentials:

```env
# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=exchange_engine
DB_USERNAME=root
DB_PASSWORD=

# Pusher (for real-time updates)
BROADCAST_DRIVER=pusher
PUSHER_APP_ID=your_app_id
PUSHER_APP_KEY=your_app_key
PUSHER_APP_SECRET=your_app_secret
PUSHER_APP_CLUSTER=your_cluster

# Frontend
VITE_PUSHER_APP_KEY="${PUSHER_APP_KEY}"
VITE_PUSHER_APP_CLUSTER="${PUSHER_APP_CLUSTER}"
```

### Step 4: Database Setup

```bash
php artisan migrate --seed
```

This creates three demo users:
- **Buyer**: buyer@test.com / password (Balance: $10,000)
- **Seller**: seller@test.com / password (Balance: $10,000, 1 BTC)
- **Trader**: trader@test.com / password (Balance: $5,000)

### Step 5: Start Development Servers

```bash
# Terminal 1 - Frontend
npm run dev

# Terminal 2 - Backend
php artisan serve
```

Visit: **http://localhost:8000**


### Manual Testing Scenarios

#### Scenario 1: Successful Order Match ✅

1. Login as **Seller** (seller@test.com / password)
2. Place SELL order: 
   - Symbol: BTC
   - Price: $50,000
   - Amount: 0.1
3. Logout and login as **Buyer** (buyer@test.com / password)
4. Place BUY order:
   - Symbol: BTC
   - Price: $50,000
   - Amount: 0.1
5. **Expected Results**:
   - Both users receive real-time notification
   - Buyer balance: $10,000 - $5,075 = $4,925 (cost + commission)
   - Buyer BTC: 0.1
   - Seller balance: $10,000 + $5,000 = $15,000
   - Seller BTC: 1 - 0.1 = 0.9
   - Both orders marked as FILLED

#### Scenario 2: Commission Calculation ✅

Example trade:
- Price: $50,000
- Amount: 0.1 BTC
- **Trade value**: $50,000 × 0.1 = $5,000
- **Commission (1.5%)**: $5,000 × 0.015 = **$75**
- **Buyer pays**: $5,000 + $75 = $5,075

#### Scenario 3: No Match (Price Mismatch) ✅

1. Seller places SELL @ $51,000
2. Buyer places BUY @ $49,000
3. **Expected**: Both orders remain OPEN (no match)

#### Scenario 4: Order Cancellation ✅

1. Place any order
2. Click "Cancel" button
3. **Expected**:
   - Order status → CANCELLED
   - Locked funds/assets returned to available balance

#### Scenario 5: Multi-User Real-time ✅

1. Browser 1: Login as seller, place SELL order
2. Browser 2 (Incognito): Login as buyer, place matching BUY order
3. **Expected**: Both browsers receive instant notifications

##  Database Schema

### users
- `id` - Primary key
- `balance` (decimal 20,8) - USD funds available

### assets
- `id` - Primary key
- `user_id` - Foreign key to users
- `symbol` - BTC or ETH
- `amount` (decimal 20,8) - Available amount
- `locked_amount` (decimal 20,8) - Amount locked in open orders

### orders
- `id` - Primary key
- `user_id` - Foreign key to users
- `symbol` - Trading symbol
- `side` - buy or sell
- `price` (decimal 20,8) - Limit price
- `amount` (decimal 20,8) - Order amount
- `status` - 1=open, 2=filled, 3=cancelled
- `locked_value` (decimal 20,8) - Locked USD or asset amount

### trades
- `id` - Primary key
- `buy_order_id`, `sell_order_id` - Foreign keys to orders
- `buyer_id`, `seller_id` - Foreign keys to users
- `price`, `amount` - Execution details
- `total_value` (decimal 20,8) - Total USD value
- `commission` (decimal 20,8) - Commission charged (1.5%)
- `executed_at` - Execution timestamp

## 🔧 API Endpoints

### Authentication
- `POST /api/login` - Login with email/password
- `POST /api/logout` - Logout current user
- `GET /api/me` - Get authenticated user

### Profile
- `GET /api/profile` - Get user balance and assets

### Orders
- `GET /api/orders?symbol=BTC` - Get orderbook for symbol
- `GET /api/my-orders` - Get user's orders
- `POST /api/orders` - Create new order
- `POST /api/orders/{id}/cancel` - Cancel order

## 🏗 Architecture

### Core Business Logic

The order matching engine is implemented in `OrderMatchingService`:

**Key Methods**:
- `createOrder()` - Validates and creates orders with fund locking
- `lockBuyerFunds()` - Atomically locks USD for buy orders
- `lockSellerAssets()` - Atomically locks assets for sell orders
- `attemptMatch()` - Finds and executes matching orders
- `executeMatch()` - Transfers assets, updates balances, calculates commission
- `cancelOrder()` - Releases locked funds/assets

### Concurrency Safety

All balance and asset updates use:
- `lockForUpdate()` on database rows
- Database transactions wrapping all operations
- Atomic increment/decrement operations
- Prevents race conditions and double-spending

### Commission Calculation

```php
const COMMISSION_RATE = 0.015; // 1.5%

$totalValue = bcmul($price, $amount, 8);
$commission = bcmul($totalValue, self::COMMISSION_RATE, 8);

$buyer->decrement('balance', $commission);
```

### Real-time Broadcasting

When orders match:
1. `OrderMatched` event created with trade data
2. Event broadcast to private channels: `private-user.{id}`
3. Frontend Echo listener receives event
4. UI updates automatically (balance, orders, notification)

##  Key Design Decisions

1. **Full Match Only** - No partial fills for simplicity and assessment requirements
2. **Commission from Buyer** - 1.5% deducted from buyer's USD balance
3. **Price-Time Priority** - Best price first, then FIFO (first-in-first-out)
4. **Separate Locked Amounts** - Prevents double-spending during order execution
5. **Maker Price Execution** - Counter order's price is the execution price

## Troubleshooting

### Pusher Not Working

```bash
# Clear config cache
php artisan config:clear

# Verify .env values
echo $PUSHER_APP_KEY
echo $VITE_PUSHER_APP_KEY

# Rebuild frontend
npm run build

# Check Pusher dashboard for events
```

### Orders Not Matching

**Requirements for match**:
- Amounts must be EXACTLY equal (full match only)
- Prices must meet conditions:
  - Buy matches if: `sell_price ≤ buy_price`
  - Sell matches if: `buy_price ≥ sell_price`

### Balance Not Updating

```bash
# Check Laravel logs
tail -f storage/logs/laravel.log

# Check browser console
# Open DevTools → Console tab

# Verify token
# In browser console:
localStorage.getItem('token')
```

### Authentication Issues

```bash
# Clear all caches
php artisan config:clear
php artisan route:clear
php artisan cache:clear

# Verify Sanctum is installed
composer show laravel/sanctum

# Check API routes
php artisan route:list --path=api
```

## Performance Optimizations

### Database Indexes
- Orders: `(symbol, status, side, price)` - Fast orderbook queries
- Assets: `(user_id, symbol)` - Quick balance lookups
- Orders: `(user_id, status)` - Efficient user order filtering

### Query Optimization
- `lockForUpdate()` only on rows being modified
- Minimal queries in hot matching path
- Eager loading for relationships where needed

### Frontend
- Computed properties for derived data
- Orderbook refresh every 10 seconds
- Debounced user input where applicable

##  Project Structure

```
app/
├── Http/
│   └── Controllers/Api/
│       ├── AuthController.php      # Login/logout
│       ├── ProfileController.php   # Balance & assets
│       └── OrderController.php     # Order CRUD
├── Models/
│   ├── User.php                   # User with balance
│   ├── Asset.php                  # Asset holdings
│   ├── Order.php                  # Order records
│   └── Trade.php                  # Trade history
├── Services/
│   └── OrderMatchingService.php   #  Core matching logic
└── Events/
    └── OrderMatched.php           # Real-time event

resources/js/
├── api/
│   └── axios.js                   # API client
├── composables/
│   └── useAuth.js                 # Auth state
└── components/
    ├── App.vue                    # Root component
    ├── Login.vue                  # Authentication
    ├── Dashboard.vue              # Main interface
    └── OrderForm.vue              # Order creation

database/
├── migrations/                     # Schema definitions
└── seeders/
    └── DemoUsersSeeder.php        # Test users
```

## ✅ Feature Checklist

### Required Features
- [x] User balance management (USD)
- [x] Asset management (BTC, ETH with locked amounts)
- [x] Order creation (buy/sell)
- [x] Full-match order matching
- [x] 1.5% commission calculation
- [x] Order cancellation with fund release
- [x] Real-time notifications via Pusher
- [x] Orderbook display
- [x] Race condition prevention
- [x] API with authentication

### Bonus Features
- [x] Trade history table
- [x] Order status filtering
- [x] Responsive UI design
- [x] Loading states and error handling
- [x] Toast notifications
- [x] Auto-refresh orderbook

##  License

This project was created as part of a technical assessment.

##  Developer Notes

**Development Time**: ~15-18 hours  
**Commit Count**: 25+  
**Test Coverage**: Core business logic (order matching, balance management)  

