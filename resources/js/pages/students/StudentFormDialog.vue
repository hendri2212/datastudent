<template>
  <Dialog v-model:open="isOpen">
    <DialogContent class="max-w-3xl max-h-[85vh] overflow-y-auto p-5 text-xs bg-white dark:bg-neutral-900 rounded-xl border border-neutral-200 dark:border-neutral-800">
      <DialogHeader class="border-b pb-2">
        <DialogTitle class="text-sm font-bold text-neutral-800 dark:text-neutral-100 flex items-center gap-2">
          <FileText class="size-4 text-indigo-600" />
          {{ form.id ? 'Perbarui Relasi & Data Pokok Siswa' : 'Form Registrasi Siswa Baru (Multi-Tabel)' }}
        </DialogTitle>
        <DialogDescription class="text-[10px] mt-0.5">
          Data formulir dipetakan langsung ke tabel `students`, `student_family`, `student_education_history`, `student_healths`, dan `student_documents`.
        </DialogDescription>
      </DialogHeader>

      <div class="py-3 space-y-4">
        <div v-if="form.is_locked" class="p-3 bg-amber-50 dark:bg-amber-950/20 text-amber-700 dark:text-amber-400 border border-amber-200 dark:border-amber-900 rounded-lg flex items-start gap-2">
          <Lock class="size-4 shrink-0 mt-0.5" />
          <p class="leading-relaxed">
            <strong>Status Data Terkunci (is_locked = true):</strong> Kolom Pokok Akademik dan Dokumen Resmi di bawah ini tidak dapat diubah tanpa izin otorisasi Super Admin. Hubungi Kepala Urusan Administrasi Tata Usaha untuk membuka kunci data.
          </p>
        </div>

        <div class="space-y-3">
          <h3 class="font-bold text-neutral-700 dark:text-neutral-300 border-b pb-1 flex items-center gap-1">
            <UserCheck class="size-3.5 text-neutral-500" /> I. Informasi Dasar & Identitas Pokok Murid
          </h3>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
            <div class="space-y-1">
              <label class="font-bold text-neutral-600 dark:text-neutral-400">Nama Lengkap Murid</label>
              <Input v-model="form.name" type="text" placeholder="Masukkan nama sesuai ijazah" class="h-8 text-xs rounded-lg bg-neutral-50 dark:bg-neutral-950" :disabled="form.is_locked" />
            </div>
            <div class="space-y-1">
              <label class="font-bold text-neutral-600 dark:text-neutral-400">Nomor Induk Siswa (NIS)</label>
              <Input v-model="form.nis" type="text" placeholder="Contoh: 22001" class="h-8 text-xs font-mono rounded-lg bg-neutral-50 dark:bg-neutral-950" :disabled="form.is_locked" />
            </div>
            <div class="space-y-1">
              <label class="font-bold text-neutral-600 dark:text-neutral-400">NISN (10 Digit Resmi)</label>
              <Input v-model="form.nisn" type="text" placeholder="Contoh: 0071234567" class="h-8 text-xs font-mono rounded-lg bg-neutral-50 dark:bg-neutral-950" :disabled="form.is_locked" />
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
            <div class="space-y-1">
              <label class="font-bold text-neutral-600 dark:text-neutral-400">Tempat Lahir</label>
              <Input v-model="form.birth_place" type="text" placeholder="Kotabaru" class="h-8 text-xs rounded-lg bg-neutral-50 dark:bg-neutral-950" />
            </div>
            <div class="space-y-1">
              <label class="font-bold text-neutral-600 dark:text-neutral-400">Tanggal Lahir</label>
              <Input v-model="form.birth_date" type="date" class="h-8 text-xs rounded-lg bg-neutral-50 dark:bg-neutral-950" />
            </div>
            <div class="space-y-1">
              <label class="font-bold text-neutral-600 dark:text-neutral-400">Jenis Kelamin</label>
              <select v-model="form.gender_id" class="w-full h-8 text-xs px-2 border rounded-lg bg-neutral-50 dark:bg-neutral-950 text-neutral-800 dark:text-neutral-200 border-neutral-200 dark:border-neutral-800 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                <option v-for="g in internalGenders" :key="g.id" :value="g.id">{{ g.name }}</option>
              </select>
            </div>
            <div class="space-y-1">
              <label class="font-bold text-neutral-600 dark:text-neutral-400">Agama</label>
              <select v-model="form.religion_id" class="w-full h-8 text-xs px-2 border rounded-lg bg-neutral-50 dark:bg-neutral-950 text-neutral-800 dark:text-neutral-200 border-neutral-200 dark:border-neutral-800 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                <option v-for="r in internalReligions" :key="r.id" :value="r.id">{{ r.name }}</option>
              </select>
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <div class="space-y-1">
              <label class="font-bold text-neutral-600 dark:text-neutral-400">Email Akun Murid</label>
              <Input v-model="form.email" type="email" placeholder="nama@student.sch.id" class="h-8 text-xs rounded-lg bg-neutral-50 dark:bg-neutral-950" />
            </div>
            <div class="space-y-1">
              <label class="font-bold text-neutral-600 dark:text-neutral-400">Nomor Handphone Aktif (WhatsApp)</label>
              <Input v-model="form.phone" type="text" placeholder="08XXXXXXXXXX" class="h-8 text-xs font-mono rounded-lg bg-neutral-50 dark:bg-neutral-950" />
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <div class="space-y-1">
              <label class="font-bold text-neutral-600 dark:text-neutral-400 flex items-center gap-1">
                <Instagram class="size-3 text-neutral-500" /> URL Profil Instagram
              </label>
              <Input v-model="form.instagram_url" type="url" placeholder="https://instagram.com/username" class="h-8 text-xs rounded-lg bg-neutral-50 dark:bg-neutral-950" />
            </div>
            <div class="space-y-1">
              <label class="font-bold text-neutral-600 dark:text-neutral-400 flex items-center gap-1">
                <Globe class="size-3 text-neutral-500" /> URL LinkedIn / Media Sosial Lain
              </label>
              <Input v-model="form.linkedin_url" type="url" placeholder="https://linkedin.com/in/username" class="h-8 text-xs rounded-lg bg-neutral-50 dark:bg-neutral-950" />
            </div>
          </div>

          <div class="space-y-1">
            <label class="font-bold text-neutral-600 dark:text-neutral-400">Alamat Rumah Domisili Lengkap (Sesuai KK)</label>
            <textarea v-model="form.address" rows="2" placeholder="Tuliskan nama jalan, RT, RW, Kelurahan, Kecamatan, dan Kabupaten/Kota" class="w-full text-xs p-2 border border-neutral-200 dark:border-neutral-800 rounded-lg bg-neutral-50 dark:bg-neutral-950 text-neutral-800 dark:text-neutral-200 focus:outline-none focus:ring-1 focus:ring-indigo-500 resize-none"></textarea>
          </div>
        </div>

        <div class="space-y-3 pt-2">
          <h3 class="font-bold text-neutral-700 dark:text-neutral-300 border-b pb-1 flex items-center gap-1">
            <School class="size-3.5 text-neutral-500" /> II. Pemetaan Relasi Kelas & Kompetensi Keahlian
          </h3>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
            <div class="space-y-1">
              <label class="font-bold text-neutral-600 dark:text-neutral-400">Kompetensi Keahlian / Jurusan (`major_id` FK)</label>
              <select v-model="form.major_id" class="w-full h-8 text-xs px-2 border rounded-lg bg-neutral-50 dark:bg-neutral-950 text-neutral-800 dark:text-neutral-200 border-neutral-200 dark:border-neutral-800 focus:outline-none focus:ring-1 focus:ring-indigo-500" :disabled="form.is_locked">
                <option v-for="m in internalMajors" :key="m.id" :value="m.id">[{{ m.code }}] {{ m.name }}</option>
              </select>
            </div>
            <div class="space-y-1">
              <label class="font-bold text-neutral-600 dark:text-neutral-400">Penempatan Kelas (`classroom_id` FK)</label>
              <select v-model="form.classroom_id" class="w-full h-8 text-xs px-2 border rounded-lg bg-neutral-50 dark:bg-neutral-950 text-neutral-800 dark:text-neutral-200 border-neutral-200 dark:border-neutral-800 focus:outline-none focus:ring-1 focus:ring-indigo-500" :disabled="form.is_locked">
                <option v-for="c in internalClassrooms" :key="c.id" :value="c.id">{{ c.name }}</option>
              </select>
            </div>
            <div class="space-y-1">
              <label class="font-bold text-neutral-600 dark:text-neutral-400">Status Keaktifan (`student_status_id` FK)</label>
              <select v-model="form.student_status_id" class="w-full h-8 text-xs px-2 border rounded-lg bg-neutral-50 dark:bg-neutral-950 text-neutral-800 dark:text-neutral-200 border-neutral-200 dark:border-neutral-800 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                <option v-for="s in internalStudentStatuses" :key="s.id" :value="s.id">{{ s.name }}</option>
              </select>
            </div>
          </div>
        </div>

        <div class="space-y-3 pt-2">
          <h3 class="font-bold text-neutral-700 dark:text-neutral-300 border-b pb-1 flex items-center gap-1">
            <Users class="size-3.5 text-neutral-500" /> III. Entitas Hubungan Keluarga & Orang Tua (`student_family`)
          </h3>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <div class="p-3 bg-neutral-50 dark:bg-neutral-800 border border-neutral-100 dark:border-neutral-700/60 rounded-xl space-y-2">
              <span class="text-[10px] font-bold text-neutral-400 block tracking-wide">JALUR AYAH KANDUNG</span>
              <div class="space-y-1">
                <label class="font-medium text-neutral-600 dark:text-neutral-400">Nama Lengkap Ayah</label>
                <Input v-model="form.family.father_name" type="text" placeholder="Nama ayah kandung" class="h-8 text-xs bg-white dark:bg-neutral-950" />
              </div>
              <div class="space-y-1">
                <label class="font-medium text-neutral-600 dark:text-neutral-400">No. Telp Ayah</label>
                <Input v-model="form.family.father_phone" type="text" placeholder="08XXXXXXXXXX" class="h-8 text-xs font-mono bg-white dark:bg-neutral-950" />
              </div>
            </div>

            <div class="p-3 bg-neutral-50 dark:bg-neutral-800 border border-neutral-100 dark:border-neutral-700/60 rounded-xl space-y-2">
              <span class="text-[10px] font-bold text-neutral-400 block tracking-wide">JALUR IBU KANDUNG</span>
              <div class="space-y-1">
                <label class="font-medium text-neutral-600 dark:text-neutral-400">Nama Lengkap Ibu</label>
                <Input v-model="form.family.mother_name" type="text" placeholder="Nama ibu kandung" class="h-8 text-xs bg-white dark:bg-neutral-950" />
              </div>
              <div class="space-y-1">
                <label class="font-medium text-neutral-600 dark:text-neutral-400">No. Telp Ibu</label>
                <Input v-model="form.family.mother_phone" type="text" placeholder="08XXXXXXXXXX" class="h-8 text-xs font-mono bg-white dark:bg-neutral-950" />
              </div>
            </div>
          </div>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <div class="space-y-1">
              <label class="font-bold text-neutral-600 dark:text-neutral-400">Nama Wali Murid</label>
              <Input v-model="form.family.guardian_name" type="text" placeholder="Nama wali" class="h-8 text-xs rounded-lg bg-neutral-50 dark:bg-neutral-950" />
            </div>
            <div class="space-y-1">
              <label class="font-bold text-neutral-600 dark:text-neutral-400">No. Telp Wali Murid</label>
              <Input v-model="form.family.guardian_phone" type="text" placeholder="08XXXXXXXXXX" class="h-8 text-xs font-mono rounded-lg bg-neutral-50 dark:bg-neutral-950" />
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-3 p-3 bg-amber-50/40 dark:bg-neutral-800/40 border border-amber-100/70 dark:border-neutral-700/60 rounded-xl mt-3">
            <div class="space-y-1">
              <label class="font-bold text-amber-800 dark:text-neutral-400">Nama Kontak Darurat</label>
              <Input v-model="form.family.emergency_contact_name" type="text" placeholder="Contoh: Paman / Kakak Kandung" class="w-full h-8 text-xs bg-white dark:bg-neutral-950 rounded-lg" />
            </div>
            <div class="space-y-1">
              <label class="font-bold text-amber-800 dark:text-neutral-400">No. HP Darurat</label>
              <Input v-model="form.family.emergency_contact_phone" type="text" placeholder="08XXXXXXXXXX" class="w-full h-8 text-xs font-mono bg-white dark:bg-neutral-950 rounded-lg" />
            </div>
          </div>
          </div>
        </div>

        <div class="space-y-3 pt-2">
          <h3 class="font-bold text-neutral-700 dark:text-neutral-300 border-b pb-1 flex items-center gap-1">
            <ClipboardList class="size-3.5 text-neutral-500" /> IV. Riwayat Pendidikan Sebelumnya (`student_education_history`)
          </h3>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
            <div class="space-y-1 md:col-span-2">
              <label class="font-bold text-neutral-600 dark:text-neutral-400">Nama Sekolah Asal (SMP / MTs)</label>
              <Input v-model="form.education_history.school_name" type="text" placeholder="Masukkan nama sekolah asal resmi" class="h-8 text-xs rounded-lg bg-neutral-50 dark:bg-neutral-950" />
            </div>
            <div class="space-y-1">
              <label class="font-bold text-neutral-600 dark:text-neutral-400">NPSN Asal Sekolah</label>
              <Input v-model="form.education_history.npsn" type="text" placeholder="Contoh: 30102040" class="h-8 text-xs font-mono rounded-lg bg-neutral-50 dark:bg-neutral-950" />
            </div>
          </div>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
            <div class="space-y-1">
              <label class="font-bold text-neutral-600 dark:text-neutral-400">Tahun Masuk</label>
              <Input v-model="form.education_history.entry_year" type="text" placeholder="Contoh: 2021" class="h-8 text-xs font-mono rounded-lg bg-neutral-50 dark:bg-neutral-950" />
            </div>
            <div class="space-y-1">
              <label class="font-bold text-neutral-600 dark:text-neutral-400">Tahun Lulus</label>
              <Input v-model="form.education_history.graduation_year" type="text" placeholder="Contoh: 2024" class="h-8 text-xs font-mono rounded-lg bg-neutral-50 dark:bg-neutral-950" />
            </div>
            <div class="space-y-1">
              <label class="font-bold text-neutral-600 dark:text-neutral-400">Nilai Akhir Ujian</label>
              <Input v-model="form.education_history.final_score" type="text" placeholder="Contoh: 85.50" class="h-8 text-xs font-mono rounded-lg bg-neutral-50 dark:bg-neutral-950" />
            </div>
          </div>
        </div>

       <div class="space-y-3 pt-2">
          <h3 class="font-bold text-neutral-700 dark:text-neutral-300 border-b pb-1 flex items-center gap-1">
            <Heart class="size-3.5 text-neutral-500" /> V. Informasi Kondisi Kesehatan Fisik & Medis (`student_healths`)
          </h3>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
            <div class="space-y-1">
              <label class="font-bold text-neutral-600 dark:text-neutral-400">Tinggi Badan <span class="text-[10px] text-neutral-400">(Centimeter)</span></label>
              <Input v-model="form.health.height" type="text" placeholder="Contoh: 165" class="h-8 text-xs font-mono rounded-lg bg-neutral-50 dark:bg-neutral-950" />
            </div>
            <div class="space-y-1">
              <label class="font-bold text-neutral-600 dark:text-neutral-400">Berat Badan <span class="text-[10px] text-neutral-400">(Kilogram)</span></label>
              <Input v-model="form.health.weight" type="text" placeholder="Contoh: 55" class="h-8 text-xs font-mono rounded-lg bg-neutral-50 dark:bg-neutral-950" />
            </div>
            <div class="space-y-1">
              <label class="font-bold text-neutral-600 dark:text-neutral-400">Golongan Darah</label>
                <select v-model="form.health.blood_type_id" class="w-full h-8 text-xs px-2 border rounded-lg bg-neutral-50 dark:bg-neutral-950 text-neutral-800 dark:text-neutral-200 border-neutral-200 dark:border-neutral-800 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                    <option :value="1">A</option>
                    <option :value="2">B</option>
                    <option :value="3">AB</option>
                    <option :value="4">O</option>
                </select>
            </div>
          </div>
          
          <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <div class="space-y-1">
              <label class="font-bold text-neutral-600 dark:text-neutral-400">Daftar Kontraindikasi Alergi Medis</label>
              <Input v-model="form.health.allergies" type="text" placeholder="Tulis tidak ada jika bersih" class="h-8 text-xs rounded-lg bg-neutral-50 dark:bg-neutral-950" />
            </div>
            <div class="space-y-1">
              <label class="font-bold text-neutral-600 dark:text-neutral-400">Riwayat Penyakit Bawaan Kronis</label>
              <Input v-model="form.health.medical_history" type="text" placeholder="Contoh: Asma, Jantung, dll (Tulis tidak ada jika bersih)" class="h-8 text-xs rounded-lg bg-neutral-50 dark:bg-neutral-950" />
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-3 p-3 bg-neutral-50 dark:bg-neutral-800/50 border border-neutral-200/60 dark:border-neutral-700/60 rounded-xl">
            <div class="space-y-1">
              <label class="font-bold text-neutral-600 dark:text-neutral-400">Fasilitas Kesehatan Rujukan Utama</label>
              <Input v-model="form.health.hospital" type="text" placeholder="Contoh: RSUD Kota Sehat" class="h-8 text-xs bg-white dark:bg-neutral-950 rounded-lg" />
            </div>
            <div class="space-y-1">
              <label class="font-bold text-neutral-600 dark:text-neutral-400">Dokter / PIC Penanggung Jawab</label>
              <Input v-model="form.health.doctor" type="text" placeholder="Contoh: dr. Budi Santoso" class="h-8 text-xs bg-white dark:bg-neutral-950 rounded-lg" />
            </div>
          </div>
        </div>

        <div class="space-y-3 pt-2">
          <h3 class="font-bold text-neutral-700 dark:text-neutral-300 border-b pb-1 flex items-center gap-1">
            <FileArchive class="size-3.5 text-neutral-500" /> VI. Unggah Dokumen Pendukung Resmi (`student_documents`)
          </h3>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <div v-for="docType in ['Kartu Keluarga (KK)', 'Akta Kelahiran', 'Ijazah SD', 'Ijazah SMP', 'KTP / KIA']" :key="docType" class="p-3 bg-neutral-50 dark:bg-neutral-800 rounded-xl border border-dashed border-neutral-300 dark:border-neutral-700 flex flex-col justify-center gap-2">
              <span class="text-[10px] font-bold text-neutral-700 dark:text-neutral-300">{{ docType }}</span>
              <div class="flex items-center gap-2">
                <label :class="['cursor-pointer bg-white dark:bg-neutral-900 border border-neutral-200 dark:border-neutral-700 text-[9px] font-semibold px-2 py-1.5 rounded-lg shadow-sm hover:bg-neutral-100 dark:hover:bg-neutral-800 transition whitespace-nowrap text-neutral-600 dark:text-neutral-300', form.is_locked ? 'opacity-50 cursor-not-allowed' : '']">
                  Pilih Berkas
                  <input type="file" class="hidden" accept=".pdf,.jpg,.jpeg,.png" @change="(e) => handleSpecificFileChange(e, docType)" :disabled="form.is_locked" />
                </label>
                <span class="text-[9px] text-neutral-500 truncate max-w-[150px]" :title="getDocumentName(docType)">
                  {{ getDocumentName(docType) || 'Belum ada berkas' }}
                </span>
              </div>
            </div>
          </div>
        </div>

        <div class="space-y-4 pt-2 border-t border-neutral-100 dark:border-neutral-800">
          
          <div class="space-y-2">
            <div class="flex items-center justify-between border-b pb-1">
              <h3 class="font-bold text-neutral-700 dark:text-neutral-300 flex items-center gap-1">
                <Award class="size-3.5 text-emerald-600" /> VII. Rekam Jejak Penghargaan & Prestasi
              </h3>
              <Button type="button" variant="outline" class="h-6 text-[10px] px-2 text-emerald-600 border-emerald-200 dark:border-emerald-900 bg-emerald-50/50 hover:bg-emerald-50" @click="addAchievementRow">
                + Tambah Prestasi
              </Button>
            </div>
            
            <div v-if="form.achievements?.length > 0" class="space-y-2">
              <div v-for="ach in form.achievements" :key="ach.id" class="p-3 bg-emerald-50/30 dark:bg-emerald-950/10 border border-emerald-100 dark:border-emerald-900/50 rounded-xl space-y-2 relative">
                <Button type="button" variant="ghost" class="absolute top-2 right-2 h-6 w-6 p-0 text-neutral-400 hover:text-red-500 rounded-full" @click="removeAchievementRow(ach)">
                  <X class="size-3.5" />
                </Button>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-2 pr-6">
                  <div class="space-y-1">
                    <label class="font-semibold text-[10px] text-neutral-500">Nama/Judul Prestasi</label>
                    <Input v-model="ach.title" type="text" placeholder="Contoh: Juara 1 LKS Web Tech" class="h-7 text-xs bg-white dark:bg-neutral-950" />
                  </div>
                  <div class="space-y-1">
                    <label class="font-semibold text-[10px] text-neutral-500">Kategori / Bidang</label>
                    <Input v-model="ach.category" type="text" placeholder="Contoh: AKADEMIK" class="h-7 text-xs bg-white dark:bg-neutral-950" />
                  </div>
                  <div class="grid grid-cols-2 gap-2">
                    <div class="space-y-1">
                      <label class="font-semibold text-[10px] text-neutral-500">Tingkat Skala</label>
                      <Input v-model="ach.level" type="text" placeholder="Nasional/Provinsi" class="h-7 text-xs bg-white dark:bg-neutral-950" />
                    </div>
                    <div class="space-y-1">
                      <label class="font-semibold text-[10px] text-neutral-500">Peringkat</label>
                      <Input v-model="ach.rank" type="text" placeholder="Juara 1" class="h-7 text-xs bg-white dark:bg-neutral-950" />
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div v-else class="text-neutral-400 p-3 text-center border border-dashed rounded-xl bg-neutral-50/30 text-[11px]">
              Belum ada data prestasi yang dimasukkan untuk siswa ini.
            </div>
          </div>

          <div class="space-y-2 pt-2">
            <div class="flex items-center justify-between border-b pb-1">
              <h3 class="font-bold text-neutral-700 dark:text-neutral-300 flex items-center gap-1">
                <AlertTriangle class="size-3.5 text-red-600" /> VIII. Catatan Pelanggaran Tata Tertib & Disiplin 
              </h3>
              <Button type="button" variant="outline" class="h-6 text-[10px] px-2 text-red-600 border-red-200 dark:border-red-900 bg-red-50/50 hover:bg-red-50" @click="addViolationRow">
                + Tambah Pelanggaran
              </Button>
            </div>
            
            <div v-if="form.violations?.length > 0" class="space-y-2">
              <div v-for="violation in form.violations" :key="violation.id" class="p-3 bg-red-50/30 dark:bg-red-950/10 border border-red-100 dark:border-red-900/50 rounded-xl space-y-2 relative">
                <Button type="button" variant="ghost" class="absolute top-2 right-2 h-6 w-6 p-0 text-neutral-400 hover:text-red-500 rounded-full" @click="removeViolationRow(violation)">
                  <X class="size-3.5" />
                </Button>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-2 pr-6">
                  <div class="space-y-1 md:col-span-1">
                    <label class="font-semibold text-[10px] text-neutral-500">Nama Pelanggaran</label>
                    <Input v-model="violation.title" type="text" placeholder="Contoh: Terlambat Masuk Sekolah" class="h-7 text-xs bg-white dark:bg-neutral-950" />
                  </div>
                  <div class="space-y-1">
                    <label class="font-semibold text-[10px] text-neutral-500">Tanggal Kejadian</label>
                    <Input v-model="violation.violation_date" type="date" class="h-7 text-xs bg-white dark:bg-neutral-950" />
                  </div>
                  <div class="space-y-1">
                    <label class="font-semibold text-[10px] text-neutral-500">Bobot Akumulasi Poin</label>
                    <Input v-model.number="violation.point" type="number" placeholder="5" class="h-7 text-xs font-mono bg-white dark:bg-neutral-950" />
                  </div>
                </div>
              </div>
            </div>
            <div v-else class="text-neutral-400 p-3 text-center border border-dashed rounded-xl bg-neutral-50/30 text-[11px]">
              Siswa bersih. Bebas dari catatan poin akumulasi pelanggaran kedisiplinan.
            </div>
          </div>
        </div>
      

      <DialogFooter class="border-t pt-2 gap-2">
        <Button variant="outline" @click="isOpen = false" class="h-8 text-xs font-bold rounded-lg px-4 border-neutral-200 dark:border-neutral-800">Batalkan Pengisian</Button>
        <Button @click="handleSubmit" class="h-8 text-xs font-bold bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg px-4 shadow-sm">
          {{ form.id ? 'Simpan Perubahan Relasi' : 'Kirim & Sinkronkan Relasi' }}
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>

