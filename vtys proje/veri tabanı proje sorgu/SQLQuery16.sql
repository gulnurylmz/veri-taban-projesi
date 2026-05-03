SELECT a.kullanici_adi, COUNT(b.id)
FROM adminler a
JOIN basvurular b ON a.id = 1
GROUP BY a.kullanici_adi;