# SPOURAL EVENT MANAGEMENT SYSTEM

This project is a login portal for the **SPOURAL ULSC (University Level Student Coordinator)** system, used for managing access to the dashboard for event management. The interface is clean, responsive, and designed using pure HTML, CSS, and PHP, following secure login practices.

## 🔧 Technologies Used

- **PHP (89.6%)**: Handles backend login logic, session management, and database operations.
- **HTML (6.3%)**: Provides the structure of the login form and UI.
- **CSS (3.6%)**: Custom styles for a modern and responsive design.
- **Hack (0.5%)**: Used minimally for compatibility or logic extensions.

## 📁 Features

- Secure login with session handling
- Password validation using `password_verify()`
- Basic error handling with logging
- Responsive design with custom styles (no CSS frameworks)
- Branded with SPOURAL and CHARUSAT identity
- Placeholder fallback password (`password`) for testing

## 🖥️ How to Run

1. Clone or download the repository
2. Place files in your server root (e.g., `htdocs/` for XAMPP)
3. Configure your `includes/config.php` with the correct DB credentials
4. Ensure a `ulsc` table exists in your database with fields like `ulsc_id`, `ulsc_name`, and `password`
5. Start the server and visit `http://localhost/your-project-folder/`

## ⚙️ File Structure


## 🚨 Security Notes

- Do **not** use fallback passwords (`$password === 'password'`) in production
- Always hash passwords using `password_hash()`
- Consider CSRF protection for forms
- Use HTTPS in deployment

## 📞 Support

For issues related to credentials or access, contact: [CHARUSAT IT Helpdesk](https://charusat.ac.in/contact_us.php)

---

© SPOURAL Event Management System · CHARUSAT · 2025