<script setup lang="ts">
import { ref, watch, computed } from 'vue';
import { Button } from '@/components/ui/button';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { 
  FileText, 
  Lock, 
  UserCheck, 
  School, 
  Users, 
  ClipboardList, 
  Heart, 
  FileArchive,
  AlertTriangle,    
  Globe      // Tambahan Icon Sosial Media / Umum
} from '@lucide/vue';

const props = defineProps({
  open: Boolean,
  student: Object
});

const emit = defineEmits(['update:open', 'submit']);

// Master Data Relasi Internal Dokumen Komponen (Lookup Master)
const internalMajors = [
  { id: 1, name: 'Rekayasa Perangkat Lunak', code: 'RPL', head: 'Ir. H. Budi Utomo, M.T.' },
  { id: 2, name: 'Akuntansi dan Keuangan Lembaga', code: 'AKL', head: 'Hj. Siti Aminah, S.E., M.Ak.' },
  { id: 3, name: 'Desain Komunikasi Visual', code: 'DKV', head: 'Rendra Wijaya, S.Sn., M.Ds.' },
  { id: 4, name: 'Teknik Komputer dan Jaringan', code: 'TKJ', head: 'Anwar Sadat, S.Kom., M.Cs.' }
];
// Selaraskan juga internalClassrooms jika diperlukan agar pilihan kelasnya sinkron
const internalClassrooms = [
  { id: 1, name: 'X RPL 1', major_id: 1, grade: 'X' },
  { id: 2, name: 'XI RPL 1', major_id: 1, grade: 'XI' },
  { id: 3, name: 'XII RPL 1', major_id: 1, grade: 'XII' },
  { id: 4, name: 'X AKL 1', major_id: 2, grade: 'X' },
  { id: 5, name: 'XI AKL 1', major_id: 2, grade: 'XI' },
  { id: 6, name: 'XII AKL 1', major_id: 2, grade: 'XII' },
  { id: 7, name: 'XII DKV 1', major_id: 3, grade: 'XII' },
  { id: 8, name: 'XII TKJ 1', major_id: 4, grade: 'XII' }
];

