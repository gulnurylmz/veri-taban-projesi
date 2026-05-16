--1. ADMIN Tablosu
-- ============================================================
CREATE TABLE ADMIN (
    AdminId         INT             PRIMARY KEY IDENTITY(1,1),
    KullaniciAdi    NVARCHAR(100)   NOT NULL,
    Sifre           NVARCHAR(255)   NOT NULL,
    Ad              NVARCHAR(100)   NOT NULL,
    Soyad           NVARCHAR(100)   NOT NULL,
    Eposta          NVARCHAR(200)   NOT NULL,
    Telefon         NVARCHAR(20)    NULL,
    OlusturmaTarihi DATETIME        NOT NULL DEFAULT GETDATE()
);
GO

-- ============================================================
-- 2. ADAYLAR Tablosu
-- ============================================================
CREATE TABLE ADAYLAR (
    AdayId          INT             PRIMARY KEY IDENTITY(1,1),
    Ad              NVARCHAR(100)   NOT NULL,
    Soyad           NVARCHAR(100)   NOT NULL,
    Eposta          NVARCHAR(200)   NOT NULL,
    Telefon         NVARCHAR(20)    NULL,
    DogumTarihi     DATE            NULL,
    Cinsiyet        NVARCHAR(10)    NULL
                        CONSTRAINT CHK_Cinsiyet CHECK (Cinsiyet IN (N'Erkek', N'Kadın', N'Diğer')),
    OlusturmaTarihi DATETIME        NOT NULL DEFAULT GETDATE(),
    CONSTRAINT UQ_Adaylar_Eposta UNIQUE (Eposta)
);
GO

-- ============================================================
-- 3. POZISYONLAR Tablosu
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
    OlusturmaTarihi DATETIME        NOT NULL DEFAULT GETDATE(),
    Departman       NVARCHAR(100),
    Maas            NVARCHAR(50),
    Durum           NVARCHAR(20) DEFAULT 'Aktif',
);
GO

-- ============================================================
-- 4. BASVURULAR Tablosu
-- ============================================================
CREATE TABLE BASVURULAR (
    BasvuruId       INT             PRIMARY KEY IDENTITY(1,1),
    AdayId          INT             NOT NULL,
    PozisyonId      INT             NOT NULL,
    AdminId         INT             NOT NULL,
    BasvuruTarihi   DATETIME        NOT NULL DEFAULT GETDATE(),
    Durum           NVARCHAR(20)    NOT NULL DEFAULT N'Beklemede',
    MaasBeklenti    DECIMAL(12,2)   NULL,
    BaslangicTarihi DATE            NULL,
    ReferansKaynagi NVARCHAR(50)    NULL,
    Hakkinda        NVARCHAR(MAX)   NULL,
    KvkkOnay        BIT             NOT NULL DEFAULT 0,

    CONSTRAINT FK_Basvurular_Adaylar
        FOREIGN KEY (AdayId) REFERENCES ADAYLAR(AdayId),

    CONSTRAINT FK_Basvurular_Pozisyonlar
        FOREIGN KEY (PozisyonId) REFERENCES POZISYONLAR(PozisyonId),

    CONSTRAINT FK_Basvurular_Admin
        FOREIGN KEY (AdminId) REFERENCES ADMIN(AdminId)
);
GO

-- ============================================================
-- 5. EGITIM Tablosu
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

    CONSTRAINT FK_Egitim_Adaylar
        FOREIGN KEY (AdayId) REFERENCES ADAYLAR(AdayId)
);
GO

-- ============================================================
-- 6. DOSYALAR (CV) Tablosu
-- ============================================================
CREATE TABLE DOSYALAR (
    DosyaId         INT             PRIMARY KEY IDENTITY(1,1),
    AdayId          INT             NOT NULL,
    DosyaAdi        NVARCHAR(300)   NOT NULL,
    DosyaTipi       NVARCHAR(10)    NOT NULL
                        CONSTRAINT CHK_DosyaTipi CHECK (
                            DosyaTipi IN ('pdf', 'doc', 'docx')
                        ),
    DosyaBoyutu     INT             NULL,
    YuklemeTarihi   DATETIME        NOT NULL DEFAULT GETDATE(),

    CONSTRAINT FK_Dosyalar_Adaylar
        FOREIGN KEY (AdayId) REFERENCES ADAYLAR(AdayId)
);
GO

-- ============================================================
-- 7. YETENEKLER Tablosu
-- ============================================================
CREATE TABLE YETENEKLER (
    YetenekId       INT             PRIMARY KEY IDENTITY(1,1),
    YetenekAdi      NVARCHAR(100)   NOT NULL,
    Aciklama        NVARCHAR(MAX)   NULL
);
GO

-- ============================================================
-- 8. ADAY_YETENEK (Çoka Çok İlişki) Tablosu
-- ============================================================
CREATE TABLE ADAY_YETENEK (
    Id              INT             PRIMARY KEY IDENTITY(1,1),
    AdayId          INT             NOT NULL,
    YetenekId       INT             NOT NULL,

    CONSTRAINT FK_AdayYetenek_Adaylar
        FOREIGN KEY (AdayId) REFERENCES ADAYLAR(AdayId),

    CONSTRAINT FK_AdayYetenek_Yetenekler
        FOREIGN KEY (YetenekId) REFERENCES YETENEKLER(YetenekId),

    -- Aynı aday-yetenek kombinasyonu tekrar eklenemez
    CONSTRAINT UQ_AdayYetenek UNIQUE (AdayId, YetenekId)
);
GO

-- ============================================================
-- İNDEKSLER (Performans için)
-- ============================================================

-- Başvurular: sık sorgulanan FK alanları
CREATE NONCLUSTERED INDEX IX_Basvurular_AdayId
    ON BASVURULAR (AdayId);

CREATE NONCLUSTERED INDEX IX_Basvurular_PozisyonId
    ON BASVURULAR (PozisyonId);

CREATE NONCLUSTERED INDEX IX_Basvurular_AdminId
    ON BASVURULAR (AdminId);

-- Eğitim: adaya göre sorgu
CREATE NONCLUSTERED INDEX IX_Egitim_AdayId
    ON EGITIM (AdayId);

-- Dosyalar: adaya göre sorgu
CREATE NONCLUSTERED INDEX IX_Dosyalar_AdayId
    ON DOSYALAR (AdayId);

-- Aday Yetenek
CREATE NONCLUSTERED INDEX IX_AdayYetenek_AdayId
    ON ADAY_YETENEK (AdayId);

CREATE NONCLUSTERED INDEX IX_AdayYetenek_YetenekId
    ON ADAY_YETENEK (YetenekId);
GO
