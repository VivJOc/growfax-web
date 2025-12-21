<?php
$secret = "0x4AAAAAACH0OBdP8ao6pf1HNlY50PefYI0";
$response = $_POST['cf-turnstile-response'] ?? null;

if(!$response){
    echo "fail"; exit;
}

$verify = file_get_contents(
    "https://challenges.cloudflare.com/turnstile/v0/siteverify",
    false,
    stream_context_create([
        'http' => [
            'method'=>'POST',
            'header'=>"Content-type: application/x-www-form-urlencoded\r\n",
            'content'=>http_build_query(['secret'=>$secret,'response'=>$response])
        ]
    ])
);

$result = json_decode($verify,true);

if(!empty($result['success'])){
    echo "success";
}else{
    echo "fail";
}
