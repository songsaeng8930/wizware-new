@echo off
chcp 65001 >nul
cd /d "%~dp0"

echo ============================================
echo   Zaemit 그룹웨어 로컬 서버
echo ============================================
echo.
echo   브라우저에서 아래 주소로 접속하세요:
echo.
echo       http://127.0.0.1:8000
echo.
echo   * 이 검은 창은 끄지 마세요 (끄면 서버도 꺼짐)
echo   * 다 봤으면 이 창을 닫으면 서버가 종료됩니다
echo ============================================
echo.

set PHP=php
where php >nul 2>nul || set PHP=C:\xampp\php\php.exe

"%PHP%" -S 127.0.0.1:8000

echo.
echo 서버가 종료되었습니다. 아무 키나 누르면 창이 닫힙니다.
pause >nul
