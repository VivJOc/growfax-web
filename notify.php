<?php
// ----------------------------------------------------
// KONFIGURASI PENTING
// ----------------------------------------------------
$discord_webhook_url = "https://discord.com/api/webhooks/1433499858441998346/flWTzMJFBtWQlwJO0zvY4a9WDGdK9bObKo2AEg2rHNlAlOH3tmT3kwkDhI0LAiv-KSf4";
$ipaymu_key         = "323B8368-487B-4EE2-B2F4-AF9371DA2D30"; 
$store_name         = "Growfax Official Role Shop";
$log_file           = "ipaymu_log.txt";

// Discord bot config
$discord_bot_token  = "MTQxODAyNDY5MTgyMTU4MDM2OQ.GUEHNu.eTA7ShZOtnVkz800LcQJIZzVHltwJeY_cpF6fY";
$discord_guild_id   = "1228763766762635376";

// Produk → Role ID mapping
$role_mapping = [
    'Growfax Role VIP' => '1427134706494996583',
    'Growfax Role VIP+' => '1427134850980384829',
    'Growfax Role Moderator' => '1427134964306280508',
    'Growfax Role Super Moderator' => '1427135140370448524',
    'Growfax Role Developer' => '1427135230464098354',
    'Growfax Role God' => '1427135503106572499',
    'Growfax Role God Max' => '1427135695461285989',
];

// ----------------------------------------------------
// AMBIL DATA POST DARI IPAYMU
// ----------------------------------------------------
$data = $_POST;

$trx_id      = isset($data['trx_id']) ? $data['trx_id'] : '';
$product     = isset($data['product']) ? $data['product'] : '';
$price       = isset($data['price']) ? $data['price'] : '';
$quantity    = isset($data['quantity']) ? $data['quantity'] : '';
$comments    = isset($data['comments']) ? $data['comments'] : '';
$buyer_name  = isset($data['buyer_name']) ? $data['buyer_name'] : 'N/A';
$buyer_email = isset($data['buyer_email']) ? $data['buyer_email'] : 'N/A';
$via         = isset($data['via']) ? $data['via'] : 'N/A';
$status      = isset($data['status']) ? $data['status'] : 'Unknown';
$notify      = isset($data['notify']) ? $data['notify'] : '';

// Ambil Discord ID dari komentar atau field tambahan
preg_match('/\b\d{17,19}\b/', $comments, $discord_id_match);
$discord_user_id = $discord_id_match[0] ?? null;

// ----------------------------------------------------
// VALIDASI SIGNATURE IPAYMU
// ----------------------------------------------------
$check_signature = hash('sha256', $trx_id.$product.$price.$quantity.$ipaymu_key);

if ($notify !== $check_signature) {
    file_put_contents($log_file, "[".date("Y-m-d H:i:s")."] Invalid signature: ".json_encode($data)."\n", FILE_APPEND);
    exit('invalid signature');
}

// ----------------------------------------------------
// LOG SEMUA TRANSAKSI
// ----------------------------------------------------
file_put_contents($log_file, "[".date("Y-m-d H:i:s")."] ".json_encode($data)."\n", FILE_APPEND);

// ----------------------------------------------------
// FUNGSI PENGIRIMAN NOTIFIKASI KE DISCORD
// ----------------------------------------------------
function send_discord_notification($webhook_url, $data) {
    $json_data = json_encode([
        "username" => "iPaymu Payment Bot",
        "avatar_url" => "https://i.imgur.com/GzB9WpG.png",
        "embeds" => [
            [
                "title" => "💰 Pembayaran Diterima - " . $data['product'],
                "description" => "**ID Transaksi:** `" . $data['trx_id'] . "`",
                "color" => 3066993,
                "fields" => [
                    ["name" => "Produk Dibeli", "value" => $data['product'], "inline" => true],
                    ["name" => "Jumlah Pembayaran", "value" => "Rp " . number_format($data['price'], 0, ',', '.') . " IDR", "inline" => true],
                    ["name" => "Dibayar Melalui", "value" => $data['via'], "inline" => true],
                    ["name" => "Nama Pembeli", "value" => $data['buyer_name'], "inline" => true],
                    ["name" => "Email Pembeli", "value" => $data['buyer_email'], "inline" => true],
                    ["name" => "Discord ID", "value" => $data['discord_user_id'] ?? 'N/A', "inline" => true],
                    ["name" => "Keterangan (Comment)", "value" => $data['comments'], "inline" => false]
                ],
                "footer" => ["text" => $data['store_name'] . " | Status: " . $data['status'], "icon_url" => "https://i.imgur.com/GzB9WpG.png"],
                "timestamp" => date("c")
            ]
        ]
    ]);

    $ch = curl_init($webhook_url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-type: application/json'));
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $response = curl_exec($ch);
    curl_close($ch);
    return $response;
}

// ----------------------------------------------------
// FUNGSI ASSIGN ROLE DISCORD
// ----------------------------------------------------
function assign_discord_role($bot_token, $guild_id, $user_id, $role_id) {
    if (!$user_id || !$role_id) return false;

    $url = "https://discord.com/api/v10/guilds/$guild_id/members/$user_id/roles/$role_id";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bot $bot_token",
        "Content-Type: application/json"
    ]);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);
    return $response;
}

// ----------------------------------------------------
// CEK STATUS DAN EKSEKUSI
// ----------------------------------------------------
if ($status === 'berhasil') {
    $discord_data = [
        'product' => $product,
        'price' => $price,
        'trx_id' => $trx_id,
        'buyer_name' => $buyer_name,
        'buyer_email' => $buyer_email,
        'comments' => $comments,
        'discord_user_id' => $discord_user_id,
        'via' => $via,
        'status' => $status,
        'store_name' => $store_name
    ];

    send_discord_notification($discord_webhook_url, $discord_data);

    // Assign role otomatis
    if (isset($role_mapping[$product]) && $discord_user_id) {
        $role_id = $role_mapping[$product];
        assign_discord_role($discord_bot_token, $discord_guild_id, $discord_user_id, $role_id);
    }

    echo 'success'; // wajib ke iPaymu
} else {
    echo 'pending or failed'; // wajib ke iPaymu
}
?>
