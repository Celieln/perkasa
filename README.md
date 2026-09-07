# Sistem PERKASA

Sistem data tenaga kerja PERKASA - import Excel massal, tracking, dan klaim.

![PHP](https://img.shields.io/badge/PHP-8.x-777BB4?logo=php&logoColor=white)
![Bootstrap](https://img.shields.io/badge/Bootstrap-5-7952B3?logo=bootstrap&logoColor=white)
![License](https://img.shields.io/badge/License-MIT-green.svg)
![PRs Welcome](https://img.shields.io/badge/PRs-welcome-brightgreen.svg)
![Repo](https://img.shields.io/badge/Status-Aktif-blue)

## Teknologi

**Backend**
- PHP 8.x - server-side scripting dengan **PDO** + **prepared statements**
- Bulk import Excel (92K+ records) dengan column-mapping & rollback
- Tracking, klaim, pencairan, lokasi - workflow lengkap
- Session-based authentication (bcrypt) & role authorization

**Frontend**
- HTML5, CSS3, JavaScript (ES6+)
- Bootstrap 5 responsive dashboard
- DataTables / pagination untuk data besar skala

**Database**
- MySQL 8 / MariaDB - indexing untuk query 90K+ baris

**Tooling & DevOps**
- Composer, PHPSpreadsheet
- Git & GitHub
- Laragon/WAMP

## Arsitektur

- **Front-end first** - hanya berisi tampilan depan (public UI)
- Routing & layout modular (includes, pages)
- Keamanan berlapis: prepared statements, input sanitization, password hashing
- Session-based auth dengan bcrypt & role-based access control

## Quick Start

Prasyarat: [Laragon](https://laragon.org) / [XAMPP](https://www.apachefriends.org)

1. Clone repository ke folder laragon/www/ atau htdocs/:

   ```bash
   git clone https://github.com/Celieln/perkasa.git
   ```

2. Import database (jika tersedia) melalui phpMyAdmin.
3. Konfigurasi koneksi database di folder config/.
4. Jalankan server Apache. Buka http://localhost/perkasa.

## Struktur Proyek

```
perkasa/
  includes/    # Komponen yang di-include (header, footer, dll)
  assets/      # CSS, JS, gambar
  *.php        # Halaman tampilan depan
```

## Kontribusi

Kontribusi sangat diterima! Baca [CONTRIBUTING](CONTRIBUTING.md) dan buka [Issues](https://github.com/Celieln/perkasa/issues) untuk melaporkan bug / request fitur.

## Lisensi

[MIT](LICENSE) (c) [Celieln](https://github.com/Celieln)
