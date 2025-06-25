# Panduan Adaptasi Template Soft UI Dashboard dengan Sistem KRS PHP

## 1. Langkah-langkah Adaptasi

### A. Persiapan File Template
1. Download template Soft UI Dashboard Tailwind dari Creative Tim
2. Extract file template ke folder proyek Anda
3. Salin file CSS, JS, dan assets yang diperlukan

### B. Struktur Folder yang Disarankan
\`\`\`
sistem-krs/
├── assets/
│   ├── css/
│   │   ├── soft-ui-dashboard.css (dari template asli)
│   │   └── soft-ui-custom.css (custom CSS kami)
│   ├── js/
│   │   └── soft-ui-dashboard.js (dari template asli)
│   └── img/ (gambar dari template)
├── config/
├── includes/
├── database/
└── *.php (file PHP sistem KRS)
\`\`\`

### C. Modifikasi File PHP

#### 1. Ganti semua link CSS di file PHP:
\`\`\`php
<!-- Ganti dari -->
<link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">

<!-- Menjadi -->
<link href="assets/css/soft-ui-dashboard.css" rel="stylesheet">
<link href="assets/css/soft-ui-custom.css" rel="stylesheet">
\`\`\`

#### 2. Tambahkan JavaScript dari template:
\`\`\`php
<!-- Sebelum closing </body> -->
<script src="assets/js/soft-ui-dashboard.js"></script>
\`\`\`

#### 3. Sesuaikan Class CSS:
- Ganti class Tailwind dengan class Soft UI Dashboard
- Gunakan gradient classes yang sudah didefinisikan
- Sesuaikan spacing dan typography

## 2. Komponen yang Perlu Disesuaikan

### A. Sidebar Navigation
\`\`\`php
<!-- Template Asli Soft UI -->
<aside class="sidenav navbar navbar-vertical navbar-expand-xs border-0 border-radius-xl my-3 fixed-start ms-3 bg-gradient-dark" id="sidenav-main">

<!-- Adaptasi untuk KRS -->
<div class="sidebar-soft h-full p-4">
\`\`\`

### B. Cards dan Statistics
\`\`\`php
<!-- Template Asli -->
<div class="card">
  <div class="card-header p-3 pt-2">
    <div class="icon icon-lg icon-shape bg-gradient-dark shadow-dark text-center border-radius-xl mt-n4 position-absolute">
      <i class="material-icons opacity-10">weekend</i>
    </div>
  </div>
</div>

<!-- Adaptasi KRS -->
<div class="stats-card">
  <div class="flex items-center justify-between">
    <div class="icon-shape bg-gradient-primary">
      <i class="fas fa-book"></i>
    </div>
  </div>
</div>
\`\`\`

### C. Tables
\`\`\`php
<!-- Gunakan class table dari Soft UI -->
<table class="table align-items-center mb-0">
  <thead>
    <tr>
      <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Mata Kuliah</th>
    </tr>
  </thead>
</table>
\`\`\`

## 3. Customization CSS

### A. Warna dan Gradient
\`\`\`css
/* Sesuaikan dengan identitas universitas */
:root {
  --primary-gradient: linear-gradient(310deg, #your-color-1 0%, #your-color-2 100%);
}
\`\`\`

### B. Typography
\`\`\`css
/* Gunakan font dari template */
body {
  font-family: "Roboto", sans-serif;
}
\`\`\`

## 4. JavaScript Integration

### A. Sidebar Toggle
\`\`\`javascript
// Gunakan JavaScript dari template untuk sidebar
const sidenav = document.getElementById('sidenav-main');
\`\`\`

### B. Charts (jika diperlukan)
\`\`\`javascript
// Integrasikan Chart.js untuk grafik IPK
// Gunakan konfigurasi dari template Soft UI
\`\`\`

## 5. Responsive Design

### A. Mobile Navigation
- Gunakan komponen mobile nav dari template
- Sesuaikan dengan struktur PHP

### B. Grid System
- Gunakan grid system dari Bootstrap/Soft UI
- Sesuaikan dengan layout KRS

## 6. Testing dan Debugging

### A. Browser Compatibility
- Test di berbagai browser
- Pastikan responsive design berfungsi

### B. Performance
- Optimize loading CSS/JS
- Compress images dari template

## 7. Deployment

### A. Production Setup
- Minify CSS/JS
- Optimize database queries
- Setup proper error handling

### B. Security
- Validate all inputs
- Sanitize output
- Use HTTPS in production
