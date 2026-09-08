<?php
namespace Xdecaro\Component\Notifications\Administrator\Value;

defined('_JEXEC') or die;

use InvalidArgumentException;

final class RecipientReference
{
    private string $type;
    private ?int $userId;
    private ?string $component;
    private ?string $entity;
    private ?string $entityId;

    private function __construct(string $type, ?int $userId, ?string $component, ?string $entity, ?string $entityId)
    {
        $this->type = $type;
        $this->userId = $userId;
        $this->component = $component;
        $this->entity = $entity;
        $this->entityId = $entityId;
    }

    public static function forUser(int $userId): self
    {
        if ($userId <= 0) {
            throw new InvalidArgumentException('User recipient ID must be positive.');
        }

        return new self('user', $userId, null, null, null);
    }

    /** @param int|string $id */
    public static function forEntity(string $component, string $entity, $id): self
    {
        $component = trim($component);
        $entity = trim($entity);
        $entityId = trim((string) $id);

        if (strlen($component) > 64 || !preg_match('/^com_[a-z0-9][a-z0-9_]*$/', $component)) {
            throw new InvalidArgumentException('Invalid recipient component.');
        }

        if (strlen($entity) > 64 || !preg_match('/^[a-z][a-z0-9_]{0,63}$/', $entity)) {
            throw new InvalidArgumentException('Invalid recipient entity type.');
        }

        if ($entityId === '' || strlen($entityId) > 128 || !preg_match('/^[A-Za-z0-9][A-Za-z0-9._:-]{0,127}$/', $entityId)) {
            throw new InvalidArgumentException('Invalid recipient entity ID.');
        }

        if (strlen($component . ':' . $entity . ':' . $entityId) > 191) {
            throw new InvalidArgumentException('Recipient reference is too long.');
        }

        return new self('entity', null, $component, $entity, $entityId);
    }

    /** @param array<string,mixed> $data */
    public static function fromArray(array $data): self
    {
        $type = isset($data['type']) ? strtolower(trim((string) $data['type'])) : '';

        if ($type === 'user') {
            $id = isset($data['id']) ? (int) $data['id'] : (isset($data['user_id']) ? (int) $data['user_id'] : 0);
            return self::forUser($id);
        }

        if ($type === 'entity') {
            $id = $data['id'] ?? ($data['entity_id'] ?? null);
            if (!isset($data['component'], $data['entity']) || $id === null) {
                throw new InvalidArgumentException('Entity recipient requires component, entity and id.');
            }
            return self::forEntity((string) $data['component'], (string) $data['entity'], $id);
        }

        throw new InvalidArgumentException('Unsupported recipient type.');
    }

    public function getType(): string { return $this->type; }
    public function getUserId(): ?int { return $this->userId; }
    public function getComponent(): ?string { return $this->component; }
    public function getEntity(): ?string { return $this->entity; }
    public function getEntityId(): ?string { return $this->entityId; }

    public function key(): string
    {
        return $this->type === 'user'
            ? 'user:' . (string) $this->userId
            : (string) $this->component . ':' . (string) $this->entity . ':' . (string) $this->entityId;
    }

    /** @return array<string,mixed> */
    public function toArray(): array
    {
        if ($this->type === 'user') {
            return ['type' => 'user', 'id' => $this->userId];
        }

        return [
            'type' => 'entity',
            'component' => $this->component,
            'entity' => $this->entity,
            'id' => $this->entityId,
        ];
    }
}
