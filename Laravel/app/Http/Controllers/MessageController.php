<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use DB;
use Carbon;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class MessageController extends Controller
{

    public function postMessage(Request $request)
    {
    	$result = array();

        //Will post an anonymous public message in the specified area
        if ($request->userPostType == "anonymous"){
                DB::table('messages')->insert(
                     ['username' => "Anonymous", 'user_id' => "", 'email' => "NULL", 'message' => $request->message, 'message_permission' => $request->messagePermissions,
                            'latitude' => $request->latitude, 'longitude' => $request->longitude, 'timestamp' => Carbon\Carbon::now()]
                    );

                $result['success'] = true;
                $result['message'] = "Created Message";
                return response()->json($result);

        } else if ($request->userPostType == "username") {

            
            	    $user = JWTAuth::parseToken()->toUser();

                    //Messages sent as private will be only be availabe to specific friends which the user has specified by if
                	if (!empty($user->username) && $request->messagePermissions == "private"){

                        $friends_ids = array();
                        $friends_ids = json_decode($request->friends, true);
                       //return $friends_ids;
                        for ($i = 0; $i < count($friends_ids); $i++){
                            
                            DB::table('friend_messages')->insert(
                                 ['username' => $user->username, 'user_id' => $user->id, 'email' => $user->email, 'message' => $request->message, 'id_message_permission' => $friends_ids[$i]['id'],
                                        'latitude' => $request->latitude, 'longitude' => $request->longitude, 'timestamp' => Carbon\Carbon::now()]
                                );
                            }
                        

                        $result['success'] = true;
                        $result['message'] = "Created Message";
                        return response()->json($result);
                    } else if (!empty($user->username)){  //If the user has selected the message permissions to be public then the username will be shown along with the message
                                       
                        DB::table('messages')->insert(
                             ['username' => $user->username, 'user_id' => $user->id, 'email' => $user->email, 'message' => $request->message, 'message_permission' => $request->messagePermissions,
                                    'latitude' => $request->latitude, 'longitude' => $request->longitude, 'timestamp' => Carbon\Carbon::now()]
                            );


                        $result['success'] = true;
                        $result['message'] = "Created Message";
                        return response()->json($result);
                    }
        } else {

    		$result['success'] = false;
            $result['message'] = "Could Not Add Message";
            return response()->json($result);
    	}

    }

    public function getAllMessages(Request $request)
    {
    	$userLatitudeFloor = $request->userLatitude - .0002;
        $userLatitudeCeil = $request->userLatitude + .0002;

        $userLongitudeFloor = $request->userLongitude + .0002;
        $userLongitudeCeil = $request->userLongitude - .0002;


        $messages = array();
        $messageCount = 0;

        $messages += DB::select("SELECT * FROM messages WHERE (( latitude BETWEEN  '$userLatitudeFloor'  AND '$userLatitudeCeil'  )) 
            AND (( longitude BETWEEN  '$userLongitudeCeil'  AND  '$userLongitudeFloor' )) AND message_permission = 'public' ORDER BY timestamp DESC" );
        
        /* Distance Between Two GPS coordinates Formula (Haversine Formula)

        $userLongitude = deg2rad($request->userLongitude);
        $userLatitude = deg2rad($request->userLatitude);

        $messageLatitude = array();

        $postedMessages = DB::select('SELECT * FROM messages');
    	$messageLongitude = DB::select('SELECT longitude FROM messages');
    	$messageLatitude = DB::select('SELECT latitude FROM messages');

    	//Compute Distance between the user and stores GPS coordinates using Haversine formuls
    	for ($i = 0; $i < count($messageLongitude); $i++){

    		$deltaLongitude = $userLongitude - deg2rad($postedMessages[$i]->longitude);
    		$deltaLatitude = $userLatitude - deg2rad($postedMessages[$i]->latitude);

    		$angle = 2 * asin(sqrt(pow(sin($deltaLatitude / 2), 2) +
    					cos($userLatitude) * cos(deg2rad($postedMessages[$i]->latitude)) * pow(sin($deltaLongitude / 2), 2)));

    		//6373 is the Earth's radius in kn
    		$distance = ($angle * 6371);

            //echo $distance;
            
    	}   
        */   
        return response()->json($messages);
    }

    public function getFriendsMessages(Request $request)
    {
        $userLatitudeFloor = $request->userLatitude - .0002;
        $userLatitudeCeil = $request->userLatitude + .0002;

        $userLongitudeFloor = $request->userLongitude + .0002;
        $userLongitudeCeil = $request->userLongitude - .0002;

        $user = JWTAuth::parseToken()->toUser();

        $friends = array();
        $friendsMessages = array();
        

        $friendsMessages += DB::select("SELECT * FROM friend_messages WHERE (( latitude BETWEEN  '$userLatitudeFloor'  AND '$userLatitudeCeil'  )) 
                            AND (( longitude BETWEEN  '$userLongitudeCeil'  AND  '$userLongitudeFloor' )) AND id_message_permission = ? 
                            ORDER BY timestamp DESC", [$user->id]);
        
        return response()->json($friendsMessages);


    }




}
