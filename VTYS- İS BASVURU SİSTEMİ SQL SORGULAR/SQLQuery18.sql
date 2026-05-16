SELECT 
    b.BasvuruId,
    b.BasvuruTarihi,
    p.PozisyonAdi,
    p.CalismaSekli,
    b.MaasBeklenti,
    b.Durum
FROM BASVURULAR b
INNER JOIN POZISYONLAR p ON b.PozisyonId = p.PozisyonId
WHERE b.Durum = N'Beklemede'
ORDER BY p.PozisyonAdi;
