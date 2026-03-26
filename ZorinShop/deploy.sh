#!/bin/bash
# ZorinShop 배포 스크립트
# 사용법: bash deploy.sh

echo "=== ZorinShop 배포 시작 ==="

# 1. /var/www/html/shop 디렉토리 생성 및 파일 복사
sudo mkdir -p /var/www/html/shop
sudo cp -r /home/chan/Desktop/zorin-lamp/shop/* /var/www/html/shop/
sudo chown -R www-data:www-data /var/www/html/shop
sudo chmod -R 755 /var/www/html/shop
sudo chmod -R 777 /var/www/html/shop/uploads

echo "✅ 파일 복사 완료"

# 2. Apache mod_rewrite 활성화
sudo a2enmod rewrite 2>/dev/null
echo "✅ mod_rewrite 활성화"

# 3. Apache 재시작
sudo systemctl restart apache2
echo "✅ Apache 재시작"

echo ""
echo "=== 배포 완료 ==="
echo "브라우저에서 http://localhost/shop/setup.php 를 열어 DB를 설치하세요."
