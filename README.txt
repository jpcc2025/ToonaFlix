TOONAFLIX - PHP / XAMPP VERSION
================================
1. Start Apache AND MySQL in the XAMPP Control Panel.
2. Copy this whole "toonaflix" folder into  C:\xampp\htdocs\
3. Open http://localhost/phpmyadmin -> SQL tab -> paste/run schema.sql
4. Open http://localhost/toonaflix/
Existing accounts from the Java app still work (plain-text passwords are upgraded to hashes on first login).
DB settings (host/user/password) are at the top of config.php.
The uploads/avatars folder must be writable (it is by default on XAMPP).
