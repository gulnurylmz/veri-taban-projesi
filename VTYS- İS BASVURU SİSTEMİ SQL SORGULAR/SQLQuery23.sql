SELECT 
    p.PozisyonAdi,
    p.CalismaSekli,
    a.Ad + ' ' + a.Soyad AS AdayAdi,
    a.Eposta,
    e.Universite,
    e.Bolum,
    e.MezuniyetDurum,
    COUNT(DISTINCT ay.YetenekId) AS YetenekSayisi,
    COUNT(DISTINCT d.DosyaId) AS YuklenenDosyaSayisi,
    b.MaasBeklenti,
    b.Durum,
    adm.Ad + ' ' + adm.Soyad AS SorumluAdmin
FROM BASVURULAR b
INNER JOIN ADAYLAR a ON b.AdayId = a.AdayId
INNER JOIN POZISYONLAR p ON b.PozisyonId = p.PozisyonId
INNER JOIN ADMIN adm ON b.AdminId = adm.AdminId
INNER JOIN EGITIM e ON a.AdayId = e.AdayId
LEFT JOIN ADAY_YETENEK ay ON a.AdayId = ay.AdayId
LEFT JOIN DOSYALAR d ON a.AdayId = d.AdayId
WHERE 
    e.MezuniyetDurum IN (N'Lisans', N'Yüksek Lisans', N'Doktora')
    AND b.MaasBeklenti > (
        SELECT AVG(MaasBeklenti) 
        FROM BASVURULAR 
        WHERE PozisyonId = b.PozisyonId
    )
GROUP BY 
    p.PozisyonId, p.PozisyonAdi, p.CalismaSekli,
    a.AdayId, a.Ad, a.Soyad, a.Eposta,
    e.Universite, e.Bolum, e.MezuniyetDurum,
    b.BasvuruId, b.MaasBeklenti, b.Durum,
    adm.Ad, adm.Soyad
HAVING COUNT(DISTINCT d.DosyaId) >= 1
ORDER BY p.PozisyonAdi, b.MaasBeklenti DESC;
