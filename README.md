
# 🛒 Online Store Platform

A modern web application representing an online store, built using **Laravel 12** and a **React Starter Kit**. This project includes full backend support for managing products, categories, carts, and orders, along with roles and permissions, rich testing and multilingual support.

---

## 🚀 Features

### 🛍️ Products & Categories
- Full **CRUD** for products and categories
- Product attributes include:
  - `name`, `description`, `price`, `slug` and more
  - `size`, `color`, `model` and etc.
- Products are organized into categories (e.g. Electronics, Apparel, Books)
- Filter products by category
- Product detail page support (via slug)
- Pagination for product listings

### 🧃 Cart
- Add products to cart
- View cart summary: quantity, subtotal, total
- Remove products from cart
- Checkout process

### ✅ Orders
- Users provide name, email, phone, and address on checkout
- Orders are saved with status and payment method
- Soft deletes enabled for safe removal
- Includes **order status tracking** (e.g. pending, completed, shipped)
- Payment method types like cash, Stripe, or PayPal supported
- Order item model tracks quantity and product snapshot

### 💳 Payment Methods & Order Statuses

- Manageable via Admin panel or API
- Used in admin workflows and checkout process

### 👤 Users
- User registration & login
- Authenticated user order history
- Role-based permissions

### 🛠️ Admin Panel
- Manage products, categories, orders and more
- Control payment methods and order statuses

---

## 🧩 Technologies Used

| Layer       | Tools                                   |
|-------------|-----------------------------------------|
| Backend     | Laravel 12 (PHP 8+)                     |
| Frontend    | React (Starter Kit)                     |
| ORM         | Laravel Eloquent                        |
| Styling     | Tailwind CSS                            |
| Routing     | RESTful APIs + Web routes               |
| Slugs       | Automatically generated                 |
| Translations| Dynamic via custom Trait                |
| Database    | SQL (SQLite, MySQL, PostgreSQL, etc)    |
| Testing     | PHPUnit                                 |
| Factories   | Complete coverage for all models        |

---

## 🧪 Testing

- Full test coverage using **PHPUnit**
- Follows **AAA structure** with clear comments
- Covers:
  - All RESTful API endpoints
  - Validation rules
  - Model relationships
  - Edge cases
  - Web and API functionality

> All tests are automatically reset with `RefreshDatabase`.

---

## 🌍 Localization

Multilingual support is implemented using built-in using **Laravel's native localization system** and a **custom dynamic translation trait**, offering robust and scalable language handling:

- Models such as Product, Category and more support dynamic translation of fields like name, description, etc.
- Translations are stored in the database
- Separate translation files for `bg`, `en`, etc. (for the static texts)
- Slugs are language-dependent, allowing localized and SEO-friendly URLs per language
- Language switching is fully supported and integrated with translated model attributes
- Easily extendable to support additional languages via the database

---

## 📦 Installation

### 1. Clone and Install Dependencies

```bash
git clone https://github.com/Petar-V-Nikolov/Laravel-React-shop.git
cd Laravel-React-shop
composer install
cp .env.example .env
php artisan key:generate
```

> ✅ Make sure to update your `.env` file with the correct database configuration and environment settings.

### 2. Compile Assets & Run Initial Setup

```bash
composer run dev
```

This will:
- Run database migrations
- Seed default data
- Compile frontend assets

### 3. Seed Additional Data (Optional)

```bash
php artisan db:seed
```

If you want to reseed the database:

```bash
php artisan migrate:fresh --seed
```

> ⚠️ **Warning:** This will reset and repopulate the database with fresh seed data. All existing data will be lost.

---

## 🧪 Run Tests

```bash
php artisan test
```

Or

```bash
vendor/bin/phpunit
```

---

## 📖 API Documentation

This project uses [`dedoc/scramble`](https://github.com/dedoc/scramble) to **automatically generate Swagger-style API documentation**.

To view the API docs:

> Live preview available at `/docs/api` (recomended to configure it)

---

## 🎯 Roadmap

### 🧩 Core Features

- [x] **Product Management**  
  - [x] Product model and migration  
  - [x] Attributes: name, description, price, slug, etc.  
  - [x] Advanced attributes: size, color, model  
  - [x] Category-based organization  
  - [ ] Product controller and CRUD routes  
  - [ ] Product UI/API integration  

- [x] **Category Management**  
  - [x] Category model and migration  
  - [x] Language-specific slugs  
  - [x] Nested category structure  
  - [ ] Category controller and routes  

- [x] **Attribute System**  
  - [x] Attribute model and migration  
  - [x] Relation to products based on categories
  - [ ] Attribute-based filtering  

### 🛒 Cart & Orders

- [x] **Cart Functionality**  
  - [x] Add/remove products from cart  
  - [x] Quantity adjustment  
  - [x] Cart total calculation  
  - [x] Cart session or DB storage  
  - [ ] Cart frontend integration  

- [x] **Orders**  
  - [x] Order model and migration  
  - [x] Customer details capture (name, phone, address)  
  - [x] Order items and snapshots  
  - [x] Soft deletes  
  - [x] Order statuses (pending, paid, shipped, etc.)  
  - [ ] Admin order management interface  

- [x] **Payment Methods & Statuses**  
  - [x] Payment method model  
  - [x] Order status model  
  - [ ] Management via admin or API  
  - [ ] Integration with Stripe / PayPal  

### 🌐 Localization & SEO

- [x] **Translations**  
  - [x] Dynamic translation trait  
  - [x] Database translation storage  
  - [x] Static translations via lang files  
  - [ ] Language switcher  

- [x] **Localized Slugs**  
  - [x] Language-dependent slugs  
  - [x] SEO-friendly URLs  
  - [x] Automatic slug generation per locale  

### 🔐 Auth & User Roles

- [x] **Users (from Starter Kit)**  
  - [x] Authentication scaffolding  
  - [x] Registration, login, logout  
  - [x] Password reset and email verification  

- [ ] **Roles & Permissions**  
  - [ ] Role and permission models  
  - [ ] Assign roles to users  
  - [ ] Policies and gates integration  
  - [ ] Admin-only access enforcement  

### 🛠️ Admin Panel

- [ ] **Admin Interfaces (planned)**  
  - [ ] Product management  
  - [ ] Category management  
  - [ ] Orders and statuses  
  - [ ] Payment method management  
  - [ ] Role and permission assignment  

### ⚙️ Dev Tools & API

- [x] **API Documentation**  
  - [x] `dedoc/scramble` integration  
  - [x] Generate docs with artisan command  
  - [x] Available at `/docs/api` after generation  

- [x] **Testing Suite**  
  - [x] Feature and unit test coverage  
  - [x] Factories for all models  
  - [x] PHPUnit configuration  
  - [x] Uses RefreshDatabase trait  

### 🎨 Frontend (React)

- [ ] **React SPA Setup**  
  - [ ] Product listing  
  - [ ] Product detail pages  
  - [ ] Cart UI  
  - [ ] Order placement flow  
  - [ ] Admin dashboard


---

## 🧑‍💻 Author

Created by Petar Nikolov for educational and personal development purposes.  
Contributions are welcome!

---

## 📜 License

This project is open-source and licensed under the [MIT License](LICENSE).
