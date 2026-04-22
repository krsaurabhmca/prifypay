<?php 
include_once('header.php');

// ================= DEFAULT VALUES =================
$default = [
    "amount" => "100.00",
    "gateway_id" => "9",
    "name" => "Kumar Saurabh",
    "email" => "myofferplant@gmail.com",
    "phone" => "9431426600"
];

// ================= GATEWAY LIST =================
$gateways = [
    ["id"=>1,"name"=>"Razorpay Utility"],
    ["id"=>3,"name"=>"Slpe Silver Prime EDU"],
    ["id"=>4,"name"=>"Slpe Gold Travel Lite"],
    ["id"=>6,"name"=>"Slpe Gold Travel"],
    ["id"=>7,"name"=>"Slpe Gold Travel Prime"],
    ["id"=>8,"name"=>"Slpe Silver Edu Lite"],
    ["id"=>9,"name"=>"Slpe Gold Travel Pure"],
    ["id"=>14,"name"=>"SLPE GOLD TARVEL FAST"],
    ["id"=>15,"name"=>"SLPE OCEAN PAY"],
    ["id"=>17,"name"=>"SLPE Marine Pay"],
    ["id"=>19,"name"=>"SLPE FAST PAY"]
];

// ================= FORM SUBMIT =================
if(isset($_POST['pay_now'])){

    $amount = number_format((float)$_POST['amount'], 2, '.', '');

    if($amount <= 0){
        die("Invalid Amount");
    }

    $data = [
        "amount" => $amount,
        "call_back_url" => "https://prifypay.morg.in/pay_status.php",
        "redirection_url" => "https://prifypay.morg.in/payout.php",
        "gateway_id" => $_POST['gateway_id'],
        "payment_link_expiry" => date("Y-m-d H:i:s", strtotime("+24 hour")),
        "payment_for" => "Utility Payment",
        "customer" => [
            "name" => $_POST['name'],
            "email" => $_POST['email'],
            "phone" => $_POST['phone']
        ],
        "mode" => [
            "netbanking" => true,
            "card" => true,
            "upi" => true,
            "wallet" => false
        ]
    ];

    // ================= API CALL =================
    $response = callAPI("POST", "create-order", $data);

    // LOG
    file_put_contents("callback_log.txt", "Create Payment ". date("Y-m-d H:i:s") . "\n" . json_encode($response) . "\n\n", FILE_APPEND);

    // ================= REDIRECT =================
    if(isset($response['data']['payment_url'])){
        header("Location: ".$response['data']['payment_url']);
        exit;
    } else {
        echo "<pre>";
        print_r($response);
        die("Payment Failed");
    }
}
?>

<!-- ================= UI FORM ================= -->
<center>
    
    <h2>Balance : 
    <?php
$res2 = callAPI("GET", "balance-check");
echo $res2['data']['data']['wallet_balance'];
?>
</h2>
<form method="post">

    <h3>Make Payment</h3>
    
    <!-- Amount -->
    <label>Amount</label>
    <input 
        type="number" 
        name="amount" 
        step="0.01"
        min="1"
        value="<?= $default['amount'] ?>"
        required
    ><br><br>

    <!-- Gateway -->
    <label>Select Gateway</label>
    <select name="gateway_id" required>
        <?php foreach($gateways as $g){ ?>
            <option value="<?= $g['id'] ?>" <?= ($g['id']==$default['gateway_id'])?'selected':'' ?>>
                <?= $g['name'] ?> (<?= $g['id'] ?>)
            </option>
        <?php } ?>
    </select><br><br>
    <!-- Customer -->
    <label>Name</label>
    <input type="text" name="name" value="<?= $default['name'] ?>" required><br><br>

    <label>Email</label>
    <input type="email" name="email" value="<?= $default['email'] ?>" required><br><br>

    <label>Phone</label>
    <input type="text" name="phone" value="<?= $default['phone'] ?>" required><br><br>

    <!-- Submit -->
    <button type="submit" name="pay_now">Pay Now</button>

</form>
</center>