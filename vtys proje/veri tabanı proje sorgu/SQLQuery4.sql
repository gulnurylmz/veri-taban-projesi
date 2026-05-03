CREATE TABLE basvurular (
    id INT IDENTITY(1,1) PRIMARY KEY,
    ad NVARCHAR(50) NOT NULL,
    soyad NVARCHAR(50) NOT NULL,
    email NVARCHAR(100) NOT NULL,
    telefon NVARCHAR(20),
    pozisyon NVARCHAR(100),
    deneyim NVARCHAR(MAX),
    cv NVARCHAR(255),
    durum NVARCHAR(20) DEFAULT 'Beklemede',
    tarih DATETIME DEFAULT GETDATE()
);