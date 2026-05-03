CREATE PROCEDURE basvuru_ekle
    @ad NVARCHAR(50),
    @soyad NVARCHAR(50)
AS
BEGIN
    INSERT INTO basvurular (ad, soyad)
    VALUES (@ad, @soyad);
END;