-- ============================================================
-- İŞ BAŞVURU SİSTEMİ — TAM VERİTABANI ŞEMASI
-- SQL Server 2019+
-- ============================================================

CREATE DATABASE IsBasvuruSistemi;
GO
USE IsBasvuruSistemi;
GO

-- ============================================================
-- 1. ADMIN Tablosu
-- ============================================================
CREATE TABLE ADMIN (
    AdminId         INT             PRIMARY KEY IDENTITY(1,1),
    KullaniciAdi    NVARCHAR(100)   NOT NULL UNIQUE,
    Sifre           NVARCHAR(255)   NOT NULL,  -- password_hash ile saklanmalı
    Ad              NVARCHAR(100)   NOT NULL,
    Soyad           NVARCHAR(100)   NOT NULL,
    Eposta          NVARCHAR(200)   NOT NULL UNIQUE,
    Telefon         NVARCHAR(20)    NULL,
    OlusturmaTarihi DATETIME        NOT NULL DEFAULT GETDATE()
);
GO

-- ============================================================
-- 2. KULLANICILAR Tablosu (YENİ — aday hesapları)
-- ============================================================
CREATE TABLE KULLANICILAR (
    KullaniciId     INT             PRIMARY KEY IDENTITY(1,1),
    Ad              NVARCHAR(100)   NOT NULL,
    Soyad           NVARCHAR(100)   NOT NULL,
    Eposta          NVARCHAR(200)   NOT NULL,
    Sifre           NVARCHAR(255)   NOT NULL,  -- password_hash
    Telefon         NVARCHAR(20)    NULL,
    DogumTarihi     DATE            NULL,
    Cinsiyet        NVARCHAR(10)    NULL
                        CONSTRAINT CHK_Cinsiyet CHECK (Cinsiyet IN (N'Erkek', N'Kadın', N'Diğer')),
    OlusturmaTarihi DATETIME        NOT NULL DEFAULT GETDATE(),
    CONSTRAINT UQ_Kullanicilar_Eposta UNIQUE (Eposta)
);
GO

-- ============================================================
-- 3. ADAYLAR Tablosu
-- ============================================================
CREATE TABLE ADAYLAR (
    AdayId          INT             PRIMARY KEY IDENTITY(1,1),
    Ad              NVARCHAR(100)   NOT NULL,
    Soyad           NVARCHAR(100)   NOT NULL,
    Eposta          NVARCHAR(200)   NOT NULL,
    Telefon         NVARCHAR(20)    NULL,
    DogumTarihi     DATE            NULL,
    Cinsiyet        NVARCHAR(10)    NULL
                        CONSTRAINT CHK_Aday_Cinsiyet CHECK (Cinsiyet IN (N'Erkek', N'Kadın', N'Diğer')),
    KullaniciId     INT             NULL,  -- giriş yapmadan başvuranlar için NULL olabilir
    OlusturmaTarihi DATETIME        NOT NULL DEFAULT GETDATE(),
    CONSTRAINT UQ_Adaylar_Eposta UNIQUE (Eposta),
    CONSTRAINT FK_Adaylar_Kullanicilar FOREIGN KEY (KullaniciId) REFERENCES KULLANICILAR(KullaniciId)
);
GO

-- ============================================================
-- 4. POZISYONLAR Tablosu
-- ============================================================
CREATE TABLE POZISYONLAR (
    PozisyonId      INT             PRIMARY KEY IDENTITY(1,1),
    PozisyonAdi     NVARCHAR(100)   NOT NULL,
    CalismaSekli    NVARCHAR(50)    NOT NULL
                        CONSTRAINT CHK_CalismaSekli CHECK (
                            CalismaSekli IN (
                                N'Tam Zamanlı', N'Yarı Zamanlı',
                                N'Uzaktan', N'Hibrit', N'Staj'
                            )
                        ),
    Aciklama        NVARCHAR(MAX)   NULL,
    Aktif           BIT             NOT NULL DEFAULT 1,
    OlusturmaTarihi DATETIME        NOT NULL DEFAULT GETDATE()
);
GO

-- ============================================================
-- 5. BASVURULAR Tablosu
-- ============================================================
CREATE TABLE BASVURULAR (
    BasvuruId       INT             PRIMARY KEY IDENTITY(1,1),
    AdayId          INT             NOT NULL,
    PozisyonId      INT             NOT NULL,
    AdminId         INT             NULL,
    BasvuruTarihi   DATETIME        NOT NULL DEFAULT GETDATE(),
    Durum           NVARCHAR(20)    NOT NULL DEFAULT N'Beklemede',
    MaasBeklenti    DECIMAL(12,2)   NULL,
    BaslangicTarihi DATE            NULL,
    ReferansKaynagi NVARCHAR(50)    NULL,
    Hakkinda        NVARCHAR(MAX)   NULL,
    KvkkOnay        BIT             NOT NULL DEFAULT 0,

    CONSTRAINT FK_Basvurular_Adaylar      FOREIGN KEY (AdayId)     REFERENCES ADAYLAR(AdayId),
    CONSTRAINT FK_Basvurular_Pozisyonlar  FOREIGN KEY (PozisyonId) REFERENCES POZISYONLAR(PozisyonId),
    CONSTRAINT FK_Basvurular_Admin        FOREIGN KEY (AdminId)    REFERENCES ADMIN(AdminId)
);
GO

