# GetDone Backend API (Laravel 12)

Backend REST API untuk platform produktivitas mahasiswa **GetDone**, dibangun menggunakan **Laravel 12**, **Laravel Sanctum** (token-based authentication), dan **PostgreSQL (Supabase)**.

---

## Daftar Isi
- [Tech Stack](#tech-stack)
- [Prasyarat](#prasyarat)
- [Setup Awal & Instalasi](#setup-awal--instalasi)
- [Menjalankan Server](#menjalankan-server)
- [Arsitektur & Struktur Direktori](#arsitektur--struktur-direktori)
- [Skema Database](#skema-database)
- [Daftar Endpoint API](#daftar-endpoint-api)
- [Autentikasi & Keamanan](#autentikasi--keamanan)

---

## Tech Stack
- **PHP**: `^8.2`
- **Framework**: Laravel `^12.0`
- **Autentikasi**: Laravel Sanctum `^4.0`
- **Database**: PostgreSQL (Supabase) melalui ekstensi PHP `pdo_pgsql` & `pgsql`
- **Testing & Tooling**: PHPUnit `^11.5.3`, Laravel Pint `^1.13`, Laravel Pail `^1.2.2`

---

## Prasyarat
1. **PHP 8.2+** dengan ekstensi aktif:
   - `pdo_pgsql` dan `pgsql` (pastikan baris `extension=pdo_pgsql` dan `extension=pgsql` tidak dikomentari di `php.ini`)
   - `mbstring`, `openssl`, `fileinfo` (diperlukan untuk upload avatar)
2. **Composer**
3. **Database PostgreSQL** (misalnya project Supabase atau PostgreSQL lokal)

---

## Setup Awal & Instalasi

1. **Masuk ke direktori backend**
   ```bash
   cd backend
   ```

2. **Install dependensi PHP**
   ```bash
   composer install
   ```

3. **Konfigurasi file `.env`**
   Salin `.env.example` (atau buat `.env`) dan sesuaikan kredensial aplikasi serta database PostgreSQL:
   ```env
   APP_NAME=GetDone
   APP_ENV=local
   APP_KEY=
   APP_DEBUG=true
   APP_URL=http://127.0.0.1:4000

   DB_CONNECTION=pgsql
   DB_HOST=your-supabase-host.supabase.co
   DB_PORT=5432
   DB_DATABASE=postgres
   DB_USERNAME=postgres
   DB_PASSWORD=your-password
   DB_SSLMODE=require
   ```

4. **Generate Application Key**
   ```bash
   php artisan key:generate
   ```

5. **Buat Symlink Storage** (untuk akses foto profil yang diunggah)
   ```bash
   php artisan storage:link
   ```

6. **Jalankan Migrasi Database**
   ```bash
   php artisan migrate
   ```

---

## Menjalankan Server

Jalankan server pengembangan Laravel pada port `4000` (sesuai konfigurasi default `VITE_API_URL` di frontend):

```bash
php artisan serve --host=127.0.0.1 --port=4000
```

Cek koneksi server & database melalui endpoint health-check:
```
GET http://127.0.0.1:4000/api/health
```

---

## Arsitektur & Struktur Direktori

Backend mengikuti pola arsitektur **Layered REST API** berbasis MVC Laravel:

```text
backend/
├── app/
│   ├── Http/
│   │   └── Controllers/
│   │       ├── Controller.php
│   │       └── Api/
│   │           ├── ApiController.php        # Base helper response JSON terstandar
│   │           ├── AuthController.php       # Register, login, logout (Sanctum)
│   │           ├── UserController.php       # Profil, ubah email, upload avatar, onboarding
│   │           ├── CourseController.php     # CRUD Mata Kuliah
│   │           ├── TaskController.php       # CRUD Tugas Akademik
│   │           └── AnalyticsController.php  # Ringkasan dashboard & pencatatan sesi fokus
│   └── Models/
│       ├── User.php                         # Model pengguna (getdone_users)
│       ├── Course.php                       # Model mata kuliah (getdone_courses)
│       ├── Task.php                         # Model tugas (getdone_tasks)
│       ├── FocusSession.php                 # Model sesi Pomodoro (getdone_focus_sessions)
│       └── GetDonePersonalAccessToken.php   # Custom Sanctum token model (getdone_personal_access_tokens)
├── database/
│   └── migrations/
│       ├── 2026_11_11_000001_create_getdone_tables.php
│       ├── 2026_11_25_000001_add_nim_to_getdone_users.php
│       └── 2026_11_25_000002_add_avatar_url_to_getdone_users.php
├── routes/
│   └── api.php                              # Definisi seluruh rute REST API (/api/*)
└── composer.json
```

### Pola Desain Utama
1. **Standardized JSON Envelope (`ApiController`)**: Seluruh respons API mengembalikan format seragam (`success`, `message`, `data`) sehingga mudah dikonsumsi oleh frontend Axios client.
2. **Custom Sanctum Token Model (`GetDonePersonalAccessToken`)**: Memisahkan tabel token akses (`getdone_personal_access_tokens`) dengan skema UUID untuk autentikasi stateless berbasis Bearer Token.
3. **Resource Ownership Scoping**: Setiap query pada `CourseController`, `TaskController`, dan `AnalyticsController` di-scope berdasarkan `auth()->id()` agar pengguna hanya dapat mengakses dan memodifikasi datanya sendiri.

---

## Skema Database

Seluruh tabel menggunakan prefix `getdone_`:

| Tabel | Deskripsi |
| :--- | :--- |
| `getdone_users` | Data mahasiswa (`id` UUID, `full_name`, `nim`, `email`, `university`, `avatar_url`, `password`, `onboarding_data` JSONB). |
| `getdone_courses` | Mata kuliah milik user (nama, kode, SKS, dosen, jadwal, ruangan, status semester). |
| `getdone_tasks` | Tugas kuliah milik user yang terhubung ke `getdone_courses` (judul, deskripsi, prioritas, tenggat, status). |
| `getdone_focus_sessions` | Riwayat sesi fokus Pomodoro (relasi ke user & tugas opsional, durasi menit, waktu selesai). |
| `getdone_personal_access_tokens` | Token autentikasi Laravel Sanctum. |

---

## Daftar Endpoint API

Semua endpoint berada di bawah prefix `/api`.

### Publik
| Method | Endpoint | Middleware | Deskripsi |
| :--- | :--- | :--- | :--- |
| `GET` | `/health` | - | Health check koneksi Laravel & PostgreSQL |
| `POST` | `/auth/register` | `throttle:6,1` | Registrasi akun mahasiswa baru (`full_name`, `nim`, `email`, `university`, `password`) |
| `POST` | `/auth/login` | `throttle:10,1` | Login dan menghasilkan Bearer Token Sanctum |

### Terproteksi (`auth:sanctum`)
| Method | Endpoint | Deskripsi |
| :--- | :--- | :--- |
| `POST` | `/auth/logout` | Mencabut token Sanctum aktif |
| `GET` | `/users/me` | Mengambil data profil user yang sedang login |
| `PUT` | `/users/profile` | Memperbarui data diri (`full_name`, `nim`, `university`, `avatar_url`) |
| `PUT` | `/users/email` | Memperbarui email dengan verifikasi password saat ini |
| `POST` | `/users/avatar` | Mengunggah file foto profil ke `storage/app/public/avatars` |
| `POST` | `/users/onboarding` | Menyimpan data onboarding akademik (tahun masuk, semester, mata kuliah awal) |
| `GET/POST/PUT/DELETE` | `/courses`, `/courses/{id}` | Manajemen CRUD Mata Kuliah |
| `GET/POST/PUT/DELETE` | `/tasks`, `/tasks/{id}` | Manajemen CRUD Tugas |
| `GET` | `/dashboard/summary` | Ringkasan statistik dashboard |
| `GET` | `/analytics` | Data analitik produktivitas & beban tugas |
| `POST` | `/analytics/focus-sessions` | Mencatat sesi fokus Pomodoro yang selesai |

---

## Autentikasi & Keamanan
- **Rate Limiting**: Endpoint `/auth/register` dibatasi `6 req/menit` dan `/auth/login` dibatasi `10 req/menit`.
- **Password Hashing**: Menggunakan bcrypt/hash bawaan Laravel (`Hash::make`).
- **Perubahan Email**: Memerlukan verifikasi kata sandi aktif sebelum email diperbarui.
- **CORS**: Dikonfigurasi pada `config/cors.php` agar mengizinkan origin frontend Vite (`http://localhost:5173`).
