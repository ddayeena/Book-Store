<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With');

$servername = "127.0.0.1";
$username = "root";
$password = "";
$dbname = "bookstore";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die(json_encode(['error' => 'Connection failed: ' . $conn->connect_error]));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    $user_id = $data['user_id'];
    $cart_items = $data['cart_items'];
    $total_price = $data['total_price'];
    $deliveryType = $data['deliveryType'];
    $paymentType = $data['paymentType'];
    $town = $data['town'];
    $street = $data['street'];
    $street_number = $data['street_number'];
    $card = $data['card'];
    $last4Digits = substr($card, -4);

    $sql = "INSERT INTO delivery (town, street, street_number, type) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssis", $town, $street, $street_number, $deliveryType);
    $stmt->execute();
    $delivery_id = $stmt->insert_id;

    $sql = "INSERT INTO orders (user_id, total_price, delivery_id, payment_method,card) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("idiss", $user_id, $total_price, $delivery_id, $paymentType, $last4Digits);
    $stmt->execute();
    $order_id = $stmt->insert_id;

    $sql = "INSERT INTO order_details (order_id, book_id) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);

    foreach ($cart_items as $cart_item) {
        $book_id = $cart_item['book_id'];
        $stmt->bind_param("ii", $order_id, $book_id);
        $stmt->execute();
    }
    $cart_id =  $cart_items[0]['cart_id'];
    
    $sql = "DELETE FROM cart_details WHERE cart_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $cart_id);
    if ($stmt->execute()) {
        $sql = "UPDATE cart SET total_price = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $new_total_price=0;
        $stmt->bind_param("di", $new_total_price, $cart_id);
    
        if ($stmt->execute()) {
            echo json_encode(['message' => 'Замовлення оформлене успішно']);
        } else {
            echo json_encode(['message' => 'Помилка оновлення загальної суми']);
        }
    } else {
        echo json_encode(['message' => 'Помилка видалення книги з кошика']);
    }

    $stmt->close();
}
$conn->close();
?>
