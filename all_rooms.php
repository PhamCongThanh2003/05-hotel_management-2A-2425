<?php
require_once 'config.php';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Danh sách tất cả phòng</title>
    <link rel="stylesheet" href="styles.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            padding-top: 80px; /* Đẩy nội dung xuống dưới header cố định */
            min-height: 100vh; /* Đảm bảo body chiếm ít nhất chiều cao màn hình */
            display: flex;
            flex-direction: column;
        }
        .all-rooms h1 {
            white-space: normal; /* Cho phép xuống dòng */
            overflow: visible; /* Đảm bảo chữ không bị ẩn */
            word-break: break-word; /* Phá vỡ từ nếu cần */
            margin-bottom: 20px;
            font-size: clamp(2rem, 5vw, 3.5rem); /* Điều chỉnh kích thước linh hoạt */
            color: #333; /* Đảm bảo màu chữ rõ ràng */
            position: relative; /* Đảm bảo không bị che */
            z-index: 1; /* Đưa lên trên các phần tử khác */
        }
        .all-rooms {
            flex: 1 0 auto; /* Cho phép section giãn để đẩy footer xuống */
            padding-top: 20px; /* Thêm padding trên để tránh chồng lấn */
        }
        footer {
            flex-shrink: 0; /* Ngăn footer co lại */
            width: 100%;
            background-color: #1a252f;
            color: #fff;
            padding: 20px 0;
        }
        .btn-container {
            display: flex;
            gap: 10px; /* Khoảng cách giữa hai nút */
            justify-content: center;
        }
        @media (max-width: 768px) {
            .all-rooms h1 {
                font-size: clamp(1.5rem, 4vw, 2rem); /* Giảm kích thước trên mobile */
            }
            body {
                padding-top: 60px; /* Điều chỉnh padding-top trên mobile */
            }
            .btn-container {
                flex-direction: column; /* Xếp dọc trên mobile */
                gap: 8px;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <nav class="navbar navbar-expand-lg navbar-light bg-light fixed-top">
            <div class="container">
                <a class="navbar-brand" href="index.php">
                    <img src="assets/2.jpg" alt="Hotel PMS Logo" style="height: 55px;">
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item"><a class="nav-link" href="index.php">Trang chủ</a></li>
                        <li class="nav-item"><a class="nav-link" href="index.php#features">Sản phẩm</a></li>
                        <li class="nav-item"><a class="nav-link" href="index.php#pricing">Bảng giá</a></li>
                        <li class="nav-item"><a class="nav-link" href="index.php#blog">Blog</a></li>
                        <li class="nav-item"><a class="nav-link" href="index.php#contact">Liên hệ</a></li>
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <li class="nav-item"><a class="nav-link" href="logout.php">Đăng xuất</a></li>
                            <li class="nav-item"><span class="nav-link">Xin chào, <?php echo htmlspecialchars($_SESSION['full_name']); ?>!</span></li>
                        <?php else: ?>
                            <li class="nav-item"><a class="nav-link btn btn-warning text-dark" href="login.php">Đăng nhập</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </nav>
    </header>

    <!-- All Rooms Section -->
    <section class="all-rooms py-5">
        <div class="container text-center">
            <h1 class="display-4 text-uppercase text-dark fw-bold">All Rooms</h1>
            <div class="row mt-4">
                <div class="col-md-4 mb-4">
                    <img src="images/deluxecity.jpg" alt="Room 1" class="img-fluid rounded">
                    <p class="mt-2">Suite Gia Đình Có Ban Công với giường King-size thoải mái và ban công rộng rãi.</p>
                    <div class="btn-container">
                        <a href="room_detail.php?room_id=1" class="btn btn-warning mt-2">View Detail</a>
                        <a href="room_detail.php?room_id=1" class="btn btn-primary mt-2">Book a Room</a>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <img src="images/senior.jpg" alt="Room 2" class="img-fluid rounded">
                    <p class="mt-2">Phòng Deluxe với tiện nghi hiện đại và tầm nhìn thành phố tuyệt đẹp.</p>
                    <div class="btn-container">
                        <a href="room_detail.php?room_id=2" class="btn btn-warning mt-2">View Detail</a>
                        <a href="room_detail.php?room_id=2" class="btn btn-primary mt-2">Book a Room</a>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <img src="images/triplesuite.jpg" alt="Room 3" class="img-fluid rounded">
                    <p class="mt-2">Suite điều hành với trang trí sang trọng và sân thượng riêng.</p>
                    <div class="btn-container">
                        <a href="room_detail.php?room_id=3" class="btn btn-warning mt-2">View Detail</a>
                        <a href="room_detail.php?room_id=3" class="btn btn-primary mt-2">Book a Room</a>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <img src="images/familysuite.jpg" alt="Room 4" class="img-fluid rounded">
                    <p class="mt-2">Phòng Superior với không gian ấm cúng và tầm nhìn ra đường chân trời thành phố.</p>
                    <div class="btn-container">
                        <a href="room_detail.php?room_id=4" class="btn btn-warning mt-2">View Detail</a>
                        <a href="room_detail.php?room_id=4" class="btn btn-primary mt-2">Book a Room</a>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <img src="images/connecting.jpg" alt="Room 5" class="img-fluid rounded">
                    <p class="mt-2">Penthouse Suite với tầm nhìn toàn cảnh và tiện nghi cao cấp.</p>
                    <div class="btn-container">
                        <a href="room_detail.php?room_id=5" class="btn btn-warning mt-2">View Detail</a>
                        <a href="room_detail.php?room_id=5" class="btn btn-primary mt-2">Book a Room</a>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <img src="images/junior.jpg" alt="Room 6" class="img-fluid rounded">
                    <p class="mt-2">Phòng nhìn ra vườn với lối đi trực tiếp ra khu vực vườn.</p>
                    <div class="btn-container">
                        <a href="room_detail.php?room_id=6" class="btn btn-warning mt-2">View Detail</a>
                        <a href="room_detail.php?room_id=6" class="btn btn-primary mt-2">Book a Room</a>
                    </div>
                </div>
            </div>
            <a href="index.php" class="btn btn-outline-secondary mt-4">Back to Home</a>
        </div>
    </section>

    <!-- Footer -->
    <footer id="contact" class="footer">
        <div class="container">
            <div class="row">
                <div class="col-md-4">
                    <h5>Liên hệ</h5>
                    <p><i class="fas fa-map-marker-alt"></i> 123 Đường Khách Sạn, TP Ha Noi, Việt Nam</p>
                    <p><i class="fas fa-phone"></i> (+84) 123 456 789</p>
                    <p><i class="fas fa-envelope"></i> info@hotel.com</p>
                </div>
                <div class="col-md-4">
                    <h5>Theo dõi chúng tôi</h5>
                    <a href="#" class="text-white me-2"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="text-white me-2"></a>
                    <a href="#" class="text-white"><i class="fab fa-twitter"></i></a>
                </div>
                <div class="col-md-4">
                    <h5>Bản đồ</h5>
                    <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3919.669618!2d106.679983!3d10.759917!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x31752f38f8ed6e45%3A0x95041e559a2c2f76!2sHo%20Chi%20Minh%20City%2C%20Vietnam!5e0!3m2!1sen!2s!4v1698687600000!5m2!1sen!2s" width="100%" height="150" style="border:0;" allowfullscreen="" loading="lazy"></iframe>
                </div>
            </div>
            <div class="text-center mt-3">
                <p>© 2025 Hệ thống quản lý khách sạn. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="scripts.js"></script>
</body>
</html>