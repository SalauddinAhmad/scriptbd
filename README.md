<p align="center">
  <img src="https://img.shields.io/badge/STATUS-LIVE-brightgreen?style=for-the-badge" />
  <img src="https://img.shields.io/badge/REACT-18-61dafb?style=for-the-badge&logo=react" />
  <img src="https://img.shields.io/badge/PHP-8.1+-777bb4?style=for-the-badge&logo=php" />
  <img src="https://img.shields.io/badge/MYSQL-8-4479A1?style=for-the-badge&logo=mysql" />
  <img src="https://img.shields.io/badge/LICENSE-MIT-green?style=for-the-badge" />
</p>

<h1 align="center">
  📜 ScriptBD
</h1>

<p align="center">
  <b>বাংলাদেশের #1 বাংলা ভিডিও স্ক্রিপ্ট মার্কেটপ্লেস</b><br/>
  <sub>ইউটিউবার, ফেসবুক ক্রিয়েটর ও কন্টেন্ট ক্রিয়েটরদের জন্য রেডিমেড ভাইরাল স্ক্রিপ্ট</sub>
</p>

<p align="center">
  <a href="https://scriptbd.com" target="_blank">
    <img src="https://img.shields.io/badge/🌐_LIVE_WEBSITE-scriptbd.com-ff6b35?style=for-the-badge&logo=googlechrome" />
  </a>
  &nbsp;
  <a href="https://github.com/SalauddinAhmad/scriptbd/actions" target="_blank">
    <img src="https://img.shields.io/github/actions/workflow/status/SalauddinAhmad/scriptbd/deploy.yml?style=for-the-badge&label=DEPLOY" />
  </a>
</p>

<br/>

---

## 🌟 কেন ScriptBD?

<table>
<tr>
  <td width="50%">
    <h3>🎬 ক্রিয়েটরদের জন্য</h3>
    <ul>
      <li>✅ <b>রেডিমেড ভাইরাল স্ক্রিপ্ট</b> — কিনুন, কাস্টমাইজ করুন, ভিডিও বানান</li>
      <li>✅ <b>৩টি প্রিমিয়াম প্ল্যান</b> — YouTube Shorts, Facebook Reels, YouTube Full</li>
      <li>✅ <b>বাংলায় টপিক</b> — সব স্ক্রিপ্ট বাংলা ভাষায়</li>
      <li>✅ <b>ইনস্ট্যান্ট অর্ডার</b> — TrxID দিয়ে সহজ পেমেন্ট</li>
      <li>✅ <b>২৪/৭ সাপোর্ট</b> — অর্ডার সংক্রান্ত যেকোনো সাহায্য</li>
    </ul>
  </td>
  <td width="50%">
    <h3>🛠️ টেকনিক্যাল হাইলাইটস</h3>
    <ul>
      <li>⚛️ <b>React 18 + Vite</b> — Blazing fast frontend</li>
      <li>🐘 <b>PHP 8.1+</b> — Robust backend API</li>
      <li>🗄️ <b>MySQL 8</b> — Reliable data storage</li>
      <li>🚀 <b>Auto-Deploy</b> — git push = live update</li>
      <li>🔒 <b>Secure</b> — PDO prepared statements, XSS protection</li>
      <li>📱 <b>Responsive</b> — Mobile, tablet, desktop ready</li>
    </ul>
  </td>
</tr>
</table>

---

## 📊 অ্যাডমিন ড্যাশবোর্ড

<p align="center">
  <b>এক পেজেই সব ম্যানেজমেন্ট!</b>
</p>

| ফিচার | বর্ণনা |
|:---:|---|
| 📊 | **Revenue Analytics** — মোট আয়, আজকের আয়, মাসিক রিপোর্ট |
| 📋 | **Order Management** — Filter, Search, Sort, Pagination |
| 💳 | **Payment Verification** — One-click verify + Bulk action |
| 📦 | **Delivery Tracking** — Status flow timeline (Order → Paid → Delivered) |
| 📝 | **Admin Notes** — প্রতি অর্ডারে প্রাইভেট নোট |
| 🎨 | **Premium UI** — Inter font, Phosphor Icons, Glassmorphism |

