<?php

declare(strict_types=1);

namespace MainApp\Infrastructure\Repositories;

use MainApp\Domain\Repositories\ImapAccountRepositoryInterface;
use MainApp\Domain\Entities\ImapAccount;
use MainApp\Infrastructure\Database\DatabaseInterface;

class ImapAccountRepository implements ImapAccountRepositoryInterface {
    
    private DatabaseInterface $database;

    public function __construct(DatabaseInterface $database) {
        $this->database = $database;
    }

    public function findByUserId(int $userId): array {
        $result = $this->database->select(
            'SELECT id, user_id, email, imap_host, imap_port, imap_password_encrypted, encryption_iv, is_active, created_at, updated_at 
            FROM imap_accounts 
            WHERE user_id = ? 
            ORDER BY email ASC',
            'i',
            $userId
        );

        if (!$result) {
            return [];
        }

        $accounts = [];
        while ($row = $result->fetch_assoc()) {
            $accounts[] = $this->mapRowToImapAccount($row);
        }

        $result->free_result();

        return $accounts;
    }

    public function findById(int $id): ?ImapAccount {
        $result = $this->database->select(
            'SELECT id, user_id, email, imap_host, imap_port, imap_password_encrypted, encryption_iv, is_active, created_at, updated_at 
            FROM imap_accounts 
            WHERE id = ?',
            'i',
            $id
        );

        if (!$result || $result->num_rows === 0) {
            return null;
        }

        $row = $result->fetch_assoc();
        $result->free_result();

        return $this->mapRowToImapAccount($row);
    }

    public function findByIdAndUserId(int $accountId, int $userId): ?ImapAccount {
        $result = $this->database->select(
            'SELECT id, user_id, email, imap_host, imap_port, imap_password_encrypted, encryption_iv, is_active, created_at, updated_at 
            FROM imap_accounts 
            WHERE id = ? AND user_id = ?',
            'ii',
            $accountId,
            $userId
        );

        if (!$result || $result->num_rows === 0) {
            return null;
        }

        $row = $result->fetch_assoc();
        $result->free_result();

        return $this->mapRowToImapAccount($row);
    }

    public function create(ImapAccount $account): int {
        return $this->database->insert(
            'INSERT INTO imap_accounts (user_id, email, imap_host, imap_port, imap_password_encrypted, encryption_iv, is_active, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())',
            'issiisi',
            $account->getUserId(),
            $account->getEmail(),
            $account->getHost(),
            $account->getPort(),
            $account->getEncryptedPassword(),
            $account->getEncryption(),
            $account->isActive() ? 1 : 0
        );
    }

    public function update(ImapAccount $account): bool {
        return $this->database->update(
            'UPDATE imap_accounts 
            SET email = ?, imap_host = ?, imap_port = ?, imap_password_encrypted = ?, encryption_iv = ?, is_active = ? 
            WHERE id = ?',
            'ssiisii',
            $account->getEmail(),
            $account->getHost(),
            $account->getPort(),
            $account->getEncryptedPassword(),
            $account->getEncryption(),
            $account->isActive() ? 1 : 0,
            $account->getId()
        );
    }

    public function delete(int $id): bool {
        return $this->database->delete(
            'DELETE FROM imap_accounts WHERE id = ?',
            'i',
            $id
        );
    }

    public function updateLastChecked(int $id): bool {
        return $this->database->update(
            'UPDATE imap_accounts SET last_checked = NOW() WHERE id = ?',
            'i',
            $id
        );
    }

    private function mapRowToImapAccount(array $row): ImapAccount {

error_log('Mapping row to ImapAccount: ' . json_encode($row));

        return new ImapAccount(
            (int)$row['id'],
            (int)$row['user_id'],
            $row['email'],
            $row['imap_host'],
            (int)$row['imap_port'],
            $row['imap_password_encrypted'],
            $row['encryption_iv'] ?? 'ssl',
            (bool)($row['is_active'] ?? true),
            $row['created_at'] ? new \DateTime($row['created_at']) : null,
            $row['updated_at'] ? new \DateTime($row['updated_at']) : null
        );
    }
}