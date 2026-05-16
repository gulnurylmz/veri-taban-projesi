-- Tüm başvuruları aday, pozisyon ve admin bilgileriyle getirme
SELECT 
    a.Ad + ' ' + a.Soyad AS AdayAdi,
    a.Eposta AS AdayEposta,
    p.PozisyonAdi,
    p.CalismaSekli,
    b.Durum,
    b.MaasBeklenti,
    b.BasvuruTarihi,
    adm.Ad + ' ' + adm.Soyad AS DegerlendirenAdmin
FROM BASVURULAR b
INNER JOIN ADAYLAR a ON b.AdayId = a.AdayId
INNER JOIN POZISYONLAR p ON b.PozisyonId = p.PozisyonId
INNER JOIN ADMIN adm ON b.AdminId = adm.AdminId
WHERE b.Durum = N'Beklemede'
ORDER BY b.BasvuruTarihi DESC;
