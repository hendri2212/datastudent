# Data Student Application

Aplikasi ini digunakan untuk mengelola dan menampilkan data siswa secara lengkap dan terstruktur.

## Fitur Utama

- **Detail Data Siswa Sangat Mendalam**:
  Menampilkan informasi menyeluruh mengenai siswa, meliputi:
  - **Data Diri**: Informasi pribadi lengkap.
  - **Data Akademik/Sekolah**: Nama sekolah, jurusan (opsional/tidak wajib), dan tahun angkatan.
  - **Data Keluarga**: Detail orang tua, wali, dan kontak darurat.
  - **Data Sosial Media**: Tautan profil sosial media siswa untuk komunikasi dan interaksi.
  - **Data Riwayat Pendidikan (History)**: Rekam jejak pendidikan formal siswa dari jenjang sebelumnya.
  - Dan data penunjang lainnya.
  - *Fitur Kemudahan*: Setiap data yang berupa teks dilengkapi dengan **tombol copy** sehingga pengguna dapat dengan mudah menyalin data tersebut.

- **Bukti Dokumen Resmi (Scan)**:
  Setiap data dilengkapi dengan bukti hasil scan dokumen resmi pendukung untuk memastikan validitas data yang diinput.
  - *Fitur Dokumen*: Untuk dokumen hasil scan, tersedia fitur **download** (unduh) dan **share** (bagikan) langsung ke sosial media.
  - *Penyimpanan Aman*: File hasil scan disimpan secara aman di **storage private Laravel** (bukan public) guna melindungi privasi dan keamanan data dokumen resmi siswa.

- **Verifikasi & Keamanan Data (Kunci Data)**:
  Data yang bersifat statis atau tidak mudah berubah (seperti nama, tanggal lahir, riwayat pendidikan, dll.) akan otomatis **dikunci setelah diverifikasi oleh Wali Kelas**. 
  - Setelah dikunci, siswa hanya dapat memperbarui data yang sifatnya dinamis/mudah berubah (seperti nomor handphone, akun sosial media, dll.).
  - *Pencatatan Verifikasi*: Sistem akan mencatat riwayat di database mengenai siapa (Wali Kelas/Verifikator) yang melakukan verifikasi data siswa tersebut demi keperluan audit.