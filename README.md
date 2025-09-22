# 🎓 Registration System / ລະບົບລົງທະບຽນ

ระบบลงทะเบียนสำหรับนักศึกษาที่พัฒนาด้วย PHP, MySQL และ Docker

## 🚀 คุณสมบัติหลัก / Features

- ✅ ลงทะเบียนนักศึกษาออนไลน์
- ✅ อัปโหลดรูปโปรไฟล์และหลักฐานการชำระเงิน
- ✅ ตรวจสอบสถานะการลงทะเบียน
- ✅ ระบบจัดการแอดมิน (Admin Dashboard)
- ✅ แจ้งเตือนแบบเรียลไทม์
- ✅ ระบบคัดลอกข้อมูลอัจฉริยะ
- ✅ รองรับทั้งภาษาลาวและไทย

## 🛠 เทคโนโลยีที่ใช้ / Tech Stack

- **Backend:** PHP 8.1, MySQL 8.0
- **Frontend:** HTML, CSS (Tailwind CSS), JavaScript
- **Database:** MySQL with Docker
- **Web Server:** Apache
- **Containerization:** Docker & Docker Compose
- **UI Components:** SweetAlert2, Animate.css

## 📋 ความต้องการระบบ / Requirements

- Docker และ Docker Compose
- พอร์ต 8080 ว่าง
- เบราว์เซอร์ที่รองรับ ES6+

## 🚀 การติดตั้งและรัน / Installation & Setup

### 1. Clone โปรเจค
\`\`\`bash
git clone [repository-url]
cd registrations
\`\`\`

### 2. เริ่มต้นระบบ
\`\`\`bash
docker-compose up -d
\`\`\`

### 3. เข้าถึงระบบ
- **หน้าลงทะเบียน:** http://localhost:8080/frontend/
- **หน้าแอดมิน:** http://localhost:8080/frontend/admin-login.html
  - Username: `admin`
  - Password: `admin123`

## 📁 โครงสร้างโปรเจค / Project Structure

\`\`\`
registrations/
├── 📁 backend/                # Backend PHP files
│   ├── 📁 admin/              # Admin API endpoints
│   ├── 📁 api/                # Public API endpoints
│   ├── 📁 auth/               # Authentication system
│   ├── 📁 config/             # Database & configuration
│   └── 📁 uploads/            # File uploads (gitignored)
│       ├── 📁 profiles/       # Profile images
│       └── 📁 payments/       # Payment proofs
├── 📁 frontend/               # Frontend files
│   ├── 📄 index.html          # Main registration page
│   ├── 📄 admin-login.html    # Admin login
│   └── 📄 admin-dashboard.html # Admin dashboard
├── 📁 logs/                   # Application logs (gitignored)
├── 📁 uploads/                # Alternative upload directory
├── 📄 docker-compose.yml      # Docker configuration
├── 📄 Dockerfile             # PHP container setup
├── 📄 init.sql               # Database initialization
└── 📄 .gitignore             # Git ignore rules
\`\`\`

## 🔧 API Endpoints

### Public APIs
- `POST /backend/api/register.php` - ลงทะเบียนนักศึกษา
- `GET /backend/api/check_status.php` - ตรวจสอบสถานะ
- `GET /backend/api/get_count.php` - ดึงจำนวนผู้ลงทะเบียน

### Admin APIs
- `POST /backend/auth/login.php` - เข้าสู่ระบบแอดมิน
- `GET /backend/admin/registrations.php` - ดูรายการลงทะเบียน
- `PUT /backend/admin/update_status.php` - อัปเดตสถานะ

## 🗄 ฐานข้อมูล / Database Schema

### ตาราง `registrations`
- `id` - Primary key
- `student_code` - รหัสนักศึกษา (auto-generated)
- `first_name`, `last_name` - ชื่อ-นามสกุล
- `email`, `phone` - ติดต่อ
- `major`, `graduation_year` - ข้อมูลการศึกษา
- `profile_image`, `payment_proof` - ไฟล์อัปโหลด
- `status` - สถานะ (pending, approved, rejected)
- `created_at`, `updated_at` - เวลา

### ตาราง `admin_users`
- `id` - Primary key  
- `username`, `password` - ข้อมูลล็อกอิน
- `created_at` - เวลาสร้าง

## 🚨 การแก้ไขปัญหา / Troubleshooting

### ปัญหาการเชื่อมต่อ
\`\`\`bash
# ตรวจสอบสถานะ containers
docker-compose ps

# ดู logs
docker-compose logs web
docker-compose logs db

# รีสตาร์ทระบบ
docker-compose restart
\`\`\`

### ปัญหาฐานข้อมูล
\`\`\`bash
# เข้าถึง MySQL
docker-compose exec db mysql -u root -p

# รีเซ็ตฐานข้อมูล
docker-compose down -v
docker-compose up -d
\`\`\`

### ปัญหาการอัปโหลดไฟล์
- ตรวจสอบสิทธิ์โฟลเดอร์ uploads
- ตรวจสอบขนาดไฟล์ไม่เกิน 5MB
- ตรวจสอบประเภทไฟล์ที่อนุญาต (.jpg, .jpeg, .png)

## 🔒 การรักษาความปลอดภัย / Security

- ไฟล์อัปโหลดถูก validate ประเภทและขนาด
- SQL injection protected ด้วย prepared statements
- XSS protection ด้วย input sanitization
- Session management สำหรับแอดมิน
- CORS headers configured

## 📝 การพัฒนา / Development

### การเพิ่ม API ใหม่
1. สร้างไฟล์ใน `/backend/api/`
2. ใช้ `require_once __DIR__ . '/../config/config.php'`
3. ใช้ `handleCORS()` และ `sendJsonResponse()`

### การแก้ไข Frontend  
1. แก้ไขไฟล์ใน `/frontend/`
2. ใช้ SweetAlert2 สำหรับ notifications
3. ใช้ Tailwind CSS สำหรับ styling

## 📞 การติดต่อ / Contact

สำหรับคำถามหรือปัญหาการใช้งาน กรุณาติดต่อทีมพัฒนา

---

Made with ❤️ for educational purposes