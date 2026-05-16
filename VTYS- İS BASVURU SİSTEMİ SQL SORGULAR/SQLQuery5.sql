-- Belirli pozisyonların çalışma şeklini güncelleme
UPDATE POZISYONLAR
SET CalismaSekli = N'Hibrit'
WHERE PozisyonAdi IN (N'Veri Analisti', N'Yazılım Test Uzmanı');

-- Adayların telefon numaralarını güncelleme
UPDATE ADAYLAR
SET Telefon = N'05320001122'
WHERE Soyad = N'Yılmaz';
