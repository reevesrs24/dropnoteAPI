<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use DB;
use Hash;
use Auth;
use App\User;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class UserController extends Controller
{
    public function registerUser(Request $request)
    {


        $result = array();

    	$user = new User;
    	$user->username = $request->username;
        $user->phone_number = $request->phoneNumber;
    	$user->email = $request->email;
    	$user->password = Hash::make($request->password);

        if ( User::where('email', $request->email)->first()){ 

            $result['success'] = false;
            $result['message'] = "Email is already taken";
            return response()->json($result);

        } else if ( User::where('username', $request->username)->first()){

            $result['success'] = false;
            $result['message'] = "Username is already taken";
            return response()->json($result);

        } else if ( User::where('phone_number', $request->phoneNumber)->first()){
            $result['success'] = false;
            $result['message'] = "Phone number is already taken";
            return response()->json($result);

        }else {

            $result['success'] = true;
            $result['message'] = "User has been added";
            $user->save();
            $credentials = $request->only('username', 'password');
            $result['token'] = JWTAuth::attempt($credentials);
            
            return response()->json($result);
            
        }

    	   
    }


    public function findUserByUsername(Request $request)
    {

        $result = array();

        if ( User::where('username', $request->username)->first()){

                $result['success'] = true;
                $result['message'] = "Found Username";
                $result['user_info'] = DB::select('SELECT id FROM users WHERE username = ?', [$request->username]);
                return response()->json($result);

        } else {

                $result['success'] = false;
                $result['message'] = "Username Not Found";
                return response()->json($result);
        }



    }

     public function findUserByPhoneNumber(Request $request)
    {

        $result = array();

        if ( User::where('phone_number', $request->phoneNumber)->first()){

                $result['success'] = true;
                $result['message'] = "Found phone number";
                $result['user_info'] = DB::select('SELECT id FROM users WHERE phone_number = ?', [$request->phoneNumber]);
                return response()->json($result);

        } else {

                $result['success'] = false;
                $result['message'] = "Phone number Not Found";
                return response()->json($result);
        }



    }


    public function addToFriends(Request $request)
    {

         $result = array();
         $friends = array();

         $user = JWTAuth::parseToken()->toUser();
         //response()->json(compact('user'));

        if (!empty($request->friendUsername)){
                
                //Check to See If the User exists
                $friendId = DB::select('SELECT id FROM users WHERE username = ?', [$request->friendUsername]);
                $userId = DB::select('SELECT id FROM users WHERE username = ?', [$user->username]);

                        if ($friendId == NULL){
                            $result['success'] = false;
                            $result['message'] = "This User Does Not Exist";
                            return response()->json($result);
                        }
                        
                        //Retrieve all friends associated with that Id
                        $friends = DB::select('SELECT friend_id FROM friends WHERE user_id = ?', [$userId[0]->id]);
                        
                        for ($i = 0; $i < count($friends); $i++){
                            if ( $friends[$i]->friend_id == $friendId[0]->id){
                                $result['success'] = false;
                                $result['message'] = "Friend Has Already Been Added";
                                $result['token'] = $token = JWTAuth::fromUser($user);
                                return response()->json($result);
                            }
                        }

                        DB::table('friends')->insert(
                                ['username' => $user->username, 'user_id' => $userId[0]->id, 'friend_id' => $friendId[0]->id, 'friend_username' => $request->friendUsername]
                            );

                            $result['success'] = true;
                            $result['message'] = "Added Friend";
                            $result['token'] = $token = JWTAuth::fromUser($user);
                            return response()->json($result);
                    } else {

                    $result['success'] = false;
                    $result['message'] = "Could Not Add Friend";
                    return response()->json($result);
                }
        
             
    }

    public function getFriends(Request $request)
    {

        $result = array();
        $friends = array();
        $friends_ids = array();

        $user = JWTAuth::parseToken()->toUser();

        $userId = DB::select('SELECT id FROM users WHERE username = ?', [$user->username]);

        if (empty($user->username)){
            $result['success'] = false;
            $result['message'] = "No Such Username";
            return response()->json($result);
        } else {
            $friends = DB::select('SELECT DISTINCT friend_username, friend_id FROM friends WHERE user_id = ?', [$userId[0]->id]);
            $result['success'] = true;
            $result['friends'] = $friends;
            return response()->json($result);
        }
    }

    public function deleteFriend(Request $request)
    {
        $result = array();
        $user = JWTAuth::parseToken()->toUser();

        //Check if the friend username posted is actually a friend
        $isFriend = DB::select('SELECT friend_id FROM friends WHERE friend_username = ? AND username = ?', [$request->delete_friend, $user->username]);

        if (empty($isFriend)){
            $result['success'] = false;
            $result['message'] = "This username is not a friend";
            return response()->json($result);
        } else {
            DB::table('friends')->where('friend_username', '=', [$request->delete_friend])->delete();
            $result['success'] = true;
            $result['friend_deleted'] = $request->delete_friend;
            return response()->json($result);
        }


    }



    

}