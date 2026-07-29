# 📜 স্ক্রিপ্টবিডি (ScriptBD) — Backend

একটি বাংলা স্ক্রিপ্ট বিক্রির ওয়েবসাইটের সম্পূর্ণ PHP + MySQL ব্যাকেন্ড।

## 📁 ফোল্ডার স্ট্রাকচার (Folder Structure)

```
backend/
├── .htaccess                  # CORS, error reporting, security
├── index.php                  # Health check endpoint
├── README.md                  # This file
├── config/
│   ├── database.php           # PDO MySQL connection
│   └── helpers.php            # CORS, JSON, auth helpers
├── database/
│   └── schema.sql             # Full MySQL schema + seed data
├── api/
│   ├── orders/
│   │   ├── create.php         # POST — Create new order
│   │   ├── list.php           # GET — List all orders (auth)
│   │   ├── update.php         # PUT — Update order status (auth)
│   │   └── delete.php         # DELETE — Delete order (auth)
│   └── auth/
│       └── login.php          # POST — Admin login, returns token
└── admin/
    ├── index.php              # Login page (Bengali UI, dark theme)
    ├── dashboard.php           # Admin dashboard (order management)
    └── logout.php              # Logout script
```

## ⚙️ প্রয়োজনীয়তা (Requirements)

- **PHP** 8.0+
- **MySQL** 5.7+ or MariaDB 10.3+
- **Apache** with `mod_rewrite` & `mod_headers` (or Nginx equivalent)
- **PDO** PHP extension
- **PDO MySQL** driver

## 🚀 সেটআপ নির্দেশনা (Setup Instructions)

### 1. ডাটাবেজ তৈরি করুন (Create Database)

Terminal বা phpMyAdmin এ SQL ফাইলটি ইম্পোর্ট করুন:

```bash
mysql -u root -p < database/schema.sql
```

অথবা phpMyAdmin এ গিয়ে `scriptbd` নামে ডাটাবেজ তৈরি করে `schema.sql` ফাইলটি ইম্পোর্ট করুন।

### 2. কনফিগারেশন (Configuration)

`config/database.php` ফাইলটি এডিট করে আপনার MySQL ক্রিডেনশিয়াল দিন:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'scriptbd');
define('DB_USER', 'root');
define('DB_PASS', '');
```

### 3. সার্ভার স্টার্ট (Start Server)

Apache/XAMPP ব্যবহার করলে `backend/` ফোল্ডারটি আপনার `htdocs` বা `www` ডিরেক্টরিতে রাখুন।

অথবা PHP বিল্ট-ইন সার্ভার ব্যবহার করুন:

```bash
cd backend
php -S localhost:8000
```

### 4. হেলথ চেক (Health Check)

ব্রাউজারে যান:
```
http://localhost:8000/
http://localhost:8000/?db_check=1
```

## 🔐 ডিফল্ট অ্যাডমিন (Default Admin)

| ফিল্ড | মান |
|--------|------|
| ইউজারনেম | `admin` |
| পাসওয়ার্ড | `admin123` |

> ⚠️ প্রথম লগইনের পর পাসওয়ার্ড পরিবর্তন করুন!

## 📡 API ডকুমেন্টেশন

### API কী (Authentication)

সুরক্ষিত API এন্ডপয়েন্টে `X-API-Key` হেডার প্রয়োজন:

```
X-API-Key: scriptbd_api_key_2026_secure
```

API কী পরিবর্তন করতে `database/schema.sql` এবং `config/helpers.php` আপডেট করুন।

### এন্ডপয়েন্টসমূহ

#### 1. অর্ডার তৈরি (Create Order)
```http
POST /api/orders/create.php
Content-Type: application/json

{
    "name": "রাকিব হাসান",
    "email": "rakib@example.com",
    "phone": "+8801712345678",
    "plan": "premium",
    "topic": "ই-কমার্স ওয়েবসাইট স্ক্রিপ্ট",
    "message": "আমার একটি পূর্ণাঙ্গ ই-কমার্স সাইট দরকার"
}
```

#### 2. অর্ডার তালিকা (List Orders)
```http
GET /api/orders/list.php
X-API-Key: scriptbd_api_key_2026_secure

# ফিল্টার সহ:
GET /api/orders/list.php?status=pending&page=1&limit=10&search=rakib
```

#### 3. স্ট্যাটাস আপডেট (Update Order Status)
```http
PUT /api/orders/update.php
X-API-Key: scriptbd_api_key_2026_secure
Content-Type: application/json

{
    "id": 1,
    "status": "completed"
}
```

#### 4. অর্ডার ডিলিট (Delete Order)
```http
DELETE /api/orders/delete.php
X-API-Key: scriptbd_api_key_2026_secure
Content-Type: application/json

{
    "id": 1
}
```

#### 5. অ্যাডমিন লগইন (Admin Login)
```http
POST /api/auth/login.php
Content-Type: application/json

{
    "username": "admin",
    "password": "admin123"
}
```

### স্ট্যাটাস ভ্যালু (Order Status Values)

| স্ট্যাটাস | বাংলা | অর্থ |
|-----------|--------|------|
| `pending` | পেন্ডিং | নতুন অর্ডার |
| `processing` | প্রসেসিং | কাজ চলছে |
| `completed` | সম্পন্ন | কাজ শেষ |
| `cancelled` | বাতিল | বাতিলকৃত |

### প্ল্যান ভ্যালু (Plan Values)

- `basic` — বেসিক
- `standard` — স্ট্যান্ডার্ড
- `premium` — প্রিমিয়াম
- `custom` — কাস্টম

## 🎨 অ্যাডমিন প্যানেল

- **লগইন পেজ:** `http://localhost:8000/admin/`
- **ড্যাশবোর্ড:** `http://localhost:8000/admin/dashboard.php`

### ফিচারসমূহ:
- ✅ বাংলা UI সহ ডার্ক থিম
- ✅ স্ট্যাটাস কার্ড (মোট / পেন্ডিং / প্রসেসিং / সম্পন্ন / বাতিল)
- ✅ সার্চ (নাম, ইমেইল, ফোন, টপিক)
- ✅ স্ট্যাটাস ফিল্টার
- ✅ অর্ডার বিস্তারিত মোডাল
- ✅ এক ক্লিকে স্ট্যাটাস আপডেট
- ✅ অর্ডার ডিলিট (কনফার্মেশন সহ)
- ✅ পেজিনেশন
- ✅ রেসপনসিভ ডিজাইন
- ✅ টোস্ট নোটিফিকেশন

## 🛡️ সিকিউরিটি (Security)

- পাসওয়ার্ড `password_hash()` (bcrypt) দ্বারা হ্যাশ করা
- API এন্ডপয়েন্টে API কী ভেরিফিকেশন
- অ্যাডমিন প্যানেলে PHP সেশন-ভিত্তিক অথেনটিকেশন
- PDO প্রিপেয়ার্ড স্টেটমেন্ট (SQL ইনজেকশন প্রতিরোধ)
- সকল ইনপুট ভ্যালিডেশন ও স্যানিটাইজেশন
- CORS হেডার সব API রেসপন্সে
- ত্রুটি লগিং (error_log) কিন্তু ইউজারকে বিস্তারিত ত্রুটি দেখায় না

## 📝 লাইসেন্স (License)

MIT License

---

**স্ক্রিপ্টবিডি — বাংলা স্ক্রিপ্টের ভাণ্ডার 📜**
