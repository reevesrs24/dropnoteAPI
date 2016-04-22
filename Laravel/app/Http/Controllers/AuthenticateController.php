<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class AuthenticateController extends Controller
{
    public function authenticate(Request $request)
    {
    	$result = array();

        $credentials = $request->only('username', 'password');

        try {
            // verify the credentials and create a token for the user
            if (! $token = JWTAuth::attempt($credentials)) {
                 $result['success'] = false;
            	 $result['message'] = "Username or Password is Incorrect";
            	 return response()->json($result);
            }
        } catch (JWTException $e) {
            $result['success'] = false;
            $result['message'] = "Error Cannot Create Token";
            return response()->json($result);
        }

        // if no errors are encountered return a JWT
        $result['success'] = true;
        $result['message'] = "Authenticated";
        $result += compact('token');
        return response()->json($result);
    }
}
