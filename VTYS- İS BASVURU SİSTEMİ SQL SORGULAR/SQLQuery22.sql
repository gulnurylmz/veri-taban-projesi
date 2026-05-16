SELECT 
    a.Ad + ' ' + a.Soyad AS AdayAdi,
    p.PozisyonAdi,
    p.CalismaSekli,
    adm.Ad + ' ' + adm.Soyad AS DegerlendirenAdmin,
    COUNT(DISTINCT ay.YetenekId) AS YetenekSayisi,
    STRING_AGG(y.YetenekAdi, ', ') AS Yetenekler,
    b.Durum,
    b.MaasBeklenti
FROM BASVURULAR b
INNER JOIN ADAYLAR a ON b.AdayId = a.AdayId
INNER JOIN POZISYONLAR p ON b.PozisyonId = p.PozisyonId
INNER JOIN ADMIN adm ON b.AdminId = adm.AdminId
INNER JOIN ADAY_YETENEK ay ON a.AdayId = ay.AdayId
INNER JOIN YETENEKLER y ON ay.YetenekId = y.YetenekId
GROUP BY 
    b.BasvuruId, a.AdayId, a.Ad, a.Soyad, 
    p.PozisyonAdi, p.CalismaSekli,
    adm.Ad, adm.Soyad, b.Durum, b.MaasBeklenti
HAVING COUNT(DISTINCT ay.YetenekId) >= 2
ORDER BY YetenekSayisi DESC;