---

## 🏗️ প্রজেক্ট স্ট্রাকচার

```
scriptbd/
│
├── frontend/                    # ⚛️ React 18 + Vite
│   ├── src/
│   │   ├── components/         # UI Components
│   │   │   ├── Home.jsx        # Landing page
│   │   │   ├── Pricing.jsx     # Plan pricing cards
│   │   │   ├── OrderModal.jsx  # Order form modal
│   │   │   └── Admin.jsx       # Admin panel (React)
│   │   ├── api/                # API client
│   │   │   └── index.js
│   │   ├── App.jsx             # Main app
│   │   └── main.jsx            # Entry point
│   ├── dist/                   # 📦 Production build
│   ├── vite.config.js
│   └── package.json
│
├── backend/                     # 🐘 PHP 8.1+
│   ├── admin/
│   │   ├── index.php           # 🔐 Login + Dashboard (ALL-IN-ONE)
│   │   └── logout.php
│   ├── api/
│   │   ├── auth/
│   │   │   └── login.php       # Authentication
│   │   ├── orders/             # Order CRUD API
│   │   └── payments/           # Payment API
│   ├── config/
│   │   ├── database.php        # DB configuration
│   │   └── helpers.php         # Utility functions
│   └── .htaccess               # Server config
│
├── deploy.php                   # 🚀 Auto-deploy webhook (OLD)
├── _deploy.php                  # 🚀 Auto-deploy webhook (NEW)
├── .github/workflows/
│   └── deploy.yml              # ⚡ CI/CD Pipeline
├── README.md                    # 📖 Documentation
└── LICENSE                      # 📄 MIT License
```

---

## 💳 পেমেন্ট সিস্টেম

<table align="center">
<tr>
  <td align="center"><b>bKash</b><br/><sub>বিকাশ</sub></td>
  <td align="center"><b>Nagad</b><br/><sub>নগদ</sub></td>
  <td align="center"><b>Rocket</b><br/><sub>রকেট</sub></td>
</tr>
<tr>
  <td align="center">✅</td>
  <td align="center">✅</td>
  <td align="center">✅</td>
</tr>
</table>

**কিভাবে কাজ করে:**
1. গ্রাহক প্ল্যান সিলেক্ট করে → অর্ডার ফর্ম পূরণ করে
2. bKash/Nagad/Rocket-এ পেমেন্ট করে
3. **TrxID** দিয়ে অর্ডার submit করে
4. অ্যাডমিন TrxID ভেরিফাই করে → **One-click deliver!**

---

## 🚀 ডিপ্লয়মেন্ট

### Auto-Deploy (GitHub Actions)
```bash
git push origin main
```
```
→ GitHub Actions triggers
→ curl _deploy.php?secret=***
→ Server downloads latest code from GitHub
→ auto-extracts + deploys
→ ✅ scriptbd.com LIVE!
```

### Manual Setup
```bash
# Clone
git clone https://github.com/SalauddinAhmad/scriptbd.git
cd scriptbd

# Frontend build
cd frontend && npm install && npm run build

# Upload to cPanel
# → public_html/ ← frontend/dist/*
# → public_html/backend/ ← backend/*
```

---

## 🔐 অ্যাডমিন

| | |
|---|---|
| **URL** | `https://scriptbd.com/backend/admin/` |
| **User** | `admin` |
| **Pass** | `admin123` |

---

## 📈 টেক স্ট্যাক

<p align="center">
  <img src="https://skillicons.dev/icons?i=react,vite,php,mysql,html,css,js,git,github,linux" />
</p>

---

## 🤝 কন্ট্রিবিউট

Pull requests welcome! বড় পরিবর্তনের আগে issue খুলুন।

---

## 👨‍💻 ডেভেলপার

**Salauddin Ahmad**

- 🐙 [GitHub](https://github.com/SalauddinAhmad)
- 🌐 [Portfolio](https://scriptbd.com)
- 📍 Dhaka, Bangladesh 🇧🇩

---

<p align="center">
  <sub>Made with ❤️ in Bangladesh | © 2026 ScriptBD</sub>
</p>
