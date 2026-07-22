-- Jalankan sekali di phpMyAdmin InfinityFree sebelum deploy aplikasi.
ALTER TABLE `users`
    MODIFY `role` ENUM('super_admin','area_manager','admin','staff','viewer')
    NOT NULL DEFAULT 'staff';
