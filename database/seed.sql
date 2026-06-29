-- Datos iniciales para una instalacion nueva.
-- Usuario: superadmin@example.com
-- Contrasena: Admin2026@
-- Cambia este correo y contrasena despues del primer inicio de sesion.

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
        'Usuario superadministrador generico para instalacion inicial.'
    )
ON DUPLICATE KEY UPDATE
    `name` = VALUES(`name`),
    `role` = VALUES(`role`),
    `status` = VALUES(`status`);
