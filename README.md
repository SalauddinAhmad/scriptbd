# 📜 ScriptBD — বাংলা ভিডিও স্ক্রিপ্ট মার্কেটপ্লেস

[![Deploy](https://github.com/SalauddinAhmad/scriptbd/actions/workflows/deploy.yml/badge.svg)](https://github.com/SalauddinAhmad/scriptbd/actions)
[![PHP](https://img.shields.io/badge/PHP-8.1%2B-777bb4?logo=php)](https://php.net)
[![React](https://img.shields.io/badge/React-18-61dafb?logo=react)](https://react.dev)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

**ScriptBD** — বাংলাদেশের প্রথম বাংলা ভিডিও স্ক্রিপ্ট বিক্রির প্ল্যাটফর্ম। ইউটিউবার, ফেসবুক ক্রিয়েটর ও টিকটকারদের জন্য রেডিমেড ভাইরাল স্ক্রিপ্ট।

🌐 **[scriptbd.com](https://scriptbd.com)** — লাইভ সাইট

---

## ✨ ফিচারসমূহ

### 🛒 ফ্রন্টএন্ড (React 18 + Vite)
- 🎨 **ডার্ক থিম** — Glassmorphism + Animated Background
- 📱 **Fully Responsive** — Mobile, Tablet, Desktop
- 📦 **৩টি প্ল্যান** — YouTube Shorts, Facebook Reels, YouTube Full
- 💳 **পেমেন্ট গেটওয়ে** — bKash, Nagad, Rocket + TrxID ভেরিফিকেশন
- 🔍 **SEO Optimized** — Bengali meta tags, Open Graph
- ⚡ **Vite Build** — Lightning fast production build

### 👨‍💼 অ্যাডমিন প্যানেল
- 📊 **Revenue Dashboard** — মোট আয়, আজকের আয়, মাসিক রিপোর্ট
- 📋 **Order Management** — Filter, Search, Sort, Pagination
- 💳 **Payment Verification** — One-click verify + bulk action
- 📦 **Delivery Tracking** — Mark delivered, status flow timeline
- 📝 **Admin Notes** — প্রতি অর্ডারে প্রাইভেট নোট
- 🎨 **Premium UI** — Inter font, Phosphor Icons, Indigo accent

### ⚙️ ব্যাকএন্ড (PHP 8.1+)
- 🔐 **Session-based Auth** — Secure admin login
- 🗄️ **MySQL Database** — PDO prepared statements
- 📡 **REST API** — JSON responses, CORS enabled
- 🛡️ **Security** — SQL injection protection, XSS prevention

---

## 🏗️ টেক স্ট্যাক

| Layer | Technology |
|-------|-----------|
| **Frontend** | React 18, Vite, CSS3 |
| **Backend** | PHP 8.1+, MySQL |
| **Hosting** | cPanel (LiteSpeed) |
| **Deploy** | GitHub Actions → Webhook |
| **Icons** | Phosphor Icons |
| **Fonts** | Inter + Noto Sans Bengali |

---

## 📁 প্রজেক্ট স্ট্রাকচার

```
scriptbd/
├── frontend/               # React Frontend
│   ├── src/
│   │   ├── components/     # React Components
│   │   │   ├── Home.jsx
│   │   │   ├── Pricing.jsx
│   │   │   ├── OrderModal.jsx
│   │   │   └── Admin.jsx
│   │   ├── api/            # API Client
│   │   ├── App.jsx
│   │   └── main.jsx
│   ├── dist/               # Production Build
│   └── vite.config.js
├── backend/                # PHP Backend
│   ├── admin/
│   │   ├── index.php       # Login + Dashboard (ONE FILE)
│   │   └── logout.php
│   ├── api/
│   │   ├── auth/login.php
│   │   ├── orders/
│   │   └── payments/
│   ├── config/
│   │   └── database.php
│   └── .htaccess
├── deploy.php              # Auto-Deploy Webhook
├── .github/workflows/
│   └── deploy.yml          # CI/CD Pipeline
└── README.md
```

---

## 🚀 ডিপ্লয়মেন্ট

### Auto-Deploy (GitHub Actions)
```bash
git push origin main
# → GitHub Actions triggers
# → Build React frontend
# → Deploy via webhook
# → scriptbd.com LIVE! 🔥
```

### Manual Deploy
1. cPanel → File Manager → upload files
2. Set database credentials in `backend/config/database.php`
3. Import `database/schema.sql` via phpMyAdmin

---

## 📊 অ্যাডমিন অ্যাক্সেস

| Field | Value |
|-------|-------|
| **URL** | `https://scriptbd.com/backend/admin/` |
| **Username** | `admin` |
| **Password** | `admin123` |

---

## 🔐 Environment Variables

```php
// backend/config/database.php
define('DB_HOST', 'localhost');
define('DB_NAME', 'your_database');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
```

---

## 📝 ডেভেলপমেন্ট

```bash
# Clone
git clone https://github.com/SalauddinAhmad/scriptbd.git
cd scriptbd

# Frontend
cd frontend
npm install
npm run dev      # Development server
npm run build    # Production build

# Backend (PHP built-in server for testing)
cd backend
php -S localhost:8000
```

---

## 🤝 কন্ট্রিবিউট

Pull requests welcome! বড় পরিবর্তনের আগে issue খুলুন।

---

## 📄 লাইসেন্স

MIT © [Salauddin Ahmad](https://github.com/SalauddinAhmad)

---

**Made with ❤️ in Dhaka, Bangladesh 🇧🇩**
