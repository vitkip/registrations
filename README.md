# 🎓 ລະບົບລົງທະບຽນ / Registration System

ລະບົບລົງທະບຽນສຳລັບນັກສຶກສາທີ່ພັດທະນາດ້ວຍ PHP, MySQL ແລະ Docker

## 🚀 ຄຸນສົມບັດຫຼັກ

- ✅ ລົງທະບຽນນັກສຶກສາອອນໄລນ໌
- ✅ ອັບໂຫຼດຮູບໂປຣໄຟລ໌ແລະຫຼັກຖານການຊຳລະເງິນ
- ✅ ກວດສອບສະຖານະການລົງທະບຽນ
- ✅ ລະບົບຈັດການແອດມິນ (Admin Dashboard)
- ✅ ແຈ້ງເຕືອນແບບເຣຍລ໌ໄທມ໌
- ✅ ລະບົບຄັດລອກຂໍ້ມູນອັດສະລິຍະ
- ✅ ຮອງຮັບທັງພາສາລາວແລະໄທ

## 🛠 ເທັກໂນໂລຢີທີ່ໃຊ້

- **Backend:** PHP 8.1, MySQL 8.0
- **Frontend:** HTML, CSS (Tailwind CSS), JavaScript
- **Database:** MySQL ກັບ Docker
- **Web Server:** Apache
- **Containerization:** Docker & Docker Compose
- **UI Components:** SweetAlert2, Animate.css

## 📋 ຄວາມຕ້ອງການລະບົບ

- Docker ແລະ Docker Compose
- ພອດ 8080 ວ່າງ
- ບາວເຊີທີ່ຮອງຮັບ ES6+

## 🚀 ການຕິດຕັ້ງແລະດຳເນີນການ

