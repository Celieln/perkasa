# Sistem PERKASA

<p align="center">
  Sistem data tenaga kerja PERKASA - import Excel massal, tracking, dan klaim.
</p>

<p align="center">
  <img src="https://img.shields.io/badge/PHP-8.x-%23777BB4?style=for-the-badge&logo=php&logoColor=white"/>
  <img src="https://img.shields.io/badge/Bootstrap-5-%237952B3?style=for-the-badge&logo=bootstrap&logoColor=white"/>
  <img src="https://img.shields.io/badge/License-MIT-green?style=for-the-badge"/>
  <img src="https://img.shields.io/badge/PRs-Welcome-brightgreen?style=for-the-badge"/>
</p>



## Highlight

- **Front-end first** - repository berisi tampilan depan (public UI) yang siap jalan
- **Ringan & cepat** - tanpa framework berat, load cepat
- **Mudah di-deploy** - cukup PHP + database, tanpa setup rumit
- **Keamanan dasar terpasang** - prepared statements, sanitization, password hashing

## Fitur Utama

- Bulk import Excel (92K+ records)
- Auto column-mapping + rollback
- Tracking kode peserta
- Klaim & pencairan
- Lokasi & pelaporan

## Teknologi

<details>
<summary><b>Lihat detail teknologi</b></summary>

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
</details>

## Struktur Proyek

```
perkasa
  includes/    # Komponen yang di-include (header, footer, dll)
  assets/      # CSS, JS, gambar
  *.php        # Halaman tampilan depan
```

## Menjalankan

Prasyarat: [Laragon](https://laragon.org) / [XAMPP](https://www.apachefriends.org)

1. Clone repository:

   ```bash
   git clone https://github.com/Celieln/perkasa.git
   ```

2. Letakkan folder di `laragon/www/` atau `htdocs/`.
3. Buka `http://localhost/perkasa`.

## Kontribusi

Kontribusi sangat diterima! Baca [CONTRIBUTING](CONTRIBUTING.md) dahulu, lalu buat Pull Request atau buka [Issues](https://github.com/Celieln/perkasa/issues) untuk melaporkan bug / request fitur.

## Lisensi

Distributed under the [MIT](LICENSE) License. (c) [Celieln](https://github.com/Celieln)
