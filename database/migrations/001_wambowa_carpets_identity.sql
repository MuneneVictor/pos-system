-- Stage 3 identity update for an already-imported Stage 1 database.

UPDATE settings
SET setting_value = 'Wambo wa Carpets',
    updated_at = CURRENT_TIMESTAMP
WHERE setting_key = 'business.name';

-- No authentication schema migration is required:
-- users, roles, branches, user_preferences and audit_logs
-- already exist in the foundational database.
