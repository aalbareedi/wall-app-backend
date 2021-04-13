<?php
header( "Access-Control-Allow-Origin: http://localhost:3001" );
header( "Access-Control-Allow-Credentials: true" );
header( "Access-Control-Allow-Methods: POST, GET, DELETE, PUT" );
header( "Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept" );

session_set_cookie_params( ["samesite" => "None", "secure" => "true"] );
session_start( );

$usersFile = "../data/users.json";
$file = "../data/posts.json";

// Authorization
if( $_SERVER["REQUEST_METHOD"] != "GET" ){

    if( !isset( $_SESSION["id"] ) ){
        // header("HTTP/1.1 401 Unauthorized");
        $response = new stdClass( );
        $response->errors = ["Not logged in."];

        echo json_encode( $response );
        exit();
    }
}


if( $_SERVER["REQUEST_METHOD"] == "GET" ) {
    
    $response = new stdClass( );
    $response->posts = [];

    if( is_file( $file ) ){
        $posts = json_decode( file_get_contents( $file ) );
        $users = json_decode( file_get_contents( $usersFile ) );
        if( !$posts ){
            $posts = [];
        }

        for( $i = 0; $i < count( $posts ); $i++ ){
            $posts[$i]->user = $users[ $posts[$i]->userId - 1 ];
            unset( $posts[$i]->userId );
        }

        $response->posts = $posts;
    }

    echo json_encode($response);
}

if( $_SERVER["REQUEST_METHOD"] == "POST" ) {
    $response = new stdClass( );
    $response->errors = [];

    if( !is_file( $file ) ){
        file_put_contents( $file, "[]" );
    }
    $post = json_decode( file_get_contents( "php://input" ) );
    $post->userId = $_SESSION[ "id" ];

    if( $post->message == "" ){
        array_push( $response->errors, "Please enter a post message." );
    }

    if( count( $response->errors ) == 0 ){
        $posts = json_decode( file_get_contents( $file ) );
        if( !$posts ){
            $posts = [];
        }
        
        array_push( $posts, $post );

        for( $i = 0; $i < count( $posts ); $i++ ){
            $posts[$i]->id = $i+1;
        }

        file_put_contents( $file, json_encode( $posts ) );
    }

    echo json_encode($response);
}

// if( $_SERVER["REQUEST_METHOD"] == "PUT" ) {
//     if( is_file( $file ) ){
//         $data = json_decode( file_get_contents( "php://input" ) );
    
//         $posts = json_decode( file_get_contents( $file ), true );

//         $post = $posts[intval( $data->id )];

//         foreach ( $data as $key => $value ) {
//             $post[$key] = $value;
//         }

//         $posts[intval( $data->id )] = $post;
        
//         file_put_contents( $file, json_encode( $posts ) );
//     }
// }

if( $_SERVER["REQUEST_METHOD"] == "DELETE" ) {
    if( is_file( $file ) ){
        $response = new stdClass( );
        $response->errors = [];

        $post = json_decode( file_get_contents( "php://input" ) );
        $postIndex = $post->id-1;

        $posts = json_decode( file_get_contents( $file ) );

        if( $postIndex < 0 || $postIndex >= count($posts) ){
            array_push( $response->errors, "Specified post does not exist." );

        } else {
            $post = $posts[$postIndex];

            if( $post->userId != $_SESSION[ "id" ] ){

                header("HTTP/1.1 401 Unauthorized");
                array_push( $response->errors, "Unauthorized." );
    
            }
        }

        if( count( $response->errors ) == 0 ){
            array_splice( $posts, intval( $postIndex ), 1 );

            for( $i = 0; $i < count( $posts ); $i++ ){
                $posts[$i]->id = $i+1;
            }

            file_put_contents( $file, json_encode( $posts ) );
        }

        echo json_encode( $response );
    }
}

?>