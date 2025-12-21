<?php
$secret = "0x4AAAAAACH0oOWwMmX3ne2TY0hqikOYzY4
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
