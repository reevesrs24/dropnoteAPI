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

        if (User::where('username', $request->username)->first()) {

        	$result['success'] = false;
            $result['message'] = "This username is already taken";
            return response()->json($result);

        }   else if (User::where('email', $request->email)->first()) { 

            $result['success'] = false;
            $result['message'] = "This email is already taken";
            return response()->json($result);

        }   else if (User::where('phone_number', $request->phoneNumber)->first() || $request->phoneNumber == null) {

            $result['success'] = false;
            $result['message'] = "This phone number is already taken ";
            return response()->json($result);

        }   else if (!ctype_digit($request->phoneNumber)) {

        	$result['success'] = false;
        	$result['message'] = "Phone number should contain only digits";
        	return response()->json($result);

        }   else if (($request->password != $request->verify_password) || $request->password == null) {

            $result['success'] = false;
            $result['message'] = "Passwords do not match";
            return response()->json($result);

        }   else {
            $user->save();

            $credentials = $request->only('username', 'password');
            $result['success'] = true;
            $result['message'] = "User has been added";
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
                $result['username'] = $request->username;
                $id = DB::select('SELECT id FROM users WHERE username = ?', [$request->username]);
                $result['id'] = $id[0]->id;

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
                $id = DB::select('SELECT id FROM users WHERE phone_number = ?', [$request->phoneNumber]);
                $username = DB::select('SELECT username FROM users WHERE phone_number = ?', [$request->phoneNumber]);
                $result['id'] = $id[0]->id;
                $result['username'] = $username[0]->username;
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
                                return response()->json($result);
                            }
                        }

                        DB::table('friends')->insert(
                                ['username' => $user->username, 'user_id' => $userId[0]->id, 'friend_id' => $friendId[0]->id, 'friend_username' => $request->friendUsername]
                            );

                        //If already friend update added me column

                        $isFriend = DB::table('friends')->select('added_me')->where('friend_username', $user->username)->where('username', $request->friendUsername)->get();
                        //return $isFriend;

                        if (empty($isFriend)){
                            DB::table('friends')
                                        ->where('username', $user->username)
                                        ->where('friend_username', $request->friendUsername)
                                        ->update(['added_me' => "false"]);
                        } else if ($isFriend[0]->added_me == "false"){
                            DB::table('friends')
                                        ->where('username', $user->username)
                                        ->where('friend_username', $request->friendUsername)
                                        ->update(['added_me' => "true"]);
                            DB::table('friends')
                                        ->where('username', $request->friendUsername)
                                        ->where('friend_username', $user->username)
                                        ->update(['added_me' => "true"]);
                                
                        } else {
                            DB::table('friends')
                                        ->where('username', $user->username)
                                        ->where('friend_username', $request->friendUsername)
                                        ->update(['added_me' => "false"]);
                        }

                            $result['success'] = true;
                            $result['message'] = "Added Friend";
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

        if (empty($user->username)){
            $result['success'] = false;
            $result['message'] = "No Such Username";
            return response()->json($result);
        } else {
            
            $friends = DB::table('friends')->select(array('friend_username','friend_id'))->where('username', $user->username)->get();
            //$friends_ids = DB::table('friends')->select('friend_id')->where('username', $user->username)->get();

            $result['success'] = true;
            $result['friends'] = $friends;
            //$result['friends_ids'] =  $friends_ids;
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

            DB::table('friends')
                        ->where('username', $request->delete_friend)
                        ->where('friend_username', $user->username)
                        ->update(['added_me' => "false"]);
                                
                        
            $result['success'] = true;
            $result['friend_deleted'] = $request->delete_friend;
            return response()->json($result);
        }


    }

    public function findUsersWhoAddedMe(Request $request)
    {
        $result = array();
        $usersWhoAddedMe = array();
        $user = JWTAuth::parseToken()->toUser();

        $usersWhoAddedMe = DB::table('friends')->select('username')->where('friend_username', $user->username)->where('added_me', "false")->get();


        $result['success'] = true;
        $result['usersWhoHaveAddedYou'] = $usersWhoAddedMe;
        return response()->json($result);
        

    }

    

}