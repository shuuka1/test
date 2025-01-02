<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\Member;  // 引入 Member 模型
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

// php artisan ui bootstrap --auth
// AuthController

class AuthController extends Controller
{

    // 註冊功能
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'Email' => 'required|email|max:100|unique:member,email',
            'UserName' => 'required|string|max:200|unique:member,username',
            'UserPWD' => 'required|string|min:8|regex:/[A-Za-z]/|regex:/\d/',  // 密碼需至少 8 位，且包含字母和數字
            'Gender' => 'nullable|in:0,1',  // 性別可以是 0 或 1，且可為null
            'Avatar' => 'nullable|regex:/^data:image\/(jpeg|png|jpg);base64,/',  // Base64 字符串
        ]);

        // 驗證失敗，返回錯誤訊息
        if ($validator->fails()) {
            return response()->json([
                'message' => '註冊失敗',
                'errors' => $validator->errors()
            ], 422);
        }

        // 創建新用戶
        try {
            $member = new Member();
            $member->email = $request->Email;
            $member->username = $request->UserName;
            $member->UserPWD = Hash::make($request->UserPWD); // 密碼加密
            $member->gender = $request->Gender ?? null; // 性別默認為 null
            // 儲存頭像（Base64 字串）
            if ($request->Avatar) {
                $avatarPath = $this->saveAvatar($request->Avatar);
                $member->avatar = $avatarPath;  // 頭像的保存路徑
            } else {
                $member->avatar = null;
            }

            $member->save();

            return response()->json([
                'message' => '註冊成功！',
                'user' => $member
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => '註冊失敗，請再試一次。',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // 登入功能
    public function login(Request $request)
    {
        // 驗證使用者輸入的資料
        $validator = Validator::make($request->all(), [
            'Email' => 'required|email|max:100',
            'UserPWD' => 'required|string|min:8',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 400);
        }

        // 尋找用戶
        $member = Member::where('Email', $request->Email)->first();

        // 如果資料庫中找不到用戶，使用模擬帳號和密碼
        if (!$member) {
            // 模擬用戶驗證
            if ($request->Email === 'admin@example.com' && $request->UserPWD === 'password') {
                // 儲存到 Session
                $request->session()->put('user', ['UID' => 0, 'email' => $request->Email]);
                return response()->json(['message' => 'Login successful (simulated)', 'user' => $request->session()->get('user')]);
            }

            // 如果模擬帳號也不正確，返回錯誤
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        // 驗證密碼
        if (!$member || !Hash::check($request->UserPWD, $member->UserPWD)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        // 登入成功，建立session
        session(['userId' => $member->UID, 'email' => $member->Email]);

        return response()->json(['message' => 'Login successful', 'user' => $member]);
    }

    // 儲存頭像 php artisan storage:link
    private function saveAvatar($base64)
    {
        // 解碼 base64 字符串，並去掉前綴
        $imageData = preg_replace('#^data:image/\w+;base64,#i', '', $base64);
        $image = base64_decode($imageData);

        if ($image === false) {
            throw new \Exception('無效的圖像數據');
        }

        // 為文件命名，並確保唯一性
        $filename = 'avatars/' . Str::random(40) . '.png';

        // 儲存圖片到 storage
        $storagePath = 'public/' . $filename;
        if (!Storage::put($storagePath, $image)) {
            throw new \Exception('圖片儲存失敗');
        }

        return $filename;  // 返回儲存的文件路徑
    }

    public function getUser(Request $request)
    {
        if ($request->session()->has('user')) {
            return response()->json(['user' => $request->session()->get('user')]);
        }
        return response()->json(['message' => 'Not logged in'], 401);
    }


    // 修改用户資料
    public function updateProfile(Request $request)
    {
        $member = Auth::user();

        if (!$member) {
            return response()->json(['message' => 'User not authenticated'], 401);
        }

        // 驗證資料
        $validator = Validator::make($request->all(), [
            'UserPWD' => 'nullable|string|min:8',
            'Gender' => 'nullable|in:0,1',
            'Avatar' => 'nullable|string', // 可能是 base64 編碼的圖像
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        // 獲取當前登錄的用戶
        $member = auth()->Member();

        // 如果未找到用戶或未登錄，返回錯誤
        if (!$member) {
            return response()->json(['message' => '未授權的請求'], 401);
        }

        // 更新密碼，如果提供了新的密碼
        if ($request->has('UserPWD') && $request->input('UserPWD')) {
            $member->password = Hash::make($request->input('UserPWD'));  // 密碼加密
        }

        // 更新性別
        if ($request->has('Gender')) {
            $member->gender = $request->input('Gender');
        }

        // 更新頭像（base64 編碼的圖像）
        if ($request->has('Avatar')) {
            $member->avatar = $request->input('Avatar');
        }

        // 保存更新
        $member->save();

        // 返回成功訊息
        return response()->json(['message' => '資料更新成功']);
    }

    // 登出功能
    public function logout(Request $request)
    {
        auth()->logout();  // 登出
        return response()->json(['message' => '登出成功']);
    }

    // 刪除帳號
    public function deleteAccount()
    {
        $member = auth()->user();  // 獲取當前已認證的用戶

        // 如果未找到用戶，返回錯誤
        if (!$member) {
            return response()->json(['message' => '未授權的請求'], 401);
        }

        // 刪除用戶資料
        $member->delete();

        // 登出用戶
        auth()->logout();

        return response()->json(['message' => '帳戶已刪除']);
    }
}
