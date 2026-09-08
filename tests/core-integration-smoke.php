<?php
define('_JEXEC', 1);
require_once __DIR__ . '/../component/admin/src/Service/CoreIntegrationService.php';

use Xdecaro\Component\Notifications\Administrator\Service\CoreIntegrationService;

$service = new CoreIntegrationService();
if ($service->isAvailable()) {
    throw new RuntimeException('Core must not be assumed available in dependency-free smoke tests.');
}
if ($service->getCapabilities() !== [] || $service->notificationReference(1) !== null) {
    throw new RuntimeException('Optional Core integration must degrade safely.');
}

echo "Notifications optional Core smoke test passed.\n";
