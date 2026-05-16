SELECT 
    a.Ad,
    a.Soyad,
    e.Universite,
    e.Bolum,
    e.MezuniyetDurum,
    e.MezuniyetYili
FROM ADAYLAR a
INNER JOIN EGITIM e ON a.AdayId = e.AdayId
ORDER BY a.Soyad, a.Ad;
