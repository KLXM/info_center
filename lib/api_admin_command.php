<?php

use rex;
use rex_api_function;
use rex_backend_login;
use rex_backend_password_policy;
use rex_csrf_token;
use rex_request;
use rex_response;
use rex_sql;
use rex_url;

/**
 * Admin-only command API for info_center search widget.
 *
 * Handles:
 *   - #user login email [role]   → create user with forced password change
 *   - #clearcache                → clear REDAXO cache
 *   - #userdisable login         → disable (lock) a user account
 */
class rex_api_info_center_admin_command extends rex_api_function
{
    protected $published = true;

    public function execute(): void
    {
        rex_response::cleanOutputBuffers();

        $user = rex::getUser();
        if (!$user || !$user->isAdmin()) {
            rex_response::sendJson(['success' => false, 'message' => 'Nicht autorisiert'], 403);
            exit;
        }

        // CSRF validation
        $csrfToken = rex_request('_csrf_token', 'string', '');
        $token = rex_csrf_token::factory('info_center_admin_command');
        if (!$token->isValid($csrfToken)) {
            rex_response::sendJson(['success' => false, 'message' => 'CSRF-Token ungültig'], 403);
            exit;
        }

        $command = trim(rex_request('command', 'string', ''));

        if ($command === 'showusers') {
            $this->showUsers();
            return;
        }

        if ($command === 'clearcache') {
            $this->clearCache();
            return;
        }

        if (str_starts_with($command, 'user ')) {
            $this->createUser(trim(substr($command, 5)));
            return;
        }

        if (str_starts_with($command, 'userdisable ')) {
            $this->disableUser(trim(substr($command, 12)));
            return;
        }

        rex_response::sendJson(['success' => false, 'message' => 'Unbekannter Befehl'], 400);
        exit;
    }

    // -------------------------------------------------------------------------
    // Commands
    // -------------------------------------------------------------------------

    private function showUsers(): void
    {
        $sql = rex_sql::factory();
        $sql->setQuery(
            'SELECT id, login, name, email, admin, status, role, lastlogin, createdate
             FROM ' . rex::getTable('user') . '
             ORDER BY name ASC'
        );

        $users = [];
        while ($sql->hasNext()) {
            $roleId   = (string) $sql->getValue('role');
            $roleName = '';
            if ($roleId !== '') {
                $roleSql = rex_sql::factory();
                $roleSql->setQuery(
                    'SELECT name FROM ' . rex::getTable('user_role') . ' WHERE id = :id LIMIT 1',
                    ['id' => (int) $roleId]
                );
                if ($roleSql->getRows() > 0) {
                    $roleName = (string) $roleSql->getValue('name');
                }
            }

            $lastlogin = $sql->getValue('lastlogin');

            $users[] = [
                'id'        => (int) $sql->getValue('id'),
                'login'     => (string) $sql->getValue('login'),
                'name'      => (string) $sql->getValue('name'),
                'email'     => (string) $sql->getValue('email'),
                'admin'     => (int) $sql->getValue('admin') === 1,
                'status'    => (int) $sql->getValue('status') === 1,
                'role'      => $roleName,
                'lastlogin' => $lastlogin ? date('d.m.Y H:i', strtotime((string) $lastlogin)) : '–',
                'url_edit'  => rex_url::backendPage('users/users', ['user_id' => (int) $sql->getValue('id')], false),

            ];
            $sql->next();
        }

        rex_response::sendJson(['success' => true, 'data' => $users]);
        exit;
    }

    private function clearCache(): void
    {
        rex_delete_cache();
        rex_response::sendJson(['success' => true, 'message' => 'Cache wurde erfolgreich geleert.']);
        exit;
    }

    private function createUser(string $args): void
    {
        // Syntax: login email [role]
        $parts = preg_split('/\s+/', $args, 3);

        if (count($parts) < 2) {
            rex_response::sendJson(['success' => false, 'message' => 'Syntax: #user login email [Rolle]']);
            exit;
        }

        [$login, $email] = $parts;
        $roleName = isset($parts[2]) ? trim($parts[2]) : '';

        // Validate login (alphanumeric, underscore, hyphen)
        if (!preg_match('/^[a-zA-Z0-9_\-]{2,50}$/', $login)) {
            rex_response::sendJson(['success' => false, 'message' => 'Login ungültig. Nur Buchstaben, Ziffern, - und _ erlaubt (2–50 Zeichen).']);
            exit;
        }

        // Validate email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            rex_response::sendJson(['success' => false, 'message' => 'E-Mail-Adresse ist ungültig.']);
            exit;
        }

        // Check if login already exists
        $check = rex_sql::factory();
        $check->setQuery(
            'SELECT id FROM ' . rex::getTable('user') . ' WHERE login = :login LIMIT 1',
            ['login' => $login]
        );
        if ($check->getRows() > 0) {
            rex_response::sendJson(['success' => false, 'message' => 'Benutzer "' . rex_escape($login) . '" existiert bereits.']);
            exit;
        }

