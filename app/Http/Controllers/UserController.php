<?php

namespace App\Http\Controllers;

use App\Models\Roles;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use App\Mail\Createuser;
use Inertia\Inertia;

class UserController extends Controller
{
    protected $model;
    public function __construct(){
        $this->model =  User::class;
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $users = $this->model::with('roles')->get();
        $roles= Roles::all();   
        return Inertia::render('Users/Index',['roles'=>$roles,'users'=>$users]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        //
    }
    /**
     * Display the specified resource.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'=>'required',
            'email'=>'required|email|unique:users,email',
            'idRole'=>'required|exists:roles,id'
        ]);
        if ($validator->fails()) {
            return response()->json(['check'=>false,'msg'=>$validator->errors()->first()]);
        }
        $data = $request->all();
        $password = random_int(10000, 99999);
        $data['password'] = Hash::make($password);
        User::create($data);
        $data = [
            'name'=>$request->name,
            'email' => $request->email,
            'password' => $password,
        ];
        Mail::to($request->email)->send(new Createuser($data));
        $users = $this->model::with('roles')->get();
        return response()->json(['check' => true,'data'=>$users]);
    }
    /**
     * Display the specified resource.
     */
    public function update(Request $request,$id){
        $validator = Validator::make($request->all(), [
            'email'=>'email|unique:users,email',
            'idRole'=>'exists:roles,id'
        ]);
        if ($validator->fails()) {
            return response()->json(['check'=>false,'msg'=>$validator->errors()->first()]);
        }
        $data= $request->all();
        if($request->has('status')){
            User::where('id',$id)->update(['status'=>$request->status,'updated_at'=>now()]);
        }else{
            User::where('id',$id)->update($data);
        }

        $users = User::with('roles')->get();
        return response()->json(['check'=> true,'data'=>$users]);
    }
    /**
     * Show the form for editing the specified resource.
     */
    public function RegisterWithEmail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email'=>'required|email|unique:users,email',
        ]);
        if ($validator->fails()) {
            return response()->json(['check'=>false,'msg'=>$validator->errors()->first()]);
        }
        $data = $request->all();
        $password = random_int(10000, 99999);
        $data['password'] = Hash::make($password);
        $data['name']=$request->email;
        $data['idRole']=2;
        User::create($data);
        $data = [
            'name'=>$request->name,
            'email' => $request->email,
            'password' => $password,
        ];
        Mail::to($request->email)->send(new Createuser($data));
        $finduser =User::where('email', $request->email)->first();
        $token = $finduser->createToken('user')->plainTextToken;
        return response()->json(['check' => true, 'token' => $token,'id'=>Auth::id()]);
    }
       /**
     * Remove the specified resource from storage.
     */
    public function LoginWithEmail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email'=>'email|exists:users,email',
        ]);
        if ($validator->fails()) {
            return response()->json(['check'=>false,'msg'=>$validator->errors()->first()]);
        }
        $finduser =User::where('email', $request->email)->where('status',1)->first();
        if(!$finduser){
            return response()->json(['check' => false, 'msg' => 'Tài khoản bị khoá. Vui lòng liên hệ admin']);
        }
        $token = $finduser->createToken('user')->plainTextToken;
        return response()->json(['check' => true, 'token' => $token,'id'=>Auth::id()]);
    }
    /**
     * Remove the specified resource from storage.
     */
    public function destroy($identifier)
    {
      User::where('id',$identifier)->delete();
      $data= $this->model::with('roles')->get();
      return response()->json(['check'=>true,'data'=> $data],200);

    }
    public function LoginIndex(){
        return Inertia::render('Users/Login');
    }

    public function Logout(){
        Auth::logout();
        return redirect()->route('login'); 
    }
    public function Login(Request $request){
        $validator = Validator::make($request->all(), [
            'email'=>'required|email|exists:users,email',
            'password'=>'required',
        ]);
        if ($validator->fails()) {
            return response()->json(['check'=>false,'msg'=>$validator->errors()->first()]);
        }
        if(Auth::attempt(['email' => $request->email, 'password' => $request->password,'status'=>1],true)){
            $token = $request->user()->createToken('user');
            $token=$token->plainTextToken;
            return response()->json(['check'=>true,'token'=>$token,'id'=>Auth::id()]);
        }else{
            return response()->json(['check'=>false,'msg'=>'Sai email hoặc mật khẩu']);
        }
    }
    public function checkLoginEmailAdmin(Request $request){
        $validator = Validator::make($request->all(), [
            'email'=>'required|email|exists:users,email',
        ]);
        if ($validator->fails()) {
            return response()->json(['check'=>false,'msg'=>$validator->errors()->first()]);
        }
            $user = User::where('email',$request->email)->where('status',1)->first();
            if ($user) {
                // Log in the user directly with only the email (without password)
                Auth::login($user);
        
                // Return success response
                return response()->json(['check' => true]);
            } else {
                // Return failure response if no user is found
                return response()->json(['check' => false, 'msg' => 'Login failed. User not found.']);
            }
    }
    public function checkLoginAdmin1(Request $request){
        $validator = Validator::make($request->all(), [
            'email'=>'required|email|exists:users,email',
            'password'=>'required',
        ]);
        if ($validator->fails()) {
            return response()->json(['check'=>false,'msg'=>$validator->errors()->first()]);
        }
        $user = User::where('email',$request->email)->first();
        if ($user) {
            // Log in the user directly with only the email (without password)
            if(Auth::attempt(['email' => $request->email, 'password' => $request->password,'status'=>1],1)){
                return response()->json(['check'=>true]);
            }
            // Return success response
            return response()->json(['check' => true]);
        } else {
            // Return failure response if no user is found
            return response()->json(['check' => false, 'msg' => 'Login failed. User not found.']);
        }
    }
    public function register (Request $request){
        $validator = Validator::make($request->all(), [
            'name'=>'required',
            'email'=>'required|email|unique:users,email',
        ]);
        if ($validator->fails()) {
            return response()->json(['check'=>false,'msg'=>$validator->errors()->first()]);
        }
        $data = $request->all();
        $password = random_int(10000, 99999);
        $data['password'] = Hash::make($password);
        $data['idRole']=2;
        User::create($data);
        $data = [
            'name'=>$request->name,
            'email' => $request->email,
            'password' => $password,
        ];
        Mail::to($request->email)->send(new Createuser($data));
        $users = $this->model::with('roles')->get();
        return response()->json(['check' => true]);
    }
}
