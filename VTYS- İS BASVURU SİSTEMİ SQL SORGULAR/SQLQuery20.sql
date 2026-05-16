SELECT 
    p.PozisyonAdi,
    adm.Ad + ' ' + adm.Soyad AS AdminAdi,
    COUNT(b.BasvuruId) AS BasvuruSayisi,
    AVG(b.MaasBeklenti) AS OrtalamaBasvuruMaasi
FROM BASVURULAR b
INNER JOIN POZISYONLAR p 
    ON b.PozisyonId = p.PozisyonId
INNER JOIN ADMIN adm 
    ON b.AdminId = adm.AdminId
GROUP BY 
    p.PozisyonAdi,
    adm.Ad,
    adm.Soyad
ORDER BY BasvuruSayisi DESC;