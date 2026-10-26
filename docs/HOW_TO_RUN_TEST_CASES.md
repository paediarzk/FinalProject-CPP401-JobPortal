# Cara menjalankan test case (panduan langkah demi langkah)

Panduan ini berlaku untuk semua dokumen `TEST_CASES_*.md`.

## 1. Yang perlu disiapkan (sekali saja, ±15 menit)

1. XAMPP berjalan (Apache dan MySQL hijau).
2. Browser untuk menguji. Catat nama dan versinya (`edge://version`) untuk Bab 5.
3. Akun uji: administrator, 2 employer, 2 job seeker. Gunakan password yang mudah diingat (misalnya `Passw0rd1`).
4. Folder bukti, misalnya `Documents\FinalProject\Evidence\M3a\`.
5. Dokumen test case dibuka di Word atau VS Code, berdampingan dengan browser.
6. Alat tambahan, dibuka hanya jika test memintanya:
   - **phpMyAdmin** untuk memeriksa isi tabel.
   - **Console** browser (F12, tab Console) untuk test yang "memanipulasi form".
   - **Jendela pribadi** (Ctrl+Shift+N) sebagai browser kedua dengan login berbeda.

## 2. Membaca satu baris test case

| Kolom | Arti | Siapa yang mengisi |
|---|---|---|
| ID | Nomor, misalnya AD29 | Sudah ada |
| Scenario | Apa yang diuji | Sudah ada |
| Steps / Input | Apa yang Anda lakukan, dengan data konkret | Sudah ada |
| Expected Result | Hasil yang seharusnya | Sudah ada |
| **Actual Result** | Apa yang **benar-benar** terjadi | **Anda** |
| **Status** | Pass atau Fail | **Anda** |
| **Evidence** | Nama file tangkapan layar | **Anda** |

## 3. Langkah menjalankan SATU test case

1. **Siapkan kondisi awal.** Baca Steps. Jika butuh data (misalnya lowongan berstatus Pending), buat dulu.
2. **Lakukan langkahnya persis seperti tertulis.** Jangan menambah atau mengubah data di tengah jalan.
3. **Perhatikan hasilnya.** Lihat layar, dan jika Expected menyebut database, cek di phpMyAdmin (buka tabel, klik Browse, lihat kolomnya).
4. **Ambil tangkapan layar** (Win+Shift+S). Pastikan pesan hasil atau isi tabel terbaca jelas. Beri nama `AD29_approve.png` dan simpan di folder bukti.
5. **Bandingkan** hasil nyata dengan Expected Result.
6. **Isi Actual Result** dengan kalimat faktual (lihat bagian 4).
7. **Isi Status**: Pass jika sama persis, Fail jika berbeda. Isi Evidence dengan nama file.

## 4. Menulis Actual Result

Tulis apa yang terlihat, bukan sekadar "sesuai".

| Kurang baik | Baik |
|---|---|
| Sesuai | After pressing Approve, the system displayed "The vacancy has been approved and is now published." and the status in the list changed to Published. |
| Berhasil | Table `vacancies` shows status = published and moderation_note = NULL for id 12. |
| Error | The page showed "Vacancy not found." (HTTP 404); no row in `vacancies` was changed. |

Untuk test manipulasi, tulis juga apa yang Anda ubah:

> The hidden field `id` of the Reject form was changed to 999999 through the browser console. The system displayed "Vacancy not found." and the real vacancy remained Pending.

## 5. Test yang memakai Console (manipulasi form)

Tujuannya membuktikan bahwa server tidak mempercayai browser.

1. Buka halaman yang disebut di test, lalu tekan **F12** dan klik tab **Console**.
2. Jika browser memperingatkan soal menempel, ketik `allow pasting` lalu Enter. Peringatan itu hanya muncul setelah Anda mencoba menempel.
3. Salin perintah **dari dokumen** (jangan diketik ulang, supaya tanda kutip tidak berubah) dan tempel di Console, lalu Enter. Console menampilkan `undefined`; itu normal.
4. **Jangan memuat ulang halaman.** Perubahan hilang saat halaman dimuat ulang.
5. Isi bagian lain yang diminta test (misalnya alasan), lalu klik tombol yang disebut.
6. Ambil **dua** tangkapan layar: perintah di Console sebelum menekan tombol, dan hasilnya sesudahnya.

Jika muncul `Uncaught SyntaxError`: tanda kutip berubah saat disalin. Salin ulang dari dokumen.
Jika muncul `Cannot read properties of null`: Anda tidak berada di halaman yang disebut test.

## 6. Test yang memakai phpMyAdmin

- **Melihat isi tabel:** buka `http://localhost/phpmyadmin`, klik database `jobportal`, klik tabel, klik tab **Browse**.
- **Menjalankan SQL:** klik database `jobportal`, buka tab **SQL**, tempel perintah, klik **Go**. Pastikan database `jobportal` terpilih sebelum menekan Go.
- Setelah mengubah data lewat SQL, muat ulang halaman aplikasi (F5).

## 7. Test dengan dua akun sekaligus

1. Jendela biasa: login sebagai akun pertama.
2. Jendela pribadi (Ctrl+Shift+N): buka `http://localhost/jobportal/` dan login sebagai akun kedua.
3. Kedua jendela memiliki sesi sendiri. Jangan login dua akun berbeda di jendela yang sama.

## 8. Jika hasilnya Fail

1. **Jangan mengubah Fail menjadi Pass** dan jangan menghapus barisnya. Hasil Fail yang jujur memperkuat laporan.
2. Pastikan dulu bahwa Anda menjalankan langkahnya dengan benar (halaman yang tepat, data yang tepat, halaman dimuat ulang).
3. Catat di defect log:

| Defect ID | Related Test | Description | Cause | Fix | Retest Result |
|---|---|---|---|---|---|
| D01 | AD29 | Status did not change after Approve | [isi setelah diselidiki] | [isi] | Pass on YYYY-MM-DD |

4. Kirim deskripsi dan tangkapan layarnya ke saya, lalu kita cari penyebabnya bersama.
5. Setelah diperbaiki, ulangi test itu dan catat hasilnya di kolom Retest.

## 9. Urutan kerja yang hemat waktu

1. Kerjakan per bagian (A, B, C, ...) sesuai urutan di dokumen. Data dari bagian sebelumnya sering dipakai bagian berikutnya.
2. Kelompokkan test yang butuh data yang sama (misalnya semua test Pending sebelum mengubah statusnya).
3. Ambil tangkapan layar langsung saat mengerjakan; jangan menunda sampai akhir.
4. Bersihkan data bulk (SQL clean-up di dokumen) setelah selesai.
5. Perkirakan waktu: 60–80 test case membutuhkan sekitar 3–4 jam.

## 10. Daftar periksa setelah selesai

- [ ] Semua baris punya Actual Result, Status dan Evidence (kecuali yang ditandai "optional")
- [ ] Setiap Fail punya entri di defect log
- [ ] Tangkapan layar tersimpan dengan nama sesuai ID
- [ ] Tabel regression di bagian akhir dokumen sudah dijalankan ulang
- [ ] Hasil dan folder bukti dicadangkan di Google Drive
- [ ] Dokumen test case di-commit ke Git (`git add docs`, `git commit`, `git push`)
