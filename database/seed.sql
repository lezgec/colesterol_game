-- Datos iniciales para una instalación nueva.
-- Usuario: superadmin@example.com
-- Contraseña: Admin2026@

INSERT INTO `users`
    (`name`, `email`, `password`, `role`, `status`, `avatar_key`, `country`, `city`, `institution`, `occupation`, `bio`)
VALUES
    (
        'Super Admin Demo',
        'superadmin@example.com',
        '$2y$10$O1SOOc82MGVIE3JqkExikeWI7T72f/DgN.U.N/oapQ/EEsWaDGHdO',
        'super_admin',
        'active',
        'star',
        'EC',
        'Guayaquil',
        'Demo Institution',
        'Administrador',
        'Usuario superadministrador genérico para instalación inicial.'
    )
ON DUPLICATE KEY UPDATE
    `name` = VALUES(`name`),
    `role` = VALUES(`role`),
    `status` = VALUES(`status`);
