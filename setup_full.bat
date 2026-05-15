@echo off
title RecipeShare Otomatik Kurulum Aracı

REM XAMPP'ın localhost portunu varsayarak, projeyi çalıştırmak için 3 PHP dosyasını sırayla çağırır.
REM Tarayıcıyı açmak için start komutunu kullanıyoruz.

echo =======================================================
echo == RecipeShare Projesi Kurulumu Baslatiliyor...
echo =======================================================
echo.
echo Lutfen acilan tarayici sekmelerini KAPATMAYIN.
echo Islem tamamlaninca bu pencere otomatik kapanacaktir.
echo.

REM 1. ADIM: VERITABANI YAPISINI OLUSTURMA (setup.php)
echo [1/3] Veritabanı yapıları (Tablolar) olusturuluyor...
start "" "http://localhost/recipeshare/setup.php"
timeout /t 5 >nul 

REM 2. ADIM: VERILERI YUKLEME (import_csv.php)
echo [2/3] Tarif verileri (1000 Tarif) veritabanına yukleniyor...
start "" "http://localhost/recipeshare/import_csv.php"
timeout /t 10 >nul 

REM 3. ADIM: ETKINLIKLERI BAGLAMA (insert_events.php)
echo [3/3] Haftalik etkinlikler tariflere baglaniyor...
start "" "http://localhost/recipeshare/insert_events.php"
timeout /t 5 >nul 

echo.
echo =======================================================
echo == KURULUM TAMAMLANDI!
echo =======================================================
echo.
echo Projeyi gormek icin ana sayfa aciliyor...
start "" "http://localhost/recipeshare/index.php"

timeout /t 5 >nul 
exit