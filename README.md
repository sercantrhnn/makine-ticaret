# Makine Ticaret Admin Panel

Modern ve kullanıcı dostu bir admin panel sistemi. Makine ticareti için geliştirilmiş, firmalar, kategoriler ve ürünleri yönetebileceğiniz kapsamlı bir yönetim paneli.

## 🚀 Özellikler

- **Modern Tasarım**: Tailwind CSS ile responsive ve şık arayüz
- **Dashboard**: Genel istatistikler ve hızlı erişim
- **Firma Yönetimi**: Firma ekleme, düzenleme, silme
- **Kategori Yönetimi**: Hiyerarşik kategori sistemi
- **Ürün Yönetimi**: Ürün ekleme, düzenleme, silme
- **Responsive Tasarım**: Mobil ve masaüstü uyumlu
- **Flash Mesajları**: İşlem sonuçları için bildirimler

## 🛠️ Teknolojiler

- **Backend**: Symfony 7.3
- **Frontend**: Tailwind CSS (CDN)
- **Database**: Doctrine ORM
- **Template Engine**: Twig
- **Icons**: Heroicons
- **JavaScript**: Alpine.js

## 📁 Proje Yapısı

```
makine-ticaret/
├── src/
│   ├── Controller/
│   │   ├── AdminController.php      # Ana dashboard
│   │   ├── CompaniesController.php  # Firma yönetimi
│   │   ├── CategoriesController.php # Kategori yönetimi
│   │   ├── ProductsController.php   # Ürün yönetimi
│   │   └── HomeController.php       # Ana sayfa
│   ├── Entity/
│   │   ├── Companies.php           # Firma entity
│   │   ├── Categories.php          # Kategori entity
│   │   └── Products.php            # Ürün entity
│   └── Form/
│       ├── CompaniesType.php       # Firma form
│       ├── CategoriesType.php      # Kategori form
│       └── ProductsType.php        # Ürün form
├── templates/
│   └── admin/
│       ├── base.html.twig          # Admin base template
│       ├── dashboard.html.twig     # Dashboard sayfası
│       ├── companies/              # Firma template'leri
│       ├── categories/             # Kategori template'leri
│       └── products/               # Ürün template'leri
└── public/
    └── index.php                   # Giriş noktası
```

## 🚀 Kurulum

1. **Projeyi klonlayın**
   ```bash
   git clone [repository-url]
   cd makine-ticaret
   ```

2. **Bağımlılıkları yükleyin**
   ```bash
   composer install
   ```

3. **Veritabanını yapılandırın**
   ```bash
   # .env dosyasında veritabanı bilgilerini güncelleyin
   php bin/console doctrine:database:create
   php bin/console doctrine:migrations:migrate
   ```

4. **Cache'i temizleyin**
   ```bash
   php bin/console cache:clear
   ```

5. **Web sunucusunu başlatın**
   ```bash
   symfony server:start
   ```

## 🌐 Kullanım

### Admin Panel Erişimi
- Ana sayfa: `http://localhost:8000/`
- Dashboard: `http://localhost:8000/admin/`
- Firmalar: `http://localhost:8000/admin/companies/`
- Kategoriler: `http://localhost:8000/admin/categories/`
- Ürünler: `http://localhost:8000/admin/products/`

### Temel İşlemler

#### Firma Yönetimi
- Firma listesi görüntüleme
- Yeni firma ekleme
- Firma bilgilerini düzenleme
- Firma silme

#### Kategori Yönetimi
- Hiyerarşik kategori listesi
- Yeni kategori ekleme
- Kategori düzenleme
- Kategori silme

#### Ürün Yönetimi
- Ürün listesi görüntüleme
- Yeni ürün ekleme
- Ürün bilgilerini düzenleme
- Ürün silme

## 🎨 Tasarım Özellikleri

- **Responsive Layout**: Mobil ve masaüstü uyumlu
- **Modern UI**: Tailwind CSS ile temiz tasarım
- **Sidebar Navigation**: Kolay sayfa geçişi
- **Card-based Layout**: Modern kart tasarımı
- **Color-coded Status**: Durum göstergeleri
- **Interactive Elements**: Hover efektleri ve geçişler

## 🔧 Geliştirme

### Yeni Entity Ekleme
1. Entity sınıfını oluşturun
2. Controller ekleyin
3. Form type oluşturun
4. Template'leri ekleyin
5. Route'ları güncelleyin

### Template Özelleştirme
- `templates/admin/base.html.twig` - Ana layout
- Her entity için ayrı template klasörü
- Tailwind CSS sınıfları ile stil özelleştirme

## 📱 Responsive Tasarım

- **Desktop**: Tam sidebar ve geniş layout
- **Tablet**: Responsive grid ve uyarlanabilir tablolar
- **Mobile**: Mobil menü ve optimize edilmiş görünüm

## 🎯 Gelecek Özellikler

- [ ] Kullanıcı yönetimi ve yetkilendirme
- [ ] Dosya yükleme sistemi
- [ ] Arama ve filtreleme
- [ ] Raporlama ve analitik
- [ ] API entegrasyonu
- [ ] Çoklu dil desteği

## 🤝 Katkıda Bulunma

1. Fork yapın
2. Feature branch oluşturun (`git checkout -b feature/AmazingFeature`)
3. Commit yapın (`git commit -m 'Add some AmazingFeature'`)
4. Push yapın (`git push origin feature/AmazingFeature`)
5. Pull Request oluşturun

## 📄 Lisans

Bu proje özel lisans altında geliştirilmiştir.

## 📞 İletişim

Proje hakkında sorularınız için lütfen iletişime geçin.

---

**Not**: Bu proje Symfony 7.3 ve modern web teknolojileri kullanılarak geliştirilmiştir.
