
<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'config.php';
require_once 'controller.php';

// Kiểm tra và tải PHPMailer
$phpmailer_dir = 'PHPMailer/PHPMailer/src/';
if (!file_exists($phpmailer_dir . 'PHPMailer.php')) {
    die('File PHPMailer.php không tồn tại. Vui lòng kiểm tra đường dẫn: ' . htmlspecialchars($phpmailer_dir));
}
require_once $phpmailer_dir . 'PHPMailer.php';
require_once $phpmailer_dir . 'SMTP.php';
require_once $phpmailer_dir . 'Exception.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Cờ để kiểm tra xem form đã được gửi thành công
$payment_submitted = false;

// Kiểm tra vai trò người dùng
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header("Location: login.php");
    exit;
}
checkRole(['customer', 'staff']);

// Xử lý xác nhận thanh toán từ customer
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['confirm_payment']) && $_SESSION['role'] == 'customer') {
    $booking_id = (int)($_POST['booking_id'] ?? 0);
    $amount = floatval($_POST['amount'] ?? 0);
    $payment_method = $_POST['payment_method'] ?? '';

    $proof_image = '';
    if ($payment_method == 'bank_transfer' && isset($_FILES['proof_image']) && $_FILES['proof_image']['error'] == 0) {
        $target_dir = "Uploads/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $target_file = $target_dir . uniqid() . '_' . basename($_FILES["proof_image"]["name"]);
        if (move_uploaded_file($_FILES["proof_image"]["tmp_name"], $target_file)) {
            $proof_image = $target_file;
        } else {
            $_SESSION['message'] = '<div class="alert alert-danger">Lỗi khi tải lên bằng chứng.</div>';
        }
    }

    $sql = "INSERT INTO payments (booking_id, user_id, amount, payment_method, proof_image, status, payment_date) VALUES (?, ?, ?, ?, ?, 'pending', NOW())";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        $_SESSION['message'] = '<div class="alert alert-danger">Lỗi prepare: ' . htmlspecialchars($conn->error) . '</div>';
    } else {
        $user_id = $_SESSION['user_id'];
        $stmt->bind_param("iisss", $booking_id, $user_id, $amount, $payment_method, $proof_image);
        if ($stmt->execute()) {
            $_SESSION['message'] = '<div class="alert alert-success">Thanh toán đã được gửi và đang chờ xác nhận.</div>';
            $payment_submitted = true;
            // Gửi email xác nhận
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'phamcongthanh0801@gmail.com';
                $mail->Password = 'qxru sgrg gjhl mmmu';
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;

                $sql_email = "SELECT email FROM users WHERE id = ?";
                $stmt_email = $conn->prepare($sql_email);
                $stmt_email->bind_param("i", $user_id);
                $stmt_email->execute();
                $result_email = $stmt_email->get_result();
                $customer_email = $result_email->num_rows > 0 ? $result_email->fetch_assoc()['email'] : 'default_email@example.com';
                $stmt_email->close();

                $mail->setFrom('phamcongthanh0801@gmail.com', 'Hotel Management');
                $mail->addAddress($customer_email);
                $mail->Subject = 'Xác nhận thanh toán';
                $mail->Body = "Cảm ơn bạn! Thanh toán cho đặt phòng #$booking_id đã được gửi và đang chờ xác nhận. Số tiền: " . number_format($amount, 2) . " USD.";
                $mail->send();
            } catch (Exception $e) {
                $_SESSION['message'] .= '<br><div class="alert alert-danger">Lỗi gửi email: ' . htmlspecialchars($e->getMessage()) . '</div>';
                error_log("Lỗi gửi email: " . $e->getMessage());
            }
        } else {
            $_SESSION['message'] = '<div class="alert alert-danger">Lỗi khi lưu thanh toán: ' . htmlspecialchars($stmt->error) . '</div>';
        }
        $stmt->close();
    }
}

