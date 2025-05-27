function validateLoginForm() {
    const email = document.getElementById('email').value;
    const password = document.getElementById('password').value;
    if (!email.includes('@') || password.length < 6) {
        alert('Email không hợp lệ hoặc mật khẩu quá ngắn!');
        return false;
    }
    return true;
}

function validateBookingForm() {
    const checkIn = new Date(document.getElementById('check_in').value);
    const checkOut = new Date(document.getElementById('check_out').value);
    if (checkOut <= checkIn) {
        alert('Ngày đi phải sau ngày đến!');
        return false;
    }
    return true;
}

function validatePaymentForm() {
    const amount = document.getElementById('amount').value;
    if (amount <= 0) {
        alert('Số tiền phải lớn hơn 0!');
        return false;
    }
    return true;
}

function validateRoomForm() {
    const price = document.getElementById('price').value;
    if (price <= 0) {
        alert('Giá phòng phải lớn hơn 0!');
        return false;
    }
    return true;
}

function validateCustomerForm() {
    const phone = document.getElementById('phone').value;
    const points = document.getElementById('points').value;
    if (!/^\d{10}$/.test(phone)) {
        alert('Số điện thoại phải có 10 chữ số!');
        return false;
    }
    if (points < 0) {
        alert('Điểm thân thiết không thể âm!');
        return false;
    }
    return true;
}

function validateUserForm() {
    const email = document.getElementById('email').value;
    const password = document.getElementById('password').value;
    if (!email.includes('@') || password.length < 6) {
        alert('Email không hợp lệ hoặc mật khẩu quá ngắn!');
        return false;
    }
    return true;
}
// Smooth Scroll for Links
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        document.querySelector(this.getAttribute('href')).scrollIntoView({
            behavior: 'smooth'
        });
    });
});

// Validate Booking Form (nếu cần thêm form sau này)
function validateBookingForm() {
    const checkIn = new Date(document.getElementById('check_in')?.value);
    const checkOut = new Date(document.getElementById('check_out')?.value);
    if (checkIn && checkOut && checkOut <= checkIn) {
        alert('Ngày đi phải sau ngày đến!');
        return false;
    }
    return true;
}