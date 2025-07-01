# Sistem KRS (Kartu Rencana Studi) - Universitas Touhou Indonesia

Sistem KRS adalah aplikasi web untuk mengelola Kartu Rencana Studi mahasiswa yang dibangun dengan teknologi modern menggunakan PHP untuk backend dan Next.js dengan React untuk frontend.

## 🚀 Fitur Utama

### Untuk Mahasiswa
- **Dashboard Interaktif** - Melihat ringkasan akademik dan statistik
- **Pengisian KRS** - Memilih mata kuliah dengan validasi otomatis
- **Jadwal Kuliah** - Melihat jadwal kuliah yang telah dipilih
- **Transkrip Nilai** - Melihat riwayat nilai dan IPK
- **Profil Mahasiswa** - Mengelola data pribadi

### Untuk Dosen
- **Manajemen Jadwal** - Mengelola jadwal mengajar
- **Input Nilai** - Memasukkan nilai mahasiswa
- **Absensi** - Mencatat kehadiran mahasiswa
- **Laporan** - Melihat laporan akademik

### Untuk Admin
- **Manajemen Pengguna** - Mengelola data mahasiswa dan dosen
- **Manajemen Mata Kuliah** - CRUD mata kuliah dan jadwal
- **Laporan Sistem** - Laporan komprehensif sistem
- **Pengaturan** - Konfigurasi sistem

## 🛠️ Teknologi yang Digunakan

### Backend
- **PHP 8.0+** - Server-side scripting
- **MySQL** - Database management
- **PDO** - Database abstraction layer

### Frontend
- **Next.js 14** - React framework dengan App Router
- **React 18** - UI library
- **TypeScript** - Type-safe JavaScript
- **Tailwind CSS** - Utility-first CSS framework
- **shadcn/ui** - Modern UI components
- **Lucide React** - Icon library

### Development Tools
- **Composer** - PHP dependency management
- **npm/yarn** - Node.js package management
- **Git** - Version control

## 📋 Persyaratan Sistem

### Server Requirements
- PHP 8.0 atau lebih tinggi
- MySQL 5.7 atau MariaDB 10.3+
- Apache/Nginx web server
- Node.js 18+ (untuk development frontend)

### PHP Extensions
- PDO MySQL
- JSON
- Session
- Hash

## 🚀 Instalasi

### 1. Clone Repository
```bash
git clone https://github.com/username/sistem-krs.git
cd sistem-krs
```

### 2. Setup Database
```bash
# Import database schema
mysql -u root -p < database/krs_database.sql

# Atau gunakan quick setup
mysql -u root -p < database/quick-setup.sql
```

### 3. Konfigurasi Database
Edit file `config/database.php`:
```php
private $host = 'localhost';
private $db_name = 'sistem_krs';
private $username = 'your_username';
private $password = 'your_password';
```

### 4. Setup Frontend (Opsional)
```bash
# Install dependencies
npm install

# Build untuk production
npm run build

# Atau jalankan development server
npm run dev
```

### 5. Konfigurasi Web Server

#### Apache (.htaccess)
```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]
```

#### Nginx
```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```

## 📁 Struktur Direktori

```
sistem-krs/
├── app/                    # Next.js App Router pages
│   ├── jadwal/            # Jadwal page
│   ├── krs/               # KRS management
│   └── layout.tsx         # Root layout
├── components/            # React components
│   ├── ui/                # shadcn/ui components
│   ├── app-sidebar.tsx    # Sidebar component
│   └── stats-card.tsx     # Statistics card
├── config/                # Configuration files
│   ├── config.php         # App configuration
│   └── database.php       # Database configuration
├── database/              # Database files
│   ├── krs_database.sql   # Main database schema
│   └── quick-setup.sql    # Quick setup script
├── includes/              # PHP includes
│   └── auth.php           # Authentication class
├── assets/                # Static assets
│   ├── css/               # Stylesheets
│   ├── js/                # JavaScript files
│   └── images/            # Images
├── uploads/               # File uploads
└── *.php                  # PHP pages
```

## 🔐 Sistem Autentikasi

### Multi-Role Authentication
Sistem mendukung 3 jenis pengguna:
- **Mahasiswa** - Login dengan NIM
- **Dosen** - Login dengan NIDN
- **Admin** - Login dengan username

### Registrasi
- Mahasiswa dapat mendaftar sendiri melalui halaman registrasi
- Dosen dan Admin didaftarkan oleh administrator sistem

### Keamanan
- Password di-hash menggunakan PHP `password_hash()`
- Session management untuk autentikasi
- Role-based access control

## 📊 Database Schema