const internalReligions = [
  { id: 1, name: 'Islam' }, { id: 2, name: 'Kristen Protestan' }, { id: 3, name: 'Katolik' }, { id: 4, name: 'Hindu' }, { id: 5, name: 'Buddha' }
];
const internalGenders = [
  { id: 1, name: 'Laki-laki' }, { id: 2, name: 'Perempuan' }
];
const internalStudentStatuses = [
  { id: 1, name: 'Aktif' }, { id: 2, name: 'Pindah' }, { id: 3, name: 'Lulus' }, { id: 4, name: 'Keluar (DO)' }
];

// Sinkronisasi state internal modal
const isOpen = computed({
  get: () => props.open,
  set: (val) => emit('update:open', val)
});

// Penampung State Formulir Basis Data
const form = ref<any>(getInitialFormState());

// Pemetaan Data Ketika Mode Ubah (Edit) Dipicu
watch(() => props.student, (newStudent) => {
  if (newStudent) {
    form.value = JSON.parse(JSON.stringify(newStudent));
    
    // Pastikan relasi turunan tabel sudah terbentuk agar tidak melempar error undefined
    if (!form.value.family) form.value.family = { father_name: '', father_phone: '', mother_name: '', mother_phone: '', guardian_name: '', guardian_phone: '' };
    if (!form.value.education_history) form.value.education_history = { school_name: '', npsn: '', final_score: '0.00' };
    if (!form.value.health) form.value.health = { height: '', weight: '', blood_type_id: 1, allergies: '' };
    if (!form.value.documents) form.value.documents = [];
  } else {
    form.value = getInitialFormState();
  }
}, { immediate: true });

