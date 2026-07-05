INSERT INTO roles (tenant_id, school_id, name, code, description, is_system, is_active)
SELECT NULL, NULL, v.name, v.code, v.description, 1, 1
FROM (
    SELECT 'Admin'   AS name, 'admin'   AS code, 'Full access within their own school.' AS description
    UNION ALL SELECT 'Teacher', 'teacher', 'Classroom-facing staff access.'
    UNION ALL SELECT 'Parent',  'parent',  'Guardian access to their own children only.'
    UNION ALL SELECT 'Student', 'student', 'Student self-service access.'
) v
WHERE NOT EXISTS (
    SELECT 1 FROM roles r WHERE r.code = v.code AND r.tenant_id IS NULL AND r.school_id IS NULL
);

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT
    (SELECT id FROM roles WHERE code = 'admin' AND tenant_id IS NULL AND school_id IS NULL LIMIT 1),
    p.id
FROM permissions p
WHERE p.deleted_at IS NULL;

SELECT r.code, COUNT(rp.permission_id) AS permission_count
FROM roles r
LEFT JOIN role_permissions rp ON rp.role_id = r.id
WHERE r.tenant_id IS NULL AND r.school_id IS NULL
GROUP BY r.code;