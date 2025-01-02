<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Member;
use App\Http\Controllers\AuthController;

// 'guards' => [
//     'web' => [
//         'driver' => 'session',
//         'provider' => 'users',
//     ],

//     'api' => [
//         'driver' => 'session',  // 使用 session 驅動，這樣 API 就可以使用會話來處理身份驗證
//         'provider' => 'users',
//     ],

// 首頁
Route::get('/', function () {
    return view('dresswall');
});

// 註冊
Route::post('register', [AuthController::class, 'register']);

// 登入
Route::post('login', [AuthController::class, 'login']);

Route::middleware(['auth'])->group(function () {
    // 顯示導覽頁
    Route::get('/dashboard', [AuthController::class, 'dashboard'])->name('dashboard');
    
    // 顯示修改會員資料頁
    Route::get('/modification', [AuthController::class, 'index'])->name('modification');
    
    // 更新會員資料
    Route::put('update-profile', [AuthController::class, 'updateProfile']);
    
    // 刪除帳號
    Route::delete('delete-account', [AuthController::class, 'deleteAccount']);
    
    // 登出
    Route::post('logout', [AuthController::class, 'logout']);
});

Route::get('/outfits/photos', function () {
    $outfits = DB::table('outfit')->select('UID', 'outfitID', 'EditedPhoto')->get();
    return response()->json($outfits);
});

Route::get('user-info/{uid}', function ($UID) {
    $member = Member::where('uid', $UID)->first();
    if ($member) {
        // 返回使用者資料（UserName 和 Avatar）
        return response()->json([
            'UserName' => $member->UserName,  // 確保欄位名稱與資料庫一致
            'Avatar' => $member->Avatar       // 同樣欄位名稱正確
        ]);
    } else {
        // 如果沒有會員資料，返回 404 或錯誤信息
        return response()->json(['message' => 'No user data found'], 404);
    }
});
