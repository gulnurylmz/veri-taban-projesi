CREATE VIEW aktif_basvurular AS
SELECT ad, soyad, pozisyon
FROM basvurular
WHERE durum = N'Beklemede';