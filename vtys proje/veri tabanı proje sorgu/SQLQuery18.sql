SELECT pozisyon, COUNT(*) AS sayi
FROM basvurular
GROUP BY pozisyon
HAVING COUNT(*) > 1;