// Xử lý xác nhận thanh toán từ staff
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['confirm_bank_payment']) && $_SESSION['role'] == 'staff') {
    $payment_id = (int)($_POST['payment_id'] ?? 0);
    $sql = "UPDATE payments SET status = 'paid' WHERE id = ? AND status = 'pending'";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        $_SESSION['message'] = '<div class="alert alert-danger">Lỗi prepare: ' . htmlspecialchars($conn->error) . '</div>';
    } else {
        $stmt->bind_param("i", $payment_id);
        if ($stmt->execute()) {
            $_SESSION['message'] = '<div class="alert alert-success">Thanh toán đã được xác nhận thành công.</div>';

            // Lấy thông tin booking và email khách hàng
            $sql_booking = "SELECT b.id, u.email FROM payments p 
                            JOIN bookings b ON p.booking_id = b.id 
                            JOIN users u ON b.user_id = u.id 
                            WHERE p.id = ?";
            $stmt_booking = $conn->prepare($sql_booking);
            $stmt_booking->bind_param("i", $payment_id);
            $stmt_booking->execute();
            $result = $stmt_booking->get_result();
            if ($row = $result->fetch_assoc()) {
                $booking_id = $row['id'];
                $customer_email = $row['email'];

                // Gửi email thông báo cho customer
                $mail = new PHPMailer(true);
                try {
                    $mail->isSMTP();
                    $mail->Host = 'smtp.gmail.com';
                    $mail->SMTPAuth = true;
                    $mail->Username = 'phamcongthanh0801@gmail.com';
                    $mail->Password = 'qxru sgrg gjhl mmmu';
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port = 587;
                    $mail->setFrom('phamcongthanh0801@gmail.com', 'Hotel Management');

                    if (empty($customer_email)) {
                        $_SESSION['message'] .= '<br><div class="alert alert-warning">Không tìm thấy email khách hàng để gửi thông báo.</div>';
                    } else {
                        $mail->addAddress($customer_email);
                        $mail->Subject = 'Thanh toán thành công';
                        $mail->Body = "Chúc mừng bạn! Thanh toán cho đặt phòng #$booking_id đã được xác nhận thành công.";
                        $mail->send();
                        $_SESSION['message'] .= '<br><div class="alert alert-info">Email thông báo đã được gửi đến khách hàng.</div>';
                    }
                } catch (Exception $e) {
                    $_SESSION['message'] .= '<br><div class="alert alert-danger">Lỗi gửi email: ' . htmlspecialchars($e->getMessage()) . '</div>';
                    error_log("Lỗi gửi email: " . $e->getMessage());
                }
            } else {
                $_SESSION['message'] .= '<br><div class="alert alert-warning">Không tìm thấy thông tin booking hoặc email khách hàng.</div>';
            }
            $stmt_booking->close();

            // Cập nhật trạng thái booking
            $sql_update_booking = "UPDATE bookings SET status = 'paid' WHERE id = (SELECT booking_id FROM payments WHERE id = ?)";
            $stmt_update = $conn->prepare($sql_update_booking);
            $stmt_update->bind_param("i", $payment_id);
            $stmt_update->execute();
            $stmt_update->close();
        } else {
            $_SESSION['message'] = '<div class="alert alert-danger">Lỗi khi xác nhận thanh toán: ' . htmlspecialchars($stmt->error) . '</div>';
        }
        $stmt->close();
    }
    header("Location: booking.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý đặt phòng</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .table-responsive { margin-top: 20px; }
        .card { margin-bottom: 20px; }
        .chart-container { position: relative; height: 300px; margin-top: 20px; }
        .btn-custom { margin-right: 5px; }
        .modal-content { border-radius: 10px; }
        .payment-form { max-width: 500px; margin: 0 auto; }
        .bank-info { margin-top: 10px; padding: 10px; border: 1px solid #ccc; border-radius: 5px; }
        .qr-code { max-width: 150px; margin-top: 10px; }
    </style>
</head>
<body>
    <div class="container mt-4">
        <h2 class="text-center mb-4">Quản lý đặt phòng</h2>
        <a href="index.php" class="btn btn-secondary mb-3">Quay lại</a>

        <?php if (isset($_SESSION['message'])): ?>
            <?php echo $_SESSION['message']; ?>
            <?php unset($_SESSION['message']); ?>
        <?php endif; ?>

        <!-- Form xác nhận thanh toán (chỉ cho customer với booking chưa thanh toán) -->
        <?php if ($_SESSION['role'] == 'customer'): ?>
            <?php
            $sql_unpaid = "SELECT b.id, b.user_id, r.room_type, r.price, b.check_in, b.check_out, b.status,
                           (SELECT status FROM payments p WHERE p.booking_id = b.id ORDER BY p.payment_date DESC LIMIT 1) as payment_status
                           FROM bookings b 
                           JOIN rooms r ON b.room_id = r.id 
                           WHERE b.user_id = ? AND b.status IN ('pending', 'confirmed', 'paid')";
            $stmt_unpaid = $conn->prepare($sql_unpaid);
            if ($stmt_unpaid === false) {
                echo '<div class="alert alert-danger">Lỗi prepare unpaid: ' . htmlspecialchars($conn->error) . '</div>';
            } else {
                $stmt_unpaid->bind_param("i", $_SESSION['user_id']);
                $stmt_unpaid->execute();
                $unpaid_result = $stmt_unpaid->get_result();

                if ($unpaid_result->num_rows > 0): ?>
                    <div class="card mb-4">
                        <div class="card-body">
                            <h5 class="card-title">Quản lý đặt phòng của bạn</h5>
                            <?php while ($unpaid = $unpaid_result->fetch_assoc()): ?>
                                <div class="payment-form mt-3">
                                    <div class="mb-3">
                                        <label class="form-label">Phòng: <?php echo htmlspecialchars($unpaid['room_type'] ?? 'N/A'); ?></label>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Ngày nhận: <?php echo isset($unpaid['check_in']) ? htmlspecialchars(date('d/m/Y', strtotime($unpaid['check_in']))) : 'N/A'; ?></label>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Ngày trả: <?php echo isset($unpaid['check_out']) ? htmlspecialchars(date('d/m/Y', strtotime($unpaid['check_out']))) : 'N/A'; ?></label>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Trạng thái đặt phòng: <?php echo htmlspecialchars($unpaid['status'] ?? 'N/A'); ?></label>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Trạng thái thanh toán: <?php echo htmlspecialchars($unpaid['payment_status'] ?? 'Chưa thanh toán'); ?></label>
                                    </div>
                                    <?php
                                    // Tính số ngày
                                    $check_in = new DateTime($unpaid['check_in']);
                                    $check_out = new DateTime($unpaid['check_out']);
                                    $number_of_days = $check_in->diff($check_out)->days;
                                    $base_price = $unpaid['price'];

                                    // Logic tính giá dựa trên số ngày
                                    if ($number_of_days == 3) {
                                        $amount = $base_price * 3 * 0.9; // Giảm 10% cho 3 ngày
                                    } elseif ($number_of_days == 4) {
                                        $amount = $base_price * 4 * 0.85; // Giảm 15% cho 4 ngày
                                    } else {
                                        $amount = $base_price * $number_of_days; // Giá mặc định
                                    }
                                    ?>
                                    <div class="mb-3">
                                        <label class="form-label">Số ngày: <?php echo htmlspecialchars($number_of_days); ?></label>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Tổng tiền (USD): <?php echo htmlspecialchars(number_format($amount, 2)); ?></label>
                                    </div>
                                    <?php
                                    // Kiểm tra nếu form đã được gửi thành công cho booking này
                                    $form_hidden = $payment_submitted && $unpaid['id'] == ($_POST['booking_id'] ?? 0);
                                    if (!$unpaid['payment_status'] && !$form_hidden): ?>
                                        <form method="POST" enctype="multipart/form-data">
                                            <input type="hidden" name="booking_id" value="<?php echo htmlspecialchars($unpaid['id']); ?>">
                                            <input type="hidden" name="amount" value="<?php echo htmlspecialchars(number_format($amount, 2)); ?>">
                                            <div class="mb-3">
                                                <label for="payment_method_<?php echo htmlspecialchars($unpaid['id']); ?>" class="form-label">Phương thức thanh toán</label>
                                                <select class="form-select" id="payment_method_<?php echo htmlspecialchars($unpaid['id']); ?>" name="payment_method" required onchange="toggleProofUpload(this.value, '<?php echo htmlspecialchars($unpaid['id']); ?>')">
                                                    <option value="credit_card">Thẻ tín dụng</option>
                                                    <option value="bank_transfer">Chuyển khoản ngân hàng</option>
                                                    <option value="cash">Tiền mặt</option>
                                                </select>
                                            </div>
                                            <div id="proofUpload_<?php echo htmlspecialchars($unpaid['id']); ?>" class="mb-3" style="display: none;">
                                                <label for="proof_image_<?php echo htmlspecialchars($unpaid['id']); ?>" class="form-label">Tải lên bằng chứng chuyển khoản</label>
                                                <input type="file" class="form-control" id="proof_image_<?php echo htmlspecialchars($unpaid['id']); ?>" name="proof_image" accept="image/*">
                                                <div class="bank-info">
                                                    <p><strong>Số tài khoản:</strong> 1234567890</p>
                                                    <p><strong>Ngân hàng:</strong> Vietcombank</p>
                                                    <p><strong>Chủ tài khoản:</strong> Hotel Management</p>
                                                    <img src="qrcode.png" alt="Mã QR" class="qr-code">
                                                </div>
                                            </div>
                                            <button type="submit" name="confirm_payment" class="btn btn-success">Xác nhận thanh toán</button>
                                        </form>
                                    <?php elseif ($unpaid['payment_status'] == 'paid' || $form_hidden): ?>
                                        <div class="alert alert-success">Thanh toán cho đặt phòng này đã được gửi và đang chờ xác nhận.</div>
                                    <?php endif; ?>
                                </div>
                                <hr>
                            <?php endwhile; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info mt-3">Không có đặt phòng nào.</div>
                <?php endif;
                $stmt_unpaid->close();
            }
            ?>
        <?php elseif ($_SESSION['role'] == 'staff'): ?>
            <?php
            $sql_pending_payments = "SELECT p.id, p.booking_id, u.full_name, r.room_type, p.amount, p.payment_method, p.proof_image 
                                    FROM payments p 
                                    JOIN bookings b ON p.booking_id = b.id 
                                    JOIN users u ON b.user_id = u.id 
                                    JOIN rooms r ON b.room_id = r.id 
                                    WHERE p.status = 'pending' AND p.payment_method = 'bank_transfer'";
            $stmt_payments = $conn->prepare($sql_pending_payments);
            if ($stmt_payments === false) {
                echo '<div class="alert alert-danger">Lỗi prepare payments: ' . htmlspecialchars($conn->error) . '</div>';
            } else {
                $stmt_payments->execute();
                $pending_payments = $stmt_payments->get_result();

                if ($pending_payments->num_rows > 0): ?>
                    <div class="card mb-4">
                        <div class="card-body">
                            <h5 class="card-title">Xác nhận thanh toán chuyển khoản</h5>
                            <?php while ($payment = $pending_payments->fetch_assoc()): ?>
                                <div class="payment-item mb-3">
                                    <p><strong>Mã đặt phòng:</strong> <?php echo htmlspecialchars($payment['booking_id']); ?></p>
                                    <p><strong>Khách hàng:</strong> <?php echo htmlspecialchars($payment['full_name'] ?? 'N/A'); ?></p>
                                    <p><strong>Phòng:</strong> <?php echo htmlspecialchars($payment['room_type'] ?? 'N/A'); ?></p>
                                    <p><strong>Số tiền:</strong> <?php echo htmlspecialchars(number_format($payment['amount'], 2)); ?> USD</p>
                                    <p><strong>Bằng chứng:</strong> <a href="<?php echo htmlspecialchars($payment['proof_image']); ?>" target="_blank">Xem ảnh</a></p>
                                    <form method="POST">
                                        <input type="hidden" name="payment_id" value="<?php echo htmlspecialchars($payment['id']); ?>">
                                        <button type="submit" name="confirm_bank_payment" class="btn btn-success btn-sm">Xác nhận thanh toán</button>
                                    </form>
                                </div>
                                <hr>
                            <?php endwhile; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info mt-3">Không có thanh toán chờ xác nhận.</div>
                <?php endif;
                $stmt_payments->close();
            }
            ?>
        <?php else: ?>
            <div class="alert alert-warning mt-3">Bạn chưa đăng nhập hoặc vai trò không được xác định.</div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleProofUpload(paymentMethod, bookingId) {
            const proofUpload = document.getElementById('proofUpload_' + bookingId);
            if (proofUpload) {
                proofUpload.style.display = paymentMethod === 'bank_transfer' ? 'block' : 'none';
            }
        }

        function checkPaymentStatus() {
            <?php if ($_SESSION['role'] == 'customer'): ?>
                fetch('check_payment_status.php?user_id=<?php echo htmlspecialchars($_SESSION['user_id']); ?>')
                    .then(response => response.json())
                    .then(data => {
                        console.log('Check status:', data);
                        if (data.updated) {
                            location.reload();
                        }
                    })
                    .catch(error => console.error('Error:', error));
            <?php endif; ?>
        }

        setInterval(checkPaymentStatus, 5000);
    </script>
</body>
</html>

<?php $conn->close(); ?>
