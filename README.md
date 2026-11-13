# Agency site without a database

Listings and text live in JSON files. They are edited through `/admin`. MySQL is not required.

## What to buy

At Globalhost: a domain (`.ba` 54 KM or `.com` 34 KM per year) and the cheapest **Start hosting** (about 54 KM per year). It includes PHP, email and SSL. A larger package and a database are not needed.

In cPanel, create an email, for example `info@yourdomain.ba` and `noreply@yourdomain.ba`.

## Locally

PHP 8.1 or newer is required, with the GD and mbstring extensions.

```text
php -S localhost:8080 router.php
```

Site: `http://localhost:8080`  
Admin: `http://localhost:8080/admin/login.php`

`config.php` is a local file and is not committed to Git. Before hosting, put a new password hash in it and set `"debug" => false`.

```text
php -r "echo password_hash('YourPassword', PASSWORD_DEFAULT), PHP_EOL;"
```

Paste the hash into `password_hash`. `mail_from` must be an address on your domain, for example `noreply@yourdomain.ba`. Leave `mail_to` empty if inquiries should go to the email entered in the admin area.

Delete the sample listings in the admin area before the site goes live.

## What to upload to public_html

The entire contents of this folder, including `data`, `uploads`, `admin`, `includes` and the `.htaccess` files. On the server, create `config.php` from `config.example.php` and enter the hash, the email and the full site address in `base_url` (for example `https://yourdomain.ba`).

Do not delete the `.htaccess` files in `data`, `includes` and `uploads`. The first one stops the data from being read in the browser, and the second stops scripts from running among the images.

If uploading several images at once fails, raise these in cPanel (MultiPHP INI):

```text
upload_max_filesize = 8M
post_max_size = 48M
```

One listing accepts at most 12 images. Large ones are resized when they are saved.

## What it does

- `/admin` — password, listing list, new listing, editing, hiding, deleting
- Content — name, slogan, introduction, About, phone, email, address, opening hours, map link

The contact form sends email through PHP `mail()`. Inquiries are not stored in a database. If the host refuses to send, the visitor sees the phone number and email.
