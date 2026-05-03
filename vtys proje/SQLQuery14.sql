SELECT E.egitim_adi, P.pozisyon_adi, COUNT(*) AS aday_sayisi
FROM Aday A
JOIN Egitim E ON A.egitim_id = E.egitim_id
JOIN Pozisyon P ON A.pozisyon_id = P.pozisyon_id
WHERE A.beklemede = 1
GROUP BY E.egitim_adi, P.pozisyon_adi;