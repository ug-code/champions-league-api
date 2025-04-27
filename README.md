# Şampiyonlar Ligi Simülasyonu - Backend API

Bu proje, Şampiyonlar Ligi simülasyonunun backend API kısmını içerir. Laravel ile geliştirilmiş, memory-based bir API'dir.

## 🚀 Kullanılan Teknolojiler

- **Laravel 12** - PHP framework
- **PHP 8.2+** - Programlama dili
- **JSON Storage** - State yönetimi
- **OOP** - Nesne yönelimli programlama

## 📋 Özellikler

- Takım yönetimi (ekleme, listeleme)
- Fikstür oluşturma
- Maç simülasyonu
- Lig tablosu hesaplama
- Şampiyonluk tahmini
- Memory-based state yönetimi

## 🛠️ Kurulum

1. Projeyi klonlayın:
```bash
git clone [repo-url]
cd api
```

2. Bağımlılıkları yükleyin:
```bash
composer install
```

3. Geliştirme sunucusunu başlatın:
```bash
php artisan serve
```

4. API'yi test edin:
```
http://127.0.0.1:8000/api/teams
```

## 🔄 API Endpoints

- `POST /api/teams` - Takım ekle
  - Body: `{ "name": "Takım Adı", "power": 50 }`
- `GET /api/teams` - Takımları listele
- `POST /api/fixtures` - Fikstür oluştur
- `POST /api/simulate-week` - Haftayı simüle et
  - Body: `{ "week": 0 }`
- `POST /api/simulate-all` - Tüm ligi simüle et
- `GET /api/standings` - Lig tablosu ve tahminler
- `POST /api/reset` - Sıfırla

## 📁 Proje Yapısı

```
api/
├── app/
│   └── Http/
│       └── Controllers/
│           └── Api/
│               └── LeagueController.php  # Ana controller
├── routes/
│   └── api.php                          # API route'ları
└── storage/
    └── app/
        └── league.json                  # State dosyası
```

## 🧮 Hesaplama Formülleri

### 1. Maç Simülasyonu
```php
// Ev sahibi avantajı
$homeAdv = 1.1;
$homePower = $match->home->power * $homeAdv;
$awayPower = $match->away->power;

// Toplam güç ve olasılıklar
$totalPower = $homePower + $awayPower;
$homeProb = $homePower / $totalPower;
$awayProb = $awayPower / $totalPower;

// Gol hesaplama
$homeGoals = max(0, round($this->randomGoal($homeProb)));
$awayGoals = max(0, round($this->randomGoal($awayProb)));
```

### 1.1 Gol Hesaplama (randomGoal) Detaylı Açıklama
```php
public function randomGoal($prob) {
    $r = mt_rand() / mt_getrandmax();  // 0 ile 1 arası rastgele sayı
    if ($r < $prob * 0.5) return 2 + Math.random();  // Yüksek gol (2-3)
    if ($r < $prob) return 1 + Math.random();        // Orta gol (1-2)
    return Math.random();                            // Düşük gol (0-1)
}
```

Bu fonksiyon, takımın gol atma olasılığına göre 0-3 arası gol üretir. Nasıl çalışır?

1. **Yüksek Gol (2-3 gol)**
   - Eğer rastgele sayı, takımın gol olasılığının yarısından küçükse
   - Örnek: Takımın gol olasılığı 0.8 ise, %40 ihtimalle 2-3 gol atar

2. **Orta Gol (1-2 gol)**
   - Eğer rastgele sayı, takımın gol olasılığından küçükse
   - Örnek: Takımın gol olasılığı 0.8 ise, %40 ihtimalle 1-2 gol atar

3. **Düşük Gol (0-1 gol)**
   - Diğer durumlarda
   - Örnek: Takımın gol olasılığı 0.8 ise, %20 ihtimalle 0-1 gol atar

**Örnek Senaryo:**
- Güçlü takım (gol olasılığı 0.8):
  - %40 ihtimalle 2-3 gol
  - %40 ihtimalle 1-2 gol
  - %20 ihtimalle 0-1 gol

- Zayıf takım (gol olasılığı 0.3):
  - %15 ihtimalle 2-3 gol
  - %15 ihtimalle 1-2 gol
  - %70 ihtimalle 0-1 gol

Bu sayede:
- Güçlü takımlar daha çok gol atar
- Zayıf takımlar daha az gol atar
- Her maç farklı ve gerçekçi sonuçlar üretir

### 2. Puan Hesaplama
- Galibiyet: 3 puan
- Beraberlik: 1 puan
- Mağlubiyet: 0 puan

### 3. Lig Tablosu Sıralama
1. Puan
2. Gol farkı
3. Atılan gol

### 4. Şampiyonluk Tahmini
```php
// Kalan maçlarda alınabilecek maksimum puan
$possibleMax = $team->points + $weeksLeft * 3;

// Şampiyonluk olasılığı
$prediction = round(($team->points / ($maxPoint ?: 1)) * 100);
```

## 📝 Notlar

- Veritabanı kullanılmıyor, tüm state `storage/app/league.json` dosyasında tutuluyor
- Her API isteğinde state dosyası okunup güncelleniyor
- Takım güçleri 1-100 arası
- Ev sahibi avantajı %10
- Gol hesaplaması takım güçlerine göre olasılıksal

## 🔧 Geliştirme

1. Yeni endpoint eklemek için:
   - `routes/api.php`'ye route ekleyin
   - `LeagueController.php`'ye method ekleyin
   - State yönetimini `league.json` üzerinden yapın

2. State yönetimi:
   - `loadState()` - JSON dosyasından state'i oku
   - `saveState()` - State'i JSON dosyasına yaz

## 🤝 Katkıda Bulunma

1. Fork'layın
2. Feature branch oluşturun (`git checkout -b feature/amazing-feature`)
3. Commit'leyin (`git commit -m 'feat: Add amazing feature'`)
4. Push'layın (`git push origin feature/amazing-feature`)
5. Pull Request açın

## 📄 Lisans

MIT License - Detaylar için [LICENSE](LICENSE) dosyasına bakın.
