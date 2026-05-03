BEGIN TRANSACTION;

INSERT INTO basvurular (ad, soyad, email)
VALUES (N'Ali', N'Kaya', N'ali@mail.com');

UPDATE basvurular
SET durum = N'Beklemede'
WHERE id = 1;

COMMIT;