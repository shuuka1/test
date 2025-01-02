<?php

// composer require laravel/sanctum -若使用session則不需要

namespace App\Http\Controllers;

use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Laravel\Pail\ValueObjects\Origin\Console    ;
use PhpParser\Node\Stmt\Echo_;

class MemberController extends Controller
{
    // 取得會員資料
    public function show($id)
    {
        $member = Member::find($id);

        if ($member) {
            return response()->json($member);
        } else {
            return response()->json(['message' => 'Member not found'], 404);
        }
    }

    // 註冊新會員
    public function register(Request $request)
    {
        $validated = $request->validate([
            'Email' => 'required|email|unique:member,email',
            'UserName' => 'required|unique:member',
            'UserPWD' => 'required|string|min:8',
            'Gender' => 'nullable|in:male,female',
            'Avatar' => 'nullable|image|mimes:jpg,jpeg,png,gif|max:2048', // 頭像驗證
        ]);

        // 性別轉換為 int 型別
        if ($request->has('Gender')) {
            $validated['Gender'] = ($validated['Gender'] == 'male') ? 1 : 0;
        }

        // 儲存頭像
        if ($request->hasFile('Avatar')) {
            $avatarPath = $request->file('Avatar')->store('avatars', 'public');
            $validatedData['Avatar'] = $avatarPath;
        }

        // 密碼加密
        $validated['UserPWD'] = Hash::make($validated['UserPWD']);

        // 創建新會員
        $member = Member::create([
            'email' => $request->Email,
            'username' => $request->UserName,
            'password' => Hash::make($request->UserPWD),  // 记得对密码进行哈希处理
            'gender' => $request->Gender,
            'avatar' => $request->Avatar,  // 直接存储BASE64字符串
        ]);

        // 登入新註冊用戶
        Auth::login($member);

        return response()->json([
            'message' => '註冊成功',
            'user' => $member,  // 返回會員資料
        ]);
    }

    // 登入會員
    public function login(Request $request)
    {
        $validated = $request->validate([
            'Email' => 'required|email',
            'UserPWD' => 'required|string'
        ]);

        // 查找使用者
        $member = Member::where('Email', $validated['Email'])->first();

        // 檢查是否找到使用者
        if (!$member) {
            return response()->json(['success' => false, 'message' => 'User not found'], 404);
        }

        // 檢查密碼是否匹配
        if (Hash::check($validated['UserPWD'], $member->UserPWD)) {
            // 使用 session 登入
            Auth::login($member);

            return response()->json([
                'success' => true,
                'user' => $member,  // 返回用戶資料
                'message' => '登入成功',
            ]);
        }
        // 密碼不匹配
        return response()->json(['success' => false, 'message' => 'Invalid credentials'], 401);
    }

    // 更新會員資料
    public function update(Request $request)
    {
        $member = Auth::member();

        // 验证输入
        $request->validate([
            'Password' => 'nullable|min:8',
            'Gender' => 'nullable|in:male,female',
            'Avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        // 更新密碼
        if ($request->has('Password')) {
            $member->password = Hash::make($request->password);
        }

        // 更新性别
        if ($request->has('Gender')) {
            $member->gender = $request->gender;
        }

        // 上傳頭像
        // if ($request->hasFile('avatar')) {
        //     // 刪除舊頭像
        //     if ($member->avatar && Storage::exists($member->avatar)) {
        //         Storage::delete($member->avatar);
        //     }

        //     // 存储新头像
        //     $path = $request->file('avatar')->store('avatars');
        //     $member->avatar = $path;
        // }

        // $member->save();

        return response()->json(['message' => '資料更新成功'], 404);
    }

    // public function getProfile()
    // {
    //     $member = auth()->user(); // 或者根據需求改為具體用戶查詢
    //     return response()->json([
    //         'avatar' => $member->avatar,
    //         'userName' => $member->name
    //     ]);
    // }

    // 登出
    public function logout()
    {
        Auth::logout(); // 登出
        return response()->json(['message' => '登出成功']);
    }

    // 删除账户
    public function deleteAccount()
    {
        $user = Auth::memeber();

        // 刪除頭像
        // if ($user->avatar && Storage::exists($user->avatar)) {
        //     Storage::delete($user->avatar);
        // }

        // 刪除帳號
        $user->delete();

        return response()->json(['message' => '帳號刪除成功']);
    }
}
