<?php

namespace App\Http\Controllers\Auth;

use App\Libs\Sso\CognitoAuthRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login', 'sso']]);
    }

    /**
     * Get a JWT token via given credentials.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if ($request->rememberMe) {
            $this->guard()->factory()->setTTL(30 * 24 * 60);
        }

        if ($token = $this->guard()->attempt($credentials)) {
            return $this->respondWithToken($token);
        }

        return response()->json(['error' => 'Unauthorized'], 401);
    }

    /**
     * Get the authenticated User
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function user()
    {
        return response()->json($this->guard()->user());
    }

    /**
     * Log the user out (Invalidate the token)
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        $this->guard()->logout();

        return response()->json(['message' => 'Successfully logged out']);
    }

    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh()
    {
        return $this->respondWithToken($this->guard()->refresh());
    }

    /**
     * OpenID Connect: exchange code for token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function sso(Request $request)
    {
        $invoker = new CognitoAuthRequest();
        $tokenResponse = $invoker->invokeTokenRequest([
            'loginResult' => $request->all(),
            'appUrl'      => config('app.url')
        ]);

        $tokens = \json_decode(
            $tokenResponse->getBody(), true
        );

        $tokenParts = explode(".", $tokens['id_token']);
        $tokenPayload = base64_decode($tokenParts[1]);
        $jwtPayload = json_decode($tokenPayload, true);

        $user = User::where('email', '=', $jwtPayload['email'])->first();
        if($user) {
            $token = $this->guard()->login($user);
            return $this->respondWithToken($token);
        } else {
            $user = new User();
            $user->type_id = 2;
            $user->name = $jwtPayload['username'];
            $user->email = $jwtPayload['email'];
            $user->password = Hash::make(Str::random(12));
            $user->save();

            $token = $this->guard()->login($user);
            if ($request->rememberMe) {
                $this->guard()->factory()->setTTL(env('JWT_TTL', 7*24*3600));
            }
            return $this->respondWithToken($token);
        }
    }

    /**
     * Get the token array structure.
     *
     * @param  string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondWithToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => $this->guard()->factory()->getTTL() * 60
        ])
        ->header('Authorization', $token);
    }

    /**
     * Get the guard to be used during authentication.
     *
     * @return \Illuminate\Contracts\Auth\Guard
     */
    public function guard()
    {
        return Auth::guard('api');
    }
}
