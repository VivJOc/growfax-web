<?php

// ----------------------------------------------------
// KONFIGURASI PENTING
// ----------------------------------------------------

// 1. GANTI DENGAN WEBHOOK URL DISCORD ANDA
$discord_webhook_url = "https://discord.com/api/webhooks/1433499858441998346/flWTzMJFBtWQlwJO0zvY4a9WDGdK9bObKo2AEg2rHNlAlOH3tmT3kwkDhI0LAiv-KSf4";

// 2. GANTI DENGAN KEY IPAYMU ANDA (Sama dengan yang di HTML)
// Key iPaymu: 323B8368-487B-4EE2-B2F4-AF9371DA2D30
$ipaymu_key = "323B8368-487B-4EE2-B2F4-AF9371DA2D30"; 

// 3. Nama Toko untuk tampilan di Discord
$store_name = "Growfax Official Role Shop";


// ----------------------------------------------------
// PENGOLAHAN NOTIFIKASI DARI IPAYMU
// ----------------------------------------------------

// Ambil data POST dari iPaymu
$data = $_POST;

// Status Transaksi
$status = isset($data['status']) ? $data['status'] : 'Unknown';

// Detail Transaksi
$trx_id = isset($data['trx_id']) ? $data['trx_id'] : 'N/A';
$product = isset($data['product']) ? $data['product'] : 'N/A';
$price = isset($data['price']) ? $data['price'] : 'N/A';
$comments = isset($data['comments']) ? $data['comments'] : 'N/A';
$reference_id = isset($data['reference_id']) ? $data['reference_id'] : 'N/A';
$buyer_name = isset($data['buyer_name']) ? $data['buyer_name'] : 'N/A';
$buyer_email = isset($data['buyer_email']) ? $data['buyer_email'] : 'N/A';
$via = isset($data['via']) ? $data['via'] : 'N/A';


// ----------------------------------------------------
// FUNGSI PENGIRIMAN KE DISCORD
// ----------------------------------------------------

function send_discord_notification($webhook_url, $data) {
    
    // Siapkan data JSON untuk Discord Webhook
    $json_data = json_encode([
        "username" => "iPaymu Payment Bot",
        "avatar_url" => "https://i.imgur.com/GzB9WpG.png", // Contoh avatar logo iPaymu
        "embeds" => [
            [
                "title" => "💰 Pembayaran Diterima - " . $data['product'],
                "description" => "**ID Transaksi:** `" . $data['trx_id'] . "`",
                "color" => 3066993, // Hijau (Warna sukses)
                "fields" => [
                    [
                        "name" => "Produk Dibeli",
                        "value" => $data['product'],
                        "inline" => true
                    ],
                    [
                        "name" => "Jumlah Pembayaran",
                        "value" => "Rp " . number_format($data['price'], 0, ',', '.') . " IDR",
                        "inline" => true
                    ],
                    [
                        "name" => "Dibayar Melalui",
                        "value" => $data['via'],
                        "inline" => true
                    ],
                    [
                        "name" => "Nama Pembeli",
                        "value" => $data['buyer_name'],
                        "inline" => true
                    ],
                    [
                        "name" => "Email Pembeli",
                        "value" => $data['buyer_email'],
                        "inline" => true
                    ],
                    [
                        "name" => "Keterangan (Comment)",
                        "value" => $data['comments'],
                        "inline" => false
                    ]
                ],
                "footer" => [
                    "text" => $data['store_name'] . " | Status: " . $data['status'],
                    "icon_url" => "https://i.imgur.com/GzB9WpG.png"
                ],
                "timestamp" => date("c")
            ]
        ]
    ]);

    // Kirim data menggunakan cURL
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
// CEK STATUS DAN EKSEKUSI
// ----------------------------------------------------

// iPaymu mengirim status "berhasil" untuk pembayaran sukses
if ($status == 'berhasil') {
    
    // Siapkan data untuk dikirim ke Discord
    $discord_data = [
        'product' => $product,
        'price' => $price,
        'trx_id' => $trx_id,
        'buyer_name' => $buyer_name,
        'buyer_email' => $buyer_email,
        'comments' => $comments,
        'via' => $via,
        'status' => $status,
        'store_name' => $store_name
    ];
    
    // Kirim notifikasi
    send_discord_notification($discord_webhook_url, $discord_data);
    
    // Tambahkan logika lain di sini, misalnya:
    // 1. Mencatat transaksi ke database.
    // 2. Memberikan Role/Item secara otomatis kepada pembeli (jika Anda tahu Discord ID-nya dari input form).

    // Wajib: Beri respons OK ke iPaymu
    echo 'success'; 

} else {
    // Jika status gagal (pending/gagal/dll), catat atau abaikan
    // Opsional: Anda bisa mengirim notifikasi kegagalan ke webhook lain
    
    // Wajib: Beri respons OK ke iPaymu
    echo 'failed or not processed status'; 
}

?>
