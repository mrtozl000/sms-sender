# SMS Sender Application

Laravel tabanlı toplu SMS gönderim uygulaması. Queue/Job yapısı, Repository Pattern, Service Layer ve Redis cache kullanılmıştır.

## Özellikler

-  Laravel 10.x
-  Repository Pattern
-  Service Layer Architecture
-  Queue/Job/Worker yapısı
-  Redis Cache
-  Docker desteği
-  RESTful API
-  Swagger/OpenAPI dokümantasyonu
-  Unit ve Integration testler
-  Webhook entegrasyonu

## Kurulum

### 1. Projeyi Klonlayın
```bash
git clone https://github.com/mrtozl000/sms-sender.git
cd $PATH/sms-sender
```
----
### 2. Environment Dosyasını Oluşturun
```bash
cp .env.example .env
```
----
### 3. Docker ile Başlatın
```bash
# Dockerı Başlat
docker-compose up -d

# Composer paketlerini yükle
docker-compose exec app composer install

# Application key oluştur
docker-compose exec app php artisan key:generate

# Veritabanı migration'ları çalıştır
docker-compose exec app php artisan migrate

# Redis bağlantısını test et
docker-compose exec app php artisan redis:ping
(redis db1)
```
----------
## 4. Composer.json Güncellemesi

`composer.json` dosyasına aşağıdaki paketleri ekleyin:
```json
{
  "require": {
    "php": "^8.1",
    "laravel/framework": "^10.0",
    "predis/predis": "^2.0",
    "darkaonline/l5-swagger": "^8.5",
    "guzzlehttp/guzzle": "^7.2", 
    "giggsey/libphonenumber-for-php": "^9.0"
  },
  "require-dev": {
    "phpunit/phpunit": "^12.3",
    "pestphp/pest": "^4.1",
    "pestphp/pest-plugin-laravel": "^4.0",
    "mockery/mockery": "^1.4.4",
    "squizlabs/php_codesniffer": "^3.13"
  }
}
```
---------
## Kullanım
### Manuel Mesaj Gönderimi
```bash

Queueya mesaj gönderme jobları
docker-compose exec app php artisan messages:process --use-queue
```

## Queue worker'ı başlat (otomatik başlar, manuel için)
```bash
docker-compose exec app php artisan queue:work

    Otomatik Mesaj Gönderimi (Crontab)
    Crontaba ekleyin
    * * * * * cd /sms-sender && docker-compose exec -T app php artisan messages:process --use-queue >> /dev/null 2>&1
    ``` 
    localde
    while true; do
    docker-compose exec app php artisan messages:process --use-queue --limit=2
    sleep 5
    done
```
#### Filtreli örnek

```bash
GET /api/messages/sent?phone_number=555&from_date=2024-01-01&to_date=2024-12-31&per_page=20
```
#### Yeni Mesaj Oluştur
```bash
POST /api/messages
Content-Type: application/json
```json
{
"phone_number": "+905551234567",
"content": "Test mesajı"
}
```
-------
>  #### Swagger Dokümantasyonu
>Swagger UI'a erişmek için:
> ```bash
>  php artisan vendor:publish --provider "L5Swagger\L5SwaggerServiceProvider"
>  php artisan l5-swagger:generate
>  http://localhost:8080/api/documentation
> ```
-------
### Test
```bash
# Tüm testleri çalıştır
docker-compose exec app php artisan test

# Sadece Unit testleri
docker-compose exec app php artisan test --testsuite=Unit

# Sadece Feature testleri
docker-compose exec app php artisan test --testsuite=Feature
```
------
### Faker
#### Örnek mesajlar oluştur
```bash

docker-compose exec app php artisan tinker
>>> \App\Models\Message::factory()->count(100)->create();
```
--------
### Monitoring
#### Queue İzleme
```bash Queue durumunu kontrol et
docker-compose exec app php artisan queue:monitor

# Failed job'ları listele
docker-compose exec app php artisan queue:failed
Redis İzleme
bash# Redis CLI
docker-compose exec redis redis-cli

127.0.0.1:6379> KEYS *

127.0.0.1:6379> GET message:1

```

-----------------
 #### ! Webhook.site adresinde yapılması gerekenler.
>  En sağ üstten "Edit" butonuna bastıktan sonra
> Content olarak.
> ```json
> {"message": "Accepted","messageId": "$request.ip$"}
> ```
> Content Type : Application/json
> status : 202 