// Fungsi Handler Penghargaan & Prestasi
function addAchievementRow() {
  form.value.achievements.push({
    id: 'ach-' + Math.random().toString(36).substring(2, 9),
    title: '',
    category: 'AKADEMIK',
    level: 'Kota',
    rank: 'Juara 1'
  });
}

function removeAchievementRow(targetAch: any) {
  form.value.achievements = form.value.achievements.filter((a: any) => a.id !== targetAch.id);
}

// Fungsi Handler Catatan Pelanggaran
function addViolationRow() {
  form.value.violations.push({
    id: 'vio-' + Math.random().toString(36).substring(2, 9),
    title: '',
    violation_date: new Date().toISOString().substring(0, 10),
    point: 5
  });
}

function removeViolationRow(targetViolation: any) {
  form.value.violations = form.value.violations.filter((v: any) => v.id !== targetViolation.id);
}

function getInitialFormState() {
  return {
    id: null,
    name: '',
    nis: '',
    nisn: '',
    email: '',
    phone: '',
    instagram_url: '', // Tambahan State Instagram
    linkedin_url: '',  // Tambahan State LinkedIn / Medsos lain
    birth_place: '',
    birth_date: '',
    address: '',
    notes: '',
    is_locked: false,
    
    classroom_id: 1,
    major_id: 1,
    religion_id: 1,
    gender_id: 1,
    student_status_id: 1,
    
    classroom: internalClassrooms[0],
    major: internalMajors[0],
    religion: internalReligions[0],
    gender: internalGenders[0],
    student_status: internalStudentStatuses[0],
    
    family: { father_name: '', father_phone: '', mother_name: '', mother_phone: '', guardian_name: '', guardian_phone: '' },
    education_history: { school_name: '', npsn: '', final_score: '0.00' },
    health: { height: '', weight: '', blood_type_id: 1, allergies: '' },
    documents: []
  };
}

