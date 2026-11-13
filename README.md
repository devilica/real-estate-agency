# ELITE ESTATE

A real estate agency website. Visitors browse properties for sale and rent, open a listing, read about the agency, and send an inquiry. Staff manage listings and page text from a password-protected admin area.

The public site is in English. Listings and page text are stored in JSON files, so MySQL is not required.

## Public site

- **Home** shows the agency name, a short introduction, featured listings, and a quick search.
- **Properties** lists every published listing. Visitors can filter by sale or rent, property type, and city, and sort by price.
- **A listing** shows photos, price, location, area, rooms, and a description, with a link to inquire about that property.
- **About** is the agency story, address, phone, email, and opening hours.
- **Contact** is a form for a listing, a valuation, or a viewing. The message is emailed to the agency. If the host cannot send mail, the visitor still sees the phone number and email.

Property types include apartments, houses, land, commercial space, garages, holiday homes, and other. A listing can be for sale or for rent, and can be marked active, reserved, or sold.

## Admin

Open `/admin` and sign in. From there you can:

- add, edit, hide, reorder, and delete listings
- upload up to 12 images per listing
- edit the site name, headline, introduction, About text, phone, email, address, opening hours, and map link

Changes appear on the site immediately.

## Run it locally

PHP 8.1 or newer is required, with the GD and mbstring extensions.

```text
php -S localhost:8080 router.php
```

Site: `http://localhost:8080`  
Admin: `http://localhost:8080/admin/login.php`

GitHub Pages cannot run this site, because it does not execute PHP. Use any host that provides PHP, email, and SSL. Copy `config.example.php` to `config.php` and set the password hash, the sending address, and the full site URL before going live.