-- ============================================================
-- 6. EGITIM Tablosu
-- ============================================================
CREATE TABLE EGITIM (
    EgitimId        INT             PRIMARY KEY IDENTITY(1,1),
    AdayId          INT             NOT NULL,
    Universite      NVARCHAR(200)   NOT NULL,
    Bolum           NVARCHAR(200)   NOT NULL,
    MezuniyetDurum  NVARCHAR(50)    NOT NULL
                        CONSTRAINT CHK_MezuniyetDurum CHECK (
                            MezuniyetDurum IN (
                                N'Lise', N'Ön Lisans', N'Lisans',
                                N'Yüksek Lisans', N'Doktora', N'Devam Ediyor'
                            )
                        ),
    MezuniyetYili   DATE            NULL,
    CONSTRAINT FK_Egitim_Adaylar FOREIGN KEY (AdayId) REFERENCES ADAYLAR(AdayId)
);
GO

-- ============================================================
-- 7. DOSYALAR (CV) Tablosu
-- ============================================================
CREATE TABLE DOSYALAR (
    DosyaId         INT             PRIMARY KEY IDENTITY(1,1),
    AdayId          INT             NOT NULL,
    DosyaAdi        NVARCHAR(300)   NOT NULL,
    DosyaTipi       NVARCHAR(10)    NOT NULL
                        CONSTRAINT CHK_DosyaTipi CHECK (DosyaTipi IN ('pdf', 'doc', 'docx')),
    DosyaBoyutu     INT             NULL,
    YuklemeTarihi   DATETIME        NOT NULL DEFAULT GETDATE(),
    CONSTRAINT FK_Dosyalar_Adaylar FOREIGN KEY (AdayId) REFERENCES ADAYLAR(AdayId)
);
GO

-- ============================================================
-- 8. YETENEKLER Tablosu
-- ============================================================
CREATE TABLE YETENEKLER (
    YetenekId       INT             PRIMARY KEY IDENTITY(1,1),
    YetenekAdi      NVARCHAR(100)   NOT NULL,
    Aciklama        NVARCHAR(MAX)   NULL
);
GO

-- ============================================================
-- 9. ADAY_YETENEK (Çoka Çok)
-- ============================================================
CREATE TABLE ADAY_YETENEK (
    Id          INT PRIMARY KEY IDENTITY(1,1),
    AdayId      INT NOT NULL,
    YetenekId   INT NOT NULL,
    CONSTRAINT FK_AY_Adaylar    FOREIGN KEY (AdayId)    REFERENCES ADAYLAR(AdayId),
    CONSTRAINT FK_AY_Yetenekler FOREIGN KEY (YetenekId) REFERENCES YETENEKLER(YetenekId),
    CONSTRAINT UQ_AdayYetenek   UNIQUE (AdayId, YetenekId)
);
GO

-- ============================================================
-- İNDEKSLER
-- ============================================================
CREATE NONCLUSTERED INDEX IX_Basvurular_AdayId     ON BASVURULAR (AdayId);
CREATE NONCLUSTERED INDEX IX_Basvurular_PozisyonId ON BASVURULAR (PozisyonId);
CREATE NONCLUSTERED INDEX IX_Basvurular_Durum      ON BASVURULAR (Durum);
CREATE NONCLUSTERED INDEX IX_Pozisyonlar_Aktif     ON POZISYONLAR (Aktif);
GO

-- ============================================================
-- ÖRNEK VERİLER
-- ============================================================

-- Admin (şifre: admin123 — gerçekte password_hash kullanın)
INSERT INTO ADMIN (KullaniciAdi, Sifre, Ad, Soyad, Eposta, Telefon)
VALUES (N'admin', N'admin123', N'Ali', N'Veli', N'admin@sirket.com', N'05301112233');
GO

-- Pozisyonlar
INSERT INTO POZISYONLAR (PozisyonAdi, CalismaSekli, Aciklama) VALUES
    (N'PHP Backend Geliştirici',   N'Hibrit',       N'Laravel veya Symfony deneyimi aranmaktadır. En az 2 yıl PHP deneyimi.'),
    (N'React Frontend Geliştirici',N'Uzaktan',      N'React, TypeScript ve modern CSS bilgisi zorunludur.'),
    (N'Veri Analisti',             N'Tam Zamanlı',  N'Python, SQL ve Power BI bilgisi tercih sebebidir.'),
    (N'DevOps Mühendisi',          N'Hibrit',       N'Docker, Kubernetes ve CI/CD süreçleri hakkında deneyim aranıyor.'),
    (N'QA Test Uzmanı',            N'Tam Zamanlı',  N'Selenium ve test otomasyon deneyimi olan adaylar tercih edilir.');
GO

-- Örnek kullanıcı (şifre: test123)
INSERT INTO KULLANICILAR (Ad, Soyad, Eposta, Sifre, Telefon) VALUES
    (N'Ahmet', N'Yılmaz', N'ahmet@example.com', N'test123', N'05301234567');
GO
