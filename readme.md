# Brand Price Lists AJAX

# این توضیحات توسط AI (هوش مصنوعی) نوشته شده است

**Brand Price Lists AJAX** یک افزونه توسعه‌پذیر برای **WordPress** و **WooCommerce** است که برای نمایش و مدیریت لیست قیمت محصولات بر اساس برند طراحی شده است.

این پروژه با تمرکز بر **Performance، AJAX، Lazy Loading، انعطاف‌پذیری و یکپارچگی با Elementor** توسعه داده شده و برای فروشگاه‌هایی مناسب است که تعداد زیادی محصول و برند دارند و نمی‌خواهند تمام اطلاعات محصولات را در اولین درخواست صفحه بارگذاری کنند.

افزونه کاملاً عمومی است و به هیچ فروشگاه، دامنه، برند یا کسب‌وکار خاصی وابسته نیست.

---

## فهرست مطالب

* [درباره پروژه](#درباره-پروژه)
* [اهداف پروژه](#اهداف-پروژه)
* [قابلیت‌ها](#قابلیت‌ها)
* [پیش‌نیازها](#پیشنیازها)
* [نصب](#نصب)
* [شروع کار](#شروع-کار)
* [استفاده از Shortcode](#استفاده-از-shortcode)
* [استفاده با Elementor](#استفاده-با-elementor)
* [Taxonomy برند](#taxonomy-برند)
* [AJAX و Lazy Loading](#ajax-و-lazy-loading)
* [ساختار پروژه](#ساختار-پروژه)
* [معماری افزونه](#معماری-افزونه)
* [امنیت](#امنیت)
* [Performance و Cache](#performance-و-cache)
* [Responsive و Styling](#responsive-و-styling)
* [عیب‌یابی](#عیبیابی)
* [راهنمای توسعه](#راهنمای-توسعه)
* [Roadmap](#roadmap)
* [Changelog](#changelog)
* [مشارکت در پروژه](#مشارکت-در-پروژه)
* [License](#license)

---

# درباره پروژه

در بسیاری از فروشگاه‌های WooCommerce، محصولات بر اساس برند دسته‌بندی می‌شوند و کاربران نیاز دارند قیمت محصولات هر برند را در قالب یک **Price List** مشاهده کنند.

روش ساده برای پیاده‌سازی چنین قابلیتی این است که تمام برندها و تمام محصولات آن‌ها در زمان بارگذاری صفحه Query شوند. این روش در فروشگاه‌های کوچک ممکن است قابل قبول باشد، اما با افزایش تعداد محصولات می‌تواند باعث افزایش:

* زمان پاسخ سرور
* حجم HTML
* تعداد Queryهای دیتابیس
* مصرف حافظه PHP
* حجم JavaScript و DOM
* زمان Rendering صفحه

شود.

**Brand Price Lists AJAX** با هدف حل همین مسئله ایجاد شده است.

به جای اینکه تمام محصولات در درخواست اولیه صفحه دریافت شوند، ساختار اصلی لیست قیمت ایجاد شده و اطلاعات محصولات در مراحل بعدی و در صورت نیاز از طریق AJAX دریافت می‌شوند.

---

# اهداف پروژه

اهداف اصلی پروژه عبارت‌اند از:

### 1. کاهش هزینه Query اولیه

فقط اطلاعات موردنیاز در درخواست اولیه دریافت شود و محصولات در مراحل بعدی بارگذاری شوند.

### 2. استفاده از AJAX برای داده‌های حجیم

محصولات هر برند می‌توانند به‌صورت مستقل و مرحله‌ای دریافت شوند.

### 3. پشتیبانی از فروشگاه‌های بزرگ

معماری افزونه باید بتواند در سایت‌هایی با تعداد زیاد محصول و برند نیز قابل استفاده باشد.

### 4. یکپارچگی با Elementor

کاربر بدون نیاز به نوشتن کد می‌تواند Widget را از طریق Elementor پیکربندی کند.

### 5. انعطاف‌پذیری در نمایش

افزونه نباید به یک Layout یا ساختار HTML خاص محدود باشد.

### 6. جداسازی منطق داده از Presentation

Query محصولات، AJAX، تنظیمات، Widget و Render تا حد امکان به شکل مستقل سازمان‌دهی شده‌اند.

### 7. مستقل بودن از یک سایت خاص

افزونه به هیچ دامنه، فروشگاه یا برند خاصی وابسته نیست و می‌تواند به‌عنوان یک پروژه مستقل توسعه داده شود.

---

# قابلیت‌ها

## مدیریت برند

* نمایش برندهای دارای محصول
* نمایش نام برند
* نمایش لوگوی برند در صورت وجود
* نمایش تعداد محصولات هر برند
* مرتب‌سازی برندها بر اساس نام
* مرتب‌سازی برندها بر اساس تعداد محصولات
* انتخاب همه برندها
* انتخاب یک برند
* انتخاب چند برند

## محصولات

* بارگذاری مرحله‌ای محصولات
* AJAX Loading
* Lazy Loading
* دکمه Load More
* فیلتر بر اساس موجودی
* فیلتر محصولات تخفیف‌دار
* فیلتر Featured
* فیلتر دسته‌بندی
* جستجوی محصولات
* مرتب‌سازی بر اساس:

  * نام
  * تاریخ
  * Menu Order
  * قیمت

## Layout

چند Layout برای نمایش Price List در نظر گرفته شده است:

* `Table`
* `Compact`
* `Stacked`
* `Zigzag`

ساختار Layout به شکلی طراحی شده که در آینده بتوان Presetهای جدیدی به آن اضافه کرد.

## Elementor

Widget اختصاصی:

```text
Brand Price List
```

با قابلیت:

* انتخاب برند
* انتخاب چند برند
* تنظیم ستون‌ها
* تغییر عنوان ستون‌ها
* Drag & Drop ستون‌ها
* کنترل Layout
* کنترل Width
* کنترل Spacing
* Typography
* رنگ‌ها
* Border Radius
* Hover
* Responsive Controls

## Performance

* AJAX
* Lazy Loading
* Query بهینه محصولات
* استفاده از `fields => ids` در Queryهای موردنیاز
* استفاده از `no_found_rows`
* Cache اطلاعات برندها
* جلوگیری از Query تمام محصولات در درخواست اولیه

---

# پیش‌نیازها

برای استفاده از نسخه فعلی پروژه، محیط زیر موردنیاز است:

| Component   | Requirement                   |
| ----------- | ----------------------------- |
| WordPress   | 7.0+                          |
| PHP         | 8.2+                          |
| WooCommerce | Required                      |
| Elementor   | Required for Elementor Widget |
| Browser     | JavaScript Enabled            |

> نسخه WordPress و PHP باید با نسخه WooCommerce و سایر افزونه‌های سایت نیز سازگار باشد.

### وابستگی‌های اصلی

افزونه برای عملکرد اصلی خود به:

```text
WordPress
WooCommerce
```

وابسته است.

برای استفاده از Widget:

```text
Elementor
```

نیز باید فعال باشد.

**Elementor برای استفاده از Shortcode الزامی نیست.**

---

# نصب

## روش اول — نصب ZIP

1. فایل ZIP افزونه را دریافت کنید.
2. وارد پنل WordPress شوید.
3. به مسیر زیر بروید:

```text
Plugins → Add New Plugin → Upload Plugin
```

4. فایل ZIP را انتخاب کنید.
5. روی **Install Now** کلیک کنید.
6. افزونه را فعال کنید.

پس از فعال‌سازی، در صورت فعال بودن Elementor، Widget نیز در Elementor ثبت می‌شود.

---

## روش دوم — نصب از Git

برای توسعه‌دهندگان می‌توان Repository را مستقیماً در محیط توسعه Clone کرد:

```bash
git clone https://github.com/hessamzm/brand-price-lists-ajax.git
```

سپس پوشه پروژه را در مسیر:

```text
wp-content/plugins/
```

قرار دهید.

ساختار باید به شکل زیر باشد:

```text
wp-content/
└── plugins/
    └── brand-price-lists-ajax/
```

سپس افزونه را از پنل WordPress فعال کنید.

---

# شروع کار

بعد از نصب، سریع‌ترین روش برای بررسی عملکرد افزونه استفاده از Shortcode است.

در یک Page یا Post قرار دهید:

```text
[price_list]
```

صفحه را ذخیره و مشاهده کنید.

اگر taxonomy برند سایت روی مقدار پیش‌فرض باشد و برندهای دارای محصول وجود داشته باشند، Price List باید نمایش داده شود.

---

# اولین استفاده با یک برند

برای تست یک برند مشخص:

```text
[price_list brand="brand-slug"]
```

مثال:

```text
[price_list brand="hikvision"]
```

در این مثال:

```text
hikvision
```

باید **slug واقعی Term برند** باشد.

نام نمایشی برند و slug الزاماً یکسان نیستند.

---

# استفاده با Elementor

اگر Elementor فعال باشد، Widget زیر در Elementor قابل استفاده خواهد بود:

```text
Brand Price List
```

برای شروع:

1. یک Page را با Elementor باز کنید.
2. پنل Widgets را باز کنید.
3. عبارت زیر را جستجو کنید:

```text
Brand Price List
```

4. Widget را به صفحه اضافه کنید.
5. برند یا برندهای موردنظر را انتخاب کنید.
6. Layout را انتخاب کنید.
7. ستون‌های Price List را تنظیم کنید.
8. Style و Responsive را پیکربندی کنید.
9. صفحه را Publish کنید.

Widget در دسته:

```text
Brand Price Lists
```

قرار می‌گیرد و برای دسترسی آسان در دسته عمومی Elementor نیز قابل مشاهده است.

---

# تنظیمات Widget

## Brand Selection

Widget سه حالت اصلی دارد:

```text
All Brands
Single Brand
Multiple Brands
```

### All Brands

تمام برندهای واجد شرایط نمایش داده می‌شوند.

### Single Brand

فقط یک برند مشخص نمایش داده می‌شود.

### Multiple Brands

چند برند به‌صورت هم‌زمان انتخاب می‌شوند.

---

# Product Filters

محصولات را می‌توان با معیارهای مختلف فیلتر کرد:

```text
Search
Stock Status
On Sale
Featured
Category
```

همچنین ترتیب نمایش قابل تنظیم است:

```text
Name
Date
Menu Order
Price
```

---

# Product Columns

ستون‌های Price List از طریق Elementor قابل مدیریت هستند.

امکانات:

* فعال/غیرفعال کردن ستون
* تغییر عنوان ستون
* تغییر ترتیب ستون‌ها
* Drag & Drop

این ساختار امکان ایجاد Price Listهای مختلف بدون تغییر کد را فراهم می‌کند.

---

# Layoutها

## Table

نمایش کلاسیک محصولات در قالب جدول.

مناسب برای:

* Price Listهای رسمی
* لیست‌های طولانی
* نمایش تعداد زیاد محصول

## Compact

نمایش فشرده برای کاهش فضای عمودی.

## Stacked

نمایش برند و محصولات به شکل عمودی.

## Zigzag

نمایش بخش برند و محصولات در ردیف‌های متوالی با امکان تغییر جهت نمایش.

---

# Taxonomy برند

افزونه به یک سیستم برند خاص وابسته نیست.

Taxonomy پیش‌فرض:

```text
product_brand
```

است.

با این حال، سایت می‌تواند از taxonomy دیگری استفاده کند؛ برای مثال:

```text
brand
pwb-brand
product_brand
```

Taxonomy مورد استفاده باید واقعاً در WordPress ثبت شده باشد و محصولات به Termهای آن متصل باشند.

---

# AJAX و Lazy Loading

یکی از بخش‌های اصلی معماری افزونه، بارگذاری مرحله‌ای محصولات است.

فرآیند کلی:

```text
Page Request
     │
     ▼
Render Price List Structure
     │
     ▼
Load Brand Information
     │
     ▼
AJAX Request
     │
     ▼
Load Product Batch
     │
     ▼
Render Products
     │
     ▼
Load More / Next Batch
```

در نتیجه، تمام محصولات مجبور نیستند در اولین درخواست صفحه دریافت شوند.

---

# AJAX Action

Action اصلی افزونه:

```text
price_list_load_products
```

درخواست شامل پارامترهایی مانند موارد زیر است:

```text
nonce
brand
taxonomy
offset
limit
columns
```

AJAX برای کاربران واردشده و کاربران مهمان ثبت شده است.

---

# Performance

هدف اصلی Performance در این پروژه کاهش هزینه درخواست اولیه است.

در Query محصولات، بسته به شرایط، از تکنیک‌هایی مانند:

```php
'fields' => 'ids'
```

و:

```php
'no_found_rows' => true
```

استفاده می‌شود.

همچنین اطلاعات برندها می‌توانند Cache شوند.

با این حال، Performance نهایی به عوامل محیطی نیز وابسته است، از جمله:

* تعداد محصولات
* تعداد Termها
* نوع taxonomy
* وضعیت دیتابیس
* Object Cache
* Page Cache
* Hosting
* PHP Version
* سایر افزونه‌ها

---

# امنیت

افزونه برای درخواست‌های AJAX و ورودی‌های کاربر از مکانیزم‌های استاندارد WordPress استفاده می‌کند.

از جمله:

* WordPress Nonce
* `sanitize_key()`
* `sanitize_title()`
* `absint()`
* اعتبارسنجی taxonomy
* اعتبارسنجی term
* محدودسازی ورودی‌ها
* Escape خروجی
* Escape URL

هدف این لایه‌ها جلوگیری از پردازش ورودی‌های نامعتبر و کاهش ریسک‌های رایج در پردازش داده‌های WordPress است.

---

# Responsive و Styling

Widget با سیستم Responsive Elementor سازگار است.

تنظیمات قابل کنترل برای:

```text
Desktop
Tablet
Mobile
```

در دسترس هستند.

همچنین می‌توان موارد زیر را شخصی‌سازی کرد:

* Typography
* Background
* Text Color
* Border
* Border Radius
* Spacing
* Padding
* Product Row Hover
* Button Styles
* Brand Logo Size

افزونه فونت اختصاصی تحمیل نمی‌کند و به‌صورت پیش‌فرض از فونت محیط سایت استفاده می‌کند:

```css
font-family: inherit;
```

---

# ساختار پروژه

ساختار فعلی پروژه:

```text
brand-price-lists-ajax/
│
├── brand-price-lists-ajax.php
├── includes/
│   └── class-elementor-widget.php
│
└── readme.md
```

## `brand-price-lists-ajax.php`

هسته اصلی افزونه است و مسئول بخش‌هایی مانند:

* Plugin Bootstrap
* Shortcode
* AJAX
* Product Query
* Brand Query
* Render
* Cache
* Front-end Assets
* Elementor Integration

است.

## `includes/class-elementor-widget.php`

Integration مربوط به Elementor در این فایل قرار دارد.

مسئولیت‌های آن شامل:

* تعریف Widget
* Elementor Controls
* Style Controls
* Responsive Controls
* ثبت Widget
* ثبت Category

است.

---

# معماری افزونه

ثبت Integration مربوط به Elementor پس از initialization شدن Elementor انجام می‌شود.

ابتدا Integration در:

```text
elementor/init
```

بارگذاری می‌شود.

سپس Widget از طریق API رسمی Elementor در:

```text
elementor/widgets/register
```

ثبت می‌شود.

دسته اختصاصی نیز از طریق:

```text
elementor/elements/categories_registered
```

ثبت می‌شود.

این معماری از وابستگی به ترتیب Load تصادفی فایل‌ها جلوگیری می‌کند و باعث می‌شود Widget در زمان مناسب در Widgets Manager Elementor ثبت شود.

---

# توسعه پروژه

اگر قصد توسعه این پروژه را دارید، پیشنهاد می‌شود ابتدا ساختار فعلی افزونه را بررسی کنید.

ترتیب پیشنهادی:

### مرحله 1 — Plugin Bootstrap

فایل:

```text
brand-price-lists-ajax.php
```

را بررسی کنید.

این فایل نقطه ورود افزونه است.

### مرحله 2 — Data Layer

منطق مربوط به:

```text
Brands
Products
Taxonomy
Filters
Query
Cache
```

را بررسی کنید.

### مرحله 3 — Rendering

نحوه تبدیل داده‌ها به خروجی HTML را بررسی کنید.

### مرحله 4 — AJAX

Action زیر را بررسی کنید:

```text
price_list_load_products
```

### مرحله 5 — Elementor

در نهایت فایل:

```text
includes/class-elementor-widget.php
```

را بررسی کنید.

این فایل لایه Integration با Elementor است.

---

# توسعه Layout جدید

برای اضافه کردن Layout جدید، پیشنهاد می‌شود Layout به‌عنوان یک Presentation Layer مستقل در نظر گرفته شود.

برای مثال:

```text
Table
Compact
Stacked
Zigzag
```

Layout جدید نباید منطق Query محصولات را دوباره پیاده‌سازی کند.

بهتر است:

```text
Data
  ↓
Query
  ↓
Normalized Product Data
  ↓
Layout Renderer
```

استفاده شود.

این تفکیک باعث می‌شود اضافه کردن Layout جدید بدون ایجاد Queryهای تکراری امکان‌پذیر باشد.

---

# توسعه Elementor Controls

Controlهای جدید باید تا حد امکان:

* قابل Responsive باشند.
* با ساختار Elementor هماهنگ باشند.
* مقدار پیش‌فرض مشخص داشته باشند.
* روی Front-end و Editor رفتار یکسانی داشته باشند.

---

# عیب‌یابی

## Widget در Elementor نمایش داده نمی‌شود

موارد زیر را بررسی کنید:

1. Elementor فعال باشد.
2. افزونه فعال باشد.
3. WooCommerce فعال باشد.
4. Elementor را به‌روز کنید.
5. از مسیر زیر استفاده کنید:

```text
Elementor → Tools → Regenerate CSS & Data
```

6. Editor را Refresh کنید.
7. عبارت زیر را جستجو کنید:

```text
Brand Price List
```

---

## برندها نمایش داده نمی‌شوند

بررسی کنید:

* taxonomy صحیح انتخاب شده باشد.
* taxonomy در WordPress ثبت شده باشد.
* Termهای برند وجود داشته باشند.
* محصولات به برندها متصل باشند.
* Cache قدیمی وجود نداشته باشد.

---

## محصولات نمایش داده نمی‌شوند

موارد زیر را بررسی کنید:

* slug برند
* taxonomy
* ارتباط محصول و برند
* وضعیت انتشار محصول
* وضعیت موجودی
* فیلترهای فعال Elementor

---

## AJAX کار نمی‌کند

ابتدا Browser Console را بررسی کنید.

سپس موارد زیر را بررسی کنید:

```text
WordPress Debug Log
Browser Console
admin-ajax.php
Nonce
Caching Plugins
Security Plugins
Server Logs
```

برای Debug کردن WordPress می‌توان از:

```php
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
```

استفاده کرد.

> فعال کردن Debug در Production باید با احتیاط انجام شود و خروجی خطا نباید برای کاربران نمایش داده شود.

---

# چگونه از پروژه شروع کنیم؟

اگر قصد دارید روی پروژه توسعه انجام دهید، مسیر پیشنهادی این است:

```text
1. Clone Repository
        ↓
2. Install WordPress
        ↓
3. Install WooCommerce
        ↓
4. Install Elementor
        ↓
5. Activate Plugin
        ↓
6. Test [price_list]
        ↓
7. Test Elementor Widget
        ↓
8. Inspect AJAX
        ↓
9. Modify / Extend
        ↓
10. Test
        ↓
11. Commit
        ↓
12. Pull Request
```

برای توسعه بهتر است یک محیط Local یا Staging داشته باشید و تغییرات را مستقیماً روی Production انجام ندهید.

---

# Development Environment

حداقل محیط پیشنهادی:

```text
WordPress
WooCommerce
Elementor
PHP 8.2+
MySQL / MariaDB
Git
```

پیشنهاد می‌شود برای توسعه از محیط‌های Local مانند:

```text
Local
Docker
XAMPP
MAMP
```

یا هر محیط استاندارد WordPress Development استفاده شود.

---

# اهداف آینده

پروژه در آینده می‌تواند در چند مسیر توسعه پیدا کند:

### Performance

* بهینه‌سازی Queryهای بزرگ
* Object Cache Integration
* بهینه‌سازی AJAX
* کاهش DOM
* بهبود Lazy Loading
* بررسی REST API به‌عنوان گزینه جایگزین AJAX

### UI

* Layoutهای بیشتر
* Table Presetهای بیشتر
* Mobile-first Layout
* Card Layout
* Comparison Layout
* Price-focused Layout

### Elementor

* Controlهای بیشتر
* Presetهای آماده
* Templateهای Layout
* کنترل‌های پیشرفته‌تر Responsive

### Developer Experience

* Hookهای عمومی برای توسعه‌دهندگان
* Filterهای بیشتر
* مستندات API
* Unit Tests
* Integration Tests
* Coding Standards
* CI/CD

---

# Roadmap

## Phase 1 — Core

* [x] WooCommerce Integration
* [x] Brand Taxonomy
* [x] Product Query
* [x] Shortcode
* [x] AJAX Loading
* [x] Cache

## Phase 2 — Price List UI

* [x] Product Columns
* [x] Product Filters
* [x] Sorting
* [x] Table Layout
* [x] Compact Layout
* [x] Stacked Layout
* [x] Zigzag Layout

## Phase 3 — Elementor

* [x] Elementor Widget
* [x] Brand Selection
* [x] Multiple Brand Selection
* [x] Typography Controls
* [x] Responsive Controls
* [x] Hover Controls
* [x] Layout Controls

## Phase 4 — Future Development

* [ ] Layout Presets
* [ ] Advanced Cache Layer
* [ ] Automated Tests
* [ ] Developer Hooks Documentation
* [ ] CI/CD
* [ ] Additional Data Sources
* [ ] Advanced Mobile Layouts

---

# Changelog

## 2.0.0

* عمومی‌سازی کامل پروژه
* حذف وابستگی نامی به سایت یا برند خاص
* معرفی Shortcode عمومی:

```text
[price_list]
```

* معرفی AJAX Action عمومی:

```text
price_list_load_products
```

* عمومی‌سازی نام‌های داخلی PHP
* عمومی‌سازی CSS و JavaScript
* عمومی‌سازی Elementor Integration
* بازنویسی مستندات پروژه
* مستندسازی معماری افزونه
* مستندسازی فرآیند توسعه

---

# مشارکت در پروژه

Pull Request و Issue برای توسعه پروژه استقبال می‌شود.

پیشنهاد می‌شود قبل از ارسال Pull Request:

1. تغییرات خود را در محیط Development تست کنید.
2. عملکرد Shortcode را بررسی کنید.
3. Elementor Widget را بررسی کنید.
4. AJAX را تست کنید.
5. عملکرد Responsive را بررسی کنید.
6. تغییرات را به‌صورت واضح Commit کنید.
7. توضیح مناسبی برای Pull Request ارائه دهید.

برای تغییرات بزرگ، بهتر است ابتدا یک Issue ایجاد شود تا درباره معماری و روش پیاده‌سازی آن تصمیم‌گیری شود.

---

# Repository

Repository پروژه:

```text
https://github.com/hessamzm
```

---

# License

لایسنس پروژه باید متناسب با تصمیم صاحب پروژه در Repository تعیین شود.

در صورت انتشار Open Source، پیشنهاد می‌شود فایل زیر در ریشه پروژه قرار گیرد:

```text
LICENSE
```

و نوع License به‌صورت صریح در همین README مشخص شود.

---

# Quick Start

### نصب

```text
WordPress
   ↓
WooCommerce
   ↓
Brand Price Lists AJAX
   ↓
Activate
```

### Shortcode

```text
[price_list]
```

### یک برند

```text
[price_list brand="brand-slug"]
```

### Elementor

```text
Elementor
   ↓
Brand Price List
```

### AJAX

```text
price_list_load_products
```

### Taxonomy پیش‌فرض

```text
product_brand
```

---

# وضعیت پروژه

**Brand Price Lists AJAX — v3.2.0**

یک افزونه عمومی برای WordPress و WooCommerce با تمرکز بر:

```text
Performance
AJAX
Lazy Loading
Brand-based Product Lists
WooCommerce
Elementor
Responsive UI
Extensibility
```

ساختار پروژه به‌گونه‌ای طراحی شده است که بتوان قابلیت‌های جدید، Layoutهای بیشتر، فیلترهای پیشرفته‌تر و Integrationهای جدید را بدون وابستگی به یک سایت یا کسب‌وکار خاص به آن اضافه کرد.
