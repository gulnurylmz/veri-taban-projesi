SELECT 
    BasvuruId,
    AdayId,
    PozisyonId,
    BasvuruTarihi,
    Durum,
    MaasBeklenti
FROM BASVURULAR
WHERE BasvuruTarihi >= DATEADD(DAY, -30, GETDATE())
ORDER BY BasvuruTarihi DESC;
