SELECT 
    a.Ad + ' ' + a.Soyad AS AdayAdi,
    a.Eposta,
    COUNT(DISTINCT b.BasvuruId) AS BasvuruSayisi,
    COUNT(DISTINCT ay.YetenekId) AS YetenekSayisi,
    MAX(e.MezuniyetDurum) AS EnYuksekEgitim
FROM ADAYLAR a
LEFT JOIN BASVURULAR b ON a.AdayId = b.AdayId
LEFT JOIN ADAY_YETENEK ay ON a.AdayId = ay.AdayId
LEFT JOIN EGITIM e ON a.AdayId = e.AdayId
GROUP BY a.AdayId, a.Ad, a.Soyad, a.Eposta
ORDER BY BasvuruSayisi DESC, YetenekSayisi DESC;
