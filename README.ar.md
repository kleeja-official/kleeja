<div align="center">

[English](README.md) · **العربية**

# كليجا (Kleeja)

**أيسر السبل لتشغيل خدمة خاصة بك لرفع الملفات ومشاركتها.**
نظامٌ ذاتي الاستضافة مبنيٌّ بلغة PHP، يحظى بثقة مديري المواقع منذ عام 2007.

[![Latest release](https://img.shields.io/github/v/release/kleeja/kleeja?label=release)](https://github.com/kleeja/kleeja/releases)
[![PHP](https://img.shields.io/badge/php-%3E%3D8.0-777bb4?logo=php&logoColor=white)](https://www.php.net)
[![License: MIT](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE.md)
[![Code style: Prettier](https://img.shields.io/badge/code_style-prettier-ff69b4.svg)](https://prettier.io)
[![Discord](https://img.shields.io/badge/chat-discord-5865f2?logo=discord&logoColor=white)](https://discord.gg/Mp3XVKP)

[التنزيل](https://github.com/kleeja/kleeja/releases) ·
[التوثيق](https://kleeja.net/getting-started/introduction) ·
[المزايا](https://kleeja.net) ·
[سجل التغييرات](CHANGELOG.md) ·
[الإبلاغ عن خلل](https://github.com/kleeja/kleeja/issues)

<img src="https://raw.githubusercontent.com/kleeja/website/master/screenshot1.png" width="720" alt="صفحة الرفع في كليجا">

</div>

> [!IMPORTANT]
> لتشغيل كليجا على موقع فعلي، يُرجى تنزيل النسخة المُعدّة للنشر من
> [صفحة الإصدارات](https://github.com/kleeja/kleeja/releases)، وعدم
> نشر نسخة مستنسخة من هذا المستودع؛ إذ إنه يتضمن أعمال التطوير الجارية.

## المزايا

- **الرفع والمشاركة**: يتيح للزوار والأعضاء رفع الملفات والحصول على روابط قابلة للمشاركة.
- **لوحة تحكم إدارية**: تُمكّن من إدارة الملفات والمستخدمين وقوائم الحظر والإعدادات عبر المتصفح.
- **التحديث بنقرة واحدة**: يمكن تحديث كليجا ذاته مباشرةً من لوحة الإدارة.
- **متجر الإضافات والأنماط**: يُتيح تنزيل الإضافات والقوالب وتثبيتها وتحديثها
  وحذفها بنقرة واحدة.
- **تعدد اللغات**: يأتي مزوّدًا باللغتين العربية والإنجليزية.

للاطلاع على [القائمة الكاملة للمزايا](https://github.com/kleeja/kleeja/wiki/Key-Features-&-Highlights-of-Kleeja)، يُرجى الرجوع إلى الويكي.

<div align="center">
<img src="https://raw.githubusercontent.com/kleeja/website/master/screenshot2.png" width="720" alt="صفحة مشاركة الملفات في كليجا">
</div>

## المتطلبات

- الإصدار 8.0 من PHP أو ما يليه، مع الامتداد `pdo_mysql` أو `pdo_sqlite` (ويُوصى بالامتدادين `gd` و`zip`)
- قاعدة بيانات MySQL أو MariaDB
- خادم ويب مثل Apache أو IIS (يتضمن المشروع ملفَّي الإعداد النموذجيين `htaccess.txt` و`web.config`)

## التثبيت

1. نزّل أحدث حزمة من [صفحة الإصدارات](https://github.com/kleeja/kleeja/releases)،
   ثم ارفع محتوياتها إلى خادم الويب.
2. امنح خادم الويب صلاحية الكتابة على المجلدين `cache/` و`uploads/`.
3. افتح الرابط `https://your-site.example/install/` في المتصفح، واتبع خطوات معالج التثبيت.

يتناول [الويكي](https://github.com/kleeja/kleeja/wiki) إعداد خادم الويب،
وإجراءات الترقية، وحل المشكلات الشائعة.

## بيئة التطوير المحلية

### التشغيل باستخدام Docker

يُشغّل ملف Compose المرفق كليجا على خادم Apache مع PHP 8.2، إلى جانب قاعدة بيانات
MySQL 8، وأداة [Mailpit](https://mailpit.axllent.org) لالتقاط الرسائل البريدية الصادرة.

```bash
docker compose up -d --build
```

| الخدمة        | العنوان                                                 |
| ------------- | ------------------------------------------------------- |
| كليجا         | <http://localhost:8080> (معالج التثبيت على `/install/`) |
| واجهة Mailpit | <http://localhost:8025/mailpit>                         |
| MySQL         | `127.0.0.1:3306`                                        |

عندما يطلب معالج التثبيت بيانات قاعدة البيانات، استخدم القيم الآتية:

| الإعداد        | القيمة    |
| -------------- | --------- |
| المضيف         | `mysql`   |
| قاعدة البيانات | `appdb`   |
| المستخدم       | `appuser` |
| كلمة المرور    | `apppass` |

لإرسال البريد عبر Mailpit، اضبط مضيف SMTP على `mailpit` والمنفذ على `1025`.

### تنسيق الشيفرة

تُنسَّق الشيفرة المصدرية باستخدام [Prettier](https://prettier.io) والإضافة
[@prettier/plugin-php](https://github.com/prettier/plugin-php)، ويرفض نظام التكامل
المستمر (CI) طلبات الدمج غير المنسّقة.

```bash
npm install                            # تثبيت أدوات التطوير
npm run format                         # تنسيق جميع الملفات
npm run format:check                   # الفحص دون تعديل (وهو ما يُنفّذه CI)
git config core.hooksPath .githooks    # تفعيل خطّافَي pre-commit و commit-msg
```

تُستثنى القوالب (`*.html`) والملفات الخارجية المُضمَّنة من التنسيق؛ راجع الملف `.prettierignore`.

## المساهمة

نرحّب بمساهماتكم. قبل فتح طلب دمج (Pull Request)، يُرجى مراعاة ما يلي:

1. تشغيل الأمر `npm run format` لضمان اجتياز فحص Prettier.
2. كتابة رسائل الإيداع (commit) وفق صيغة [Conventional Commits](https://www.conventionalcommits.org)،
   ومثال ذلك: `fix(auth): sign the whole login cookie payload`.
   ويتولى الخطّاف `commit-msg` التحقق من الالتزام بهذه الصيغة.

للاستفسارات، يمكنكم الانضمام إلى [خادم Discord](https://discord.gg/Mp3XVKP) أو
[فتح بلاغ جديد](https://github.com/kleeja/kleeja/issues).

## الترخيص

يُوزَّع كليجا بموجب [رخصة MIT](LICENSE.md).
