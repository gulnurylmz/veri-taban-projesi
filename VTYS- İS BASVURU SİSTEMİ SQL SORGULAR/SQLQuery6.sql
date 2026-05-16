-- KVKK onayı verilmemiş başvuruları silme
DELETE FROM BASVURULAR
WHERE KvkkOnay = 0 
  AND BasvuruTarihi < DATEADD(DAY, -30, GETDATE());

-- Belirli bir yeteneği adaydan kaldırma
DELETE FROM ADAY_YETENEK
WHERE AdayId = 2 AND YetenekId = 1;
