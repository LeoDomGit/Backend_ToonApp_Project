<?php

namespace App\Http\Controllers;

use App\Models\Customers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Laravel\Sanctum\HasApiTokens;

class CustomersController extends Controller
{
    /**
     * Register a new customer.
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|unique:customers,username',
            'email' => 'required|email|unique:customers,email',
            'password' => 'required|min:8',
            'fullname' => 'nullable|string',
            'age' => 'nullable|integer',
            'gender' => 'nullable|string',
            'place_of_birth' => 'nullable|string',
            'country' => 'nullable|string',
            'city' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['check' => false, 'msg' => $validator->errors()->first()], 400);
        }

        $customerData = $request->only([
            'username', 'email', 'fullname', 'age', 'gender', 'place_of_birth', 'country', 'city'
        ]);
        $customerData['password'] = Hash::make($request->password);

        $customer = Customers::create($customerData);

        return response()->json(['check' => true, 'msg' => 'Registration successful', 'data' => $customer], 201);
    }

    /**
     * Customer login.
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:customers,email',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['check' => false, 'msg' => $validator->errors()->first()], 400);
        }

        $customer = Customers::where('email', $request->email)->first();

        if (!$customer || !Hash::check($request->password, $customer->password)) {
            return response()->json(['check' => false, 'msg' => 'Invalid email or password'], 401);
        }

        $token = $customer->createToken('customer_token')->plainTextToken;
        $customer->update([
            'last_login' => now(),
            'remember_token' => $token,
        ]);

        return response()->json(['check' => true, 'token' => $token, 'customer' => $customer], 200);
    }

    /**
     * Customer logout.
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        Customers::where('id', $request->user()->id)->update(['remember_token' => null]);
        return response()->json(['check' => true, 'msg' => 'Logged out successfully'], 200);
    }

    /**
     * Show customer details.
     */
    public function show(Request $request)
    {
        return response()->json(['check' => true, 'customer' => $request->user()], 200);
    }
    public function social_login(Request $request){
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:customers,email',
        ]);
        if ($validator->fails()) {
            return response()->json(['check' => false, 'msg' => $validator->errors()->first()], 400);
        }
        $customer = Customers::where('email', $request->email)->first();
        if(!$customer){
            return response()->json(['check' => false, 'msg' => 'Invalid email'], 401);
        }
        $token = $customer->createToken('customer_token')->plainTextToken;
        $customer->update([
            'last_login' => now(),
            'remember_token' => $token,
        ]);
        return response()->json(['check' => true, 'token' => $token, 'customer' => $customer], 200);
    }
     /**
     * Show customer details.
     */
    public function forget_password(Request $request){
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:customers,email',
            'username'=>'required|exists:customers,username',
            'password'=>'required|min:8',
        ]);
        if ($validator->fails()) {
            return response()->json(['check' => false, 'msg' => $validator->errors()->first()], 400);
        }
        $customer = Customers::where('email', $request->email)->where('username', $request->username)->first();
        if (!$customer) {
            return response()->json(['check' => false, 'msg' => 'Invalid email or username'], 401);
        }
        $customer->update([
            'password' => Hash::make($request->password),
            'updated_at'=>now(),
            'remember_token'=>null
        ]);
        return response()->json(['check' => true, 'msg' => 'Password updated successfully'], 200);
    }
    /**
     * Update customer details.
     */
    public function update(Request $request)
    {
        $customer = $request->user();

        $validator = Validator::make($request->all(), [
            'fullname' => 'nullable|string',
            'age' => 'nullable|integer',
            'gender' => 'nullable|string',
            'place_of_birth' => 'nullable|string',
            'country' => 'nullable|string',
            'city' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['check' => false, 'msg' => $validator->errors()->first()], 400);
        }

        $customer->update($request->all());

        return response()->json(['check' => true, 'msg' => 'Profile updated successfully', 'customer' => $customer], 200);
    }

    /**
     * Delete a customer.
     */
    public function destroy(Request $request)
    {
        $customer = $request->user();
        $customer->delete();

        return response()->json(['check' => true, 'msg' => 'Account deleted successfully'], 200);
    }
}
