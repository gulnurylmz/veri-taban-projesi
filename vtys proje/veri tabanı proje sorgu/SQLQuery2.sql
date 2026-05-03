CREATE TABLE adminler (
    id INT IDENTITY(1,1) PRIMARY KEY,
    kullanici_adi NVARCHAR(50) NOT NULL,
    sifre NVARCHAR(255) NOT NULL
);