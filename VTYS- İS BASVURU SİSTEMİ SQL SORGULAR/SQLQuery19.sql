SELECT 
    p.PozisyonAdi,
    p.CalismaSekli,
    COUNT(b.BasvuruId) AS ToplamBasvuru,
    AVG(b.MaasBeklenti) AS OrtalamaMaas,
    MAX(b.MaasBeklenti) AS MaksimumMaas,
    MIN(b.MaasBeklenti) AS MinimumMaas
FROM POZISYONLAR p
INNER JOIN BASVURULAR b ON p.PozisyonId = b.PozisyonId
INNER JOIN ADAYLAR a ON b.AdayId = a.AdayId
GROUP BY p.PozisyonId, p.PozisyonAdi, p.CalismaSekli
ORDER BY ToplamBasvuru DESC;