### 1. Clone ໂປຣເຈັກ
\`\`\`bash
git clone [repository-url]
cd registrations
\`\`\`

### 2. ເລີ່ມຕົ້ນລະບົບ
\`\`\`bash
docker-compose up -d
\`\`\`

### 3. ເຂົ້າເຖິງລະບົບ
- **ໜ້າລົງທະບຽນ:** http://localhost:8080/frontend/
- **ໜ້າແອດມິນ:** http://localhost:8080/frontend/admin-login.html
  - Username: `admin`
  - Password: `admin123`

## 📁 ໂຄງສ້າງໂປຣເຈັກ

\`\`\`
registrations/
├── 📁 backend/                # ໄຟລ໌ Backend PHP
│   ├── 📁 admin/              # API endpoints ສຳລັບແອດມິນ
│   ├── 📁 api/                # API endpoints ສາທາລະນະ
│   ├── 📁 auth/               # ລະບົບການພິສູດຕົວຕົນ
│   ├── 📁 config/             # ການຕັ້ງຄ່າຖານຂໍ້ມູນ
│   └── 📁 uploads/            # ອັບໂຫຼດໄຟລ໌ (gitignored)
│       ├── 📁 profiles/       # ຮູບໂປຣໄຟລ໌
│       └── 📁 payments/       # ຫຼັກຖານການຊຳລະ
├── 📁 frontend/               # ໄຟລ໌ Frontend
│   ├── 📄 index.html          # ໜ້າລົງທະບຽນຫຼັກ
│   ├── 📄 admin-login.html    # ໜ້າເຂົ້າສູ່ລະບົບແອດມິນ
│   └── 📄 admin-dashboard.html # ໜ້າຈັດການແອດມິນ
├── 📁 logs/                   # ແຟ້ມບັນທຶກລະບົບ (gitignored)
├── 📁 uploads/                # ໄດເຣັກທໍຣີອັບໂຫຼດສຳຮອງ
├── 📄 docker-compose.yml      # ການຕັ້ງຄ່າ Docker
├── 📄 Dockerfile             # ການຕິດຕັ້ງຄອນເທນເນີ PHP
├── 📄 init.sql               # ການເລີ່ມຕົ້ນຖານຂໍ້ມູນ
└── 📄 .gitignore             # ກົດການ ignore ຂອງ Git
\`\`\`

## 🔧 API Endpoints

### APIs ສາທາລະນະ
- `POST /backend/api/register.php` - ລົງທະບຽນນັກສຶກສາ
- `GET /backend/api/check_status.php` - ກວດສອບສະຖານະ
- `GET /backend/api/get_count.php` - ດຶງຈຳນວນຜູ້ລົງທະບຽນ

### APIs ແອດມິນ
- `POST /backend/auth/login.php` - ເຂົ້າສູ່ລະບົບແອດມິນ
- `GET /backend/admin/registrations.php` - ເບິ່ງລາຍການລົງທະບຽນ
- `PUT /backend/admin/update_status.php` - ອັບເດດສະຖານະ

## 🗄 ຖານຂໍ້ມູນ

### ຕາຕະລາງ `registrations`
- `id` - Primary key
- `student_code` - ລະຫັດນັກສຶກສາ (ສ້າງອັດຕະໂນມັດ)
- `first_name`, `last_name` - ຊື່-ນາມສະກຸນ
- `email`, `phone` - ຂໍ້ມູນຕິດຕໍ່
- `major`, `graduation_year` - ຂໍ້ມູນການສຶກສາ
- `profile_image`, `payment_proof` - ໄຟລ໌ອັບໂຫຼດ
- `status` - ສະຖານະ (pending, approved, rejected)
- `created_at`, `updated_at` - ເວລາ

### ຕາຕະລາງ `admin_users`
- `id` - Primary key  
- `username`, `password` - ຂໍ້ມູນເຂົ້າສູ່ລະບົບ
- `created_at` - ເວລາສ້າງ

## 🚨 ການແກ້ໄຂບັນຫາ

### ບັນຫາການເຊື່ອມຕໍ່
\`\`\`bash
# ກວດສອບສະຖານະ containers
docker-compose ps

# ເບິ່ງ logs
docker-compose logs web
docker-compose logs db

# ຣີສະຕາດລະບົບ
docker-compose restart
\`\`\`

### ບັນຫາຖານຂໍ້ມູນ
\`\`\`bash
# ເຂົ້າເຖິງ MySQL
docker-compose exec db mysql -u root -p

# ຣີເຊັດຖານຂໍ້ມູນ
docker-compose down -v
docker-compose up -d
\`\`\`

### ບັນຫາການອັບໂຫຼດໄຟລ໌
- ກວດສອບສິດທິໂຟນເດີ uploads
- ກວດສອບຂະໜາດໄຟລ໌ບໍ່ເກີນ 5MB
- ກວດສອບປະເພດໄຟລ໌ທີ່ອະນຸຍາດ (.jpg, .jpeg, .png)

## 🔒 ການຮັກສາຄວາມປອດໄພ

- ໄຟລ໌ອັບໂຫຼດຖືກກວດສອບປະເພດແລະຂະໜາດ
- ປ້ອງກັນ SQL injection ດ້ວຍ prepared statements
- ປ້ອງກັນ XSS ດ້ວຍ input sanitization
- ການຈັດການ session ສຳລັບແອດມິນ
- CORS headers ຖືກຕັ້ງຄ່າແລ້ວ

## 📝 ການພັດທະນາ

### ການເພີ່ມ API ໃໝ່
1. ສ້າງໄຟລ໌ໃນ `/backend/api/`
2. ໃຊ້ `require_once __DIR__ . '/../config/config.php'`
3. ໃຊ້ `handleCORS()` ແລະ `sendJsonResponse()`

### ການແກ້ໄຂ Frontend  
1. ແກ້ໄຂໄຟລ໌ໃນ `/frontend/`
2. ໃຊ້ SweetAlert2 ສຳລັບ notifications
3. ໃຊ້ Tailwind CSS ສຳລັບ styling

## 📞 ການຕິດຕໍ່ 2077772338

ສຳລັບຄຳຖາມຫຼືບັນຫາການນຳໃຊ້ ກະລຸນາຕິດຕໍ່ທີມພັດທະນາ

---

ສ້າງດ້ວຍ ❤️ ສຳລັບການສຶກສາ