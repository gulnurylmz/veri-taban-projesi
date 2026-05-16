CREATE TABLE KULLANICILAR (
    KullaniciId     INT            IDENTITY(1,1) PRIMARY KEY,
    Ad              NVARCHAR(100)  NOT NULL,
    Soyad           NVARCHAR(100)  NOT NULL,
    Eposta          NVARCHAR(150)  NOT NULL UNIQUE,
    Sifre           NVARCHAR(255)  NOT NULL,
    Telefon         NVARCHAR(20)   NULL,
    OlusturmaTarihi DATETIME       DEFAULT GETDATE()
);