# اپلیکیشن‌های اندروید چایخانه امام مهدی

این مخزن دو برنامه Native اندروید دارد؛ هیچ‌کدام WebView نیستند:

- `app`: برنامه کاربران، بدون نیاز به حساب کاربری؛ دریافت خانه، مراسم‌ها، گالری و چالش‌ها از API عمومی و ارسال فرم ثبت‌نام.
- `adminApp`: برنامه مدیر با ورود از طریق رمز مدیریت سایت؛ مدیریت درخواست‌های عضویت، مراسم‌ها، پیام‌ها/چالش‌ها و کمک‌های غیرنقدی.

## نشانی‌های API مورد استفاده
- API عمومی کاربران: `https://api.aghreza1080sub.ir/mobile/public.php`
- ثبت‌نام: `https://api.aghreza1080sub.ir/api/register.php`
- API مدیر: `https://api.aghreza1080sub.ir/mobile/server/admin.php`

فایل API مدیر در `server/mobile/server/admin.php` قرار دارد و باید روی هاست در مسیر متناظر `public_html/mobile/server/admin.php` قرار بگیرد. فایل‌های موجود سایت را حذف یا جایگزین نکنید.

## ساخت برنامه‌ها
از مسیر Actions گردش‌کار `Build User and Admin APKs` را اجرا کنید. دو فایل APK در Artifact با نام `Chaykhaneh-User-and-Admin-APKs` قرار می‌گیرند.

حداقل نسخه اندروید ۶ (API 23) است. رمز مدیر در برنامه ذخیره نمی‌شود و نشست ورود با کوکی و CSRF محافظت می‌شود.
