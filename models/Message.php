<?php
require_once __DIR__ . '/../config/database.php';

class Message {
    private $conn;
    private $table = 'messages';

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Save a message to the database.
     */
    public function send($sender_id, $receiver_id, $message, $is_ai = 0, $attachment = null) {
        $stmt = $this->conn->prepare(
            "INSERT INTO {$this->table} (sender_id, receiver_id, message, is_ai_reply, attachment)
             VALUES (:sender_id, :receiver_id, :message, :is_ai, :attachment)"
        );
        $stmt->execute([
            ':sender_id'  => (int)$sender_id,
            ':receiver_id'=> (int)$receiver_id,
            ':message'    => $message,
            ':is_ai'      => (int)$is_ai,
            ':attachment' => $attachment,
        ]);
        return $stmt->rowCount() ? (int)$this->conn->lastInsertId() : false;
    }

    /**
     * Full conversation between two users, oldest first.
     */
    public function getConversation($user1_id, $user2_id) {
        $stmt = $this->conn->prepare(
            "SELECT m.*,
                    su.name          AS sender_name,
                    su.profile_photo AS sender_photo,
                    ru.name          AS receiver_name
             FROM {$this->table} m
             JOIN users su ON su.id = m.sender_id
             JOIN users ru ON ru.id = m.receiver_id
             WHERE (m.sender_id = :a AND m.receiver_id = :b)
                OR (m.sender_id = :c AND m.receiver_id = :d)
             ORDER BY m.created_at ASC, m.id ASC"
        );
        $stmt->execute([
            ':a' => (int)$user1_id, ':b' => (int)$user2_id,
            ':c' => (int)$user2_id, ':d' => (int)$user1_id,
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Contact list for the sidebar — one row per unique conversation partner.
     * Uses a clean derived-table approach that works on MySQL 5.7+.
     */
    public function getContacts($user_id) {
        $uid = (int)$user_id;

        // Step 1: get all distinct partner IDs with the latest message time
        $stmt = $this->conn->prepare(
            "SELECT
                partner_id,
                MAX(last_time) AS last_time
             FROM (
                 SELECT receiver_id AS partner_id, MAX(created_at) AS last_time
                 FROM {$this->table}
                 WHERE sender_id = :uid1
                 GROUP BY receiver_id

                 UNION

                 SELECT sender_id AS partner_id, MAX(created_at) AS last_time
                 FROM {$this->table}
                 WHERE receiver_id = :uid2
                 GROUP BY sender_id
             ) AS pairs
             GROUP BY partner_id
             ORDER BY last_time DESC"
        );
        $stmt->execute([':uid1' => $uid, ':uid2' => $uid]);
        $partners = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($partners)) return [];

        $contacts = [];
        foreach ($partners as $p) {
            $pid = (int)$p['partner_id'];

            // User info
            $uStmt = $this->conn->prepare(
                "SELECT id, name, profile_photo, role FROM users WHERE id = :pid LIMIT 1"
            );
            $uStmt->execute([':pid' => $pid]);
            $user = $uStmt->fetch(PDO::FETCH_ASSOC);
            if (!$user) continue;

            // Last message text
            $lmStmt = $this->conn->prepare(
                "SELECT message, created_at FROM {$this->table}
                 WHERE (sender_id = :a AND receiver_id = :b)
                    OR (sender_id = :c AND receiver_id = :d)
                 ORDER BY created_at DESC, id DESC LIMIT 1"
            );
            $lmStmt->execute([':a'=>$uid,':b'=>$pid,':c'=>$pid,':d'=>$uid]);
            $lm = $lmStmt->fetch(PDO::FETCH_ASSOC);

            // Unread count (messages FROM partner TO me, not yet read)
            $urStmt = $this->conn->prepare(
                "SELECT COUNT(*) AS cnt FROM {$this->table}
                 WHERE sender_id = :pid AND receiver_id = :uid AND is_read = 0"
            );
            $urStmt->execute([':pid'=>$pid,':uid'=>$uid]);
            $ur = $urStmt->fetch(PDO::FETCH_ASSOC);

            $contacts[] = [
                'contact_id'        => $pid,
                'contact_name'      => $user['name'],
                'contact_photo'     => $user['profile_photo'],
                'contact_role'      => $user['role'],
                'last_message'      => $lm['message']      ?? '',
                'last_message_time' => $lm['created_at']   ?? null,
                'unread_count'      => (int)($ur['cnt']    ?? 0),
            ];
        }

        return $contacts;
    }

    /**
     * Mark all messages from $sender_id to $receiver_id as read.
     */
    public function markAsRead($sender_id, $receiver_id) {
        $stmt = $this->conn->prepare(
            "UPDATE {$this->table} SET is_read = 1
             WHERE sender_id = :sid AND receiver_id = :rid AND is_read = 0"
        );
        return $stmt->execute([':sid' => (int)$sender_id, ':rid' => (int)$receiver_id]);
    }

    /**
     * Unread message count for a user (excludes AI replies).
     */
    public function getUnreadCount($user_id) {
        $stmt = $this->conn->prepare(
            "SELECT COUNT(*) AS cnt FROM {$this->table}
             WHERE receiver_id = :uid AND is_read = 0 AND is_ai_reply = 0"
        );
        $stmt->execute([':uid' => (int)$user_id]);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Poll for new messages since $last_id in a conversation.
     */
    public function getNewMessages($user_id, $contact_id, $last_id) {
        $stmt = $this->conn->prepare(
            "SELECT m.*, su.name AS sender_name, su.profile_photo AS sender_photo
             FROM {$this->table} m
             JOIN users su ON su.id = m.sender_id
             WHERE ((m.sender_id = :a AND m.receiver_id = :b)
                 OR (m.sender_id = :c AND m.receiver_id = :d))
               AND m.id > :last_id
             ORDER BY m.created_at ASC, m.id ASC"
        );
        $stmt->execute([
            ':a'       => (int)$user_id,   ':b' => (int)$contact_id,
            ':c'       => (int)$contact_id,':d' => (int)$user_id,
            ':last_id' => (int)$last_id,
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
