<?php
define('_JEXEC', 1);
require_once __DIR__ . '/../component/admin/src/Value/RecipientReference.php';

use Xdecaro\Component\Notifications\Administrator\Value\RecipientReference;

$user = RecipientReference::forUser(42);
if ($user->key() !== 'user:42' || $user->getUserId() !== 42) {
    throw new RuntimeException('User recipient reference failed.');
}

$person = RecipientReference::forEntity('com_xdecaropeople', 'person', 125);
if ($person->key() !== 'com_xdecaropeople:person:125') {
    throw new RuntimeException('Entity recipient reference failed.');
}

$roundTrip = RecipientReference::fromArray($person->toArray());
if ($roundTrip->key() !== $person->key()) {
    throw new RuntimeException('Recipient reference round-trip failed.');
}

$rejected = false;
try {
    RecipientReference::forEntity('people', 'person', 1);
} catch (InvalidArgumentException $exception) {
    $rejected = true;
}
if (!$rejected) {
    throw new RuntimeException('Invalid component identifier must be rejected.');
}

echo "Notifications recipient reference smoke test passed.\n";