        // Resolve role ID
        $roleId = null;
        if ($roleName !== '') {
            $roleCheck = rex_sql::factory();
            $roleCheck->setQuery(
                'SELECT id FROM ' . rex::getTable('user_role') . ' WHERE name = :name LIMIT 1',
                ['name' => $roleName]
            );
            if ($roleCheck->getRows() > 0) {
                $roleId = (int) $roleCheck->getValue('id');
            } else {
                rex_response::sendJson(['success' => false, 'message' => 'Rolle "' . rex_escape($roleName) . '" nicht gefunden.']);
                exit;
            }
        }

        // Generate secure temporary password
        $password = $this->generatePassword(14);
        $passwordHash = rex_backend_login::passwordHash($password);

        $passwordPolicy = rex_backend_password_policy::factory();

        $sql = rex_sql::factory();
        $sql->setTable(rex::getTable('user'));
        $sql->setValue('name', $login);
        $sql->setValue('login', $login);
        $sql->setValue('email', $email);
        $sql->setValue('password', $passwordHash);
        $sql->setValue('admin', 0);
        $sql->setValue('status', 1);
        $sql->setValue('login_tries', 0);
        $sql->setValue('password_change_required', 1);
        if ($roleId !== null) {
            $sql->setValue('role', (string) $roleId);
        }
        $sql->addGlobalCreateFields(rex::getUser()->getLogin());
        $sql->addGlobalUpdateFields(rex::getUser()->getLogin());
        $sql->setDateTimeValue('password_changed', time());
        $sql->setArrayValue('previous_passwords', $passwordPolicy->updatePreviousPasswords(null, $passwordHash));
        $sql->insert();

        $userId = (int) $sql->getLastId();

        rex_response::sendJson([
            'success' => true,
            'message' => 'Benutzer erfolgreich angelegt.',
            'data' => [
                'id' => $userId,
                'login' => $login,
                'email' => $email,
                'password' => $password,
                'role' => $roleName ?: '–',
                'url_backend' => rex_url::backendPage('users/users', ['user_id' => $userId], false),
            ],
        ]);
        exit;
    }

    private function disableUser(string $login): void
    {
        if ($login === '') {
            rex_response::sendJson(['success' => false, 'message' => 'Kein Benutzername angegeben.']);
            exit;
        }

        // Prevent self-lockout
        $currentUser = rex::getUser();
        if ($currentUser && strtolower($currentUser->getLogin()) === strtolower($login)) {
            rex_response::sendJson(['success' => false, 'message' => 'Du kannst dich nicht selbst sperren.']);
            exit;
        }

        $check = rex_sql::factory();
        $check->setQuery(
            'SELECT id, status FROM ' . rex::getTable('user') . ' WHERE login = :login LIMIT 1',
            ['login' => $login]
        );

        if ($check->getRows() === 0) {
            rex_response::sendJson(['success' => false, 'message' => 'Benutzer "' . rex_escape($login) . '" nicht gefunden.']);
            exit;
        }

        $userId = (int) $check->getValue('id');
        $currentStatus = (int) $check->getValue('status');

        if ($currentStatus === 0) {
            rex_response::sendJson(['success' => false, 'message' => 'Benutzer "' . rex_escape($login) . '" ist bereits gesperrt.']);
            exit;
        }

        $sql = rex_sql::factory();
        $sql->setTable(rex::getTable('user'));
        $sql->setWhere(['id' => $userId]);
        $sql->setValue('status', 0);
        $sql->addGlobalUpdateFields(rex::getUser()->getLogin());
        $sql->update();

        rex_response::sendJson([
            'success' => true,
            'message' => 'Benutzer "' . rex_escape($login) . '" wurde gesperrt.',
            'data' => ['login' => $login],
        ]);
        exit;
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Generates a cryptographically secure random password.
     * Contains at least one lowercase, one uppercase, one digit and one special char.
     */
    private function generatePassword(int $length = 14): string
    {
        $lower   = 'abcdefghijkmnpqrstuvwxyz';
        $upper   = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
        $digits  = '23456789';
        $special = '!@#$%&*';
        $all     = $lower . $upper . $digits . $special;

        // Ensure at least one of each required character type
        $password  = $lower[random_int(0, strlen($lower) - 1)];
        $password .= $upper[random_int(0, strlen($upper) - 1)];
        $password .= $digits[random_int(0, strlen($digits) - 1)];
        $password .= $special[random_int(0, strlen($special) - 1)];

        for ($i = 4; $i < $length; $i++) {
            $password .= $all[random_int(0, strlen($all) - 1)];
        }

        // Shuffle to avoid predictable positions
        return str_shuffle($password);
    }
}