### Tabel Utama
- `mahasiswa` - Data mahasiswa
- `dosen` - Data dosen
- `mata_kuliah` - Data mata kuliah
- `jadwal_kuliah` - Jadwal perkuliahan
- `krs` - Kartu Rencana Studi
- `nilai` - Nilai mahasiswa
- `absensi` - Data kehadiran

### Relasi Database
- One-to-Many: Dosen → Jadwal Kuliah
- Many-to-Many: Mahasiswa ↔ Mata Kuliah (melalui KRS)
- One-to-Many: KRS → Nilai

## 🎨 UI/UX Design

### Design System
- **Modern & Clean** - Interface yang bersih dan intuitif
- **Responsive** - Mendukung desktop, tablet, dan mobile
- **Dark/Light Mode** - Theme switching (dalam pengembangan)
- **Accessibility** - Mengikuti standar WCAG

### Color Palette
- Primary: Blue gradient (#4facfe → #00f2fe)
- Secondary: Purple gradient (#667eea → #764ba2)
- Success: Green (#10b981)
- Warning: Yellow (#f59e0b)
- Error: Red (#ef4444)

## 🔧 Konfigurasi

### Environment Variables
```php
// config/config.php
define('BASE_URL', 'http://localhost/sistem-krs/');
define('APP_NAME', 'Sistem KRS - Universitas Touhou Indonesia');
define('APP_VERSION', '2.0.0');
```

### Database Configuration
```php
// config/database.php
class Database {
    private $host = 'localhost';
    private $db_name = 'sistem_krs';
    private $username = 'root';
    private $password = '';
}
```

## 📱 API Endpoints

### Authentication
- `POST /login.php` - User login
- `POST /register.php` - Student registration
- `GET /logout.php` - User logout

### KRS Management
- `GET /krs.php` - View KRS
- `POST /krs-save.php` - Save KRS selections
- `GET /jadwal.php` - View schedule

### Admin Functions
- `GET /admin-mahasiswa.php` - Manage students
- `GET /admin-dosen.php` - Manage lecturers
- `GET /admin-matakuliah.php` - Manage courses

## 🧪 Testing

### Manual Testing
1. Test registrasi mahasiswa baru
2. Test login dengan berbagai role
3. Test pengisian KRS dengan validasi
4. Test responsive design di berbagai device

### Database Testing
```bash
# Test koneksi database
php test-connection.php

# Test setup database
php test-setup.php
```

## 🚀 Deployment

### Production Setup
1. Upload files ke web server
2. Import database schema
3. Update konfigurasi database
4. Set proper file permissions
5. Configure web server

### File Permissions
```bash
chmod 755 sistem-krs/
chmod 644 *.php
chmod 755 uploads/
chmod 644 config/*.php
```

## 🔍 Troubleshooting

### Common Issues

#### Database Connection Error
```
Solution: Check database credentials in config/database.php
```

#### Session Issues
```
Solution: Ensure session_start() is called and check PHP session configuration
```

#### File Upload Issues
```
Solution: Check uploads/ directory permissions and PHP upload settings
```

### Debug Mode
Enable error reporting untuk development:
```php
// Tambahkan di config/config.php
ini_set('display_errors', 1);
error_reporting(E_ALL);
```

## 📈 Roadmap

### Version 2.1 (Planned)
- [ ] Real-time notifications
- [ ] Mobile app (React Native)
- [ ] Advanced reporting dashboard
- [ ] Integration with payment gateway

### Version 2.2 (Future)
- [ ] AI-powered course recommendations
- [ ] Video conferencing integration
- [ ] Advanced analytics
- [ ] Multi-language support

## 🤝 Contributing

### Development Workflow
1. Fork repository
2. Create feature branch
3. Make changes
4. Test thoroughly
5. Submit pull request

### Code Standards
- Follow PSR-12 untuk PHP
- Use TypeScript untuk React components
- Follow conventional commits
- Add comments untuk complex logic

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 👥 Team

### Development Team
- **Backend Developer** - PHP & MySQL
- **Frontend Developer** - React & Next.js
- **UI/UX Designer** - Interface Design
- **Database Administrator** - Database Design

### Contact
- **Email**: admin@university.ac.id
- **Website**: https://university.ac.id
- **Support**: https://university.ac.id/support

## 🙏 Acknowledgments

- Universitas Touhou Indonesia
- shadcn/ui untuk komponen UI
- Tailwind CSS untuk styling
- Lucide untuk icon set
- Next.js team untuk framework

---

**Sistem KRS v2.0.0** - Built with ❤️ for Universitas Touhou Indonesia
```
```

