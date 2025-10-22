# PHP Serialize Compat 🧩

A lightweight compatibility layer that ensures forward and backward compatibility between  
`__sleep()` / `__wakeup()` (legacy) and `__serialize()` / `__unserialize()` (modern)  
serialization mechanisms in PHP.

> ⚙️ Supports PHP 5.6 → PHP 8.5+

---

## ✨ Features

- ✅ Works across **all PHP versions** (5.6+)
- 🔄 Automatically detects and adapts to the serialization mechanism
- 🧠 Uses **Reflection** to handle public/protected/private properties
- 🚫 No deprecated `setAccessible()` or reflection hacks
- 🔐 Safely restores private/protected properties via bound closures
- 🧩 Provides a clean hook `initAfterUnserialize()` for derived state reconstruction

---

## 📦 Installation

Clone or require it manually (no composer dependency needed):

```bash
git clone https://github.com/YOUR_USERNAME/php-serialize-compat.git
```

Include the trait in your class:

```php
require_once 'SerializeCompat.php';

class MyClass {
    use SerializeCompat;

    private $data;
    protected $cache;

    protected function initAfterUnserialize() {
        $this->cache = md5(serialize($this->data));
    }
}
```

---

## 🧪 Example

```php
require 'test.php';
```

Output sample:

```
PHP version: 8.3.0

=== Before serialize ===
id: 123
name: 'freemius'
flags: array ( 'beta' => true, )
computed (PRIVATE): 'FREEMIUS'

Serialized length: 182
Serialized sample: O:4:"Demo":3:{s:2:"id";i:123;...

=== After unserialize ===
id: 123
name: 'freemius'
flags: array ( 'beta' => true, )
computed (PRIVATE): 'FREEMIUS'
```

---

## 🧰 Design Principles

- Avoid breaking changes across PHP versions  
- Avoid deprecated APIs  
- Favor simple introspection (Reflection + `(array)$this`)  
- Safe to include in SDKs, libraries, or legacy systems  

---

## 🧑‍💻 Compatibility Matrix

| PHP Version | Supported | Notes |
|--------------|------------|-------|
| 5.6–7.3 | ✅ | Uses `__sleep` / `__wakeup` |
| 7.4–8.4 | ✅ | Uses `__serialize` / `__unserialize` |
| 8.5+ | ✅ | Avoids deprecated `__wakeup` |

---

## 📜 License

MIT © Daniele Alessandra

---

## 🙌 Contributing

Pull requests are welcome!  
You can:
- Improve test coverage
- Add more example classes
- Benchmark serialization performance
- Suggest edge-case handling (e.g. circular references)

---

## 🧩 Future Ideas

- PHPUnit test suite (`phpunit.xml.dist`)
- GitHub Actions CI for PHP 5.6–8.5
- Composer package (`composer.json`)
- Benchmark comparison (`serialize()` vs `json_encode()`)
- Extended example: object graph with inheritance & traits
