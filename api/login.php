<?php
include_once( "../config.php" );

header( "Access-Control-Allow-Origin: " . FRONT_END_URL );
header( "Access-Control-Allow-Credentials: true" );
header( "Access-Control-Allow-Methods: GET, DELETE, PUT" );
header( "Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept" );

session_set_cookie_params( ["samesite" => "None", "secure" => "true"] );
session_start( );

$file = "../data/users.json";

if( $_SERVER["REQUEST_METHOD"] == "GET" ) {
    $response = new stdClass( );
    $response->errors = [];

    if( empty( $_SESSION["id"] ) ){
        $response->user = null;
    } else {
        if( !is_file( $file ) ){
            file_put_contents( $file, "[]" );
        }
        $users = json_decode( file_get_contents( $file ) );
        if( !$users ){
            $users = [];
        }
        
        $user = $users[$_SESSION["id"] - 1];

        // CRITICAL: Throw away secret data
        unset( $user->password );
        unset( $user->passwordHash );

        $response->user = $user;
    }

    echo json_encode( $response );
}

if( $_SERVER["REQUEST_METHOD"] == "DELETE" ) {
    $response = new stdClass( );
    $response->errors = [];

    unset( $_SESSION["id"] );

    echo json_encode( $response );
}

if( $_SERVER["REQUEST_METHOD"] == "PUT" ) {
    $response = new stdClass( );
    $response->errors = [];

    if( !is_file( $file ) ){
        file_put_contents( $file, "[]" );
    }
    $user = json_decode( file_get_contents( "php://input" ) );

    $users = json_decode( file_get_contents( $file ) );
    if( !$users ){
        $users = [];
    }

    // Input validation
    if( empty( $user->email ) ){
        array_push( $response->errors, "Please provide an email." );

    } else if( empty( $user->password ) ){
        array_push( $response->errors, "Please provide a password." );

    } else {
        $emailFound = false;

        for( $i = 0; $i < count( $users ); $i++ ){
            if( $user->email == $users[$i]->email ){
                $emailFound = true;
                if( !password_verify( $user->password, $users[$i]->passwordHash ) ){
                    // Wrong password
                    array_push( $response->errors, "Incorrect email/password." );
                } else {
                    $user = $users[$i];
                }
                break;
            }
        }

        if( !$emailFound ){
            // Email not found
            array_push( $response->errors, "Incorrect email/password." );
        }
    }

    // No errors so far
    if( count( $response->errors ) == 0 ){

        // CRITICAL: Throw away secret data
        unset( $user->password );
        unset( $user->passwordHash );

        $_SESSION["id"] = $user->id;

        $response->user = $user;
    }

    echo json_encode( $response );
}

?>