// Menangani unggahan file secara spesifik berdasarkan tipe berkas
function handleSpecificFileChange(event: Event, type: string) {
  const target = event.target as HTMLInputElement;
  if (target.files && target.files.length > 0) {
    const file = target.files[0];
    
    if (!form.value.documents) {
      form.value.documents = [];
    }
    
    // Hapus berkas lama dengan tipe yang sama agar tidak menumpuk di array
    form.value.documents = form.value.documents.filter((d: any) => d.type !== type);
    
    // Push berkas baru yang diunggah ke array skema relasi dokumen
    form.value.documents.push({
      id: Math.floor(Math.random() * 10000),
      type: type,
      original_name: file.name,
      file_path: URL.createObjectURL(file) // Menggunakan blob lokal sebagai representasi path berkas sementara
    });
  }
}

// Mengambil nama berkas yang telah dipilih berdasarkan tipenya
function getDocumentName(type: string) {
  if (!form.value.documents) return '';
  const doc = form.value.documents.find((d: any) => d.type === type);
  return doc ? doc.original_name : '';
}



// Submit Form handler
function handleSubmit() {
  // Map ID pilihan ke Objek Relasi lengkap sebelum dikirim ke repositori
  form.value.major = internalMajors.find(m => m.id === Number(form.value.major_id)) || internalMajors[0];
  form.value.classroom = internalClassrooms.find(c => c.id === Number(form.value.classroom_id)) || internalClassrooms[0];
  form.value.religion = internalReligions.find(r => r.id === Number(form.value.religion_id)) || internalReligions[0];
  form.value.gender = internalGenders.find(g => g.id === Number(form.value.gender_id)) || internalGenders[0];
  form.value.student_status = internalStudentStatuses.find(s => s.id === Number(form.value.student_status_id)) || internalStudentStatuses[0];
  
  emit('submit', form.value);
}
</script>