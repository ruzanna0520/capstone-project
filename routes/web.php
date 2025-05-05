<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Product;
use App\Models\Category;
use App\Models\Order;

use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\OrderController as ApiOrderController;
use App\Http\Controllers\Api\UserController;

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\UserDashboardController;


Route::get('/', function () {
    return redirect()->route('login');
});


Route::middleware(['auth'])->prefix('internal-api')->name('api.')->group(function () {

    Route::apiResource('categories', CategoryController::class);
    Route::apiResource('products', ProductController::class);

    Route::get('orders', [ApiOrderController::class, 'index'])->name('orders.index');
    Route::post('orders', [ApiOrderController::class, 'store'])->name('orders.store');
    Route::get('orders/{order}', [ApiOrderController::class, 'show'])->name('orders.show');
    Route::put('orders/{order}', [ApiOrderController::class, 'update'])->name('orders.update');
    Route::delete('orders/{order}', [ApiOrderController::class, 'destroy'])->name('orders.destroy');

    Route::get('users', [UserController::class, 'index'])->name('users.index');
    Route::post('users', [UserController::class, 'store'])->name('users.store');
    Route::get('users/{user}', [UserController::class, 'show'])->name('users.show');
    Route::put('users/{user}', [UserController::class, 'update'])->name('users.update');
    Route::delete('users/{user}', [UserController::class, 'destroy'])->name('users.destroy');

    Route::get('customers', [UserController::class, 'indexCustomers'])->name('customers.index');

});


Route::middleware(['auth', 'verified'])->group(function () {

    Route::get('/dashboard', function (Request $request) {
        if ($request->user()?->is_admin) {
            return redirect()->route('admin.dashboard');
        }
        return redirect()->route('user.dashboard');
    })->name('dashboard');

    Route::get('/admin-dashboard', AdminDashboardController::class)
        ->middleware(['can:viewAdminDashboard'])
        ->name('admin.dashboard');

    Route::get('/user-dashboard', UserDashboardController::class)
        ->name('user.dashboard');

    Route::get('/catalog', function (Request $request) {
        $categoryId = $request->query('category');

        $productsQuery = Product::with('category')->latest();

        if ($categoryId) {
            $productsQuery->where('category_id', $categoryId);
        }

        $products = $productsQuery->paginate($request->query('per_page', 12))->withQueryString();
        $categories = Category::orderBy('name')->get(['id', 'name']);

        return Inertia::render('Catalog', [
            'products' => $products,
            'categories' => $categories,
            'activeCategoryId' => $categoryId ? (int)$categoryId : null,
        ]);
    })->name('catalog.index');

    Route::get('/orders', function(Request $request) {
        $filters = $request->only(['status', 'date_from', 'date_to']);

        $ordersQuery = $request->user()
            ->orders()
            ->with(['products' => function ($query) {
                $query->select('products.id', 'products.name')
                    ->withPivot('quantity', 'price_at_time_of_order');
            }])
            ->when($filters['status'] ?? null, function ($query, $status) {
                $query->where('status', $status);
            })
            ->when($filters['date_from'] ?? null, function ($query, $dateFrom) {
                $query->whereDate('created_at', '>=', $dateFrom);
            })
            ->when($filters['date_to'] ?? null, function ($query, $dateTo) {
                $query->whereDate('created_at', '<=', $dateTo);
            })
            ->latest();

        $orders = $ordersQuery->paginate($request->query('per_page', 10))->withQueryString();

        return Inertia::render('Orders', [
            'orders' => $orders,
            'filters' => $filters,
        ]);
    })->name('orders.index');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

});

require __DIR__.'/auth.php';
